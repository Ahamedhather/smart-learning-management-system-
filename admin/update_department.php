<?php
include("../config/db.php");
include("../config/functions.php");

$id = intval($_POST['id']);
$name = $_POST['name'];
$desc = $_POST['desc'];

$stmt = $conn->prepare("UPDATE Department SET DepartmentName=?, Description=? WHERE DepartmentID=?");
$stmt->bind_param("ssi", $name, $desc, $id);
$stmt->execute();

header("Location: manage_departments.php");
exit();
?>