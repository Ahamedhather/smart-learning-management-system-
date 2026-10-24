<?php
include("../config/db.php");
include("../config/functions.php");

$name = $_POST['name'];
$desc = $_POST['desc'];

$stmt = $conn->prepare("INSERT INTO Department (DepartmentName, Description) VALUES (?, ?)");
$stmt->bind_param("ss", $name, $desc);
$stmt->execute();

header("Location: manage_departments.php");
exit();
?>