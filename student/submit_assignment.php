<?php
session_start();
include("../config/db.php");

// Determine user ID based on who is logged in (checking both student_id, user_id, or nested session patterns)
$student_id = $_SESSION['student_id'] ?? $_SESSION['user_id'] ?? $_SESSION['user']['user_id'] ?? null;

if(!$student_id){
    header("Location: ../login.php");
    exit();
}

// Fetch user role to dynamically include the correct sidebar (teacher or student)
$user_role = $_SESSION['role'] ?? 'student';

if(!isset($_GET['id']) || !is_numeric($_GET['id'])){
    die("Invalid Assignment ID");
}

$assignment_id = intval($_GET['id']);

// get assignment + check enrolled course (supporting both student role checks or flexible enrollment)
$stmt = $conn->prepare("
SELECT a.*, c.CourseTitle
FROM Assignment a
JOIN Course c ON a.CourseID = c.CourseID
JOIN Enrollment e ON e.CourseID = c.CourseID
WHERE a.AssignmentID=? AND e.StudentID=?
");
$stmt->bind_param("ii", $assignment_id, $student_id);
$stmt->execute();
$assignment = $stmt->get_result()->fetch_assoc();

if(!$assignment){
    die("Access Denied or Assignment not found");
}

if(strtotime($assignment['Deadline']) < time()){
    die("Deadline Passed");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Submit Assignment | Smart LMS</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<style>
body { background: #f7f9ff; font-family: 'Segoe UI', sans-serif; }
/* Main Layout Offset for Sidebar */
.main-content {
    margin-left: 270px;
    padding: 20px;
    transition: all 0.3s ease;
}
.main-content.expanded {
    margin-left: 85px;
}
@media(max-width: 900px) {
    .main-content {
        margin-left: 0 !important;
    }
}
</style>
</head>
<body>

<!-- Include Sidebar Component dynamically based on role -->
  <?php include("sidebar_student.php"); ?>


<!-- MAIN WRAPPER CONTAINER -->
<div class="main-content" id="content">
    <div class="container-fluid py-4" style="max-width: 700px;">
        <div class="card shadow-sm border-0 p-4 rounded-4 bg-white">
            <h3 class="mb-3 text-primary"><i class="bi bi-upload me-2"></i>Submit Assignment</h3>
            <p class="mb-1"><b>Course:</b> <?= htmlspecialchars($assignment['CourseTitle']) ?></p>
            <p class="mb-1"><b>Assignment:</b> <?= htmlspecialchars($assignment['Title']) ?></p>
            <p class="mb-4 text-muted"><b>Deadline:</b> <?= date("d M Y, h:i A", strtotime($assignment['Deadline'])) ?></p>

            <form action="submit_assignment_process.php" method="POST" enctype="multipart/form-data">

                <input type="hidden" name="assignment_id" value="<?= $assignment_id ?>">

                <div class="mb-3">
                    <label class="form-label text-secondary fw-semibold small">Upload File</label>
                    <input type="file" name="file" class="form-control" required>
                </div>

                <div class="d-flex gap-2">
                    <button class="btn btn-primary px-4"><i class="bi bi-send-fill me-1"></i> Submit</button>
                    <a href="assignments.php?course=<?= $assignment['CourseID'] ?>" class="btn btn-outline-secondary px-4">Cancel</a>
                </div>

            </form>
        </div>
    </div>
</div>

</body>
</html>