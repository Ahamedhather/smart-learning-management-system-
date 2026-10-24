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

$user_id = $_SESSION['user_id'];

// FETCH TEACHER ID
$stmt = $conn->prepare("SELECT TeacherID FROM Teacher WHERE UserID = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$teacher = $stmt->get_result();

if ($teacher->num_rows === 0) {
    $_SESSION['error'] = "Teacher profile not found. Please contact system administrator.";
    header("Location: dashboard.php");
    exit();
}

$teacher_id = $teacher->fetch_assoc()['TeacherID'];

// FETCH ASSIGNED COURSES FOR ATTENDANCE
$stmt = $conn->prepare("
    SELECT CourseID, CourseTitle, CourseDescription, Semester
    FROM Course 
    WHERE TeacherID = ?
    ORDER BY CourseTitle ASC
");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$courses = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Attendance Courses | LMS</title>
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
    max-width: 450px;
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

.course-card {
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    background: #ffffff;
    transition: all 0.25s ease;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.course-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.06);
    border-color: #cbd5e1;
}

.course-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: #eef2ff;
    color: #4f46e5;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
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
                    <h1 class="fw-bold mb-1">Attendance Management</h1>
                    <p class="text-muted mb-0">Select a course to record or view attendance history</p>
                </div>
            </div>
            <!-- <div>
                <a href="dashboard.php" class="btn btn-outline-secondary rounded-3 px-3">
                    <i class="bi bi-house-door me-1"></i> Dashboard
                </a>
            </div> -->
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

            <!-- SEARCH BAR -->
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <div class="search-box w-100">
                    <i class="bi bi-search"></i>
                    <input type="text" id="searchInput" class="form-control" placeholder="Search courses by title, description, or semester...">
                </div>
            </div>

            <!-- COURSE CARDS GRID -->
            <div class="row g-4" id="courseContainer">
                <?php if ($courses->num_rows === 0): ?>
                    <div class="col-12">
                        <div class="text-center py-5 text-muted border rounded-3 bg-light">
                            <i class="bi bi-journal-x fs-1 text-secondary d-block mb-2"></i>
                            No courses currently assigned to you for attendance management.
                        </div>
                    </div>
                <?php else: ?>
                    <?php while ($c = $courses->fetch_assoc()): ?>
                        <div class="col-md-6 col-xl-4 course-item">
                            <div class="card course-card p-4">
                                <div>
                                    <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                                        <div class="course-icon">
                                            <i class="bi bi-calendar-check"></i>
                                        </div>
                                        <span class="badge bg-light text-secondary border px-2.5 py-1.5 fw-normal">
                                            Semester: <?= htmlspecialchars($c['Semester'] ?? 'N/A') ?>
                                        </span>
                                    </div>

                                    <h5 class="fw-bold text-dark mb-2"><?= htmlspecialchars($c['CourseTitle']) ?></h5>

                                    <p class="text-secondary small mb-4 lh-base text-truncate-2">
                                        <?= htmlspecialchars($c['CourseDescription'] ?? 'No description available for this course.') ?>
                                    </p>
                                </div>

                                <div class="d-flex gap-2 pt-3 border-top">
                                    <a href="mark_attendance.php?course=<?= $c['CourseID'] ?>" class="btn btn-primary btn-sm rounded-2 w-100 fw-semibold">
                                        <i class="bi bi-pencil-square me-1"></i> Mark
                                    </a>
                                    <a href="view_attendance.php?course=<?= $c['CourseID'] ?>" class="btn btn-outline-dark btn-sm rounded-2 w-100 fw-semibold">
                                        <i class="bi bi-eye me-1"></i> View Records
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
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
    const cards = document.querySelectorAll(".course-item");

    cards.forEach(function(card) {
        card.style.display = card.innerText.toLowerCase().includes(value) ? "" : "none";
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