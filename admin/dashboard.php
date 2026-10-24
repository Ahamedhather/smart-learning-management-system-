<?php
session_start();
include("../config/db.php");

/* SECURITY CHECK */
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin' || !isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

/* FETCH CURRENT LOGGED-IN USER DATA */
$current_user_id = intval($_SESSION['user_id']);
$user_query = $conn->query("SELECT full_name, role FROM users WHERE user_id = $current_user_id");
$current_user = $user_query ? $user_query->fetch_assoc() : null;

/* STATISTICS */
$users = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
$students = $conn->query("SELECT COUNT(*) as total FROM Student")->fetch_assoc()['total'];
$teachers = $conn->query("SELECT COUNT(*) as total FROM Teacher")->fetch_assoc()['total'];
$courses = $conn->query("SELECT COUNT(*) as total FROM Course")->fetch_assoc()['total'];
$enrollments = $conn->query("SELECT COUNT(*) as total FROM Enrollment")->fetch_assoc()['total'];
$announcements = $conn->query("SELECT COUNT(*) as total FROM Announcement")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard | A too Z LMS</title>
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

.search-box{
    width:330px;
    border:1px solid #ddd;
    border-radius:12px;
    padding:12px 15px;
    background:white;
}

.date-box{
    background:white;
    padding:10px 15px;
    border-radius:12px;
    box-shadow:0 2px 8px rgba(0,0,0,0.05);
    font-weight:600;
}

/* CARDS */
.stat-card{
    border-radius:18px;
    padding:25px;
    min-height:150px;
    background:white;
    box-shadow:0 6px 18px rgba(0,0,0,0.08);
    transition:.3s;
}

.stat-card:hover{
    transform:translateY(-4px);
}

.stat-icon{
    width:60px;
    height:60px;
    border-radius:15px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:30px;
    margin-bottom:12px;
}

.stat-card h2{
    font-weight:700;
    margin:0;
}

.stat-card p{
    margin:0;
    color:#374151;
    font-weight:500;
}

.purple{
    background:linear-gradient(135deg,#faf5ff,#ffffff);
    border:1px solid #c084fc;
}

.green{
    background:linear-gradient(135deg,#f0fdf4,#ffffff);
    border:1px solid #86efac;
}

.orange{
    background:linear-gradient(135deg,#fff7ed,#ffffff);
    border:1px solid #fdba74;
}

.pink{
    background:linear-gradient(135deg,#fff1f2,#ffffff);
    border:1px solid #fb7185;
}

.blue{
    background:linear-gradient(135deg,#eff6ff,#ffffff);
    border:1px solid #93c5fd;
}

/* SECTIONS */
.section-box{
    background:white;
    border-radius:18px;
    padding:20px;
    margin-top:25px;
    box-shadow:0 6px 18px rgba(0,0,0,0.07);
}

.quick-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(120px,1fr));
    gap:15px;
}

.quick-item{
    text-decoration:none;
    color:#111827;
    padding:18px;
    border-radius:14px;
    text-align:center;
    background:#f8fafc;
    border:1px solid #e5e7eb;
    font-weight:600;
    transition:.3s;
}

.quick-item i{
    font-size:28px;
    display:block;
    margin-bottom:8px;
}

.quick-item:hover{
    background:#6d35f5;
    color:white;
}

.announcement-card{
    background:#fff;
    border:1px solid #e5e7eb;
    border-radius:15px;
    padding:18px;
    margin-bottom:12px;
}

.badge-soft{
    background:#ede9fe;
    color:#5b35f5;
    padding:6px 10px;
    border-radius:8px;
    font-size:13px;
    margin-right:5px;
    display:inline-block;
    margin-top:6px;
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

    .search-box{
        width:100%;
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
        <li><a href="dashboard.php" class="active"><i class="bi bi-house-door"></i><span>Dashboard</span></a></li>
        <li><a href="manage_users.php"><i class="bi bi-people"></i><span>Users</span></a></li>
        <li><a href="manage_departments.php"><i class="bi bi-building"></i><span>Departments</span></a></li>
        <li><a href="manage_students.php"><i class="bi bi-mortarboard"></i><span>Students</span></a></li>
        <li><a href="manage_teachers.php"><i class="bi bi-person-workspace"></i><span>Teachers</span></a></li>
        <li><a href="manage_subjects.php"><i class="bi bi-book"></i><span>Modules / Subjects</span></a></li>
        <li><a href="manage_courses.php"><i class="bi bi-journal-bookmark"></i><span>Courses</span></a></li>
        <li><a href="manage_enrollment.php"><i class="bi bi-card-checklist"></i><span>Enrollments</span></a></li>
        <li><a href="attendance_report.php"><i class="bi bi-calendar-check"></i><span>Attendance</span></a></li>
        <li><a href="performance_report.php"><i class="bi bi-bar-chart"></i><span>Performance</span></a></li>
        <li><a href="announcements.php"><i class="bi bi-megaphone"></i><span>Announcements</span></a></li>
        <!-- NEW MY PROFILE NAVIGATION TAB LINK -->
        <li><a href="profile.php"><i class="bi bi-person-gear"></i><span>My Profile</span></a></li>
        <li><a href="../public/login.php"><i class="bi bi-box-arrow-right"></i><span>Logout</span></a></li>
    </ul>

    <!-- DYNAMIC LOGGED-IN PROFILE ACTION BOX BUTTON -->
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
                <h1 class="fw-bold mb-1">Admin Dashboard</h1>
                <p class="text-muted mb-0">Welcome back! Here's what's happening in your LMS.</p>
            </div>
        </div>

        <div>
            <input type="text" class="search-box" placeholder="Search anything...">
            <div class="date-box mt-2">
                <i class="bi bi-calendar-event"></i>
                <?= date("l, F d, Y") ?>
            </div>
        </div>
    </div>

    <!-- STATISTICS -->
    <div class="row g-4">

        <div class="col-md-3">
            <div class="stat-card purple">
                <div class="stat-icon bg-white text-primary">
                    <i class="bi bi-people-fill"></i>
                </div>
                <h2><?= $users ?></h2>
                <p>Total Users</p>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-card green">
                <div class="stat-icon bg-white text-success">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                <h2><?= $students ?></h2>
                <p>Students</p>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-card orange">
                <div class="stat-icon bg-white text-warning">
                    <i class="bi bi-person-workspace"></i>
                </div>
                <h2><?= $teachers ?></h2>
                <p>Teachers</p>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-card pink">
                <div class="stat-icon bg-white text-danger">
                    <i class="bi bi-journal-bookmark-fill"></i>
                </div>
                <h2><?= $courses ?></h2>
                <p>Courses</p>
            </div>
        </div>

        <div class="col-md-6">
            <div class="stat-card blue">
                <div class="stat-icon bg-white text-primary">
                    <i class="bi bi-card-checklist"></i>
                </div>
                <h2><?= $enrollments ?></h2>
                <p>Enrollments</p>
            </div>
        </div>

        <div class="col-md-6">
            <div class="stat-card purple">
                <div class="stat-icon bg-white text-primary">
                    <i class="bi bi-megaphone-fill"></i>
                </div>
                <h2><?= $announcements ?></h2>
                <p>Announcements</p>
            </div>
        </div>

    </div>

    <!-- QUICK MENU -->
    <div class="section-box">
        <h4 class="mb-3"><i class="bi bi-lightning-charge-fill text-primary"></i> Quick Menu</h4>

        <div class="quick-grid">
            <a href="manage_users.php" class="quick-item"><i class="bi bi-people"></i>Users</a>
            <a href="manage_departments.php" class="quick-item"><i class="bi bi-building"></i>Departments</a>
            <a href="manage_students.php" class="quick-item"><i class="bi bi-mortarboard"></i>Students</a>
            <a href="manage_teachers.php" class="quick-item"><i class="bi bi-person-workspace"></i>Teachers</a>
            <a href="manage_subjects.php" class="quick-item"><i class="bi bi-book"></i>Subjects</a>
            <a href="manage_courses.php" class="quick-item"><i class="bi bi-journal-bookmark"></i>Courses</a>
            <a href="manage_enrollment.php" class="quick-item"><i class="bi bi-card-checklist"></i>Enrollments</a>
            <a href="attendance_report.php" class="quick-item"><i class="bi bi-calendar-check"></i>Attendance</a>
            <!-- <a href="performance_report.php" class="quick-item"><i class="bi bi-bar-chart"></i>Performance</a> -->
            <a href="announcements.php" class="quick-item"><i class="bi bi-megaphone"></i>Announcements</a>
        </div>
    </div>

    <!-- LATEST ANNOUNCEMENTS -->
    <div class="section-box">
        <h4 class="mb-3"><i class="bi bi-megaphone-fill text-primary"></i> Latest Announcements</h4>

        <?php
        $latest = $conn->query("
        SELECT 
            a.Title,
            a.Message,
            a.CreatedDate,
            u.full_name,
            c.CourseTitle,
            d.DepartmentName
        FROM Announcement a
        JOIN users u ON a.UserID = u.user_id
        JOIN Course c ON a.CourseID = c.CourseID
        JOIN Subject s ON c.SubjectID = s.SubjectID
        JOIN Department d ON s.DepartmentID = d.DepartmentID
        ORDER BY a.CreatedDate DESC
        LIMIT 3
        ");

        if ($latest->num_rows == 0) {
            echo "<p class='text-muted'>No announcements available.</p>";
        } else {
            while ($row = $latest->fetch_assoc()) {
                echo "
                <div class='announcement-card'>
                    <h5>".htmlspecialchars($row['Title'])."</h5>
                    <p>".htmlspecialchars($row['Message'])."</p>

                    <span class='badge-soft'><i class='bi bi-book'></i> Course: ".htmlspecialchars($row['CourseTitle'])."</span>
                    <span class='badge-soft'><i class='bi bi-building'></i> Department: ".htmlspecialchars($row['DepartmentName'])."</span>
                    <span class='badge-soft'><i class='bi bi-person'></i> Posted By: ".htmlspecialchars($row['full_name'])."</span>
                    <span class='badge-soft'><i class='bi bi-calendar'></i> Date: ".$row['CreatedDate']."</span>
                </div>";
            }
        }
        ?>

    </div>

</main>

</div>

<script>
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
</script>

</body>
</html>