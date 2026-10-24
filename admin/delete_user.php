<?php
session_start();

include("../config/db.php");
include("../includes/auth.php");

// Admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    die("Access Denied");
}

// Validate ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid User ID");
}

$user_id = intval($_GET['id']);

// Don't allow deleting yourself
if ($user_id == $_SESSION['user_id']) {
    die("You cannot delete your own account.");
}

// Check user exists
$stmt = $conn->prepare("
SELECT user_id
FROM users
WHERE user_id=?
");

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("User not found.");
}

// Delete user
$stmt = $conn->prepare("
DELETE FROM users
WHERE user_id=?
");

$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {

    header("Location: manage_user.php?msg=deleted");
    exit();

} else {

    echo "Unable to delete user.";

}
?>