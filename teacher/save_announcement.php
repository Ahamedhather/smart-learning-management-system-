<?php
session_start();
include("../config/db.php");

// ✅ validation
if(!isset($_POST['title'], $_POST['message'], $_POST['course'])){
    echo "Invalid Data!";
    exit();
}

$title = $_POST['title'];
$message = $_POST['message'];
$course = intval($_POST['course']);
$user = $_SESSION['user_id'];

// insert
$stmt = $conn->prepare("
INSERT INTO Announcement (Title, Message, CreatedDate, CourseID, UserID)
VALUES (?, ?, NOW(), ?, ?)
");

$stmt->bind_param("ssii", $title, $message, $course, $user);
$stmt->execute();

// redirect
header("Location: view_announcements.php?course=$course");
exit();
?>