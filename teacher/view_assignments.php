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
$stmt_course = $conn->prepare("SELECT CourseTitle, CourseDescription FROM Course WHERE CourseID = ?");
$stmt_course->bind_param("i", $course_id);
$stmt_course->execute();
$courseData = $stmt_course->get_result()->fetch_assoc();

if (!$courseData) {
    header("Location: my_courses.php");
    exit();
}

// FETCH ASSIGNMENTS FOR THIS COURSE
// Note: If your student table uses 'RegistrationNumber' instead of 'RegNo', update it below:
$stmt_assignments = $conn->prepare("
    SELECT 
        a.AssignmentID, 
        a.Title, 
        a.Description, 
        a.Deadline,
        s.RegistrationNumber AS RegNo
    FROM Assignment a
    LEFT JOIN AssignmentSubmission sub ON a.AssignmentID = sub.AssignmentID
    LEFT JOIN Student s ON sub.StudentID = s.StudentID
    WHERE a.CourseID = ?
    ORDER BY a.Deadline ASC
");
$stmt_assignments->bind_param("i", $course_id);
$stmt_assignments->execute();
$result = $stmt_assignments->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>View Assignments | LMS</title>
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

.badge-status {
    padding: 6px 10px;
    border-radius: 8px;
    font-size: 0.78rem;
    font-weight: 600;
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
                    <h1 class="fw-bold mb-1">Course Assignments</h1>
                    <p class="text-muted mb-0">
                        Course: <span class="fw-semibold text-primary"><?= htmlspecialchars($courseData['CourseTitle']) ?></span>
                    </p>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="add_assignment.php?course=<?= $course_id ?>" class="btn btn-warning text-dark fw-semibold rounded-3 px-3">
                    <i class="bi bi-plus-lg me-1"></i> Add Assignment
                </a>
            </div>
        </div>

        <div class="section-box">

            <!-- SEARCH TOOLBAR -->
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <div class="search-box w-100">
                    <i class="bi bi-search"></i>
                    <input type="text" id="searchInput" class="form-control" placeholder="Search assignments by title, description, Registration Number, or date...">
                </div>
            </div>

            <!-- ASSIGNMENTS TABLE -->
            <div class="table-responsive table-custom">
                <table class="table table-hover align-middle mb-0" id="assignmentTable">
                    <thead>
                        <tr>
                            <th>Assignment Title</th>
                            <th>Description</th>
                            <th>Student Registration No</th>
                            <th>Deadline</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows === 0): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-journal-x fs-1 text-secondary d-block mb-2"></i>
                                    No assignments created for this course yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <?php 
                                    $deadlineTimestamp = strtotime($row['Deadline']);
                                    $isExpired = $deadlineTimestamp < time();
                                ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark">
                                            <i class="bi bi-journal-text text-primary me-1"></i>
                                            <?= htmlspecialchars($row['Title']) ?>
                                        </div>
                                    </td>

                                    <td style="max-width: 250px;">
                                        <span class="text-muted small d-block text-truncate">
                                            <?= htmlspecialchars($row['Description'] ?? 'No description provided.') ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?php if (!empty($row['RegNo'])): ?>
                                            <span class="badge bg-light text-dark border px-2 py-1">
                                                <i class="bi bi-person-badge me-1 text-secondary"></i>
                                                <?= htmlspecialchars($row['RegNo']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small italic">No submissions yet</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-nowrap small">
                                        <i class="bi bi-calendar-event me-1 text-muted"></i>
                                        <?= date("d M Y, h:i A", $deadlineTimestamp) ?>
                                    </td>

                                    <td>
                                        <?php if ($isExpired): ?>
                                            <span class="badge-status bg-danger-subtle text-danger border border-danger-subtle">
                                                <i class="bi bi-clock-history"></i> Passed
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-status bg-success-subtle text-success border border-success-subtle">
                                                <i class="bi bi-clock"></i> Active
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-end">
                                        <a href="view_submissions.php?id=<?= $row['AssignmentID'] ?>" class="btn btn-sm btn-primary rounded-2 px-3">
                                            <i class="bi bi-card-checklist me-1"></i> Submissions
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
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
    const rows = document.querySelectorAll("#assignmentTable tbody tr");

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