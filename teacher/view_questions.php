<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("../config/db.php");
include("../includes/auth.php");
include("../config/functions.php");
include("delete_quiz_process.php"); // Include external delete logic file

// 🔒 SECURITY CHECK
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'teacher') {
    header("Location: ../login.php");
    exit();
}

// Check if a Quiz ID is provided in the URL
$quiz_id = intval($_GET['quiz'] ?? 0);

if ($quiz_id <= 0) {
    header("Location: my_courses.php");
    exit();
}

// HANDLE INDIVIDUAL QUESTION DELETION
if (isset($_GET['delete_question'])) {
    $delete_question_id = intval($_GET['delete_question']);

    if ($delete_question_id > 0) {
        $stmt_del = $conn->prepare("DELETE FROM QuizQuestion WHERE QuestionID = ? AND QuizID = ?");
        $stmt_del->bind_param("ii", $delete_question_id, $quiz_id);
        
        if ($stmt_del->execute()) {
            $_SESSION['success'] = "Question deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete the question.";
        }
        $stmt_del->close();
    }

    header("Location: view_questions.php?quiz=" . $quiz_id);
    exit();
}

// FETCH QUIZ DETAILS & ASSOCIATED COURSE
$stmt = $conn->prepare("
    SELECT q.*, c.CourseTitle, c.CourseID 
    FROM Quiz q
    JOIN Course c ON q.CourseID = c.CourseID
    WHERE q.QuizID = ?
");
$stmt->bind_param("i", $quiz_id);
$stmt->execute();
$quiz = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$quiz) {
    header("Location: my_courses.php");
    exit();
}

// FETCH ALL COLUMNS SAFELY USING *
$stmt = $conn->prepare("
    SELECT * 
    FROM QuizQuestion 
    WHERE QuizID = ?
    ORDER BY QuestionID ASC
");
$stmt->bind_param("i", $quiz_id);
$stmt->execute();
$questions = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>View Quiz Questions | LMS</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Bootstrap 5 & Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<style>
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background: #f7f9ff;
    color: #111827;
}

.wrapper {
    display: flex;
    width: 100%;
    min-height: 100vh;
}

.content {
    margin-left: 290px;
    padding: 35px;
    width: calc(100% - 290px);
    transition: all .3s ease;
}

.content.expanded {
    margin-left: 105px;
    width: calc(100% - 105px);
}

.section-box {
    background: white;
    border-radius: 18px;
    padding: 30px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.04);
}

.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.toggle-btn {
    border: none;
    background: linear-gradient(135deg, #6d35f5, #8b5cf6);
    color: white;
    width: 45px;
    height: 45px;
    border-radius: 12px;
    font-size: 24px;
    cursor: pointer;
}

#mobileMenuToggle {
    display: none;
}

.question-card {
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    background: #fff;
    transition: all 0.2s ease;
}

.question-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.option-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 8px;
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    margin-right: 10px;
    margin-bottom: 8px;
    font-size: 0.9rem;
}

/* Highlight style for Correct Answer */
.option-badge.correct {
    background: #dcfce7;
    color: #166534;
    border-color: #86efac;
    font-weight: 600;
}

/* Highlight style for Incorrect Options */
.option-badge.incorrect {
    background: #fee2e2;
    color: #991b1b;
    border-color: #fca5a5;
}

@media(max-width:900px) {
    #mobileMenuToggle {
        display: block !important;
    }
    .content, .content.expanded {
        margin-left: 0;
        width: 100%;
        padding: 20px;
    }
}
</style>
</head>

<body>

<div class="wrapper">

    <!-- SIDEBAR INCLUSION -->
    <?php include("sidebar_teacher.php"); ?>

    <!-- MAIN CONTENT -->
    <main class="content" id="content">

        <!-- TOPBAR -->
        <div class="topbar">
            <div class="d-flex align-items-center gap-3">
                <button id="mobileMenuToggle" class="toggle-btn">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    <h1 class="fw-bold mb-1">Questions for: <?= htmlspecialchars($quiz['QuizTitle']) ?></h1>
                    <p class="text-muted mb-0">Course: <span class="fw-semibold text-primary"><?= htmlspecialchars($quiz['CourseTitle']) ?></span></p>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="add_question.php?quiz=<?= $quiz_id ?>" class="btn btn-primary fw-semibold rounded-3 px-3">
                    <i class="bi bi-plus-lg me-1"></i> Add More Questions
                </a>
                <a href="quiz_list.php?course=<?= $quiz['CourseID'] ?>" class="btn btn-outline-secondary rounded-3 px-3">
                    <i class="bi bi-arrow-left me-1"></i> Back to Quizzes
                </a>
            </div>
        </div>

        <div class="section-box">

            <!-- SESSION NOTIFICATIONS -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($_SESSION['success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($_SESSION['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if ($questions->num_rows === 0): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-question-circle fs-1 text-secondary d-block mb-2"></i>
                    No questions have been added to this quiz yet.
                    <div class="mt-3">
                        <a href="add_question.php?quiz=<?= $quiz_id ?>" class="btn btn-sm btn-primary">Add First Question</a>
                    </div>
                </div>
            <?php else: ?>
                <?php $count = 1; while ($q = $questions->fetch_assoc()): ?>
                    <?php 
                        // Automatically detect the question text column variant
                        $question_text = $q['Questiontext'] ?? ($q['QuestionText'] ?? ($q['Question'] ?? 'Untitled Question'));
                        
                        // Automatically detect the correct answer column variant
                        $correct = strtoupper(trim($q['CorrectAnswer'] ?? ($q['CorrectOption'] ?? '')));
                    ?>
                    <div class="question-card">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="fw-bold text-dark">Q<?= $count++ ?>. <?= htmlspecialchars($question_text) ?></h5>
                            <!-- Delete Question Button -->
                            <a href="view_questions.php?quiz=<?= $quiz_id ?>&delete_question=<?= $q['QuestionID'] ?>" 
                               class="btn btn-sm btn-outline-danger rounded-2 px-2" 
                               title="Delete Question" 
                               onclick="return confirm('Are you sure you want to delete this question?');">
                                <i class="bi bi-trash"></i>
                            </a>
                        </div>

                        <div class="mt-3">
                            <!-- Option A -->
                            <?php if (!empty($q['OptionA'])): ?>
                                <div class="option-badge <?= ($correct === 'A') ? 'correct' : 'incorrect' ?>">
                                    <strong>A:</strong> <?= htmlspecialchars($q['OptionA']) ?>
                                    <?php if ($correct === 'A'): ?><i class="bi bi-check-circle-fill ms-1"></i><?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Option B -->
                            <?php if (!empty($q['OptionB'])): ?>
                                <div class="option-badge <?= ($correct === 'B') ? 'correct' : 'incorrect' ?>">
                                    <strong>B:</strong> <?= htmlspecialchars($q['OptionB']) ?>
                                    <?php if ($correct === 'B'): ?><i class="bi bi-check-circle-fill ms-1"></i><?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Option C -->
                            <?php if (!empty($q['OptionC'])): ?>
                                <div class="option-badge <?= ($correct === 'C') ? 'correct' : 'incorrect' ?>">
                                    <strong>C:</strong> <?= htmlspecialchars($q['OptionC']) ?>
                                    <?php if ($correct === 'C'): ?><i class="bi bi-check-circle-fill ms-1"></i><?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Option D -->
                            <?php if (!empty($q['OptionD'])): ?>
                                <div class="option-badge <?= ($correct === 'D') ? 'correct' : 'incorrect' ?>">
                                    <strong>D:</strong> <?= htmlspecialchars($q['OptionD']) ?>
                                    <?php if ($correct === 'D'): ?><i class="bi bi-check-circle-fill ms-1"></i><?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>

        </div>

    </main>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Mobile Sidebar Handler
const mobileToggleBtn = document.getElementById("mobileMenuToggle");
const sidebar = document.getElementById("sidebar");
const content = document.getElementById("content");

if (mobileToggleBtn) {
    mobileToggleBtn.addEventListener("click", () => {
        if (window.innerWidth <= 900) {
            sidebar.classList.toggle("mobile-open");
        } else {
            sidebar.classList.toggle("collapsed");
            content.classList.toggle("expanded");
        }
    });
}
</script>

</body>
</html>