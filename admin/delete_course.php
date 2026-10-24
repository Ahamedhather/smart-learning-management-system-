<?php
include("../config/db.php");
include("../config/functions.php");

$id = intval($_GET['id']);

$stmt = $conn->prepare("DELETE FROM Course WHERE CourseID=?");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: manage_courses.php");
exit();
?>