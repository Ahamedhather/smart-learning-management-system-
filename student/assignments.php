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

// Validate Course Parameter
if (!isset($_GET['course']) || !filter_var($_GET['course'], FILTER_VALIDATE_INT)) {
    header("Location: my_courses.php");
    exit();
}

$course_id = intval($_GET['course']);

// Check Student Enrollment & Get Course Title
$enroll_stmt = $conn->prepare("
    SELECT c.CourseTitle 
    FROM Enrollment e
    JOIN Course c ON e.CourseID = c.CourseID
    WHERE e.StudentID = ? AND e.CourseID = ?
");
$enroll_stmt->bind_param("ii", $student_id, $course_id);
$enroll_stmt->execute();
$enroll_res = $enroll_stmt->get_result();

if ($enroll_res->num_rows == 0) {
    header("Location: my_courses.php?error=access_denied");
    exit();
}

$course_data = $enroll_res->fetch_assoc();
$course_title = $course_data['CourseTitle'];

// Fetch Course Assignments and Student Submissions (including SubmissionFile, SubmissionDate, and Marks)
$stmt = $conn->prepare("
    SELECT 
        a.*, 
        sub.SubmissionID,
        sub.SubmissionDate,
        sub.SubmissionFile,
        sub.Marks
    FROM Assignment a
    LEFT JOIN AssignmentSubmission sub 
        ON a.AssignmentID = sub.AssignmentID AND sub.StudentID = ?
    WHERE a.CourseID = ?
    ORDER BY a.Deadline ASC
");
$stmt->bind_param("ii", $student_id, $course_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Assignments | Student Portal</title>

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

        .grade-badge {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 6px 14px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 13px;
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
                <h3 class="fw-bold mb-1">Assignments</h3>
                <p class="text-muted mb-0">Course: <strong class="text-dark"><?= htmlspecialchars($course_title) ?></strong></p>
            </div>
            <a href="my_courses.php" class="btn btn-outline-secondary btn-action">
                <i class="bi bi-arrow-left"></i> Back to Courses
            </a>
        </div>

        <!-- CONTENT CARD -->
        <div class="content-card">
            
            <!-- SEARCH BAR -->
            <div class="row mb-4">
                <div class="col-md-6 col-lg-5">
                    <div class="search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" id="searchInput" class="form-control" placeholder="Search assignment title or description...">
                    </div>
                </div>
            </div>

            <!-- ASSIGNMENTS TABLE -->
            <div class="table-responsive">
                <table class="table custom-table align-middle mb-0" id="assignmentTable">
                    <thead>
                        <tr>
                            <th>Assignment Details</th>
                            <th>Deadline</th>
                            <th>Submitted File</th>
                            <th>Grade / Marks</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows == 0) { ?>
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-journal-check fs-1 d-block mb-2 text-secondary"></i>
                                        <h6 class="fw-semibold">No Assignments Found</h6>
                                        <p class="small mb-0">There are currently no assignments posted for this course.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>

                        <?php 
                        while ($row = $result->fetch_assoc()) { 
                            $deadline_time = strtotime($row['Deadline']);
                            $deadline_passed = $deadline_time < time();
                            $is_submitted = !empty($row['SubmissionID']);
                            $is_graded = ($row['Marks'] !== null);
                        ?>
                            <tr class="assignment-row">
                                <td>
                                    <div class="fw-bold text-dark fs-6">
                                        <?= htmlspecialchars($row['Title']) ?>
                                    </div>
                                    <?php if (!empty($row['Description'])) { ?>
                                        <small class="text-muted d-block mt-1">
                                            <?= nl2br(htmlspecialchars($row['Description'])) ?>
                                        </small>
                                    <?php } ?>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2 text-secondary fw-medium">
                                        <i class="bi bi-calendar-event text-primary"></i>
                                        <span><?= date("d M Y, h:i A", $deadline_time) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($is_submitted && !empty($row['SubmissionFile'])) { ?>
                                        <a href="../uploads/<?= htmlspecialchars($row['SubmissionFile']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-file-earmark-arrow-down me-1"></i> View File
                                        </a>
                                        <?php if (!empty($row['SubmissionDate'])) { ?>
                                            <small class="text-muted d-block mt-1" style="font-size: 11px;">
                                                Submitted: <?= date("d M Y, h:i A", strtotime($row['SubmissionDate'])) ?>
                                            </small>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <span class="text-muted small">No file submitted</span>
                                    <?php } ?>
                                </td>
                                <td>
                                    <div class="d-inline-flex align-items-center gap-2 grade-badge">
                                        <i class="bi bi-trophy text-warning"></i>
                                        <span>
                                            <strong><?= $is_graded ? htmlspecialchars($row['Marks']) : 'Not graded' ?></strong>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($is_submitted) { ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded">
                                            <i class="bi bi-check-circle-fill me-1"></i> Submitted
                                        </span>
                                    <?php } elseif ($deadline_passed) { ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 rounded">
                                            <i class="bi bi-x-circle-fill me-1"></i> Deadline Passed
                                        </span>
                                    <?php } else { ?>
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1 rounded">
                                            <i class="bi bi-clock-history me-1"></i> Pending
                                        </span>
                                    <?php } ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($is_submitted) { ?>
                                        <?php if ($is_graded) { ?>
                                            <span class="text-secondary small fw-semibold">
                                                <i class="bi bi-lock-fill me-1"></i> Graded (Locked)
                                            </span>
                                        <?php } elseif (!$deadline_passed) { ?>
                                            <a href="submit_assignment.php?id=<?= $row['AssignmentID'] ?>" class="btn btn-sm btn-outline-secondary btn-action">
                                                <i class="bi bi-arrow-repeat"></i> Re-submit
                                            </a>
                                        <?php } else { ?>
                                            <span class="text-secondary small fw-semibold">
                                                <i class="bi bi-lock-fill me-1"></i> Locked
                                            </span>
                                        <?php } ?>
                                    <?php } elseif (!$deadline_passed) { ?>
                                        <a href="submit_assignment.php?id=<?= $row['AssignmentID'] ?>" class="btn btn-sm btn-primary btn-action">
                                            <i class="bi bi-upload"></i> Submit Assignment
                                        </a>
                                    <?php } else { ?>
                                        <span class="text-secondary small fw-semibold">
                                            <i class="bi bi-lock-fill me-1"></i> Closed
                                        </span>
                                    <?php } ?>
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
        // Search Filter
        document.getElementById("searchInput").addEventListener("keyup", function() {
            let filterValue = this.value.toLowerCase().trim();
            let rows = document.querySelectorAll("#assignmentTable tbody tr.assignment-row");

            rows.forEach(function(row) {
                let text = row.innerText.toLowerCase();
                row.style.display = text.includes(filterValue) ? "" : "none";
            });
        });

        // Mobile Sidebar Drawer Toggle
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');

        if (sidebarToggle && sidebar) {
            sidebarToggle.appendEventListener('click', function() {
                sidebar.classList.toggle('mobile-open');
            });
        }
    </script>

</body>
</html>