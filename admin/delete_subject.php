<?php
include("../config/db.php");
include("../config/functions.php");

$id = intval($_GET['id']);

$stmt = $conn->prepare("DELETE FROM Subject WHERE SubjectID=?");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: manage_subjects.php");
exit();
?>