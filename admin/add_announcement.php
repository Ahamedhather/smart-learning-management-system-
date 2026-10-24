<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("../config/db.php");
include("../includes/auth.php");
include("../config/functions.php");

// Fetch active courses for the dropdown selection
$courses = $conn->query("
    SELECT c.CourseID, c.CourseTitle, d.DepartmentName
    FROM Course c
    LEFT JOIN Subject s ON c.SubjectID = s.SubjectID
    LEFT JOIN Department d ON s.DepartmentID = d.DepartmentID
    ORDER BY c.CourseTitle ASC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Announcement | LMS</title>
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

.form-control, .form-select {
    border-radius: 10px;
    padding: 12px 15px;
    border: 1px solid #cbd5e1;
}

.form-control:focus, .form-select:focus {
    border-color: #8b5cf6;
    box-shadow: 0 0 0 0.25rem rgba(139, 92, 246, 0.15);
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
                    <h1 class="fw-bold mb-1">Add Announcement</h1>
                    <p class="text-muted mb-0">Create and broadcast a new notice to students and faculty.</p>
                </div>
            </div>
            <div class="d-flex gap-2">
                <!-- <a href="announcements.php" class="btn btn-outline-secondary rounded-3 px-3">
                    <i class="bi bi-arrow-left me-1"></i> Back to Announcements
                </a> -->
            </div>
        </div>

        <!-- FORM CONTAINER -->
        <div class="section-box">

            <form action="save_announcement.php" method="POST" onsubmit="return validateForm()">

                <!-- TITLE FIELD -->
                <div class="mb-4">
                    <label class="form-label fw-semibold text-dark">
                        <i class="bi bi-megaphone me-1 text-primary"></i> Announcement Title
                    </label>
                    <input type="text" name="title" id="title" class="form-control" placeholder="Enter concise announcement title..." required>
                    <div class="form-text">Make the title brief and informative.</div>
                </div>

                <!-- TARGET COURSE SELECTION -->
                <div class="mb-4">
                    <label class="form-label fw-semibold text-dark">
                        <i class="bi bi-journal-bookmark me-1 text-primary"></i> Target Course Scope
                    </label>
                    <select name="course" id="course" class="form-select">
                        <option value="">-- General / Global Announcement (All Students) --</option>
                        <?php if($courses && $courses->num_rows > 0) { ?>
                            <?php while($c = $courses->fetch_assoc()) { ?>
                                <option value="<?= $c['CourseID'] ?>">
                                    <?= htmlspecialchars($c['CourseTitle']) ?> (<?= htmlspecialchars($c['DepartmentName'] ?? 'General') ?>)
                                </option>
                            <?php } ?>
                        <?php } ?>
                    </select>
                    <div class="form-text">Select a specific course or leave as Global to notify everyone.</div>
                </div>

                <!-- MESSAGE FIELD -->
                <div class="mb-4">
                    <label class="form-label fw-semibold text-dark">
                        <i class="bi bi-card-text me-1 text-primary"></i> Announcement Details
                    </label>
                    <textarea name="message" id="message" class="form-control" rows="6" placeholder="Write full announcement content here..." required></textarea>
                </div>

                <!-- SUBMIT / CANCEL ACTIONS -->
                <div class="d-flex gap-2 pt-2">
                    <button type="submit" class="btn btn-primary rounded-3 px-4 py-2 fw-medium">
                        <i class="bi bi-send me-1"></i> Publish Announcement
                    </button>
                    <a href="announcements.php" class="btn btn-light border rounded-3 px-4 py-2">
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
/* CLIENT-SIDE FORM VALIDATION */
function validateForm() {
    let title = document.getElementById("title").value.trim();
    let message = document.getElementById("message").value.trim();

    if (title.length < 3) {
        alert("Title must be at least 3 characters long.");
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