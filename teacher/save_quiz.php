<?php
include("../config/db.php");

$title = $_POST['title'];
$time = intval($_POST['time']);
$course = intval($_POST['course']);

$stmt = $conn->prepare("
INSERT INTO Quiz (QuizTitle, TimeLimit, CourseID)
VALUES (?, ?, ?)
");

$stmt->bind_param("sii", $title, $time, $course);
$stmt->execute();

header("Location: quiz_list.php?course=" . $course);
exit();
?>