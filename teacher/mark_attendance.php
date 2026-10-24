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

// FETCH ENROLLED STUDENTS
$stmt = $conn->prepare("
    SELECT s.StudentID, u.full_name, s.RegistrationNumber
    FROM Enrollment e
    JOIN Student s ON e.StudentID = s.StudentID
    JOIN users u ON s.UserID = u.user_id
    WHERE e.CourseID = ?
    ORDER BY u.full_name ASC
");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$students = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Mark Attendance | LMS</title>
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
                    <h1 class="fw-bold mb-1">Mark Class Attendance</h1>
                    <p class="text-muted mb-0">Course: <span class="fw-semibold text-primary"><?= htmlspecialchars($courseData['CourseTitle']) ?></span></p>
                </div>
            </div>
            <div>
                <!-- <a href="my_courses.php" class="btn btn-outline-secondary rounded-3 px-3">
                    <i class="bi bi-arrow-left me-1"></i> Back to Courses
                </a> -->
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

            <form action="save_attendance.php" method="POST" onsubmit="return confirm('Save attendance record for this class date?')">

                <input type="hidden" name="course" value="<?= $course_id ?>">

                <!-- TOOLBAR CONTROLS -->
                <div class="row align-items-center mb-4 g-3">
                    <!-- DATE PICKER -->
                    <div class="col-md-4">
                        <label class="form-label fw-bold mb-1">Attendance Date <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-calendar-check"></i></span>
                            <input type="date" name="attendance_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <!-- SEARCH INPUT -->
                    <div class="col-md-4">
                        <label class="form-label fw-bold mb-1">Filter Students</label>
                        <div class="search-box">
                            <i class="bi bi-search"></i>
                            <input type="text" id="searchInput" class="form-control" placeholder="Search by name or reg no...">
                        </div>
                    </div>

                    <!-- QUICK TOGGLE ACTION BUTTONS -->
                    <div class="col-md-4 text-md-end pt-md-4">
                        <button type="button" class="btn btn-outline-success btn-sm rounded-2 me-1" onclick="setAllStatus('Present')">
                            <i class="bi bi-check-all me-1"></i> All Present
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm rounded-2" onclick="setAllStatus('Absent')">
                            <i class="bi bi-x-circle me-1"></i> All Absent
                        </button>
                    </div>
                </div>

                <!-- ATTENDANCE TABLE -->
                <div class="table-responsive table-custom mb-4">
                    <table class="table table-hover align-middle mb-0" id="attendanceTable">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Registration Number</th>
                                <th style="width: 220px;">Attendance Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($students->num_rows === 0): ?>
                                <tr>
                                    <td colspan="3" class="text-center py-5 text-muted">
                                        <i class="bi bi-people fs-1 text-secondary d-block mb-2"></i>
                                        No students enrolled in this course yet.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php while ($row = $students->fetch_assoc()): ?>
                                    <tr>
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
                                            <select name="status[<?= $row['StudentID'] ?>]" class="form-select attendance-status-select rounded-3 p-2" required>
                                                <option value="Present" selected>Present</option>
                                                <option value="Absent">Absent</option>
                                                <option value="Late">Late</option>
                                            </select>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($students->num_rows > 0): ?>
                    <div class="d-flex gap-3">
                        <button type="submit" class="btn btn-success px-4 rounded-3 fw-semibold">
                            <i class="bi bi-check2-circle me-1"></i> Save Attendance
                        </button>
                        <a href="view_attendance.php" class="btn btn-light border px-4 rounded-3">Cancel</a>
                    </div>
                <?php endif; ?>

            </form>

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

// Quick Batch Toggle for Status
function setAllStatus(statusValue) {
    const selects = document.querySelectorAll('.attendance-status-select');
    selects.forEach(select => {
        select.value = statusValue;
    });
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