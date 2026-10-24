<?php
include("../config/db.php");
include("../config/functions.php");

$title = $_POST['title'];
$desc = $_POST['desc'];
$subject = intval($_POST['subject']);
$teacher = intval($_POST['teacher']);
$semester = $_POST['semester'];

$stmt = $conn->prepare("
INSERT INTO Course (CourseTitle, CourseDescription, SubjectID, TeacherID, Semester)
VALUES (?, ?, ?, ?, ?)
");

$stmt->bind_param("ssiis", $title, $desc, $subject, $teacher, $semester);
$stmt->execute();

header("Location: manage_courses.php");
exit();
?>