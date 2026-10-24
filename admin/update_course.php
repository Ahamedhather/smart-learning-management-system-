<?php
include("../config/db.php");
include("../config/functions.php");

$id = intval($_POST['id']);
$title = $_POST['title'];
$desc = $_POST['desc'];
$subject = intval($_POST['subject']);
$teacher = intval($_POST['teacher']);
$semester = $_POST['semester'];

$stmt = $conn->prepare("
UPDATE Course 
SET CourseTitle=?, CourseDescription=?, SubjectID=?, TeacherID=?, Semester=? 
WHERE CourseID=?
");

$stmt->bind_param("ssiisi", $title, $desc, $subject, $teacher, $semester, $id);
$stmt->execute();

header("Location: manage_courses.php");
exit();
?>