<?php
include("../config/db.php");
include("../config/functions.php");

$id = $_GET['id'];
$status = $_GET['status'];

$stmt = $conn->prepare("UPDATE users SET status=? WHERE user_id=?");
$stmt->bind_param("si", $status, $id);
$stmt->execute();

header("Location: manage_users.php");
?>