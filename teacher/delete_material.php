<?php
include("../config/db.php");

$id = intval($_GET['id']);

// Get file
$res = $conn->query("SELECT FilePath FROM StudyMaterial WHERE MaterialID=$id");
$row = $res->fetch_assoc();

$file = "../uploads/" . $row['FilePath'];

// Delete file
if(file_exists($file)){
    unlink($file);
}

// Delete record
$stmt = $conn->prepare("DELETE FROM StudyMaterial WHERE MaterialID=?");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: my_courses.php");
exit();
?>