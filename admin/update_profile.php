<?php
session_start();
include("../config/db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Check if user session ID token is active
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../login.php");
        exit();
    }
    
    $user_id   = intval($_SESSION['user_id']);
    $full_name = trim($_POST['full_name']);
    $email     = trim($_POST['email']);
    $phone     = trim($_POST['phone']);
    $username  = trim($_POST['username']);
    $password  = $_POST['password'];

    // Mandatory Field Verification
    if (empty($full_name) || empty($email) || empty($username)) {
        $_SESSION['error'] = "Required form fields cannot be processed blank.";
        header("Location: profile.php");
        exit();
    }

    // Username Format Check
    if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $_SESSION['error'] = "The username structure is invalid. Use alphanumeric characters and underscores only.";
        header("Location: profile.php");
        exit();
    }

    // Process SQL Query statement preparation based on password field submission status
    if (!empty($password)) {
        if (strlen($password) < 6) {
            $_SESSION['error'] = "Your new secure password replacement must be at least 6 characters long.";
            header("Location: profile.php");
            exit();
        }
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, username = ?, email = ?, phone = ?, password = ? WHERE user_id = ?");
        $stmt->bind_param("sssssi", $full_name, $username, $email, $phone, $hashed_password, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, username = ?, email = ?, phone = ? WHERE user_id = ?");
        $stmt->bind_param("ssssi", $full_name, $username, $email, $phone, $user_id);
    }

    try {
        $stmt->execute();
        
        // Dynamic synchronization update back to session arrays if needed
        $_SESSION['username'] = $username; 
        
        header("Location: profile.php?msg=profile_updated");
        exit();
        
    } catch (mysqli_sql_exception $e) {
        // Intercept database constraint fault code 1062
        if ($e->getCode() == 1062) {
            $error_string = $e->getMessage();
            if (strpos($error_string, 'username') !== false) {
                $_SESSION['error'] = "The username '@" . htmlspecialchars($username) . "' is already taken by another account.";
            } elseif (strpos($error_string, 'email') !== false) {
                $_SESSION['error'] = "The email address '" . htmlspecialchars($email) . "' is already bound to another profile.";
            } else {
                $_SESSION['error'] = "Database constraint conflict: Duplicate key registration error.";
            }
        } else {
            $_SESSION['error'] = "System profile storage execution exception: " . $e->getMessage();
        }
        header("Location: profile.php");
        exit();
    } finally {
        $stmt->close();
        $conn->close();
    }
} else {
    header("Location: dashboard.php");
    exit();
}
?>