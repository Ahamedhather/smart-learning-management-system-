<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("../config/db.php");
include("../config/functions.php");

// 1. Validate Course ID Input
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    header("Location: manage_courses.php");
    exit();
}

// 2. Fetch Course Details Safely
$stmt = $conn->prepare("SELECT * FROM Course WHERE CourseID = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();

// If course does not exist, redirect back
if (!$course) {
    header("Location: manage_courses.php");
    exit();
}

// 3. Fetch Subjects and Teachers Dropdown Data
$subjects = $conn->query("SELECT * FROM Subject ORDER BY SubjectName ASC");
$teachers = $conn->query("
    SELECT t.TeacherID, u.full_name 
    FROM Teacher t
    JOIN users u ON t.UserID = u.user_id
    ORDER BY u.full_name ASC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Course | LMS</title>
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
    box-shadow: 0 6px 18px rgba(0,0,0,0.07);
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
    <?php include("sidebar.php"); ?>

    <!-- MAIN CONTENT CONTAINER -->
    <main class="content" id="content">

        <!-- TOPBAR -->
        <div class="topbar">
            <div class="d-flex align-items-center gap-3">
                <button id="mobileMenuToggle" class="toggle-btn">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    <h1 class="fw-bold mb-1">Edit Course Profile</h1>
                    <p class="text-muted mb-0">Modify curriculum settings and details for course #<?= htmlspecialchars($course['CourseID']) ?></p>
                </div>
            </div>
            <div>
                <a href="manage_courses.php" class="btn btn-outline-secondary rounded-3 px-3">
                    <i class="bi bi-arrow-left me-1"></i> Back to Courses
                </a>
            </div>
        </div>

        <!-- EDIT FORM CONTAINER -->
        <div class="section-box">
            <form action="update_course.php" method="POST">
                
                <!-- Hidden Course ID -->
                <input type="hidden" name="id" value="<?= htmlspecialchars($course['CourseID']) ?>">

                <div class="row g-3">
                    <!-- Course Title -->
                    <div class="col-md-8">
                        <label class="form-label fw-semibold text-dark">Course Title <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-journal-text text-primary"></i></span>
                            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($course['CourseTitle']) ?>" placeholder="e.g., Advanced Database Systems" required>
                        </div>
                    </div>

                    <!-- Semester -->
                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-dark">Semester <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-calendar-event text-primary"></i></span>
                            <input type="text" name="semester" class="form-control" value="<?= htmlspecialchars($course['Semester'] ?? '') ?>" placeholder="e.g., Fall 2026" required>
                        </div>
                    </div>

                    <!-- Subject Category Dropdown -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Subject Category <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-book text-primary"></i></span>
                            <select name="subject" class="form-select" required>
                                <option value="">-- Select Subject --</option>
                                <?php while($s = $subjects->fetch_assoc()) { ?>
                                    <option value="<?= $s['SubjectID'] ?>" <?= ($s['SubjectID'] == $course['SubjectID']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s['SubjectName']) ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>

                    <!-- Assigned Teacher Dropdown -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Assigned Teacher</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-person-badge text-primary"></i></span>
                            <select name="teacher" class="form-select">
                                <option value="">-- Unassigned --</option>
                                <?php while($t = $teachers->fetch_assoc()) { ?>
                                    <option value="<?= $t['TeacherID'] ?>" <?= ($t['TeacherID'] == $course['TeacherID']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($t['full_name']) ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>

                    <!-- Course Description -->
                    <div class="col-12">
                        <label class="form-label fw-semibold text-dark">Course Description</label>
                        <textarea name="desc" class="form-control" rows="4" placeholder="Brief summary of syllabus and learning outcomes..."><?= htmlspecialchars($course['CourseDescription'] ?? '') ?></textarea>
                    </div>

                    <!-- Action Buttons -->
                    <div class="col-12 pt-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary rounded-3 px-4 py-2 fw-semibold">
                            <i class="bi bi-check-circle me-1"></i> Update Course
                        </button>
                        <a href="manage_courses.php" class="btn btn-light border rounded-3 px-4 py-2">
                            Cancel
                        </a>
                    </div>
                </div>

            </form>
        </div>

    </main>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
/* Collapsible Sidebar Handler */
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