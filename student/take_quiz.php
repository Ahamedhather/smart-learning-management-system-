<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once("../config/db.php");

// Session Protection
if (!isset($_SESSION['student_id'])) {
    header("Location: ../login.php");
    exit();
}

// Validate Quiz ID
if (!isset($_GET['quiz']) || !filter_var($_GET['quiz'], FILTER_VALIDATE_INT)) {
    header("Location: quiz_list.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$quiz_id = intval($_GET['quiz']);

// Get Quiz Details & Check Enrollment
$stmt = $conn->prepare("
    SELECT q.*, c.CourseTitle
    FROM Quiz q
    JOIN Course c ON q.CourseID = c.CourseID
    JOIN Enrollment e ON c.CourseID = e.CourseID
    WHERE q.QuizID = ? AND e.StudentID = ?
");
$stmt->bind_param("ii", $quiz_id, $student_id);
$stmt->execute();
$quizData = $stmt->get_result()->fetch_assoc();

if (!$quizData) {
    header("Location: quiz_list.php?error=not_found");
    exit();
}

// Check Duplicate Attempt
$stmt = $conn->prepare("
    SELECT SubmissionID FROM QuizSubmission
    WHERE QuizID = ? AND StudentID = ?
");
$stmt->bind_param("ii", $quiz_id, $student_id);
$stmt->execute();
$attempt = $stmt->get_result();

$already_submitted = ($attempt->num_rows > 0);

// Fetch Questions
$stmt = $conn->prepare("SELECT * FROM QuizQuestion WHERE QuizID = ? ORDER BY QuestionID ASC");
$stmt->bind_param("i", $quiz_id);
$stmt->execute();
$questions = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Take Quiz: <?= htmlspecialchars($quizData['QuizTitle']) ?> | Student Portal</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            min-height: 100vh;
        }

        /* MAIN CONTENT LAYOUT */
        .main-content {
            margin-left: 290px;
            padding: 30px 40px;
            transition: all 0.3s ease;
            min-height: 100vh;
        }

        /* CARD STYLES */
        .content-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
            padding: 28px;
            animation: fadeUp 0.4s ease-out;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* STICKY TIMER BAR */
        .sticky-timer-box {
            position: sticky;
            top: 20px;
            z-index: 100;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .timer-display {
            font-size: 22px;
            font-weight: 700;
            font-family: monospace;
        }

        /* QUESTION BOX STYLING */
        .question-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 20px 24px;
            margin-bottom: 20px;
            transition: all 0.2s ease;
        }

        .question-card:hover {
            border-color: #cbd5e1;
            background: #ffffff;
        }

        .option-label {
            display: flex;
            align-items: center;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            padding: 12px 16px;
            border-radius: 10px;
            margin-top: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 500;
        }

        .option-label:hover {
            background-color: #f1f5f9;
            border-color: #cbd5e1;
        }

        .form-check-input:checked + .option-label {
            background-color: #eff6ff;
            border-color: #3b82f6;
            color: #1e40af;
            font-weight: 600;
        }

        .btn-action {
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            padding: 10px 20px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }
        .btn-action:hover {
            transform: translateY(-2px);
        }

        /* MOBILE HEADER BAR */
        .mobile-header {
            display: none;
            background: #0f172a;
            padding: 14px 20px;
            color: white;
            align-items: center;
            justify-content: space-between;
        }

        @media (max-width: 900px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
            .mobile-header {
                display: flex;
            }
        }
    </style>
</head>

<body>

    <!-- MOBILE NAVBAR HEADER -->
    <div class="mobile-header">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-mortarboard-fill fs-4 text-primary"></i>
            <span class="fw-bold fs-5">LMS Portal</span>
        </div>
        <button class="btn btn-outline-light btn-sm" id="sidebarToggle">
            <i class="bi bi-list fs-5"></i>
        </button>
    </div>

    <!-- INCLUDED STUDENT SIDEBAR -->
    <?php include("sidebar_student.php"); ?>

    <!-- MAIN PAGE CONTENT -->
    <main class="main-content">
        
        <!-- PAGE HEADER -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <h3 class="fw-bold mb-1"><?= htmlspecialchars($quizData['QuizTitle']) ?></h3>
                <p class="text-muted mb-0">Course: <strong class="text-dark"><?= htmlspecialchars($quizData['CourseTitle']) ?></strong></p>
            </div>
            <a href="quiz_list.php" class="btn btn-outline-secondary btn-action">
                <i class="bi bi-arrow-left"></i> Back to Quizzes
            </a>
        </div>

        <?php if ($already_submitted) { ?>
            <!-- ALREADY SUBMITTED ERROR STATE -->
            <div class="content-card text-center py-5">
                <i class="bi bi-check-circle-fill text-success fs-1 mb-3 d-block"></i>
                <h4 class="fw-bold">Quiz Already Submitted</h4>
                <p class="text-muted">You have already completed and submitted your response for this quiz.</p>
                <a href="quiz_list.php" class="btn btn-primary btn-action mt-2">
                    <i class="bi bi-list-task"></i> View Available Quizzes
                </a>
            </div>
        <?php } else { ?>

            <!-- TIMER HEADER BAR -->
            <div class="sticky-timer-box d-flex align-items-center justify-content-between mb-4">
                <div class="d-flex align-items-center gap-2 text-dark fw-semibold">
                    <i class="bi bi-clock-history text-warning fs-5"></i>
                    <span>Time Remaining:</span>
                </div>
                <div class="timer-display text-primary" id="timer">--:--</div>
            </div>

            <!-- QUIZ FORM -->
            <div class="content-card">
                
                <form action="submit_quiz.php" method="POST" id="quizForm" onsubmit="return validateQuiz()">
                    <input type="hidden" name="quiz_id" value="<?= $quiz_id ?>">

                    <?php if ($questions->num_rows == 0) { ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-exclamation-triangle fs-1 d-block mb-2 text-warning"></i>
                            <h6 class="fw-semibold">No Questions Found</h6>
                            <p class="small mb-0">There are no questions attached to this quiz yet.</p>
                        </div>
                    <?php } ?>

                    <?php 
                    $count = 1;
                    while ($q = $questions->fetch_assoc()) { 
                        $qID = $q['QuestionID'];
                    ?>
                        <div class="question-card">
                            <h6 class="fw-bold text-dark mb-3">
                                <span class="badge bg-primary me-2">Q<?= $count ?></span>
                                <?= htmlspecialchars($q['Question']) ?>
                            </h6>

                            <div class="options-group">
                                <!-- Option A -->
                                <div>
                                    <input class="form-check-input d-none" type="radio" name="q<?= $qID ?>" id="q<?= $qID ?>_a" value="A" required>
                                    <label class="option-label" for="q<?= $qID ?>_a">
                                        <span class="fw-bold me-2 text-secondary">A.</span> <?= htmlspecialchars($q['OptionA']) ?>
                                    </label>
                                </div>

                                <!-- Option B -->
                                <div>
                                    <input class="form-check-input d-none" type="radio" name="q<?= $qID ?>" id="q<?= $qID ?>_b" value="B">
                                    <label class="option-label" for="q<?= $qID ?>_b">
                                        <span class="fw-bold me-2 text-secondary">B.</span> <?= htmlspecialchars($q['OptionB']) ?>
                                    </label>
                                </div>

                                <!-- Option C -->
                                <div>
                                    <input class="form-check-input d-none" type="radio" name="q<?= $qID ?>" id="q<?= $qID ?>_c" value="C">
                                    <label class="option-label" for="q<?= $qID ?>_c">
                                        <span class="fw-bold me-2 text-secondary">C.</span> <?= htmlspecialchars($q['OptionC']) ?>
                                    </label>
                                </div>

                                <!-- Option D -->
                                <div>
                                    <input class="form-check-input d-none" type="radio" name="q<?= $qID ?>" id="q<?= $qID ?>_d" value="D">
                                    <label class="option-label" for="q<?= $qID ?>_d">
                                        <span class="fw-bold me-2 text-secondary">D.</span> <?= htmlspecialchars($q['OptionD']) ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                    <?php 
                        $count++;
                    } 
                    ?>

                    <?php if ($count > 1) { ?>
                        <div class="pt-3 border-top d-flex justify-content-end">
                            <button type="submit" class="btn btn-success btn-action px-4 py-2">
                                <i class="bi bi-send-check-fill"></i> Submit Quiz
                            </button>
                        </div>
                    <?php } ?>

                </form>

            </div>
        <?php } ?>

    </main>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <?php if (!$already_submitted) { ?>
    <!-- Countdown Timer & Auto-Submit Script -->
    <script>
        let totalSeconds = <?= intval($quizData['TimeLimit']) ?> * 60;
        let timerElement = document.getElementById("timer");
        let formSubmitted = false;

        function updateTimer() {
            let minutes = Math.floor(totalSeconds / 60);
            let seconds = totalSeconds % 60;

            timerElement.innerHTML = 
                (minutes < 10 ? "0" : "") + minutes + ":" + 
                (seconds < 10 ? "0" : "") + seconds;

            // Visual warning when under 2 minutes
            if (totalSeconds <= 120) {
                timerElement.classList.remove("text-primary");
                timerElement.classList.add("text-danger");
            }

            if (totalSeconds <= 0 && !formSubmitted) {
                formSubmitted = true;
                alert("Time is up! Your quiz will now be submitted automatically.");
                document.getElementById("quizForm").submit();
            }

            totalSeconds--;
        }

        setInterval(updateTimer, 1000);
        updateTimer();

        function validateQuiz() {
            if (confirm("Are you sure you want to submit your quiz answers?")) {
                formSubmitted = true;
                return true;
            }
            return false;
        }

        // Mobile Sidebar Drawer Toggle
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');

        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('mobile-open');
            });
        }
    </script>
    <?php } ?>

</body>
</html>