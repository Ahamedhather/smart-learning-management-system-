<?php
include("../config/db.php");

$title = $_POST['title'];
$desc = $_POST['desc'];
$deadline = $_POST['deadline'];
$course = intval($_POST['course']);

$stmt = $conn->prepare("
INSERT INTO Assignment (Title, Description, Deadline, CourseID)
VALUES (?, ?, ?, ?)
");

$stmt->bind_param("sssi", $title, $desc, $deadline, $course);
$stmt->execute();

header("Location: view_submissions.php");
?>