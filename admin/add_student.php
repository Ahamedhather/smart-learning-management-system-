<?php
include("../config/db.php");
include("../includes/auth.php");
include("../config/functions.php");

/* SECURITY CHECK */
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    die("Access Denied");
}

/* FETCH CURRENT LOGGED-IN USER DATA FOR SIDEBAR DISPLAY */
$current_user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
$current_user = null;
if ($current_user_id > 0) {
    $user_query = $conn->query("SELECT full_name, role FROM users WHERE user_id = $current_user_id");
    if ($user_query) {
        $current_user = $user_query->fetch_assoc();
    }
}

/* FETCH AVAILABLE STUDENT USERS NOT YET ASSIGNED TO A STUDENT PROFILE */
$users = $conn->query("
    SELECT u.user_id, u.full_name, u.username 
    FROM users u 
    LEFT JOIN Student s ON u.user_id = s.UserID 
    WHERE u.role='student' AND u.status='active' AND s.StudentID IS NULL
    ORDER BY u.full_name ASC
");

/* FETCH DEPARTMENTS FOR SELECT DROPDOWN */
$departments = $conn->query("SELECT * FROM Department ORDER BY DepartmentName ASC");
$error = "";

/* POST REQUEST HANDLER */
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = intval($_POST['user_id']);
    $reg_no = trim($_POST['reg_no']);
    $department = intval($_POST['department']);
    $year = intval($_POST['year']);
    $status = $_POST['status'];

    if ($user_id <= 0 || $reg_no == "" || $department <= 0 || $year <= 0) {
        $error = "Please fill all required fields.";
    } else {
        $stmt = $conn->prepare("
            INSERT INTO Student (UserID, RegistrationNumber, DepartmentID, EnrollmentYear, Status)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("isiis", $user_id, $reg_no, $department, $year, $status);

        if ($stmt->execute()) {
            header("Location: manage_students.php");
            exit();
        } else {
            $error = "Student registration failed. Registration number may already exist.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Student | A too Z LMS</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

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

/* SECTION CARD DESIGNS */
.section-box {
    background: white;
    border-radius: 18px;
    padding: 35px;
    margin-top: 25px;
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
    transition: all 0.2s;
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
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
}

.btn-gradient:hover {
    box-shadow: 0 4px 12px rgba(109, 53, 245, 0.3);
    color: white;
    transform: translateY(-1px);
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
</style>
</head>

<body>

<div class="wrapper">

    <!-- MODULAR SIDEBAR CALL -->
    <?php include("sidebar.php"); ?>

    <!-- 
      LATE-LOADING CSS OVERRIDE ENGINE
      Placing this block here guarantees it executes AFTER sidebar.php's styles 
      load, permanently pushing your form elements out from under the sidebar.
    -->
    <style>
    .wrapper .content, 
    main.content, 
    #content {
        margin-left: 280px !important; /* Pushes form past the fixed sidebar */
        width: calc(100% - 280px) !important;
        padding: 35px !important;
        box-sizing: border-box !important;
        display: block !important;
        position: relative !important;
        transition: all 0.3s ease-in-out;
    }

    /* Auto-collapse state handlers */
    body.sidebar-collapsed .content,
    body.collapsed .content,
    .wrapper.sidebar-collapsed .content,
    .wrapper.collapsed .content {
        margin-left: 105px !important;
        width: calc(100% - 105px) !important;
    }

    /* Responsive Mobile Override */
    @media (max-width: 1024px) {
        .wrapper .content, 
        main.content, 
        #content,
        body.sidebar-collapsed .content,
        body.collapsed .content,
        .wrapper.sidebar-collapsed .content,
        .wrapper.collapsed .content {
            margin-left: 0 !important;
            width: 100% !important;
            padding: 20px !important;
        }
    }
    </style>

    <!-- MAIN CONTENT -->
    <main class="content" id="content">

        <div class="topbar">
            <div class="left-title">
                <div>
                    <h1 class="fw-bold mb-1">Add New Student</h1>
                    <p class="text-muted mb-0">Create and map a physical student profile to an active registered system user account.</p>
                </div>
            </div>
        </div>

        <!-- FORM INTERFACE STORAGE CARD -->
        <div class="section-box">
            
            <?php if($error != "") { ?>
                <div class="alert alert-danger d-flex align-items-center mb-4 rounded-3" role="alert" style="border: none; background-color: #fee2e2; color: #991b1b;">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div><?= htmlspecialchars($error) ?></div>
                </div>
            <?php } ?>

            <form method="POST" onsubmit="return validateForm()" class="needs-validation" novalidate>
                <div class="row g-4">
                    
                    <!-- Select User Dropdown -->
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="user_id">Select User Account</label>
                        <select name="user_id" id="user_id" class="form-select" required>
                            <option value="">Select Student User</option>
                            <?php if ($users && $users->num_rows > 0) { ?>
                                <?php while($u = $users->fetch_assoc()) { ?>
                                    <option value="<?= $u['user_id'] ?>">
                                        <?= htmlspecialchars($u['full_name']) ?> (<?= htmlspecialchars($u['username']) ?>)
                                    </option>
                                <?php } ?>
                            <?php } else { ?>
                                <option value="" disabled>No unlinked active student users found</option>
                            <?php } ?>
                        </select>
                        <div class="invalid-feedback">Please link this profile to an active system user account.</div>
                    </div>

                    <!-- Registration Number Input Field -->
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="reg_no">Registration Number</label>
                        <input type="text" name="reg_no" id="reg_no" class="form-control" 
                               placeholder="e.g. STU-2026-001" required>
                        <div class="invalid-feedback">Please enter an academic identification string (minimum 3 characters).</div>
                    </div>

                    <!-- Academic Department Dropdown Field -->
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="department">Department</label>
                        <select name="department" id="department" class="form-select" required>
                            <option value="">Select Department</option>
                            <?php if ($departments && $departments->num_rows > 0) { ?>
                                <?php while($d = $departments->fetch_assoc()) { ?>
                                    <option value="<?= $d['DepartmentID'] ?>">
                                        <?= htmlspecialchars($d['DepartmentName']) ?>
                                    </option>
                                <?php } ?>
                            <?php } ?>
                        </select>
                        <div class="invalid-feedback">Please assign this student to an academic department.</div>
                    </div>

                    <!-- Enrollment Year Input Field -->
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="year">Enrollment Year</label>
                        <input type="number" name="year" id="year" class="form-control" 
                               placeholder="e.g. 2026" value="2026" required>
                        <div class="invalid-feedback">Please enter a valid numeric enrollment calendar year.</div>
                    </div>

                    <!-- Profile Status Selector Dropdown -->
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="status">Profile Status</label>
                        <select name="status" id="status" class="form-select" required>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>

                    <!-- Form Controls Triggers Row -->
                    <div class="col-12 mt-4 pt-2">
                        <button type="submit" class="btn-gradient me-2">
                            <i class="bi bi-save me-1"></i> Save Student Profile
                        </button>
                        <a href="manage_students.php" class="btn-cancel">Cancel</a>
                    </div>

                </div>
            </form>
        </div>

    </main>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function validateForm() {
    let regNo = document.getElementById("reg_no").value.trim();
    let year = document.getElementById("year").value.trim();
    let userId = document.getElementById("user_id").value;
    let dept = document.getElementById("department").value;

    if (userId === "") {
        alert("Please select a student user account to link.");
        return false;
    }

    if (regNo.length < 3) {
        alert("Registration number must be at least 3 characters long.");
        return false;
    }

    if (dept === "") {
        alert("Please select a valid academic department.");
        return false;
    }

    if (year < 2000 || year > 2100) {
        alert("Please specify a valid enrollment calendar year between 2000 and 2100.");
        return false;
    }

    return true;
}

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