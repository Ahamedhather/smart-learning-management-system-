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

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    $_SESSION['error'] = "Invalid or missing Attendance ID.";
    header("Location: my_courses.php");
    exit();
}

// FETCH ATTENDANCE RECORD DETAILS
$stmt = $conn->prepare("
    SELECT a.*, u.full_name, c.CourseTitle
    FROM Attendance a
    JOIN Student s ON a.StudentID = s.StudentID
    JOIN users u ON s.UserID = u.user_id
    JOIN Course c ON a.CourseID = c.CourseID
    WHERE a.AttendanceID = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    $_SESSION['error'] = "Attendance record not found.";
    header("Location: my_courses.php");
    exit();
}

$course_id = $data['CourseID'];

// HANDLE FORM SUBMISSION
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = trim($_POST['status'] ?? '');
    $valid_statuses = ['Present', 'Absent', 'Late', 'Excused'];

    if (!in_array($status, $valid_statuses)) {
        $_SESSION['error'] = "Invalid status selected. Please choose a valid status.";
    } else {
        $update_stmt = $conn->prepare("
            UPDATE Attendance 
            SET Status = ? 
            WHERE AttendanceID = ?
        ");
        $update_stmt->bind_param("si", $status, $id);

        if ($update_stmt->execute()) {
            $_SESSION['success'] = "Attendance updated successfully for " . $data['full_name'] . ".";
            header("Location: view_attendance.php?course=" . $course_id);
            exit();
        } else {
            $_SESSION['error'] = "Failed to update attendance record. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Update Attendance | LMS</title>
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
    max-width: 700px;
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

.info-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 20px;
}

.form-select {
    border-radius: 10px;
    border: 1px solid #cbd5e1;
    padding: 12px 16px;
    font-size: 15px;
}

.form-select:focus {
    border-color: #6d35f5;
    box-shadow: 0 0 0 0.25rem rgba(109, 53, 245, 0.15);
}

.status-badge-present { background-color: #d1fae5; color: #065f46; }
.status-badge-absent { background-color: #fee2e2; color: #991b1b; }
.status-badge-late { background-color: #fef3c7; color: #92400e; }
.status-badge-excused { background-color: #e0f2fe; color: #075985; }

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
                    <h1 class="fw-bold mb-1">Update Attendance</h1>
                    <p class="text-muted mb-0">Modify record status for student</p>
                </div>
            </div>
            <div>
                <!-- <a href="view_attendance.php?course=<?= $course_id ?>" class="btn btn-outline-secondary rounded-3 px-3">
                    <i class="bi bi-arrow-left me-1"></i> Back to Attendance
                </a> -->
            </div>
        </div>

        <div class="section-box">

            <!-- ERROR NOTIFICATION -->
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($_SESSION['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- STUDENT & COURSE SUMMARY CARD -->
            <div class="info-card mb-4">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <small class="text-muted d-block fw-semibold text-uppercase fs-7 mb-1">Student Name</small>
                        <span class="fw-bold text-dark fs-6">
                            <i class="bi bi-person-circle text-primary me-1"></i>
                            <?= htmlspecialchars($data['full_name']) ?>
                        </span>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block fw-semibold text-uppercase fs-7 mb-1">Course</small>
                        <span class="fw-semibold text-dark">
                            <i class="bi bi-journal-bookmark text-primary me-1"></i>
                            <?= htmlspecialchars($data['CourseTitle']) ?>
                        </span>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block fw-semibold text-uppercase fs-7 mb-1">Attendance Date</small>
                        <span class="fw-semibold text-dark">
                            <i class="bi bi-calendar-event text-primary me-1"></i>
                            <?= date("F d, Y", strtotime($data['AttendanceDate'])) ?>
                        </span>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block fw-semibold text-uppercase fs-7 mb-1">Current Status</small>
                        <?php 
                            $status_class = match($data['Status']) {
                                'Present' => 'status-badge-present',
                                'Absent'  => 'status-badge-absent',
                                'Late'    => 'status-badge-late',
                                default   => 'status-badge-excused'
                            };
                        ?>
                        <span class="badge px-2.5 py-1.5 rounded-2 <?= $status_class ?>">
                            <?= htmlspecialchars($data['Status']) ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- UPDATE FORM -->
            <form method="POST" onsubmit="return confirm('Are you sure you want to update this attendance record?')">

                <div class="mb-4">
                    <label for="statusSelect" class="form-label fw-bold">Select New Status <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 rounded-start-3"><i class="bi bi-check2-circle"></i></span>
                        <select name="status" id="statusSelect" class="form-select border-start-0 rounded-end-3" required>
                            <option value="Present" <?= ($data['Status'] === 'Present') ? 'selected' : '' ?>>Present</option>
                            <option value="Absent" <?= ($data['Status'] === 'Absent') ? 'selected' : '' ?>>Absent</option>
                            <option value="Late" <?= ($data['Status'] === 'Late') ? 'selected' : '' ?>>Late</option>
                            <option value="Excused" <?= ($data['Status'] === 'Excused') ? 'selected' : '' ?>>Excused</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex gap-3 pt-2">
                    <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold">
                        <i class="bi bi-check-lg me-1"></i> Save Changes
                    </button>
                    <a href="view_attendance.php?course=<?= $course_id ?>" class="btn btn-light border rounded-3 px-4">
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