<?php
include("../config/db.php");
include("../config/functions.php");

$name = $_POST['name'];
$code = $_POST['code'];
$dept = intval($_POST['dept']);

// Check duplicate code
$check = $conn->prepare("SELECT SubjectID FROM Subject WHERE SubjectCode=?");
$check->bind_param("s", $code);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    die("Subject code already exists!");
}

$stmt = $conn->prepare("INSERT INTO Subject (SubjectName, SubjectCode, DepartmentID) VALUES (?, ?, ?)");
$stmt->bind_param("ssi", $name, $code, $dept);
$stmt->execute();

header("Location: manage_subjects.php");
exit();
?>
