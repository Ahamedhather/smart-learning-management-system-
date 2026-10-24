<?php
include("../config/db.php");
include("../config/functions.php");

$id = intval($_POST['id']);
$name = $_POST['name'];
$code = $_POST['code'];
$dept = intval($_POST['dept']);

$stmt = $conn->prepare("UPDATE Subject SET SubjectName=?, SubjectCode=?, DepartmentID=? WHERE SubjectID=?");
$stmt->bind_param("ssii", $name, $code, $dept, $id);
$stmt->execute();

header("Location: manage_subjects.php");
exit();
?>