<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("../config/db.php");

// 🔒 SECURITY CHECK
if (!isset($_SESSION['student_id'])) {
    header("Location: ../login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

// ==========================================
// HANDLE FORM SUBMISSIONS
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. UPDATE PERSONAL INFORMATION
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $full_name = trim($_POST['full_name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $phone     = trim($_POST['phone'] ?? '');

        if (empty($full_name) || empty($email)) {
            $_SESSION['error'] = "Full name and email address cannot be empty.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Please enter a valid email address.";
        } else {
            // Check if email is taken by another user
            $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $check_stmt->bind_param("si", $email, $student_id);
            $check_stmt->execute();
            
            if ($check_stmt->get_result()->num_rows > 0) {
                $_SESSION['error'] = "This email is already in use by another account.";
            } else {
                $update_stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE user_id = ?");
                $update_stmt->bind_param("sssi", $full_name, $email, $phone, $student_id);

                if ($update_stmt->execute()) {
                    $_SESSION['full_name'] = $full_name; // Sync session name if needed
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
            $pwd_stmt->bind_param("i", $student_id);
            $pwd_stmt->execute();
            $res = $pwd_stmt->get_result()->fetch_assoc();

            if ($res && password_verify($current_password, $res['password'])) {
                $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
                $upd_pwd_stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $upd_pwd_stmt->bind_param("si", $hashed_new_password, $student_id);

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
// FETCH STUDENT INFORMATION
// ==========================================
$stmt = $conn->prepare("
    SELECT u.full_name, u.email, u.phone, u.role, u.created_at, s.EnrollmentYear, d.DepartmentName 
    FROM users u
    LEFT JOIN Student s ON u.user_id = s.UserID
    LEFT JOIN Department d ON s.DepartmentID = d.DepartmentID
    WHERE u.user_id = ?
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    die("Student account not found.");
}

// Generate initials for avatar circle
$words = explode(" ", $student['full_name']);
$initials = "";
foreach ($words as $w) {
    if (!empty($w)) {
        $initials .= strtoupper($w[0]);
    }
}
$initials = substr($initials, 0, 2) ?: 'ST';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Profile | LMS</title>
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
    <?php include("sidebar_student.php"); ?>

    <!-- MAIN CONTENT -->
    <main class="content" id="content">

        <!-- TOPBAR -->
        <div class="topbar">
            <div class="d-flex align-items-center gap-3">
                <button id="mobileMenuToggle" class="toggle-btn">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    <h1 class="fw-bold mb-1">Student Profile</h1>
                    <p class="text-muted mb-0">Manage your account information and academic security settings.</p>
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
                    <h4 class="fw-bold mb-1"><?= htmlspecialchars($student['full_name']) ?></h4>
                    <p class="text-muted small mb-3"><?= htmlspecialchars($student['email']) ?></p>

                    <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-semibold text-capitalize mb-4">
                        <i class="bi bi-mortarboard me-1"></i> Student Account
                    </span>

                    <hr class="my-3 text-muted opacity-25">

                    <div class="text-start">
                        <div class="d-flex align-items-center justify-content-between text-muted small py-2">
                            <span><i class="bi bi-building me-2 text-primary"></i> Department</span>
                            <strong class="text-dark"><?= htmlspecialchars($student['DepartmentName'] ?? 'N/A') ?></strong>
                        </div>
                        <div class="d-flex align-items-center justify-content-between text-muted small py-2">
                            <span><i class="bi bi-calendar-event me-2 text-primary"></i> Enrollment Year</span>
                            <strong class="text-dark"><?= htmlspecialchars($student['EnrollmentYear'] ?? 'N/A') ?></strong>
                        </div>
                        <div class="d-flex align-items-center justify-content-between text-muted small py-2">
                            <span><i class="bi bi-calendar3 me-2 text-primary"></i> Joined</span>
                            <strong class="text-dark">
                                <?= !empty($student['created_at']) ? date("M Y", strtotime($student['created_at'])) : 'Active' ?>
                            </strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: EDIT DETAILS & PASSWORD FORMS -->
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
                                    <input type="text" name="full_name" id="full_name" class="form-control" value="<?= htmlspecialchars($student['full_name'] ?? '') ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($student['email'] ?? '') ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="phone" class="form-label fw-semibold">Phone Number</label>
                                    <input type="text" name="phone" id="phone" class="form-control" value="<?= htmlspecialchars($student['phone'] ?? '') ?>" placeholder="Enter phone number">
                                </div>

                                <!-- Read-only Academic Info -->
                                <div class="row mb-4">
                                    <div class="col-md-6 mb-3 mb-md-0">
                                        <label class="form-label text-muted small fw-semibold">Department</label>
                                        <input type="text" class="form-control bg-light text-muted" value="<?= htmlspecialchars($student['DepartmentName'] ?? 'N/A') ?>" readonly disabled>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted small fw-semibold">Enrollment Year</label>
                                        <input type="text" class="form-control bg-light text-muted" value="<?= htmlspecialchars($student['EnrollmentYear'] ?? 'N/A') ?>" readonly disabled>
                                    </div>
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
// Client-side password validation
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