<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("../config/db.php");
include("../includes/auth.php");
include("../config/functions.php");

// 🔒 SECURITY CHECK
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'teacher') {
    header("Location: ../login.php");
    exit();
}

$quiz_id = intval($_GET['quiz'] ?? 0);

if ($quiz_id <= 0) {
    $_SESSION['error'] = "Invalid or missing Quiz ID.";
    header("Location: my_courses.php");
    exit();
}

// FETCH QUIZ & COURSE DETAILS
$stmt = $conn->prepare("
    SELECT q.QuizTitle, q.CourseID, c.CourseTitle
    FROM Quiz q
    JOIN Course c ON q.CourseID = c.CourseID
    WHERE q.QuizID = ?
");
$stmt->bind_param("i", $quiz_id);
$stmt->execute();
$quizData = $stmt->get_result()->fetch_assoc();

if (!$quizData) {
    $_SESSION['error'] = "Quiz not found.";
    header("Location: my_courses.php");
    exit();
}

$course_id = $quizData['CourseID'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Quiz Question | LMS</title>
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
    padding: 35px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.04);
    max-width: 850px;
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

.form-control, .form-select {
    border-radius: 10px;
    border: 1px solid #cbd5e1;
    padding: 12px 16px;
}

.form-control:focus, .form-select:focus {
    border-color: #6d35f5;
    box-shadow: 0 0 0 0.25rem rgba(109, 53, 245, 0.15);
}

.option-badge {
    width: 40px;
    background-color: #eef2ff;
    color: #4f46e5;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    border-top-left-radius: 10px;
    border-bottom-left-radius: 10px;
    border: 1px solid #cbd5e1;
    border-end-0: 0;
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
                    <h1 class="fw-bold mb-1">Add Quiz Question</h1>
                    <p class="text-muted mb-0">
                        Quiz: <strong class="text-dark"><?= htmlspecialchars($quizData['QuizTitle']) ?></strong> 
                        (<span class="text-primary"><?= htmlspecialchars($quizData['CourseTitle']) ?></span>)
                    </p>
                </div>
            </div>
            <div>
                <a href="quiz_list.php?course=<?= $course_id ?>" class="btn btn-outline-secondary rounded-3 px-3">
                    <i class="bi bi-arrow-left me-1"></i> Back to Quizzes
                </a>
            </div>
        </div>

        <div class="section-box">

            <!-- SESSION NOTIFICATIONS -->
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($_SESSION['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($_SESSION['success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <form action="save_question.php" method="POST" onsubmit="return validateQuestion()">

                <input type="hidden" name="quiz" value="<?= $quiz_id ?>">

                <!-- QUESTION TEXT -->
                <div class="mb-4">
                    <label for="question" class="form-label fw-bold">
                        Question Statement <span class="text-danger">*</span>
                    </label>
                    <textarea name="question" id="question" class="form-control" rows="3" placeholder="Enter question statement here..." required></textarea>
                </div>

                <!-- OPTIONS GRID -->
                <h6 class="fw-bold text-secondary mb-3"><i class="bi bi-list-task me-1"></i> Multiple Choice Options</h6>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small text-muted">Option A <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="option-badge">A</span>
                            <input type="text" name="a" id="a" class="form-control" placeholder="Enter option A" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small text-muted">Option B <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="option-badge">B</span>
                            <input type="text" name="b" id="b" class="form-control" placeholder="Enter option B" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small text-muted">Option C <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="option-badge">C</span>
                            <input type="text" name="c" id="c" class="form-control" placeholder="Enter option C" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small text-muted">Option D <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="option-badge">D</span>
                            <input type="text" name="d" id="d" class="form-control" placeholder="Enter option D" required>
                        </div>
                    </div>
                </div>

                <!-- CORRECT ANSWER SELECT -->
                <div class="mb-4">
                    <label for="correct" class="form-label fw-bold">
                        Correct Answer <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 rounded-start-3"><i class="bi bi-check2-circle text-success"></i></span>
                        <select name="correct" id="correct" class="form-select border-start-0 rounded-end-3" required>
                            <option value="">-- Choose Correct Option --</option>
                            <option value="A">Option A</option>
                            <option value="B">Option B</option>
                            <option value="C">Option C</option>
                            <option value="D">Option D</option>
                        </select>
                    </div>
                </div>

                <!-- ACTION BUTTONS -->
                <div class="d-flex gap-3 pt-2">
                    <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold">
                        <i class="bi bi-plus-circle me-1"></i> Save Question
                    </button>
                    <a href="quiz_list.php?course=<?= $course_id ?>" class="btn btn-light border rounded-3 px-4">
                        Cancel
                    </a>
                </div>

            </form>

        </div>

    </main>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
function validateQuestion() {
    const question = document.getElementById("question").value.trim();
    const a = document.getElementById("a").value.trim();
    const b = document.getElementById("b").value.trim();
    const c = document.getElementById("c").value.trim();
    const d = document.getElementById("d").value.trim();
    const correct = document.getElementById("correct").value;

    if (question.length < 5) {
        alert("Question text must be at least 5 characters long.");
        document.getElementById("question").focus();
        return false;
    }

    if (!a || !b || !c || !d) {
        alert("Please fill in all four options (A, B, C, and D).");
        return false;
    }

    if (!correct) {
        alert("Please select the correct answer from the dropdown.");
        document.getElementById("correct").focus();
        return false;
    }

    return true;
}

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