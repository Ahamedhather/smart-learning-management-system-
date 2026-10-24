<?php
session_start();
include("../config/db.php");

// Determine user ID and ensure role is teacher (supporting various session array patterns)
$my_id = $_SESSION['user_id'] ?? $_SESSION['student_id'] ?? $_SESSION['user']['user_id'] ?? null;
$user_role = $_SESSION['role'] ?? 'teacher';

if (!$my_id || strtolower($user_role) !== 'teacher') {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid Submission ID");
}

$id = intval($_GET['id']);

$stmt = $conn->prepare("
SELECT sub.*, a.Title, u.full_name
FROM AssignmentSubmission sub
JOIN Assignment a ON sub.AssignmentID = a.AssignmentID
JOIN Student s ON sub.StudentID = s.StudentID
JOIN users u ON s.UserID = u.user_id
WHERE sub.SubmissionID=?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    die("Submission not found");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Grade Submission | Smart LMS</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<style>
body {
    background: #f7f9ff;
    font-family: 'Segoe UI', sans-serif;
}
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
.form-card {
    max-width: 650px;
    margin: 20px auto;
    background: #fff;
    padding: 30px;
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    animation: fadeUp 0.5s ease-in-out;
}
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(18px); }
    to { opacity: 1; transform: translateY(0); }
}
.form-control {
    border-radius: 10px;
}
</style>
</head>

<body>

<!-- Include Teacher Sidebar Component -->
<?php 
if (strtolower($user_role) === 'teacher') {
    include("sidebar_teacher.php"); 
} else {
    include("sidebar_student.php"); 
}
?>

<!-- MAIN WRAPPER CONTAINER -->
<div class="main-content" id="content">
    <div class="container-fluid py-4">
        <div class="form-card">

            <h3 class="text-center mb-4 text-primary"><i class="bi bi-award-fill me-2"></i>Grade Submission</h3>

            <p class="mb-2"><strong>Student:</strong> <?= htmlspecialchars($data['full_name']) ?></p>
            <p class="mb-2"><strong>Assignment:</strong> <?= htmlspecialchars($data['Title']) ?></p>
            <?php if (!empty($data['SubmissionFile'])): ?>
                <p class="mb-4"><strong>Submitted File:</strong> <a href="../uploads/<?= htmlspecialchars($data['SubmissionFile']) ?>" target="_blank" class="btn btn-sm btn-outline-primary ms-2"><i class="bi bi-download me-1"></i> Download File</a></p>
            <?php endif; ?>

            <form action="save_grade.php" method="POST" onsubmit="return validateGrade()">

                <input type="hidden" name="id" value="<?= $id ?>">

                <label class="form-label fw-semibold text-secondary">Marks</label>
                <input type="number" name="marks" id="marks"
                       value="<?= htmlspecialchars($data['Marks'] ?? '') ?>"
                       class="form-control mb-3"
                       min="0" max="100" required>

                <label class="form-label fw-semibold text-secondary">Feedback</label>
                <textarea name="feedback" id="feedback" class="form-control mb-4" rows="4"
                placeholder="Enter feedback"><?= htmlspecialchars($data['Feedback'] ?? '') ?></textarea>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success w-50 py-2"><i class="bi bi-check-circle-fill me-1"></i> Save Grade</button>
                    <a href="javascript:history.back()" class="btn btn-secondary w-50 py-2">Cancel</a>
                </div>

            </form>

        </div>
    </div>
</div>

<script>
function validateGrade() {
    let marks = document.getElementById("marks").value;

    if (marks === "" || marks < 0 || marks > 100) {
        alert("Marks must be between 0 and 100.");
        return false;
    }

    return true;
}
</script>

</body>
</html>