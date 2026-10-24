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

$student_id = $_SESSION['student_id'];

// Optional Course Parameter Filter (if accessed from a specific course)
$course_filter = isset($_GET['course']) && filter_var($_GET['course'], FILTER_VALIDATE_INT) ? intval($_GET['course']) : null;

if ($course_filter) {
    $stmt = $conn->prepare("
        SELECT 
            q.QuizID,
            q.QuizTitle,
            q.TimeLimit,
            q.TotalMarks,
            c.CourseTitle
        FROM Quiz q
        JOIN Course c ON q.CourseID = c.CourseID
        JOIN Enrollment e ON c.CourseID = e.CourseID
        WHERE e.StudentID = ? AND q.CourseID = ?
        ORDER BY q.QuizID DESC
    ");
    $stmt->bind_param("ii", $student_id, $course_filter);
} else {
    $stmt = $conn->prepare("
        SELECT 
            q.QuizID,
            q.QuizTitle,
            q.TimeLimit,
            q.TotalMarks,
            c.CourseTitle
        FROM Quiz q
        JOIN Course c ON q.CourseID = c.CourseID
        JOIN Enrollment e ON c.CourseID = e.CourseID
        WHERE e.StudentID = ?
        ORDER BY q.QuizID DESC
    ");
    $stmt->bind_param("i", $student_id);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Quizzes | Student Portal</title>

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

        /* SEARCH BAR */
        .search-box {
            position: relative;
        }
        .search-box i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 18px;
        }
        .search-box .form-control {
            padding-left: 46px;
            height: 48px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
            font-weight: 500;
        }
        .search-box .form-control:focus {
            background-color: #ffffff;
            border-color: #6d35f5;
            box-shadow: 0 0 0 4px rgba(109, 53, 245, 0.1);
        }

        /* TABLE STYLES */
        .custom-table {
            border-collapse: separate;
            border-spacing: 0 8px;
        }
        .custom-table thead th {
            background: #f1f5f9;
            color: #475569;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
            padding: 14px 18px;
        }
        .custom-table thead th:first-child { border-radius: 10px 0 0 10px; }
        .custom-table thead th:last-child { border-radius: 0 10px 10px 0; }

        .custom-table tbody tr {
            background: #ffffff;
            transition: all 0.2s ease;
        }
        .custom-table tbody tr:hover {
            background: #f8fafc;
            transform: translateY(-1px);
        }
        .custom-table td {
            padding: 16px 18px;
            border-top: 1px solid #f1f5f9;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        .custom-table td:first-child { border-left: 1px solid #f1f5f9; border-radius: 12px 0 0 12px; }
        .custom-table td:last-child { border-right: 1px solid #f1f5f9; border-radius: 0 12px 12px 0; }

        /* BUTTONS & BADGES */
        .btn-action {
            border-radius: 10px;
            font-weight: 600;
            font-size: 13px;
            padding: 7px 16px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }
        .btn-action:hover {
            transform: translateY(-2px);
        }

        .badge-subtle {
            background-color: #f1f5f9;
            color: #475569;
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 8px;
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

        /* RESPONSIVE BREAKPOINTS */
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
                <h3 class="fw-bold mb-1">Available Quizzes</h3>
                <p class="text-muted mb-0">Test your knowledge and complete assigned online assessments.</p>
            </div>
            <!-- <a href="student_dashboard.php" class="btn btn-outline-secondary btn-action">
                <i class="bi bi-arrow-left"></i> Dashboard
            </a> -->
        </div>

        <!-- CONTENT CARD -->
        <div class="content-card">
            
            <!-- SEARCH BAR -->
            <div class="row mb-4">
                <div class="col-md-6 col-lg-5">
                    <div class="search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" id="searchInput" class="form-control" placeholder="Search by quiz title or course...">
                    </div>
                </div>
            </div>

            <!-- QUIZZES TABLE -->
            <div class="table-responsive">
                <table class="table custom-table align-middle mb-0" id="quizTable">
                    <thead>
                        <tr>
                            <th>Quiz Title</th>
                            <th>Course</th>
                            <th>Time Limit</th>
                            <th>Total Marks</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows == 0) { ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-patch-question fs-1 d-block mb-2 text-secondary"></i>
                                        <h6 class="fw-semibold">No Quizzes Available</h6>
                                        <p class="small mb-0">There are currently no active quizzes assigned to your courses.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>

                        <?php while ($q = $result->fetch_assoc()) { ?>
                            <tr class="quiz-row">
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="p-2 rounded bg-primary-subtle text-primary fs-5">
                                            <i class="bi bi-patch-question-fill"></i>
                                        </div>
                                        <div>
                                            <span class="fw-bold text-dark fs-6 d-block">
                                                <?= htmlspecialchars($q['QuizTitle']) ?>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge-subtle">
                                        <i class="bi bi-journal-bookmark me-1"></i><?= htmlspecialchars($q['CourseTitle']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1 rounded fw-semibold">
                                        <i class="bi bi-clock-history me-1"></i><?= htmlspecialchars($q['TimeLimit']) ?> mins
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-semibold text-dark">
                                        <i class="bi bi-trophy text-primary me-1"></i><?= htmlspecialchars($q['TotalMarks'] ?? 'N/A') ?> Marks
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="take_quiz.php?quiz=<?= $q['QuizID'] ?>" class="btn btn-sm btn-primary btn-action">
                                        <i class="bi bi-pencil-square"></i> Take Quiz
                                    </a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

        </div>

    </main>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Interactive Scripts -->
    <script>
        // Real-time Search Filter
        document.getElementById("searchInput").addEventListener("keyup", function() {
            let filterValue = this.value.toLowerCase().trim();
            let rows = document.querySelectorAll("#quizTable tbody tr.quiz-row");

            rows.forEach(function(row) {
                let text = row.innerText.toLowerCase();
                row.style.display = text.includes(filterValue) ? "" : "none";
            });
        });

        // Mobile Sidebar Drawer Toggle
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');

        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('mobile-open');
            });
        }
    </script>

</body>
</html>