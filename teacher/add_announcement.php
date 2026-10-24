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
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Post Announcement | LMS</title>
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
    max-width: 800px;
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

.form-control {
    border-radius: 10px;
    border: 1px solid #cbd5e1;
    padding: 12px 16px;
}

.form-control:focus {
    border-color: #6d35f5;
    box-shadow: 0 0 0 0.25rem rgba(109, 53, 245, 0.15);
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
                    <h1 class="fw-bold mb-1">Create Announcement</h1>
                    <p class="text-muted mb-0">Course: <span class="fw-semibold text-primary"><?= htmlspecialchars($courseData['CourseTitle']) ?></span></p>
                </div>
            </div>
            <div>
                <!-- <a href="announcements.php?course=<?= $course_id ?>" class="btn btn-outline-secondary rounded-3 px-3">
                    <i class="bi bi-arrow-left me-1"></i> Back to Announcements
                </a> -->
            </div>
        </div>

        <div class="section-box">

            <!-- SESSION NOTIFICATIONS -->
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($_SESSION['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <form action="save_announcement.php" method="POST" id="announcementForm" onsubmit="return validateAnnouncement()">

                <input type="hidden" name="course" value="<?= $course_id ?>">

                <!-- TITLE INPUT -->
                <div class="mb-4">
                    <label for="title" class="form-label fw-bold">
                        Announcement Title <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 rounded-start-3"><i class="bi bi-card-heading"></i></span>
                        <input type="text" name="title" id="title" class="form-control border-start-0 rounded-end-3" placeholder="e.g., Exam Schedule Update, Assignment Due Date Extension" required>
                    </div>
                    <div class="form-text text-muted">Title should be at least 3 characters long.</div>
                </div>

                <!-- MESSAGE INPUT -->
                <div class="mb-4">
                    <label for="message" class="form-label fw-bold">
                        Message Content <span class="text-danger">*</span>
                    </label>
                    <textarea name="message" id="message" class="form-control" rows="6" placeholder="Write your announcement details here..." required></textarea>
                    <div class="form-text text-muted">Include all relevant information for enrolled students.</div>
                </div>

                <!-- ACTION BUTTONS -->
                <div class="d-flex gap-3 pt-2">
                    <button type="submit" class="btn btn-success rounded-3 px-4 fw-semibold">
                        <i class="bi bi-send me-1"></i> Publish Announcement
                    </button>
                    <a href="view_announcements.php?course=<?= $course_id ?>" class="btn btn-light border rounded-3 px-4">
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
function validateAnnouncement() {
    const title = document.getElementById("title").value.trim();
    const message = document.getElementById("message").value.trim();

    if (title.length < 3) {
        alert("Announcement title must be at least 3 characters long.");
        document.getElementById("title").focus();
        return false;
    }

    if (message.length < 5) {
        alert("Announcement message must be at least 5 characters long.");
        document.getElementById("message").focus();
        return false;
    }

    return true;
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