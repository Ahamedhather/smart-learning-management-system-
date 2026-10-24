<?php
include("../config/db.php");
include("../config/functions.php");

if (!isset($_GET['id'])) {
    die("Invalid request");
}

$id = intval($_GET['id']);

$stmt = $conn->prepare("DELETE FROM Department WHERE DepartmentID=?");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: manage_departments.php");
exit();
?>