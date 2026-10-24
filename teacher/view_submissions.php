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

$assignment_id = intval($_GET['id'] ?? 0);

if ($assignment_id <= 0) {
    $_SESSION['error'] = "Invalid or missing Assignment ID.";
    header("Location: my_courses.php");
    exit();
}

// FETCH ASSIGNMENT & COURSE DETAILS
$stmt = $conn->prepare("
    SELECT a.Title, a.CourseID, c.CourseTitle
    FROM Assignment a
    JOIN Course c ON a.CourseID = c.CourseID
    WHERE a.AssignmentID = ?
");
$stmt->bind_param("i", $assignment_id);
$stmt->execute();
$assignmentData = $stmt->get_result()->fetch_assoc();

if (!$assignmentData) {
    $_SESSION['error'] = "Assignment not found.";
    header("Location: my_courses.php");
    exit();
}

// FETCH SUBMISSIONS
$stmt = $conn->prepare("
    SELECT 
        sub.SubmissionID,
        sub.SubmissionFile,
        sub.SubmissionDate,
        sub.Marks,
        sub.Feedback,
        u.full_name
    FROM AssignmentSubmission sub
    JOIN Student s ON sub.StudentID = s.StudentID
    JOIN users u ON s.UserID = u.user_id
    WHERE sub.AssignmentID = ?
    ORDER BY sub.SubmissionDate DESC
");
$stmt->bind_param("i", $assignment_id);
$stmt->execute();
$result = $stmt->get_result();

// CALCULATE METRICS
$total_submissions = $result->num_rows;
$graded_count = 0;

$submissions_list = [];
while ($row = $result->fetch_assoc()) {
    if ($row['Marks'] !== null) {
        $graded_count++;
    }
    $submissions_list[] = $row;
}
$pending_count = $total_submissions - $graded_count;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Assignment Submissions | LMS</title>
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

.metric-card {
    border-radius: 14px;
    padding: 18px 22px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
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

.table > :not(caption) > * > * {
    padding: 14px 16px;
}

.status-graded {
    background-color: #d1fae5;
    color: #065f46;
}

.status-pending {
    background-color: #fef3c7;
    color: #92400e;
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
                    <h1 class="fw-bold mb-1">Assignment Submissions</h1>
                    <p class="text-muted mb-0">
                        Assignment: <strong class="text-dark"><?= htmlspecialchars($assignmentData['Title']) ?></strong> 
                        (<span class="text-primary"><?= htmlspecialchars($assignmentData['CourseTitle']) ?></span>)
                    </p>
                </div>
            </div>
            <div>
                <a href="view_assignments.php?course=<?= $assignmentData['CourseID'] ?>" class="btn btn-outline-secondary rounded-3 px-3">
                    <i class="bi bi-arrow-left me-1"></i> Back to Assignments
                </a>
            </div>
        </div>

        <!-- METRICS ROW -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="metric-card d-flex align-items-center gap-3">
                    <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-3 fs-4">
                        <i class="bi bi-file-earmark-check"></i>
                    </div>
                    <div>
                        <small class="text-muted fw-semibold d-block">Total Submissions</small>
                        <h4 class="fw-bold mb-0 text-dark"><?= $total_submissions ?></h4>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="metric-card d-flex align-items-center gap-3">
                    <div class="p-3 bg-success bg-opacity-10 text-success rounded-3 fs-4">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div>
                        <small class="text-muted fw-semibold d-block">Graded</small>
                        <h4 class="fw-bold mb-0 text-dark"><?= $graded_count ?></h4>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="metric-card d-flex align-items-center gap-3">
                    <div class="p-3 bg-warning bg-opacity-10 text-warning rounded-3 fs-4">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div>
                        <small class="text-muted fw-semibold d-block">Pending Evaluation</small>
                        <h4 class="fw-bold mb-0 text-dark"><?= $pending_count ?></h4>
                    </div>
                </div>
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
                    <input type="text" id="searchInput" class="form-control" placeholder="Search by student name, date, or feedback...">
                </div>
            </div>

            <!-- SUBMISSIONS TABLE -->
            <div class="table-responsive">
                <table class="table table-hover align-middle border mb-0" id="submissionTable">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Student Name</th>
                            <th scope="col">Submitted File</th>
                            <th scope="col">Submission Date</th>
                            <th scope="col">Grade / Marks</th>
                            <th scope="col">Feedback</th>
                            <th scope="col" class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($submissions_list)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block text-secondary mb-2"></i>
                                    No submissions uploaded for this assignment yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($submissions_list as $row): ?>
                                <tr>
                                    <!-- STUDENT NAME -->
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-person-circle text-primary fs-5"></i>
                                            <span class="fw-semibold text-dark"><?= htmlspecialchars($row['full_name']) ?></span>
                                        </div>
                                    </td>

                                    <!-- FILE ATTACHMENT -->
                                    <td>
                                        <?php
                                        $filePath = "../uploads/" . $row['SubmissionFile'];
                                        if (!empty($row['SubmissionFile']) && file_exists($filePath)):
                                        ?>
                                            <a href="../uploads/<?= rawurlencode($row['SubmissionFile']) ?>" target="_blank" class="btn btn-outline-primary btn-sm rounded-2">
                                                <i class="bi bi-download me-1"></i> Download File
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger rounded-2 px-2.5 py-1.5 fw-normal">
                                                <i class="bi bi-exclamation-triangle me-1"></i> File Missing
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- SUBMISSION DATE -->
                                    <td>
                                        <small class="text-secondary fw-medium">
                                            <i class="bi bi-clock me-1"></i>
                                            <?= date("d M Y, h:i A", strtotime($row['SubmissionDate'])) ?>
                                        </small>
                                    </td>

                                    <!-- MARKS / STATUS -->
                                    <td>
                                        <?php if ($row['Marks'] !== null): ?>
                                            <span class="badge status-graded rounded-2 px-2.5 py-1.5 fw-semibold">
                                                <i class="bi bi-check2-circle me-1"></i> <?= htmlspecialchars($row['Marks']) ?> Pts
                                            </span>
                                        <?php else: ?>
                                            <span class="badge status-pending rounded-2 px-2.5 py-1.5 fw-normal">
                                                <i class="bi bi-clock-history me-1"></i> Not Graded
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- FEEDBACK -->
                                    <td>
                                        <small class="text-secondary">
                                            <?= htmlspecialchars($row['Feedback'] ?? 'No feedback provided.') ?>
                                        </small>
                                    </td>

                                    <!-- ACTIONS -->
                                    <td class="text-end">
                                        <a href="grade_submission.php?id=<?= $row['SubmissionID'] ?>" class="btn btn-success btn-sm rounded-2 px-3 fw-semibold">
                                            <i class="bi bi-pencil-square me-1"></i> Grade
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
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
    const rows = document.querySelectorAll("#submissionTable tbody tr");

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