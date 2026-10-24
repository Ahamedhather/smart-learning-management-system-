<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("../config/db.php");
include("../config/functions.php");

// Fetch Subjects and Teachers Dropdown Data
$subjects = $conn->query("SELECT SubjectID, SubjectName, SubjectCode FROM Subject ORDER BY SubjectName ASC");

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
<title>Add Course | LMS</title>
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

.form-control, .form-select {
    border-radius: 10px;
    padding: 10px 14px;
}

.input-group-text {
    border-radius: 10px 0 0 10px;
}

.form-control:focus, .form-select:focus {
    box-shadow: 0 0 0 0.25rem rgba(109, 53, 245, 0.15);
    border-color: #8b5cf6;
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
                    <h1 class="fw-bold mb-1">Add New Course</h1>
                    <p class="text-muted mb-0">Provision a new academic offering in the LMS registry.</p>
                </div>
            </div>
            <div>
                <a href="manage_courses.php" class="btn btn-outline-secondary rounded-3 px-3">
                    <i class="bi bi-arrow-left me-1"></i> Back to Courses
                </a>
            </div>
        </div>

        <!-- ADD FORM CONTAINER -->
        <div class="section-box">
            <form action="insert_course.php" method="POST" onsubmit="return validateForm()">

                <div class="row g-3">
                    <!-- Course Title -->
                    <div class="col-md-8">
                        <label class="form-label fw-semibold text-dark">Course Title <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-journal-text text-primary"></i></span>
                            <input type="text" name="title" id="title" class="form-control" placeholder="e.g., Introduction to Computer Science" required>
                        </div>
                    </div>

                    <!-- Semester -->
                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-dark">Semester <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-calendar-event text-primary"></i></span>
                            <input type="text" name="semester" id="semester" class="form-control" placeholder="e.g., Semester 1" required>
                        </div>
                    </div>

                    <!-- Subject Dropdown -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Subject Category <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-book text-primary"></i></span>
                            <select name="subject" id="subject" class="form-select" required>
                                <option value="">-- Select Subject --</option>
                                <?php while($s = $subjects->fetch_assoc()) { ?>
                                    <option value="<?= $s['SubjectID'] ?>">
                                        <?= htmlspecialchars($s['SubjectName']) ?> (<?= htmlspecialchars($s['SubjectCode']) ?>)
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>

                    <!-- Teacher Dropdown -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Assigned Teacher <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-person-badge text-primary"></i></span>
                            <select name="teacher" id="teacher" class="form-select" required>
                                <option value="">-- Select Teacher --</option>
                                <?php while($t = $teachers->fetch_assoc()) { ?>
                                    <option value="<?= $t['TeacherID'] ?>">
                                        <?= htmlspecialchars($t['full_name']) ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>

                    <!-- Course Description -->
                    <div class="col-12">
                        <label class="form-label fw-semibold text-dark">Course Description</label>
                        <textarea name="desc" id="desc" class="form-control" rows="4" placeholder="Brief outline of course objectives and syllabus..."></textarea>
                    </div>

                    <!-- Action Buttons -->
                    <div class="col-12 pt-3 d-flex gap-2">
                        <button type="submit" class="btn btn-success rounded-3 px-4 py-2 fw-semibold">
                            <i class="bi bi-plus-circle me-1"></i> Save Course
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
/* FORM VALIDATION SCRIPT */
function validateForm() {
    let title = document.getElementById("title").value.trim();
    let subject = document.getElementById("subject").value;
    let teacher = document.getElementById("teacher").value;
    let semester = document.getElementById("semester").value.trim();

    if (title.length < 3) {
        alert("Course title must be at least 3 characters long.");
        return false;
    }

    if (subject === "") {
        alert("Please select a subject category.");
        return false;
    }

    if (teacher === "") {
        alert("Please select an assigned teacher.");
        return false;
    }

    if (semester.length < 2) {
        alert("Please enter a valid semester designation.");
        return false;
    }

    return true;
}

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