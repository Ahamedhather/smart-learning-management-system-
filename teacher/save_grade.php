<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../login.php");
    exit();
}

if(
    !isset($_POST['id']) ||
    !isset($_POST['marks']) ||
    !is_numeric($_POST['id']) ||
    !is_numeric($_POST['marks'])
){
    die("Invalid Data");
}

$id = intval($_POST['id']);
$marks = intval($_POST['marks']);
$feedback = trim($_POST['feedback']);

// validate marks
if($marks < 0 || $marks > 100){
    die("Marks must be between 0 and 100");
}

// get assignment id
$stmt = $conn->prepare("
SELECT AssignmentID 
FROM AssignmentSubmission 
WHERE SubmissionID=?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$submission = $stmt->get_result()->fetch_assoc();

if(!$submission){
    die("Submission not found");
}

$assignment_id = $submission['AssignmentID'];

// save grade
$stmt = $conn->prepare("
UPDATE AssignmentSubmission 
SET Marks=?, Feedback=? 
WHERE SubmissionID=?
");

$stmt->bind_param("isi", $marks, $feedback, $id);
$stmt->execute();

// redirect back to submissions page
header("Location: view_submissions.php?id=".$assignment_id);
exit();
?>