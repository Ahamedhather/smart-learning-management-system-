<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("../config/db.php");
include("../includes/auth.php");
include("../config/functions.php");

// 🔒 SECURITY CHECK
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'teacher') {
    header("Location: ../login.php");
    exit();
}

$teacher_id = intval($_SESSION['teacher_id'] ?? 0);

// 📊 STATISTICS QUERIES
$stmt_courses = $conn->prepare("SELECT COUNT(*) as total FROM Course WHERE TeacherID = ?");
$stmt_courses->bind_param("i", $teacher_id);
$stmt_courses->execute();
$courses = $stmt_courses->get_result()->fetch_assoc()['total'] ?? 0;

$stmt_materials = $conn->prepare("
    SELECT COUNT(*) as total FROM StudyMaterial sm
    JOIN Course c ON sm.CourseID = c.CourseID
    WHERE c.TeacherID = ?
");
$stmt_materials->bind_param("i", $teacher_id);
$stmt_materials->execute();
$materials = $stmt_materials->get_result()->fetch_assoc()['total'] ?? 0;

$stmt_assignments = $conn->prepare("
    SELECT COUNT(*) as total FROM Assignment a
    JOIN Course c ON a.CourseID = c.CourseID
    WHERE c.TeacherID = ?
");
$stmt_assignments->bind_param("i", $teacher_id);
$stmt_assignments->execute();
$assignments = $stmt_assignments->get_result()->fetch_assoc()['total'] ?? 0;

$stmt_students = $conn->prepare("
    SELECT COUNT(DISTINCT e.StudentID) as total
    FROM Enrollment e
    JOIN Course c ON e.CourseID = c.CourseID
    WHERE c.TeacherID = ?
");
$stmt_students->bind_param("i", $teacher_id);
$stmt_students->execute();
$students = $stmt_students->get_result()->fetch_assoc()['total'] ?? 0;

// FETCH TEACHER'S ASSIGNED COURSES
$stmt_course_list = $conn->prepare("SELECT * FROM Course WHERE TeacherID = ? ORDER BY CourseTitle ASC");
$stmt_course_list->bind_param("i", $teacher_id);
$stmt_course_list->execute();
$courseList = $stmt_course_list->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Teacher Dashboard | LMS</title>
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
    box-shadow: 0 6px 18px rgba(0,0,0,0.05);
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

/* KPI Stat Cards */
.stat-card {
    background: white;
    border-radius: 16px;
    padding: 22px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.08);
}

.stat-icon {
    width: 55px;
    height: 55px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
}

.icon-purple { background: rgba(109, 53, 245, 0.12); color: #6d35f5; }
.icon-green  { background: rgba(16, 185, 129, 0.12); color: #10b981; }
.icon-orange { background: rgba(245, 158, 11, 0.12); color: #f59e0b; }
.icon-red    { background: rgba(239, 68, 68, 0.12); color: #ef4444; }

/* Quick Action Buttons */
.action-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 14px 18px;
    color: #1e293b;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 600;
    transition: all 0.2s ease;
}

.action-card:hover {
    background: #6d35f5;
    color: white;
    border-color: #6d35f5;
    transform: translateY(-2px);
}

.course-card {
    background: white;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    padding: 20px;
    transition: all 0.2s ease;
}

.course-card:hover {
    box-shadow: 0 6px 18px rgba(0,0,0,0.06);
    border-color: #cbd5e1;
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

    <!-- MAIN CONTENT CONTAINER -->
    <main class="content" id="content">

        <!-- TOPBAR -->
        <div class="topbar">
            <div class="d-flex align-items-center gap-3">
                <button id="mobileMenuToggle" class="toggle-btn">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    <h1 class="fw-bold mb-1">Teacher Dashboard</h1>
                    <p class="text-muted mb-0">Overview of your courses, active students, and academic materials.</p>
                </div>
            </div>
            <div class="d-flex gap-2">
                <!-- <a href="../public/login.php" class="btn btn-outline-danger rounded-3 px-3">
                    <i class="bi bi-box-arrow-right me-1"></i> Logout
                </a> -->
            </div>
        </div>

        <!-- STATS KPI CARDS -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div>
                        <span class="text-muted fs-7 d-block mb-1">My Courses</span>
                        <h3 class="fw-bold mb-0"><?= number_format($courses) ?></h3>
                    </div>
                    <div class="stat-icon icon-purple">
                        <i class="bi bi-journal-bookmark"></i>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div>
                        <span class="text-muted fs-7 d-block mb-1">Enrolled Students</span>
                        <h3 class="fw-bold mb-0"><?= number_format($students) ?></h3>
                    </div>
                    <div class="stat-icon icon-green">
                        <i class="bi bi-people"></i>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div>
                        <span class="text-muted fs-7 d-block mb-1">Study Materials</span>
                        <h3 class="fw-bold mb-0"><?= number_format($materials) ?></h3>
                    </div>
                    <div class="stat-icon icon-orange">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div>
                        <span class="text-muted fs-7 d-block mb-1">Assignments</span>
                        <h3 class="fw-bold mb-0"><?= number_format($assignments) ?></h3>
                    </div>
                    <div class="stat-icon icon-red">
                        <i class="bi bi-card-checklist"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- QUICK ACTIONS HUB -->
        <div class="section-box mb-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-lightning-charge text-warning me-2"></i>Quick Actions</h5>
            <div class="d-flex flex-wrap gap-3">
                <a href="my_courses.php" class="action-card">
                    <i class="bi bi-journal-text fs-5"></i> My Courses
                </a>
                <a href="attendance_courses.php" class="action-card">
                    <i class="bi bi-calendar-check fs-5"></i> Attendance Courses
                </a>
                <a href="chat.php" class="action-card">
                    <i class="bi bi-chat-dots fs-5"></i> Send Message
                </a>
            </div>
        </div>

        <!-- ASSIGNED COURSES SECTION -->
        <div class="section-box">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold mb-0"><i class="bi bi-book me-2 text-primary"></i>My Assigned Courses</h5>
                <a href="my_courses.php" class="btn btn-sm btn-outline-primary rounded-3">View All</a>
            </div>

            <?php if($courseList && $courseList->num_rows > 0){ ?>
                <div class="row g-3">
                    <?php while($c = $courseList->fetch_assoc()){ ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="course-card h-100 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <i class="bi bi-folder2-open text-primary fs-5"></i>
                                        <h6 class="fw-bold text-dark mb-0"><?= htmlspecialchars($c['CourseTitle']) ?></h6>
                                    </div>
                                    <p class="text-muted small mb-3">
                                        <?= htmlspecialchars($c['CourseDescription'] ?? 'No description available for this course.') ?>
                                    </p>
                                </div>
                                <div>
                                    <a href="my_courses.php?id=<?= $c['CourseID'] ?>" class="btn btn-sm btn-light border w-100 rounded-3">
                                        Manage Course <i class="bi bi-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            <?php } else { ?>
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-journal-x fs-2 d-block mb-2 text-secondary"></i>
                    No courses have been assigned to your profile yet.
                </div>
            <?php } ?>
        </div>

    </main>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
/* SIDEBAR TOGGLE HANDLER */
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