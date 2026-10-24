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
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Department | A too Z LMS</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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

.form-control {
    padding: 12px 15px;
    border: 1px solid #d1d5db;
    border-radius: 10px;
    background-color: #f9fafb;
    transition: all 0.2s;
}

.form-control:focus {
    background-color: #fff;
    border-color: #6d35f5;
    box-shadow: 0 0 0 4px rgba(109, 53, 245, 0.1);
    outline: none;
}

.btn-gradient-success {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    padding: 12px 25px;
    border-radius: 10px;
    font-weight: 600;
    border: none;
}

.btn-gradient-success:hover {
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
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
        <li><a href="manage_departments.php" class="active"><i class="bi bi-building"></i><span>Departments</span></a></li>
        <li><a href="manage_students.php"><i class="bi bi-mortarboard"></i><span>Students</span></a></li>
        <li><a href="manage_teachers.php"><i class="bi bi-person-workspace"></i><span>Teachers</span></a></li>
        <li><a href="manage_subjects.php"><i class="bi bi-book"></i><span>Modules / Subjects</span></a></li>
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
                <h1 class="fw-bold mb-1">Add New Department</h1>
                <p class="text-muted mb-0">Establish a new academic faculty branch or global corporate division.</p>
            </div>
        </div>
    </div>

    <!-- FORM INTERFACE STORAGE BOX -->
    <div class="section-box">
        <form action="insert_department.php" method="POST" class="needs-validation" novalidate>
            <div class="row g-4">
                
                <!-- Department Title Name Field -->
                <div class="col-12">
                    <label class="form-label">Department Name</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Department of Mechanical Engineering" required minlength="2">
                    <div class="invalid-feedback">Please enter a valid department title registration name string.</div>
                </div>

                <!-- Textarea Segment Description Field -->
                <div class="col-12">
                    <label class="form-label">Description (Optional)</label>
                    <textarea name="desc" class="form-control" rows="4" placeholder="Brief descriptive summary layout regarding department operational scopes..."></textarea>
                </div>

                <!-- Control Submission Triggers Panel -->
                <div class="col-12 mt-4 pt-2">
                    <button type="submit" class="btn-gradient-success me-2">
                        <i class="bi bi-save me-2"></i>Save Department
                    </button>
                    <a href="manage_departments.php" class="btn text-secondary fw-semibold">Cancel and Return</a>
                </div>

            </div>
        </form>
    </div>

</main>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
(() => {
  'use strict'
  // Bootstrap Form Submissions client-side validation logic workflow
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

  // Collapsible responsive navigation menu click trigger logic mapping
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