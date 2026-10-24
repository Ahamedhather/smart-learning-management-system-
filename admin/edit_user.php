<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("../config/db.php");
include("../config/functions.php");

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid User ID");
}

$id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM users WHERE user_id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    die("User not found");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit User | A too Z LMS</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Bootstrap 5 & Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<style>
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background: #f7f9ff;
}

/* ==========================================================================
   LAYOUT STRUCTURE FIX (Prevents Sidebar Overlap)
   ========================================================================== */
.wrapper {
    display: flex;
    width: 100%;
    min-height: 100vh;
}

.content {
    flex: 1;
    padding: 40px;
    background: #f7f9ff;
    min-width: 0; /* Prevents flex items from overflowing */
    transition: all 0.3s ease;
    
    /* 
       Adjust this value (e.g., 260px or 280px) to match the exact 
       width of your sidebar. This forces the form to sit alongside the sidebar.
    */
    margin-left: 280px; 
}

/* Responsive adjustment for tablets & mobile where sidebars are typically hidden/collapsed */
@media (max-width: 1024px) {
    .content {
        margin-left: 0;
        padding: 20px;
    }
}

/* ==========================================================================
   FORM & INTERACTIVE COMPONENTS
   ========================================================================== */
.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}
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
.invalid-feedback {
    font-size: 0.85rem;
    margin-top: 5px;
}
/* Advanced Arrow Transition Classes */
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
.advanced-toggle-btn:hover {
    color: #6d35f5;
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
            <div>
                <h1 class="fw-bold mb-1">Edit User Profile</h1>
                <p class="text-muted mb-0">Modify account credentials and system authorization settings.</p>
            </div>
            <div>
                <a href="manage_users.php" class="btn-cancel">
                    <i class="bi bi-arrow-left me-2"></i>Back to List
                </a>
            </div>
        </div>

        <!-- NOTIFICATION ALERTS -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert" style="max-width: 850px; border-radius: 12px; border: none; background-color: #fee2e2; color: #991b1b;">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- STYLED FORM CONTAINER -->
        <div class="section-box">
            <form action="update_user.php" method="POST" class="needs-validation" novalidate>
                
                <input type="hidden" name="id" value="<?= $user['user_id'] ?>">

                <div class="row g-4">
                    
                    <!-- Full Name -->
                    <div class="col-md-6">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" id="full_name" class="form-control" 
                               value="<?= htmlspecialchars($user['full_name']) ?>" required minlength="3">
                        <div class="invalid-feedback">Please enter a valid profile name (at least 3 characters).</div>
                    </div>

                    <!-- Email Address -->
                    <div class="col-md-6">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" id="email" class="form-control" 
                               value="<?= htmlspecialchars($user['email']) ?>" required>
                        <div class="invalid-feedback">Please enter a valid unique email address.</div>
                    </div>

                    <!-- Phone Number -->
                    <div class="col-md-6">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" id="phone" class="form-control" 
                               value="<?= htmlspecialchars($user['phone'] ?? '') ?>" pattern="^[0-9+\s-]{9,15}$">
                        <div class="invalid-feedback">Please use a valid phone layout sequence (minimum 9 digits).</div>
                    </div>

                    <!-- Account System Role -->
                    <div class="col-md-6">
                        <label class="form-label">System Authorization Role</label>
                        <select name="role" class="form-select" required>
                            <option value="admin" <?= ($user['role'] == 'admin') ? 'selected' : '' ?>>Admin</option>
                            <option value="teacher" <?= ($user['role'] == 'teacher') ? 'selected' : '' ?>>Teacher</option>
                            <option value="student" <?= ($user['role'] == 'student') ? 'selected' : '' ?>>Student</option>
                        </select>
                    </div>

                    <!-- Account Operational Status -->
                    <div class="col-md-6">
                        <label class="form-label">Account Operational Status</label>
                        <select name="status" class="form-select" required>
                            <option value="active" <?= ($user['status'] == 'active') ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= ($user['status'] == 'inactive') ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>

                    <!-- ADVANCED ROW ACCORDION BUTTON -->
                    <div class="col-12 mt-3">
                        <a class="advanced-toggle-btn" data-bs-toggle="collapse" href="#advancedSettingsBox" role="button" aria-expanded="false" aria-controls="advancedSettingsBox">
                            <span>Advanced Settings (Username & Password)</span>
                            <i class="bi bi-chevron-down transition-arrow" id="arrowChevron"></i>
                        </a>
                    </div>

                    <!-- ADVANCED HIDDEN INPUT PANELS -->
                    <div class="col-12 collapse" id="advancedSettingsBox">
                        <div class="row g-4 p-4 rounded-4 bg-light border">
                            
                            <!-- Username Change Field -->
                            <div class="col-md-6">
                                <label class="form-label text-dark">System Username</label>
                                <input type="text" name="username" class="form-control bg-white" 
                                       value="<?= htmlspecialchars($user['username']) ?>" required pattern="^[a-zA-Z0-9_]{3,20}$">
                                <div class="invalid-feedback">Username must be 3-20 characters long (letters, numbers, underscores only).</div>
                            </div>

                            <!-- Optional Password Overwrite Field -->
                            <div class="col-md-6">
                                <label class="form-label text-dark">New Password</label>
                                <input type="password" name="password" class="form-control bg-white" 
                                       placeholder="Leave blank to keep current password" minlength="6">
                                <div class="invalid-feedback">New passwords must be at least 6 characters long.</div>
                            </div>

                        </div>
                    </div>

                    <!-- Submit Actions Button Group Area -->
                    <div class="col-12 mt-4 pt-2">
                        <button type="submit" class="btn-gradient me-2">
                            <i class="bi bi-arrow-repeat me-2"></i>Update User Record
                        </button>
                        <a href="manage_users.php" class="btn-cancel">Cancel</a>
                    </div>

                </div>
            </form>
        </div>

    </main>
</div>

<!-- Bootstrap Bundle JS Asset -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Combined Client Side Live Verification & Arrow Animation Engine -->
<script>
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
  });

  // Handle advanced arrow transition rotation mechanics
  const advBox = document.getElementById('advancedSettingsBox');
  const arrow = document.getElementById('arrowChevron');
  
  if(advBox && arrow) {
      advBox.addEventListener('show.bs.collapse', () => {
          arrow.style.transform = 'rotate(180deg)';
      });
      advBox.addEventListener('hide.bs.collapse', () => {
          arrow.style.transform = 'rotate(0deg)';
      });
  }
})()
</script>

</body>
</html>