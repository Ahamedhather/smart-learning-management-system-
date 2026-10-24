<?php
session_start();
include("../config/db.php");

$sender = $_SESSION['user_id'];
$receiver = $_POST['receiver'];
$msg = $_POST['message'];

$stmt = $conn->prepare("
INSERT INTO Message (SenderID, ReceiverID, MessageText)
VALUES (?, ?, ?)
");

$stmt->bind_param("iis", $sender, $receiver, $msg);
$stmt->execute();

echo "Message Sent!";
?>