<?php
session_start();
include("../config/db.php");
include_once("../includes/log_action.php");
include_once("../config/functions.php");

$title = $_POST['title'];
$message = $_POST['message'];
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("INSERT INTO Announcement (Title, Message, UserID) VALUES (?, ?, ?)");
$stmt->bind_param("ssi", $title, $message, $user_id);
$stmt->execute();

// Log action
logAction($conn, $user_id, "Created announcement: $title");

header("Location: announcements.php");
exit();
?>