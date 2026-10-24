<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("../config/db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Sanitize incoming fields
    $full_name = trim($_POST['full_name']);
    $username  = trim($_POST['username']);
    $email     = trim($_POST['email']);
    $phone     = trim($_POST['phone']);
    $password  = $_POST['password'];
    $role      = isset($_POST['role']) ? trim($_POST['role']) : '';

    // 2. Validate mandatory empty fields
    if (empty($full_name) || empty($username) || empty($email) || empty($password) || empty($role)) {
        $_SESSION['error'] = "All mandatory fields must be filled out.";
        header("Location: add_user.php");
        exit();
    }

    // 3. Simple format validation checks
    if (strlen($full_name) < 3) {
        $_SESSION['error'] = "Full Name must be at least 3 characters long.";
        header("Location: add_user.php");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Please provide a valid email address layout.";
        header("Location: add_user.php");
        exit();
    }

    if (strlen($password) < 6) {
        $_SESSION['error'] = "Password must contain at least 6 characters.";
        header("Location: add_user.php");
        exit();
    }

    // 4. Secure Password Hashing
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // 5. Direct Secure Insertion Block
    $insert_stmt = $conn->prepare("INSERT INTO users (full_name, username, email, phone, password, role, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
    $insert_stmt->bind_param("ssssss", $full_name, $username, $email, $phone, $hashed_password, $role);

    if ($insert_stmt->execute()) {
        // Success! Redirect back to management board
        header("Location: manage_users.php?msg=user_added");
        exit();
    } else {
        // 6. CATCH THE UNIQUE CONSTRAINT EXCEPTION (Error 1062 = Duplicate Entry)
        if ($conn->errno == 1062) {
            $error_message = $conn->error;
            
            // Check whether the duplicate error points to email or username keys
            if (strpos($error_message, 'email') !== false) {
                $_SESSION['error'] = "The Email address '" . htmlspecialchars($email) . "' is already registered to an account.";
            } elseif (strpos($error_message, 'username') !== false) {
                $_SESSION['error'] = "The Username '@" . htmlspecialchars($username) . "' is already taken.";
            } else {
                $_SESSION['error'] = "Duplicate registration values detected. Account already exists.";
            }
        } else {
            // General fallback system error
            $_SESSION['error'] = "Database processing failure. Please try again later.";
        }
        
        header("Location: add_user.php");
        exit();
    }
    
    $insert_stmt->close();
    $conn->close();
} else {
    header("Location: add_user.php");
    exit();
}