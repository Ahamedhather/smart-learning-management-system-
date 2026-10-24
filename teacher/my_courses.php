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

$user_id = intval($_SESSION['user_id']);
$teacher_id = 0;
$error_msg = null;

// Get teacher record
$stmt = $conn->prepare("SELECT TeacherID FROM Teacher WHERE UserID = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$teacher_res = $stmt->get_result();

if ($teacher_res->num_rows === 0) {
    $error_msg = "Teacher profile record not found for your account. Please contact system administrator.";
} else {
    $teacher_id = $teacher_res->fetch_assoc()['TeacherID'];
}

// Fetch assigned courses
$courses = null;
if ($teacher_id > 0) {
    $stmt_courses = $conn->prepare("
        SELECT CourseID, CourseTitle, CourseDescription, Semester
        FROM Course 
        WHERE TeacherID = ?
        ORDER BY CourseTitle ASC
    ");
    $stmt_courses->bind_param("i", $teacher_id);
    $stmt_courses->execute();
    $courses = $stmt_courses->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Courses | LMS</title>
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

.action-btn-group {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

.btn-action {
    padding: 5px 10px;
    font-size: 0.78rem;
    border-radius: 8px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 4px;
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
                    <h1 class="fw-bold mb-1">My Assigned Courses</h1>
                    <p class="text-muted mb-0">Manage course material, assignments, quizzes, attendance, and student updates.</p>
                </div>
            </div>
            <div>
                <!-- <a href="teacher_dashboard.php" class="btn btn-outline-secondary rounded-3 px-3">
                    <i class="bi bi-arrow-left me-1"></i> Dashboard -->
                </a>
            </div>
        </div>

        <?php if ($error_msg): ?>
            <div class="alert alert-danger rounded-4 border-0 shadow-sm p-4 text-center">
                <i class="bi bi-exclamation-octagon fs-1 text-danger d-block mb-2"></i>
                <h5 class="fw-bold">Profile Resolution Notice</h5>
                <p class="mb-0 text-muted"><?= htmlspecialchars($error_msg) ?></p>
            </div>
        <?php else: ?>

        <div class="section-box">
            
            <!-- SEARCH TOOLBAR -->
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <div class="search-box w-100">
                    <i class="bi bi-search"></i>
                    <input type="text" id="searchInput" class="form-control" placeholder="Search courses by title, description, or semester...">
                </div>
            </div>

            <!-- COURSES TABLE -->
            <div class="table-responsive table-custom">
                <table class="table table-hover align-middle mb-0" id="courseTable">
                    <thead>
                        <tr>
                            <th>Course Info</th>
                            <th>Semester</th>
                            <th>Materials</th>
                            <th>Assignments</th>
                            <th>Quiz</th>
                            <th>Attendance</th>
                            <th>Communication</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$courses || $courses->num_rows === 0): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-journal-x fs-1 text-secondary d-block mb-2"></i>
                                    No assigned courses found for your profile.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php while ($c = $courses->fetch_assoc()): ?>
                                <tr>
                                    <td style="min-width: 200px;">
                                        <div class="fw-bold text-dark mb-1">
                                            <i class="bi bi-journal-bookmark-fill text-primary me-1"></i>
                                            <?= htmlspecialchars($c['CourseTitle']) ?>
                                        </div>
                                        <small class="text-muted d-block line-clamp-2">
                                            <?= htmlspecialchars($c['CourseDescription'] ?? 'No description') ?>
                                        </small>
                                    </td>

                                    <td>
                                        <span class="badge bg-light text-dark border px-2 py-1 rounded-2">
                                            <?= htmlspecialchars($c['Semester'] ?? 'N/A') ?>
                                        </span>
                                    </td>

                                    <td>
                                        <div class="action-btn-group">
                                            <a href="upload_material.php?course=<?= $c['CourseID'] ?>" class="btn btn-success btn-action">
                                                <i class="bi bi-upload"></i> Upload
                                            </a>
                                            <a href="view_materials.php?course=<?= $c['CourseID'] ?>" class="btn btn-outline-primary btn-action">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="action-btn-group">
                                            <a href="add_assignment.php?course=<?= $c['CourseID'] ?>" class="btn btn-warning text-dark btn-action">
                                                <i class="bi bi-plus-lg"></i> Add
                                            </a>
                                            <a href="view_assignments.php?course=<?= $c['CourseID'] ?>" class="btn btn-outline-dark btn-action">
                                                <i class="bi bi-list-check"></i> View
                                            </a>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="action-btn-group">
                                            <a href="add_quiz.php?course=<?= $c['CourseID'] ?>" class="btn btn-info text-white btn-action">
                                                <i class="bi bi-patch-plus"></i> Create
                                            </a>
                                            <a href="quiz_list.php?course=<?= $c['CourseID'] ?>" class="btn btn-outline-secondary btn-action">
                                                <i class="bi bi-gear"></i> Manage
                                            </a>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="action-btn-group">
                                            <a href="mark_attendance.php?course=<?= $c['CourseID'] ?>" class="btn btn-primary btn-action">
                                                <i class="bi bi-check2-square"></i> Mark
                                            </a>
                                            <a href="view_attendance.php?course=<?= $c['CourseID'] ?>" class="btn btn-outline-dark btn-action">
                                                <i class="bi bi-calendar3"></i> View
                                            </a>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="action-btn-group">
                                            <a href="add_announcement.php?course=<?= $c['CourseID'] ?>" class="btn btn-success btn-action">
                                                <i class="bi bi-megaphone"></i> Post
                                            </a>
                                            <a href="view_announcements.php?course=<?= $c['CourseID'] ?>" class="btn btn-outline-info btn-action">
                                                <i class="bi bi-bell"></i> View
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>

        <?php endif; ?>

    </main>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Real-time Course Search Filter
document.getElementById("searchInput")?.addEventListener("keyup", function() {
    const value = this.value.toLowerCase();
    const rows = document.querySelectorAll("#courseTable tbody tr");

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