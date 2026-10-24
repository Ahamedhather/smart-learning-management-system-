<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("../config/db.php");
include("../config/functions.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Capture and clean all standard form inputs
    $id        = trim($_POST['id']);
    $full_name = trim($_POST['full_name']);
    $email     = trim($_POST['email']);
    $phone     = trim($_POST['phone']);
    $role      = trim($_POST['role']);
    $status    = trim($_POST['status']);
    
    // Capture new advanced row inputs
    $username  = trim($_POST['username']);
    $password  = $_POST['password']; // Do not trim passwords intentionally

    // Core validation checks
    if (empty($id) || empty($full_name) || empty($email) || empty($username) || empty($role) || empty($status)) {
        $_SESSION['error'] = "All mandatory fields must be filled out.";
        header("Location: edit_user.php?id=" . urlencode($id));
        exit();
    }

    if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $_SESSION['error'] = "Username must be 3-20 characters long and contain only numbers, letters, or underscores.";
        header("Location: edit_user.php?id=" . urlencode($id));
        exit();
    }

    // Determine query configuration depending on whether the user is changing their password
    if (!empty($password)) {
        if (strlen($password) < 6) {
            $_SESSION['error'] = "New passwords must be at least 6 characters long.";
            header("Location: edit_user.php?id=" . urlencode($id));
            exit();
        }
        
        // Hash the new password replacement string
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        
        // Prepare Statement including password updates
        $stmt = $conn->prepare("UPDATE users SET full_name=?, username=?, email=?, phone=?, password=?, role=?, status=? WHERE user_id=?");
        $stmt->bind_param("sssssssi", $full_name, $username, $email, $phone, $hashed_password, $role, $status, $id);
    } else {
        // Prepare Statement excluding password updates
        $stmt = $conn->prepare("UPDATE users SET full_name=?, username=?, email=?, phone=?, role=?, status=? WHERE user_id=?");
        $stmt->bind_param("ssssssi", $full_name, $username, $email, $phone, $role, $status, $id);
    }

    try {
        $stmt->execute();
        
        // Success redirect
        header("Location: manage_users.php?msg=user_updated");
        exit();
        
    } catch (mysqli_sql_exception $e) {
        // Intercept Unique Database Constraints Conflicts (Code 1062)
        if ($e->getCode() == 1062) {
            $errText = $e->getMessage();
            if (strpos($errText, 'username') !== false) {
                $_SESSION['error'] = "The username '@" . htmlspecialchars($username) . "' is already taken by another profile account.";
            } elseif (strpos($errText, 'email') !== false) {
                $_SESSION['error'] = "The email address '" . htmlspecialchars($email) . "' is already registered to another user.";
            } else {
                $_SESSION['error'] = "Duplicate account credential conflict detected.";
            }
        } else {
            $_SESSION['error'] = "System processing fault error occurred: " . $e->getMessage();
        }
        
        header("Location: edit_user.php?id=" . urlencode($id));
        exit();
    } finally {
        $stmt->close();
        $conn->close();
    }
} else {
    header("Location: manage_users.php");
    exit();
}
?>