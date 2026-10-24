<?php
session_start();
include("../config/db.php");

$student_id = $_SESSION['student_id'];

// attendance percentage
$att = $conn->query("
SELECT 
COUNT(*) as total,
SUM(CASE WHEN Status='Present' THEN 1 ELSE 0 END) as present
FROM Attendance WHERE StudentID=$student_id
")->fetch_assoc();

$attendance_percent = ($att['total'] > 0) ? 
($att['present'] / $att['total']) * 100 : 0;

// avg assignment marks
$assign = $conn->query("
SELECT AVG(Marks) as avg_marks FROM AssignmentSubmission
WHERE StudentID=$student_id
")->fetch_assoc();

// avg quiz score
$quiz = $conn->query("
SELECT AVG(Score) as avg_score FROM QuizSubmission
WHERE StudentID=$student_id
")->fetch_assoc();
?>

<h2>Performance Report</h2>

<p><strong>Attendance:</strong> <?= round($attendance_percent) ?>%</p>

<p><strong>Average Assignment Marks:</strong> <?= round($assign['avg_marks']) ?></p>

<p><strong>Average Quiz Score:</strong> <?= round($quiz['avg_score']) ?></p>