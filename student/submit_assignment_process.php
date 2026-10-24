<?php
session_start();
include("../config/db.php");

// Determine user ID dynamically based on session variables
$student_id = $_SESSION['student_id'] ?? $_SESSION['user_id'] ?? $_SESSION['user']['user_id'] ?? null;

if(!$student_id){
    header("Location: ../login.php");
    exit();
}

if(!isset($_POST['assignment_id']) || !is_numeric($_POST['assignment_id'])){
    die("Invalid Assignment ID");
}

$assignment_id = intval($_POST['assignment_id']);

// Check if a file was uploaded without errors
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    die("File upload failed or no file selected.");
}

$file = $_FILES['file'];
$upload_dir = "../uploads/";

// Ensure the uploads directory exists
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Secure filename generation
$file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$safe_filename = preg_replace("/[^a-zA-Z0-9_-]/", "_", pathinfo($file['name'], PATHINFO_FILENAME));
$name = time() . "_" . $safe_filename . "." . $file_extension;

if (move_uploaded_file($file['tmp_name'], $upload_dir . $name)) {
    // Insert submission record into database
    $stmt = $conn->prepare("
        INSERT INTO AssignmentSubmission (AssignmentID, StudentID, SubmissionFile)
        VALUES (?, ?, ?)
    ");

    $stmt->bind_param("iis", $assignment_id, $student_id, $name);
    
    if ($stmt->execute()) {
        // Redirect back or display a clean success message (adjust path as needed)
        header("Location: assignments.php?success=submitted");
        exit();
    } else {
        die("Database error: Could not save submission.");
    }
} else {
    die("Failed to move uploaded file.");
}
?>