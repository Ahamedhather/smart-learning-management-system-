<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("../config/db.php");
include("../includes/auth.php");
include("../config/functions.php");

// 🔒 SECURITY CHECK
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ==========================================
// HANDLE FORM SUBMISSIONS
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. UPDATE PERSONAL INFORMATION
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $full_name = trim($_POST['full_name'] ?? '');
        $email     = trim($_POST['email'] ?? '');

        if (empty($full_name) || empty($email)) {
            $_SESSION['error'] = "Full name and email address cannot be empty.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Please enter a valid email address.";
        } else {
            // Check if email is taken by another user
            $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $check_stmt->bind_param("si", $email, $user_id);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                $_SESSION['error'] = "This email is already in use by another account.";
            } else {
                // Update User
                $update_stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ? WHERE user_id = ?");
                $update_stmt->bind_param("ssi", $full_name, $email, $user_id);

                if ($update_stmt->execute()) {
                    $_SESSION['full_name'] = $full_name; // Sync session
                    $_SESSION['success'] = "Profile details updated successfully!";
                } else {
                    $_SESSION['error'] = "Failed to update profile. Please try again.";
                }
            }
        }
        header("Location: profile.php");
        exit();
    }

    // 2. CHANGE PASSWORD
    if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password     = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $_SESSION['error'] = "All password fields are required.";
        } elseif ($new_password !== $confirm_password) {
            $_SESSION['error'] = "New password and confirm password do not match.";
        } elseif (strlen($new_password) < 6) {
            $_SESSION['error'] = "New password must be at least 6 characters long.";
        } else {
            // Fetch stored password hash
            $pwd_stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
            $pwd_stmt->bind_param("i", $user_id);
            $pwd_stmt->execute();
            $res = $pwd_stmt->get_result()->fetch_assoc();

            if ($res && password_verify($current_password, $res['password'])) {
                // Hash new password and update
                $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
                $upd_pwd_stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $upd_pwd_stmt->bind_param("si", $hashed_new_password, $user_id);

                if ($upd_pwd_stmt->execute()) {
                    $_SESSION['success'] = "Password changed successfully!";
                } else {
                    $_SESSION['error'] = "Failed to update password. Please try again.";
                }
            } else {
                $_SESSION['error'] = "Incorrect current password.";
            }
        }
        header("Location: profile.php");
        exit();
    }
}

// ==========================================
// FETCH CURRENT USER INFORMATION
// ==========================================
$stmt = $conn->prepare("SELECT full_name, email, role, created_at FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    die("User account not found.");
}

// Generate initials for default avatar
$words = explode(" ", $user['full_name']);
$initials = "";
foreach ($words as $w) {
    if (!empty($w)) {
        $initials .= strtoupper($w[0]);
    }
}
$initials = substr($initials, 0, 2) ?: 'U';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Profile | LMS</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Bootstrap 5 & Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<style>
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background: #f7f9ff;
    color: #111827;
}

.wrapper {
    display: flex;
    width: 100%;
    min-height: 100vh;
}

.content {
    margin-left: 290px;
    padding: 35px;
    width: calc(100% - 290px);
    transition: all .3s ease;
}

.content.expanded {
    margin-left: 105px;
    width: calc(100% - 105px);
}

.section-box {
    background: white;
    border-radius: 18px;
    padding: 30px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.04);
}

.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.toggle-btn {
    border: none;
    background: linear-gradient(135deg, #6d35f5, #8b5cf6);
    color: white;
    width: 45px;
    height: 45px;
    border-radius: 12px;
    font-size: 24px;
    cursor: pointer;
}

#mobileMenuToggle {
    display: none;
}

/* PROFILE CARD CUSTOM STYLES */
.profile-avatar-circle {
    width: 90px;
    height: 90px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6d35f5, #8b5cf6);
    color: white;
    font-size: 32px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px auto;
    box-shadow: 0 8px 20px rgba(109, 53, 245, 0.25);
}

.form-control {
    border-radius: 10px;
    border: 1px solid #cbd5e1;
    padding: 12px 16px;
}

.form-control:focus {
    border-color: #6d35f5;
    box-shadow: 0 0 0 0.25rem rgba(109, 53, 245, 0.15);
}

.nav-pills .nav-link {
    border-radius: 10px;
    color: #475569;
    font-weight: 600;
    padding: 10px 20px;
}

.nav-pills .nav-link.active {
    background-color: #6d35f5;
    color: white;
}

@media(max-width:900px) {
    #mobileMenuToggle {
        display: block !important;
    }
    .content, .content.expanded {
        margin-left: 0;
        width: 100%;
        padding: 20px;
    }
}
</style>
</head>

<body>

<div class="wrapper">

    <!-- SIDEBAR INCLUSION -->
    <?php include("sidebar_teacher.php"); ?>

    <!-- MAIN CONTENT -->
    <main class="content" id="content">

        <!-- TOPBAR -->
        <div class="topbar">
            <div class="d-flex align-items-center gap-3">
                <button id="mobileMenuToggle" class="toggle-btn">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    <h1 class="fw-bold mb-1">Account Profile</h1>
                    <p class="text-muted mb-0">Manage your account information and login security settings.</p>
                </div>
            </div>
        </div>

        <!-- SESSION NOTIFICATIONS -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="row g-4">

            <!-- LEFT COLUMN: PROFILE CARD SUMMARY -->
            <div class="col-lg-4">
                <div class="section-box text-center">
                    <div class="profile-avatar-circle">
                        <?= $initials ?>
                    </div>
                    <h4 class="fw-bold mb-1"><?= htmlspecialchars($user['full_name']) ?></h4>
                    <p class="text-muted small mb-3"><?= htmlspecialchars($user['email']) ?></p>

                    <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-semibold text-capitalize mb-4">
                        <i class="bi bi-person-badge me-1"></i> <?= htmlspecialchars($user['role']) ?> Account
                    </span>

                    <hr class="my-3 text-muted opacity-25">

                    <div class="text-start">
                        <div class="d-flex align-items-center justify-content-between text-muted small py-2">
                            <span><i class="bi bi-shield-check me-2 text-primary"></i> Role</span>
                            <strong class="text-dark text-capitalize"><?= htmlspecialchars($user['role']) ?></strong>
                        </div>
                        <div class="d-flex align-items-center justify-content-between text-muted small py-2">
                            <span><i class="bi bi-calendar3 me-2 text-primary"></i> Joined</span>
                            <strong class="text-dark">
                                <?= !empty($user['created_at']) ? date("M Y", strtotime($user['created_at'])) : 'Active' ?>
                            </strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: EDIT DETAILS & SECURITY FORM -->
            <div class="col-lg-8">
                <div class="section-box">

                    <!-- TAB NAVIGATION -->
                    <ul class="nav nav-pills mb-4" id="profileTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="details-tab" data-bs-toggle="pill" data-bs-target="#details" type="button" role="tab">
                                <i class="bi bi-person me-1"></i> Personal Info
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="security-tab" data-bs-toggle="pill" data-bs-target="#security" type="button" role="tab">
                                <i class="bi bi-key me-1"></i> Change Password
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="profileTabContent">

                        <!-- TAB 1: EDIT PERSONAL DETAILS -->
                        <div class="tab-pane fade show active" id="details" role="tabpanel">
                            <form action="profile.php" method="POST">
                                <input type="hidden" name="action" value="update_profile">

                                <div class="mb-3">
                                    <label for="full_name" class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" name="full_name" id="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                                </div>

                                <div class="mb-4">
                                    <label for="email" class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                                </div>

                                <div class="d-flex justify-content-start">
                                    <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold">
                                        <i class="bi bi-check-lg me-1"></i> Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- TAB 2: CHANGE PASSWORD -->
                        <div class="tab-pane fade" id="security" role="tabpanel">
                            <form action="profile.php" method="POST" onsubmit="return validatePasswordForm()">
                                <input type="hidden" name="action" value="change_password">

                                <!-- Current Password -->
                                <div class="mb-3">
                                    <label for="current_password" class="form-label fw-semibold">Current Password <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="password" name="current_password" id="current_password" class="form-control bg-white" placeholder="Enter current password" required style="border-top-right-radius: 0; border-bottom-right-radius: 0;">
                                        <button class="btn btn-outline-secondary bg-white border-start-0" type="button" id="toggleCurrentPassword" style="border-color: #cbd5e1; border-top-right-radius: 10px; border-bottom-right-radius: 10px;">
                                            <i class="bi bi-eye" id="iconCurrentPassword"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- New Password -->
                                <div class="mb-3">
                                    <label for="new_password" class="form-label fw-semibold">New Password <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="password" name="new_password" id="new_password" class="form-control bg-white" placeholder="Minimum 6 characters" required style="border-top-right-radius: 0; border-bottom-right-radius: 0;">
                                        <button class="btn btn-outline-secondary bg-white border-start-0" type="button" id="toggleNewPassword" style="border-color: #cbd5e1; border-top-right-radius: 10px; border-bottom-right-radius: 10px;">
                                            <i class="bi bi-eye" id="iconNewPassword"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Confirm New Password -->
                                <div class="mb-4">
                                    <label for="confirm_password" class="form-label fw-semibold">Confirm New Password <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="password" name="confirm_password" id="confirm_password" class="form-control bg-white" placeholder="Re-enter new password" required style="border-top-right-radius: 0; border-bottom-right-radius: 0;">
                                        <button class="btn btn-outline-secondary bg-white border-start-0" type="button" id="toggleConfirmPassword" style="border-color: #cbd5e1; border-top-right-radius: 10px; border-bottom-right-radius: 10px;">
                                            <i class="bi bi-eye" id="iconConfirmPassword"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-start">
                                    <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold">
                                        <i class="bi bi-shield-lock me-1"></i> Update Password
                                    </button>
                                </div>
                            </form>
                        </div>

                    </div>

                </div>
            </div>

        </div>

    </main>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Client-side validation for password match
function validatePasswordForm() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;

    if (newPassword.length < 6) {
        alert("New password must be at least 6 characters long.");
        return false;
    }

    if (newPassword !== confirmPassword) {
        alert("New password and confirm password do not match.");
        return false;
    }

    return true;
}

// Universal Toggle Password Visibility Function
function setupPasswordToggle(toggleBtnId, inputId, iconId) {
    const toggleBtn = document.getElementById(toggleBtnId);
    const passwordInput = document.getElementById(inputId);
    const passwordIcon = document.getElementById(iconId);

    if (toggleBtn && passwordInput && passwordIcon) {
        toggleBtn.addEventListener('click', () => {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            passwordIcon.classList.toggle('bi-eye', !isPassword);
            passwordIcon.classList.toggle('bi-eye-slash', isPassword);
        });
    }
}

// Initialize visibility toggle for all three password fields
setupPasswordToggle('toggleCurrentPassword', 'current_password', 'iconCurrentPassword');
setupPasswordToggle('toggleNewPassword', 'new_password', 'iconNewPassword');
setupPasswordToggle('toggleConfirmPassword', 'confirm_password', 'iconConfirmPassword');

// Mobile Sidebar Handler
const mobileToggleBtn = document.getElementById("mobileMenuToggle");
const sidebar = document.getElementById("sidebar");
const content = document.getElementById("content");

if (mobileToggleBtn) {
    mobileToggleBtn.addEventListener("click", () => {
        if (window.innerWidth <= 900) {
            sidebar.classList.toggle("mobile-open");
        } else {
            sidebar.classList.toggle("collapsed");
            content.classList.toggle("expanded");
        }
    });
}
</script>

</body>
</html>