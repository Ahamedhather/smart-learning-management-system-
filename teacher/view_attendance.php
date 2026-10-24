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
$courseData = $stmt->get_result()->fetch_assoc();

if (!$courseData) {
    header("Location: my_courses.php");
    exit();
}

// FETCH ATTENDANCE RECORDS
$stmt = $conn->prepare("
    SELECT a.AttendanceID, a.AttendanceDate, a.Status, u.full_name, s.RegistrationNumber
    FROM Attendance a
    JOIN Student s ON a.StudentID = s.StudentID
    JOIN users u ON s.UserID = u.user_id
    WHERE a.CourseID = ?
    ORDER BY a.AttendanceDate DESC, u.full_name ASC
");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Attendance Records | LMS</title>
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

.search-box {
    position: relative;
    max-width: 400px;
}

.search-box .bi-search {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
}

.search-box input {
    padding-left: 42px;
    border-radius: 10px;
    border: 1px solid #cbd5e1;
}

.table-custom {
    border-radius: 12px;
    overflow: hidden;
}

.table-custom thead {
    background: #1e293b;
    color: white;
}

.table-custom th {
    padding: 14px;
    font-weight: 600;
    font-size: 0.9rem;
    white-space: nowrap;
}

.table-custom td {
    padding: 14px;
    vertical-align: middle;
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
                    <h1 class="fw-bold mb-1">Attendance History</h1>
                    <p class="text-muted mb-0">Course: <span class="fw-semibold text-primary"><?= htmlspecialchars($courseData['CourseTitle'] ?? 'Unknown Course') ?></span></p>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="mark_attendance.php?course=<?= $course_id ?>" class="btn btn-success fw-semibold rounded-3 px-3">
                    <i class="bi bi-plus-lg me-1"></i> Mark Attendance
                </a>
                <a href="my_courses.php" class="btn btn-outline-secondary rounded-3 px-3">
                    <i class="bi bi-arrow-left me-1"></i> Back to Courses
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

            <!-- SEARCH TOOLBAR -->
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <div class="search-box w-100">
                    <i class="bi bi-search"></i>
                    <input type="text" id="searchInput" class="form-control" placeholder="Search by student name, registration no, date, or status...">
                </div>
            </div>

            <!-- RECORDS TABLE -->
            <div class="table-responsive table-custom">
                <table class="table table-hover align-middle mb-0" id="attendanceTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Student Name</th>
                            <th>Registration No</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows === 0): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-calendar-x fs-1 text-secondary d-block mb-2"></i>
                                    No attendance records recorded for this course yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <span class="fw-semibold text-dark">
                                            <i class="bi bi-calendar-event me-1 text-secondary"></i>
                                            <?= date("d M Y", strtotime($row['AttendanceDate'])) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="fw-semibold text-dark d-block">
                                            <i class="bi bi-person me-1 text-primary"></i>
                                            <?= htmlspecialchars($row['full_name']) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="badge bg-light text-dark border px-2.5 py-1.5 fw-normal">
                                            <?= htmlspecialchars($row['RegistrationNumber']) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?php 
                                        $status = $row['Status'];
                                        if ($status === 'Present') {
                                            echo '<span class="badge bg-success-subtle text-success-emphasis border border-success-subtle px-2.5 py-1.5"><i class="bi bi-check-circle me-1"></i> Present</span>';
                                        } elseif ($status === 'Absent') {
                                            echo '<span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle px-2.5 py-1.5"><i class="bi bi-x-circle me-1"></i> Absent</span>';
                                        } elseif ($status === 'Late') {
                                            echo '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2.5 py-1.5"><i class="bi bi-clock-history me-1"></i> Late</span>';
                                        } else {
                                            echo '<span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle px-2.5 py-1.5">' . htmlspecialchars($status) . '</span>';
                                        }
                                        ?>
                                    </td>

                                    <td class="text-end">
                                        <a href="update_attendance.php?id=<?= $row['AttendanceID'] ?>&course=<?= $course_id ?>" 
                                           class="btn btn-sm btn-outline-warning rounded-2 px-3">
                                            <i class="bi bi-pencil me-1"></i> Edit
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>

    </main>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Live Search Filter
document.getElementById("searchInput")?.addEventListener("keyup", function() {
    const value = this.value.toLowerCase();
    const rows = document.querySelectorAll("#attendanceTable tbody tr");

    rows.forEach(function(row) {
        row.style.display = row.innerText.toLowerCase().includes(value) ? "" : "none";
    });
});

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