<?php
session_start();
include("../config/db.php");
include_once("../includes/log_action.php");
include_once("../config/functions.php");

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Check if AnnouncementID is provided
if (isset($_GET['id'])) {
    $announcement_id = intval($_GET['id']);
    $user_id = $_SESSION['user_id'];

    // Optional: Fetch the title for logging purposes before deleting
    $stmt = $conn->prepare("SELECT Title FROM Announcement WHERE AnnouncementID = ?");
    $stmt->bind_param("i", $announcement_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $title = $row['Title'];

        // Delete the announcement
        $delete_stmt = $conn->prepare("DELETE FROM Announcement WHERE AnnouncementID = ?");
        $delete_stmt->bind_param("i", $announcement_id);
        
        if ($delete_stmt->execute()) {
            // Log the action
            logAction($conn, $user_id, "Deleted announcement: $title");
        }
    }
}

// Redirect back to the announcements list
header("Location: announcements.php");
exit();
?>