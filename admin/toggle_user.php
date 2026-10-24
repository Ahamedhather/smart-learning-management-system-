<?php
session_start();

include("../config/db.php");
include("../includes/auth.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    die("Access Denied");
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid User ID");
}

if (!isset($_GET['status'])) {
    die("Invalid Status");
}

$id = (int)$_GET['id'];
$status = $_GET['status'];

if ($status != "active" && $status != "inactive") {
    die("Invalid Status");
}

$stmt = $conn->prepare("
UPDATE users
SET status=?
WHERE user_id=?
");

$stmt->bind_param("si", $status, $id);

if($stmt->execute()){
    header("Location: manage_users.php");
    exit();
}else{
    echo "Failed to update user status.";
}
?>