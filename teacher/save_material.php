<?php
session_start();
include("../config/db.php");

// Check request
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Invalid request method.");
}

if (!isset($_POST['title'], $_POST['course'], $_FILES['file'])) {
    die("Invalid request. Missing data.");
}

$title  = trim($_POST['title']);
$course = intval($_POST['course']);
$file   = $_FILES['file'];

if ($title == "" || $course <= 0) {
    die("Title or Course ID is invalid.");
}

// Check upload error
if ($file['error'] !== UPLOAD_ERR_OK) {
    die("File upload error. Error code: " . $file['error']);
}

// Upload folder
$uploadDir = "../uploads/";

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Original file name clean
$originalName = basename($file['name']);
$originalName = preg_replace("/[^a-zA-Z0-9._-]/", "_", $originalName);

// File extension
$ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

// Allowed file types
$allowed = ['pdf', 'doc', 'docx', 'ppt', 'pptx'];

if (!in_array($ext, $allowed)) {
    die("Invalid file type! Only PDF, DOC, DOCX, PPT, PPTX allowed.");
}

// File size limit: 20MB
$maxSize = 20 * 1024 * 1024;

if ($file['size'] > $maxSize) {
    die("File too large. Maximum allowed size is 20MB.");
}

// New unique file name
$fileName = time() . "_" . uniqid() . "_" . $originalName;
$targetPath = $uploadDir . $fileName;

// Move file
if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    die("File upload failed. Please check uploads folder permission.");
}

// Save only file name in DB
$stmt = $conn->prepare("
    INSERT INTO StudyMaterial (Title, FilePath, FileType, CourseID)
    VALUES (?, ?, ?, ?)
");

$stmt->bind_param("sssi", $title, $fileName, $ext, $course);

if ($stmt->execute()) {
    header("Location: view_materials.php?course=" . $course);
    exit();
} else {
    // Remove uploaded file if DB insert fails
    if (file_exists($targetPath)) {
        unlink($targetPath);
    }

    die("Database insert failed: " . $stmt->error);
}
?>