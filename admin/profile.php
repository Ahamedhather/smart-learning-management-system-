<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("../config/db.php");
include("../includes/auth.php");
include("../config/functions.php");

// Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);

// Fetch latest profile records directly from the database
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    die("User profile session data could not be resolved.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Profile | A to Z LMS</title>
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
    padding: 35px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.04);
    max-width: 850px;
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

.form-label {
    font-weight: 600;
    color: #4b5563;
    margin-bottom: 8px;
}

.form-control {
    padding: 12px 15px;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    background-color: #f8fafc;
    transition: all 0.2s;
}

.form-control:focus {
    background-color: #fff;
    border-color: #6d35f5;
    box-shadow: 0 0 0 4px rgba(109, 53, 245, 0.15);
    outline: none;
}

.btn-gradient {
    background: linear-gradient(135deg, #6d35f5, #8b5cf6);
    color: white;
    padding: 12px 25px;
    border-radius: 10px;
    font-weight: 600;
    border: none;
    transition: all 0.2s;
}

.btn-gradient:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(109, 53, 245, 0.3);
    color: white;
}

.transition-arrow {
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.advanced-toggle-btn {
    color: #5b35f5;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
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
    <?php include("sidebar.php"); ?>

    <!-- CONTENT MODULE -->
    <main class="content" id="content">

        <!-- TOPBAR -->
        <div class="topbar">
            <div class="d-flex align-items-center gap-3">
                <button id="mobileMenuToggle" class="toggle-btn">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    <h1 class="fw-bold mb-1">Account Profile Settings</h1>
                    <p class="text-muted mb-0">Manage your personal identification details and account security rules.</p>
                </div>
            </div>
        </div>

        <!-- NOTIFICATION BANNER ENGINE -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4 text-dark" role="alert" style="max-width: 850px; border-radius: 12px; background-color: #fee2e2; border: none;">
                <i class="bi bi-exclamation-triangle-fill text-danger me-2"></i> <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'profile_updated'): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4 text-dark" role="alert" style="max-width: 850px; border-radius: 12px; background-color: #dcfce7; border: none;">
                <i class="bi bi-check-circle-fill text-success me-2"></i> Your profile has been updated successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- FORM INTERFACE BOX -->
        <div class="section-box">
            <form action="update_profile.php" method="POST" class="needs-validation" novalidate>
                <div class="row g-4">
                    
                    <!-- Full Name -->
                    <div class="col-md-6">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" required minlength="3">
                        <div class="invalid-feedback">Please enter your full name (at least 3 characters).</div>
                    </div>

                    <!-- Email Address -->
                    <div class="col-md-6">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                        <div class="invalid-feedback">Please enter a valid unique email address.</div>
                    </div>

                    <!-- Phone Number -->
                    <div class="col-md-6">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" pattern="^[0-9+\s-]{9,15}$">
                        <div class="invalid-feedback">Please provide a valid phone number.</div>
                    </div>

                    <!-- System Info Readonly Badges -->
                    <div class="col-md-6">
                        <label class="form-label d-block">Account Status & Role</label>
                        <span class="badge bg-primary text-capitalize px-3 py-2 rounded-3 me-2">
                            <i class="bi bi-shield-lock me-1"></i> Role: <?= htmlspecialchars($user['role']) ?>
                        </span>
                        <span class="badge bg-success text-capitalize px-3 py-2 rounded-3">
                            <i class="bi bi-activity me-1"></i> Status: <?= htmlspecialchars($user['status']) ?>
                        </span>
                    </div>

                    <!-- ADVANCED OPTIONS TOGGLE LINK WITH CHEVRON ACCORDION -->
                    <div class="col-12 mt-3">
                        <a class="advanced-toggle-btn" data-bs-toggle="collapse" href="#profileAdvancedAccordion" role="button" aria-expanded="false">
                            <span>Change Username & Password Credentials</span>
                            <i class="bi bi-chevron-down transition-arrow" id="chevronArrow"></i>
                        </a>
                    </div>

                    <!-- ACCORDION INNER BOX BODY -->
                    <div class="col-12 collapse" id="profileAdvancedAccordion">
                        <div class="row g-4 p-4 rounded-4 bg-light border">
                            <div class="col-md-6">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control bg-white" value="<?= htmlspecialchars($user['username']) ?>" required pattern="^[a-zA-Z0-9_]{3,20}$">
                                <div class="invalid-feedback">Usernames can only use letters, numbers, and underscores (3-20 characters).</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">New Password</label>
                                <div class="input-group">
                                    <input type="password" name="password" id="newPasswordInput" class="form-control bg-white" placeholder="Leave empty to retain existing password" minlength="6">
                                    <button class="btn btn-outline-secondary bg-white border-start-0" type="button" id="togglePasswordBtn" style="border-color: #cbd5e1;">
                                        <i class="bi bi-eye" id="togglePasswordIcon"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback">New passwords must consist of at least 6 characters.</div>
                            </div>
                        </div>
                    </div>

                    <!-- SUBMIT CONTAINER AREA -->
                    <div class="col-12 mt-4 pt-2 d-flex align-items-center gap-2">
                        <button type="submit" class="btn-gradient">
                            <i class="bi bi-save me-2"></i> Save Profile Changes
                        </button>
                        <a href="dashboard.php" class="btn btn-light border rounded-3 px-4 py-2">
                            Cancel
                        </a>
                    </div>

                </div>
            </form>
        </div>

    </main>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
(() => {
    'use strict'

    // Form submission validation logic
    const forms = document.querySelectorAll('.needs-validation')
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    // Password Visibility Toggle Logic
    const togglePasswordBtn = document.getElementById('togglePasswordBtn');
    const newPasswordInput = document.getElementById('newPasswordInput');
    const togglePasswordIcon = document.getElementById('togglePasswordIcon');

    if (togglePasswordBtn && newPasswordInput && togglePasswordIcon) {
        togglePasswordBtn.addEventListener('click', () => {
            const isPassword = newPasswordInput.type === 'password';
            newPasswordInput.type = isPassword ? 'text' : 'password';
            togglePasswordIcon.classList.toggle('bi-eye', !isPassword);
            togglePasswordIcon.classList.toggle('bi-eye-slash', isPassword);
        });
    }

    // Chevron arrow dynamic rotation
    const accordion = document.getElementById('profileAdvancedAccordion');
    const chevron = document.getElementById('chevronArrow');
    if (accordion && chevron) {
        accordion.addEventListener('show.bs.collapse', () => chevron.style.transform = 'rotate(180deg)');
        accordion.addEventListener('hide.bs.collapse', () => chevron.style.transform = 'rotate(0deg)');
    }

    // Sidebar toggle handler
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
})();
</script>

</body>
</html>