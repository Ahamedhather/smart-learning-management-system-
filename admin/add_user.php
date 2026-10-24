<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start(); 
}
include("../config/db.php");
include("../config/functions.php"); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add New User | A too Z LMS</title>
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

/* MAIN CONTENT */
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

/* TOPBAR */
.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    gap: 20px;
}

.left-title {
    display: flex;
    align-items: center;
    gap: 15px;
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

/* Mobile Topbar Toggle Hidden on Desktop */
#mobileMenuToggle {
    display: none;
}

/* SECTIONS & TABLES */
.section-box {
    background: white;
    border-radius: 18px;
    padding: 35px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.04);
    max-width: 850px;
}

.form-label {
    font-weight: 600;
    color: #4b5563;
    margin-bottom: 8px;
}

.form-control, .form-select {
    padding: 12px 15px;
    border: 1px solid #d1d5db;
    border-radius: 10px;
    background-color: #f9fafb;
    transition: all 0.2s ease;
}

.form-control:focus, .form-select:focus {
    background-color: #fff;
    border-color: #6d35f5;
    box-shadow: 0 0 0 4px rgba(109, 53, 245, 0.1);
    outline: none;
}

.btn-gradient {
    background: linear-gradient(135deg, #6d35f5, #8b5cf6);
    color: white;
    padding: 12px 25px;
    border-radius: 10px;
    font-weight: 600;
    border: none;
    transition: all 0.3s ease;
}

.btn-gradient:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(109, 53, 245, 0.3);
    color: white;
}

.btn-cancel {
    background-color: #f3f4f6;
    color: #4b5563;
    padding: 12px 25px;
    border-radius: 10px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
    display: inline-block;
}

.btn-cancel:hover {
    background-color: #e5e7eb;
    color: #1f2937;
}

/* Style validation errors slightly cleaner */
.invalid-feedback {
    font-size: 0.85rem;
    margin-top: 5px;
}

/* RESPONSIVE */
@media(max-width:900px) {
    #mobileMenuToggle {
        display: block !important;
    }

    .content,
    .content.expanded {
        margin-left: 0;
        width: 100%;
        padding: 20px;
    }

    .topbar {
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
    }
}
</style>
</head>

<body>

<div class="wrapper">

    <!-- SIDEBAR INCLUSION -->
    <?php include("sidebar.php"); ?>

    <!-- MAIN CONTENT CONTAINER -->
    <main class="content" id="content">
        
        <!-- HEADER SECTION -->
        <div class="topbar">
            <div class="left-title">
                <!-- Mobile Toggle Button (Only displays on small screens) -->
                <button id="mobileMenuToggle" class="toggle-btn">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    <h1 class="fw-bold mb-1">Add New User</h1>
                    <p class="text-muted mb-0">Create and assign roles to system accounts.</p>
                </div>
            </div>
            <div>
                <a href="manage_users.php" class="btn-cancel">
                    <i class="bi bi-arrow-left me-2"></i>Back to List
                </a>
            </div>
        </div>

        <!-- SERVER SIDE VALIDATION MESSAGES DISPLAY -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4 style-alert" role="alert" style="max-width: 850px; border-radius: 12px;">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- STYLED FORM CONTAINER -->
        <div class="section-box">
            <!-- Added 'needs-validation' and 'novalidate' to activate Bootstrap front-end checking -->
            <form action="add_user_process.php" method="POST" class="needs-validation" novalidate>
                <div class="row g-4">
                    
                    <!-- Full Name -->
                    <div class="col-md-6">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control" placeholder="e.g. John Doe" required minlength="3">
                        <div class="invalid-feedback">Please enter a valid name (at least 3 characters).</div>
                    </div>

                    <!-- Username -->
                    <div class="col-md-6">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" placeholder="e.g. johndoe12" required pattern="^[a-zA-Z0-9_]{3,20}$">
                        <div class="invalid-feedback">Username must be 3-20 characters long and contain no spaces or special characters (letters, numbers, underscores only).</div>
                    </div>

                    <!-- Email -->
                    <div class="col-md-6">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="name@example.com" required>
                        <div class="invalid-feedback">Please provide a valid email address structure.</div>
                    </div>

                    <!-- Phone Number -->
                    <div class="col-md-6">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control" placeholder="e.g. 0771234567" pattern="^[0-9+\s-]{9,15}$">
                        <div class="invalid-feedback">Please enter a valid telephone number sequence.</div>
                    </div>

                    <!-- Password -->
                    <div class="col-md-6">
                        <label class="form-label">Account Password</label>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required minlength="6">
                        <div class="invalid-feedback">Password is required and must contain at least 6 characters.</div>
                    </div>

                    <!-- Account Role -->
                    <div class="col-md-6">
                        <label class="form-label">System Role</label>
                        <select name="role" class="form-select" required>
                            <option value="" disabled selected>Choose a role...</option>
                            <option value="admin">Admin</option>
                            <option value="teacher">Teacher</option>
                            <option value="student">Student</option>
                        </select>
                        <div class="invalid-feedback">Please assign a system role definition.</div>
                    </div>

                    <!-- Submit Action Button Footer Area -->
                    <div class="col-12 mt-4 pt-2">
                        <button type="submit" class="btn-gradient me-2">
                            <i class="bi bi-check-circle-fill me-2"></i>Save Account User
                        </button>
                        <a href="manage_users.php" class="btn-cancel">Cancel</a>
                    </div>

                </div>
            </form>
        </div>

    </main>
</div>

<!-- Bootstrap JS Bundle for Alert Dismissal -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Bootstrap Client Side Form Validation & Sidebar Toggle Script -->
<script>
/* COLLAPSIBLE SIDEBAR CONFIGURATION */
const toggleBtn = document.getElementById("menuToggle");
const mobileToggleBtn = document.getElementById("mobileMenuToggle");
const sidebar = document.getElementById("sidebar");
const content = document.getElementById("content");

function handleToggle() {
    if (window.innerWidth <= 900) {
        sidebar.classList.toggle("mobile-open");
    } else {
        sidebar.classList.toggle("collapsed");
        content.classList.toggle("expanded");
    }
}

if (toggleBtn) {
    toggleBtn.addEventListener("click", handleToggle);
}
if (mobileToggleBtn) {
    mobileToggleBtn.addEventListener("click", handleToggle);
}

/* Bootstrap Client Side Validation */
(() => {
  'use strict'
  const forms = document.querySelectorAll('.needs-validation')
  Array.from(forms).forEach(form => {
    form.addEventListener('submit', event => {
      if (!form.checkValidity()) {
        event.preventDefault()
        event.stopPropagation()
      }
      form.classList.add('was-validated')
    }, false)
  })
})()
</script>

</body>
</html>