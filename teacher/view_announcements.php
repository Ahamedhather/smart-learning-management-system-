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

$user_id = $_SESSION['user_id'];

// HANDLE DELETE ACTION
if (isset($_GET['delete'])) {
    $announcement_id = intval($_GET['delete']);
    
    // Ensure the teacher can only delete their own announcements or announcements for their course
    $del_stmt = $conn->prepare("DELETE FROM Announcement WHERE AnnouncementID = ? AND CourseID = ?");
    $del_stmt->bind_param("ii", $announcement_id, $course_id);
    
    if ($del_stmt->execute()) {
        $_SESSION['success'] = "Announcement deleted successfully.";
    } else {
        $_SESSION['error'] = "Failed to delete announcement.";
    }
    header("Location: announcements.php?course=" . $course_id);
    exit();
}

// HANDLE EDIT / UPDATE ACTION (In-file Form Submission)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $announcement_id = intval($_POST['announcement_id']);
    $title = trim($_POST['title']);
    $message = trim($_POST['message']);

    if (!empty($title) && !empty($message)) {
        $update_stmt = $conn->prepare("UPDATE Announcement SET Title = ?, Message = ? WHERE AnnouncementID = ? AND CourseID = ? AND UserID = ?");
        $update_stmt->bind_param("ssiii", $title, $message, $announcement_id, $course_id, $user_id);

        if ($update_stmt->execute()) {
            $_SESSION['success'] = "Announcement updated successfully.";
        } else {
            $_SESSION['error'] = "Failed to update announcement.";
        }
    } else {
        $_SESSION['error'] = "Title and message cannot be empty.";
    }
    header("Location: announcements.php?course=" . $course_id);
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

// FETCH ANNOUNCEMENTS
$stmt = $conn->prepare("
    SELECT a.*, u.full_name
    FROM Announcement a
    JOIN users u ON a.UserID = u.user_id
    WHERE a.CourseID = ?
    ORDER BY a.CreatedDate DESC
");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Course Announcements | LMS</title>
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

.announcement-card {
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    background: #ffffff;
    transition: all 0.25s ease;
}

.announcement-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
    border-color: #cbd5e1;
}

.announcement-icon-wrapper {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: #eef2ff;
    color: #4f46e5;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
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
                    <h1 class="fw-bold mb-1">Course Announcements</h1>
                    <p class="text-muted mb-0">Course: <span class="fw-semibold text-primary"><?= htmlspecialchars($courseData['CourseTitle']) ?></span></p>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="add_announcement.php?course=<?= $course_id ?>" class="btn btn-success fw-semibold rounded-3 px-3">
                    <i class="bi bi-megaphone me-1"></i> Add Announcement
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
                    <input type="text" id="searchInput" class="form-control" placeholder="Search announcements by title or content...">
                </div>
            </div>

            <!-- ANNOUNCEMENTS LIST -->
            <div id="announcementContainer">
                <?php if ($result->num_rows === 0): ?>
                    <div class="text-center py-5 text-muted border rounded-3 bg-light">
                        <i class="bi bi-megaphone fs-1 text-secondary d-block mb-2"></i>
                        No announcements published for this course yet.
                    </div>
                <?php else: ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <div class="card announcement-card p-4 mb-3 announcement-item">
                            <div class="d-flex align-items-start gap-3">
                                <div class="announcement-icon-wrapper">
                                    <i class="bi bi-broadcast"></i>
                                </div>
                                <div class="w-100">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                                        <h5 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($row['Title']) ?></h5>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-light text-secondary border px-2.5 py-1.5 fw-normal">
                                                <i class="bi bi-clock me-1"></i>
                                                <?= date("d M Y, h:i A", strtotime($row['CreatedDate'])) ?>
                                            </span>
                                            <!-- EDIT & DELETE BUTTONS -->
                                            <?php if ($row['UserID'] == $user_id): ?>
                                                <button type="button" class="btn btn-sm btn-outline-primary rounded-3" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['AnnouncementID'] ?>" title="Edit">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                                <a href="announcements.php?course=<?= $course_id ?>&delete=<?= $row['AnnouncementID'] ?>" class="btn btn-sm btn-outline-danger rounded-3" title="Delete" onclick="return confirm('Are you sure you want to delete this announcement?');">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <p class="text-secondary mb-3 lh-base">
                                        <?= nl2br(htmlspecialchars($row['Message'])) ?>
                                    </p>

                                    <div class="d-flex align-items-center gap-2 pt-2 border-top">
                                        <i class="bi bi-person-circle text-primary"></i>
                                        <small class="text-muted">
                                            Posted by <strong class="text-dark"><?= htmlspecialchars($row['full_name']) ?></strong>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- EDIT MODAL FOR EACH ANNOUNCEMENT -->
                        <?php if ($row['UserID'] == $user_id): ?>
                            <div class="modal fade" id="editModal<?= $row['AnnouncementID'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border-0 shadow rounded-4">
                                        <form method="POST" action="announcements.php?course=<?= $course_id ?>">
                                            <div class="modal-header border-bottom px-4 py-3">
                                                <h5 class="modal-title fw-bold">Edit Announcement</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body p-4">
                                                <input type="hidden" name="action" value="edit">
                                                <input type="hidden" name="announcement_id" value="<?= $row['AnnouncementID'] ?>">
                                                
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Title</label>
                                                    <input type="text" name="title" class="form-control rounded-3" value="<?= htmlspecialchars($row['Title']) ?>" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Message</label>
                                                    <textarea name="message" rows="4" class="form-control rounded-3" required><?= htmlspecialchars($row['Message']) ?></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-top px-4 py-3">
                                                <button type="button" class="btn btn-outline-secondary rounded-3 px-3" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary rounded-3 px-4">Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

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
    const cards = document.querySelectorAll(".announcement-item");

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