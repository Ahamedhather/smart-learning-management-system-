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

$course_id = intval($_GET['course'] ?? 0);

if ($course_id <= 0) {
    header("Location: my_courses.php");
    exit();
}

// FETCH COURSE DETAILS
$stmt = $conn->prepare("SELECT CourseTitle FROM Course WHERE CourseID = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();

if (!$course) {
    header("Location: my_courses.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Create Quiz | LMS</title>
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
    max-width: 750px;
    margin: 0 auto;
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
                    <h1 class="fw-bold mb-1">Create New Quiz</h1>
                    <p class="text-muted mb-0">Course: <span class="fw-semibold text-primary"><?= htmlspecialchars($course['CourseTitle']) ?></span></p>
                </div>
            </div>
            <div>
                <a href="quiz_list.php?course=<?= $course_id ?>" class="btn btn-outline-secondary rounded-3 px-3">
                    <i class="bi bi-arrow-left me-1"></i> View Quizzes
                </a>
            </div>
        </div>

        <div class="section-box">

            <!-- ERROR ALERT BANNER -->
            <div id="error-alert" class="alert alert-danger d-none rounded-3 mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <span id="error-message"></span>
            </div>

            <form action="save_quiz.php" method="POST" onsubmit="return validateQuiz()">

                <input type="hidden" name="course" value="<?= $course_id ?>">

                <!-- QUIZ TITLE -->
                <div class="mb-4">
                    <label class="form-label fw-bold">Quiz Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" id="title" class="form-control rounded-3 p-2.5" placeholder="e.g. Midterm Quiz - Unit 1 & 2" required>
                </div>

                <!-- TIME LIMIT -->
                <div class="mb-4">
                    <label class="form-label fw-bold">Time Limit (Minutes) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-stopwatch"></i></span>
                        <input type="number" name="time" id="time" class="form-control rounded-end p-2.5" placeholder="e.g. 30" min="1" required>
                    </div>
                    <small class="text-muted mt-1 d-block">Specify the duration students will have once they start the quiz.</small>
                </div>

                <!-- SUBMIT CONTROL BUTTONS -->
                <div class="d-flex gap-3 pt-2">
                    <button type="submit" class="btn btn-success px-4 rounded-3 fw-semibold">
                        <i class="bi bi-plus-circle me-1"></i> Create Quiz
                    </button>
                    <a href="quiz_list.php?course=<?= $course_id ?>" class="btn btn-light border px-4 rounded-3">Cancel</a>
                </div>

            </form>

        </div>

    </main>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
const errorAlert = document.getElementById("error-alert");
const errorMessage = document.getElementById("error-message");

function showError(msg) {
    errorMessage.textContent = msg;
    errorAlert.classList.remove("d-none");
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function validateQuiz() {
    let title = document.getElementById("title").value.trim();
    let time = parseInt(document.getElementById("time").value, 10);

    errorAlert.classList.add("d-none");

    if (title.length < 3) {
        showError("Quiz title must be at least 3 characters long.");
        return false;
    }

    if (isNaN(time) || time <= 0) {
        showError("Please enter a valid time limit greater than 0 minutes.");
        return false;
    }

    return true;
}

// Mobile Menu Handler
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