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

/* FETCH TARGET STUDENT DETAILS */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid Student ID");
}

$id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM Student WHERE StudentID=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    die("Student not found");
}

/* FETCH DEPARTMENTS FOR SELECT DROPDOWN */
$departments = $conn->query("SELECT * FROM Department ORDER BY DepartmentName ASC");
$error = "";

/* POST REQUEST HANDLER */
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $reg_no = trim($_POST['reg_no']);
    $department = intval($_POST['department']);
    $year = intval($_POST['year']);
    $status = $_POST['status'];

    if ($reg_no == "" || $department <= 0 || $year <= 0) {
        $error = "Please fill all required fields.";
    } else {
        $stmt = $conn->prepare("
            UPDATE Student 
            SET RegistrationNumber=?, DepartmentID=?, EnrollmentYear=?, Status=? 
            WHERE StudentID=?
        ");
        $stmt->bind_param("siisi", $reg_no, $department, $year, $status, $id);

        if ($stmt->execute()) {
            header("Location: manage_students.php");
            exit();
        } else {
            $error = "Update failed. Registration number may already exist.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Student | A too Z LMS</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<style>
body{
    margin:0;
    font-family:'Segoe UI', sans-serif;
    background:#f7f9ff;
    color:#111827;
}

.wrapper{
    display:flex;
}

/* SIDEBAR */
.sidebar{
    width:270px;
    height:100vh;
    background:#fff;
    padding:25px 20px;
    position:fixed;
    left:0;
    top:0;
    box-shadow:0 0 25px rgba(0,0,0,0.08);
    overflow-y:auto;
    overflow-x:hidden;
    transition:all .3s ease;
    z-index:1000;
}

.sidebar::-webkit-scrollbar{
    width:6px;
}

.sidebar::-webkit-scrollbar-thumb{
    background:#cbd5e1;
    border-radius:10px;
}

.sidebar.collapsed{
    width:85px;
}

.logo{
    font-size:23px;
    font-weight:700;
    color:#5b35f5;
    margin-bottom:30px;
    white-space:nowrap;
}

.logo i{
    margin-right:8px;
}

.sidebar.collapsed .logo span{
    display:none;
}

.menu{
    list-style:none;
    padding:0;
    margin:0;
}

.menu li{
    margin-bottom:9px;
}

.menu a{
    text-decoration:none;
    display:flex;
    align-items:center;
    gap:13px;
    color:#1f2937;
    padding:13px 15px;
    border-radius:13px;
    font-weight:500;
    white-space:nowrap;
}

.menu a i{
    font-size:20px;
    min-width:22px;
    text-align:center;
}

.menu a:hover,
.menu a.active{
    background:linear-gradient(135deg,#6d35f5,#8b5cf6);
    color:white;
}

.sidebar.collapsed .menu a span{
    display:none;
}

.sidebar.collapsed .menu a{
    justify-content:center;
    padding:14px 0;
}

.admin-box{
    margin-top:30px;
    background:#f8fafc;
    padding:15px;
    border-radius:15px;
    display:flex;
    align-items:center;
    gap:12px;
    box-shadow:0 4px 12px rgba(0,0,0,0.08);
    transition: all 0.2s ease;
}

.admin-box:hover {
    background: #f1f5f9;
}

.admin-box i{
    font-size:35px;
    color:#5b35f5;
}

.sidebar.collapsed .admin-box div{
    display:none;
}

.sidebar.collapsed .admin-box{
    justify-content:center;
    padding:12px;
}

/* MAIN CONTENT */
.content{
    margin-left:290px;
    padding:35px;
    width:calc(100% - 290px);
    transition:all .3s ease;
}

.content.expanded{
    margin-left:105px;
    width:calc(100% - 105px);
}

/* TOPBAR */
.topbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:25px;
    gap:20px;
}

.left-title{
    display:flex;
    align-items:center;
    gap:15px;
}

.toggle-btn{
    border:none;
    background:linear-gradient(135deg,#6d35f5,#8b5cf6);
    color:white;
    width:45px;
    height:45px;
    border-radius:12px;
    font-size:24px;
    cursor:pointer;
}

/* FORMS AND PANEL DESIGNS */
.section-box{
    background:white;
    border-radius:18px;
    padding:35px;
    margin-top:25px;
    box-shadow:0 6px 18px rgba(0,0,0,0.07);
    max-width: 800px;
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
}

.btn-gradient:hover {
    box-shadow: 0 4px 12px rgba(109, 53, 245, 0.3);
    color: white;
    transform: translateY(-1px);
}

/* RESPONSIVE */
@media(max-width:900px){
    .sidebar{
        left:-280px;
    }

    .sidebar.mobile-open{
        left:0;
    }

    .sidebar.collapsed{
        width:270px;
    }

    .sidebar.collapsed .logo span,
    .sidebar.collapsed .menu a span,
    .sidebar.collapsed .admin-box div{
        display:inline;
    }

    .sidebar.collapsed .menu a{
        justify-content:flex-start;
        padding:13px 15px;
    }

    .content,
    .content.expanded{
        margin-left:0;
        width:100%;
        padding:20px;
    }

    .topbar{
        flex-direction:column;
        align-items:flex-start;
    }
}
</style>
</head>

<body>

<div class="wrapper">

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">

    <div class="logo">
        <i class="bi bi-mortarboard-fill"></i>
        <span>A too Z LMS</span>
    </div>
    <div class="left-title">
            <button id="menuToggle" class="toggle-btn">
                <i class="bi bi-list"></i>
            </button>
    </div>
    <br>

    <ul class="menu">
        <li><a href="dashboard.php"><i class="bi bi-house-door"></i><span>Dashboard</span></a></li>
        <li><a href="manage_users.php"><i class="bi bi-people"></i><span>Users</span></a></li>
        <li><a href="manage_departments.php"><i class="bi bi-building"></i><span>Departments</span></a></li>
        <li><a href="manage_students.php" class="active"><i class="bi bi-mortarboard"></i><span>Students</span></a></li>
        <li><a href="manage_teachers.php"><i class="bi bi-person-workspace"></i><span>Teachers</span></a></li>
        <li><a href="manage_subjects.php"><i class="bi bi-book"></i><span>Subjects</span></a></li>
        <li><a href="manage_courses.php"><i class="bi bi-journal-bookmark"></i><span>Courses</span></a></li>
        <li><a href="manage_enrollment.php"><i class="bi bi-card-checklist"></i><span>Enrollments</span></a></li>
        <li><a href="attendance_report.php"><i class="bi bi-calendar-check"></i><span>Attendance</span></a></li>
        <li><a href="performance_report.php"><i class="bi bi-bar-chart"></i><span>Performance</span></a></li>
        <li><a href="announcements.php"><i class="bi bi-megaphone"></i><span>Announcements</span></a></li>
        <li><a href="profile.php"><i class="bi bi-person-gear"></i><span>My Profile</span></a></li>
        <li><a href="../public/login.php"><i class="bi bi-box-arrow-right"></i><span>Logout</span></a></li>
    </ul>

    <div class="admin-box" style="cursor: pointer;" onclick="window.location.href='profile.php';">
        <i class="bi bi-person-circle"></i>
        <div>
            <strong><?= htmlspecialchars($current_user['full_name'] ?? 'Admin User') ?></strong><br>
            <small class="text-capitalize"><?= htmlspecialchars($current_user['role'] ?? 'Administrator') ?></small>
        </div>
    </div>

</aside>

<!-- MAIN CONTENT -->
<main class="content" id="content">

    <div class="topbar">
        <div class="left-title">
            <div>
                <h1 class="fw-bold mb-1">Edit Student Profile</h1>
                <p class="text-muted mb-0">Modify student identification numbers, academic departments, and enrollment terms.</p>
            </div>
        </div>
    </div>

    <!-- FORM INTERFACE STORAGE CARD -->
    <div class="section-box">
        
        <?php if($error != "") { ?>
            <div class="alert alert-danger d-flex align-items-center mb-4 rounded-3" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <div><?= htmlspecialchars($error) ?></div>
            </div>
        <?php } ?>

        <form method="POST" onsubmit="return validateForm()" class="needs-validation" novalidate>
            <div class="row g-4">
                
                <!-- Registration Number Input Field -->
                <div class="col-12 col-md-6">
                    <label class="form-label" for="reg_no">Registration Number</label>
                    <input type="text" name="reg_no" id="reg_no"
                           value="<?= htmlspecialchars($student['RegistrationNumber']) ?>"
                           class="form-control" required placeholder="e.g. REG-2026-005">
                    <div class="invalid-feedback">Please enter a valid academic registration string.</div>
                </div>

                <!-- Academic Department Dropdown Field -->
                <div class="col-12 col-md-6">
                    <label class="form-label" for="department">Department</label>
                    <select name="department" id="department" class="form-select" required>
                        <option value="">Select Department</option>
                        <?php if ($departments && $departments->num_rows > 0) { ?>
                            <?php while($d = $departments->fetch_assoc()) { ?>
                                <option value="<?= $d['DepartmentID'] ?>"
                                    <?= ($student['DepartmentID'] == $d['DepartmentID']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($d['DepartmentName']) ?>
                                </option>
                            <?php } ?>
                        <?php } ?>
                    </select>
                    <div class="invalid-feedback">Please select an assigned organizational faculty department.</div>
                </div>

                <!-- Enrollment Year Input Field -->
                <div class="col-12 col-md-6">
                    <label class="form-label" for="year">Enrollment Year</label>
                    <input type="number" name="year" id="year"
                           value="<?= htmlspecialchars($student['EnrollmentYear']) ?>"
                           class="form-control" required placeholder="e.g. 2026">
                    <div class="invalid-feedback">Please enter a valid numeric year.</div>
                </div>

                <!-- Profile Status Selector Dropdown -->
                <div class="col-12 col-md-6">
                    <label class="form-label" for="status">Status</label>
                    <select name="status" id="status" class="form-select" required>
                        <option value="Active" <?= ($student['Status'] == 'Active') ? 'selected' : '' ?>>Active</option>
                        <option value="Inactive" <?= ($student['Status'] == 'Inactive') ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>

                <!-- Form Controls Triggers Row -->
                <div class="col-12 mt-4 pt-2">
                    <button type="submit" class="btn-gradient me-2">
                        <i class="bi bi-check-circle me-2"></i>Update Profile
                    </button>
                    <a href="manage_students.php" class="btn text-secondary fw-semibold">Cancel and Return</a>
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

    if (regNo.length < 3) {
        alert("Registration number must be at least 3 characters long.");
        return false;
    }

    if (year < 2000 || year > 2100) {
        alert("Please specify a valid academic enrollment calendar year between 2000 and 2100.");
        return false;
    }

    return true;
}

(() => {
  'use strict'
  // Bootstrap 5 client-side validation logic workflow mapping
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

  // Collapsible responsive navigation handler toggle triggers
  const toggleBtn = document.getElementById("menuToggle");
  const sidebar = document.getElementById("sidebar");
  const content = document.getElementById("content");

  toggleBtn.addEventListener("click", function(){
      if(window.innerWidth <= 900){
          sidebar.classList.toggle("mobile-open");
      }else{
          sidebar.classList.toggle("collapsed");
          content.classList.toggle("expanded");
      }
  });
})()
</script>

</body>
</html>