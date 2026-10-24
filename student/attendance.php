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

// Fetch Detailed Attendance Records
$stmt = $conn->prepare("
    SELECT 
        a.AttendanceDate,
        a.Status,
        c.CourseTitle
    FROM Attendance a
    JOIN Course c ON a.CourseID = c.CourseID
    WHERE a.StudentID = ?
    ORDER BY a.AttendanceDate DESC
");

$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

// Fetch Attendance Summary Statistics
$stmt2 = $conn->prepare("
    SELECT 
        COUNT(*) AS total,
        SUM(CASE WHEN Status = 'Present' THEN 1 ELSE 0 END) AS present,
        SUM(CASE WHEN Status = 'Absent' THEN 1 ELSE 0 END) AS absent
    FROM Attendance
    WHERE StudentID = ?
");
$stmt2->bind_param("i", $student_id);
$stmt2->execute();
$summary = $stmt2->get_result()->fetch_assoc();

$total = $summary['total'] ?? 0;
$present = $summary['present'] ?? 0;
$absent = $summary['absent'] ?? 0;
$percentage = ($total > 0) ? round(($present / $total) * 100, 1) : 0;

// Dynamic Progress Color Logic
$progress_class = "bg-success";
if ($percentage < 75 && $percentage >= 60) {
    $progress_class = "bg-warning";
} elseif ($percentage < 60) {
    $progress_class = "bg-danger";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Attendance | Student Portal</title>

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

        /* SUMMARY STAT CARDS */
        .stat-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            transition: transform 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
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
                <h3 class="fw-bold mb-1">My Attendance Record</h3>
                <p class="text-muted mb-0">Track your daily class attendance and overall presence score.</p>
            </div>
            <!-- <a href="student_dashboard.php" class="btn btn-outline-secondary btn-action">
                <i class="bi bi-arrow-left"></i> Dashboard
            </a> -->
        </div>

        <!-- SUMMARY STAT CARDS GRID -->
        <div class="row g-3 mb-4">
            <!-- Overall Percentage Card -->
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-icon bg-primary-subtle text-primary">
                        <i class="bi bi-percent"></i>
                    </div>
                    <div>
                        <span class="text-muted small fw-semibold d-block">Overall Attendance</span>
                        <h4 class="fw-bold mb-0 text-dark"><?= $percentage ?>%</h4>
                    </div>
                </div>
            </div>

            <!-- Present Days Card -->
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-icon bg-success-subtle text-success">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <div>
                        <span class="text-muted small fw-semibold d-block">Present Days</span>
                        <h4 class="fw-bold mb-0 text-dark"><?= $present ?></h4>
                    </div>
                </div>
            </div>

            <!-- Absent Days Card -->
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-icon bg-danger-subtle text-danger">
                        <i class="bi bi-x-circle-fill"></i>
                    </div>
                    <div>
                        <span class="text-muted small fw-semibold d-block">Absent Days</span>
                        <h4 class="fw-bold mb-0 text-dark"><?= $absent ?></h4>
                    </div>
                </div>
            </div>

            <!-- Total Sessions Card -->
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-icon bg-secondary-subtle text-secondary">
                        <i class="bi bi-calendar-event-fill"></i>
                    </div>
                    <div>
                        <span class="text-muted small fw-semibold d-block">Total Recorded</span>
                        <h4 class="fw-bold mb-0 text-dark"><?= $total ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- CONTENT CARD -->
        <div class="content-card">
            
            <!-- PROGRESS BAR INDICATOR -->
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="fw-semibold text-secondary small">Attendance Threshold Indicator</span>
                    <span class="fw-bold small"><?= $percentage ?>%</span>
                </div>
                <div class="progress" style="height: 10px; border-radius: 8px;">
                    <div class="progress-bar <?= $progress_class ?>" role="progressbar" style="width: <?= $percentage ?>%;" aria-valuenow="<?= $percentage ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>

            <!-- SEARCH BAR -->
            <div class="row mb-4">
                <div class="col-md-6 col-lg-5">
                    <div class="search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" id="searchInput" class="form-control" placeholder="Search by course title, date, or status...">
                    </div>
                </div>
            </div>

            <!-- ATTENDANCE TABLE -->
            <div class="table-responsive">
                <table class="table custom-table align-middle mb-0" id="attendanceTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Course Name</th>
                            <th class="text-end">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows == 0) { ?>
                            <tr>
                                <td colspan="3" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-calendar-x fs-1 d-block mb-2 text-secondary"></i>
                                        <h6 class="fw-semibold">No Attendance Records Found</h6>
                                        <p class="small mb-0">There are no attendance sessions logged for your account yet.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>

                        <?php while ($row = $result->fetch_assoc()) { ?>
                            <tr class="attendance-row">
                                <td>
                                    <div class="d-flex align-items-center gap-2 text-dark fw-semibold">
                                        <i class="bi bi-calendar3 text-primary"></i>
                                        <span><?= date("d M Y", strtotime($row['AttendanceDate'])) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-medium text-dark">
                                        <?= htmlspecialchars($row['CourseTitle']) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <?php if ($row['Status'] == 'Present') { ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill">
                                            <i class="bi bi-check-circle-fill me-1"></i> Present
                                        </span>
                                    <?php } else { ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1 rounded-pill">
                                            <i class="bi bi-x-circle-fill me-1"></i> Absent
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
        // Real-time Search Filter
        document.getElementById("searchInput").addEventListener("keyup", function() {
            let filterValue = this.value.toLowerCase().trim();
            let rows = document.querySelectorAll("#attendanceTable tbody tr.attendance-row");

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