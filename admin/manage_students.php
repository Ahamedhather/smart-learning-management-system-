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

/* FETCH STUDENTS WITH USER AND DEPARTMENT DETAILS */
$result = $conn->query("
SELECT s.StudentID, s.RegistrationNumber, s.EnrollmentYear, s.Status,
       u.full_name, d.DepartmentName
FROM Student s
JOIN users u ON s.UserID = u.user_id
LEFT JOIN Department d ON s.DepartmentID = d.DepartmentID
ORDER BY s.StudentID DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Management | A too Z LMS</title>
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

/* SIDEBAR */
.sidebar {
    width: 270px;
    height: 100vh;
    background: #fff;
    padding: 25px 20px;
    position: fixed;
    left: 0;
    top: 0;
    box-shadow: 0 0 25px rgba(0,0,0,0.08);
    overflow-y: auto;
    overflow-x: hidden;
    transition: all .3s ease;
    z-index: 1000;
}

.sidebar::-webkit-scrollbar {
    width: 6px;
}

.sidebar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}

.sidebar.collapsed {
    width: 85px;
}

.logo {
    font-size: 23px;
    font-weight: 700;
    color: #5b35f5;
    margin-bottom: 30px;
    white-space: nowrap;
}

.logo i {
    margin-right: 8px;
}

.sidebar.collapsed .logo span {
    display: none;
}

.menu {
    list-style: none;
    padding: 0;
    margin: 0;
}

.menu li {
    margin-bottom: 9px;
}

.menu a {
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 13px;
    color: #1f2937;
    padding: 13px 15px;
    border-radius: 13px;
    font-weight: 500;
    white-space: nowrap;
}

.menu a i {
    font-size: 20px;
    min-width: 22px;
    text-align: center;
}

.menu a:hover,
.menu a.active {
    background: linear-gradient(135deg, #6d35f5, #8b5cf6);
    color: white;
}

.sidebar.collapsed .menu a span {
    display: none;
}

.sidebar.collapsed .menu a {
    justify-content: center;
    padding: 14px 0;
}

.admin-box {
    margin-top: 30px;
    background: #f8fafc;
    padding: 15px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    transition: all 0.2s ease;
}

.admin-box:hover {
    background: #f1f5f9;
}

.admin-box i {
    font-size: 35px;
    color: #5b35f5;
}

.sidebar.collapsed .admin-box div {
    display: none;
}

.sidebar.collapsed .admin-box {
    justify-content: center;
    padding: 12px;
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
    padding: 25px;
    margin-top: 25px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.07);
}

.search-box {
    width: 100%;
    max-width: 380px;
    border: 1px solid #d1d5db;
    border-radius: 12px;
    padding: 11px 16px;
    background-color: white;
    transition: all 0.2s;
}

.search-box:focus {
    border-color: #6d35f5;
    box-shadow: 0 0 0 4px rgba(109, 53, 245, 0.1);
    outline: none;
}

.btn-gradient-success {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    font-weight: 600;
    border: none;
    border-radius: 10px;
    padding: 10px 20px;
}

.btn-gradient-success:hover {
    color: white;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    transform: translateY(-1px);
}

.custom-table th {
    background-color: #f8fafc;
    color: #4b5563;
    font-weight: 600;
    border-bottom: 2px solid #e2e8f0;
    padding: 15px;
}

.custom-table td {
    padding: 15px;
    vertical-align: middle;
}

.badge-status {
    padding: 6px 12px;
    border-radius: 30px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
}

.badge-active {
    background-color: #def7ec;
    color: #03543f;
}

.badge-inactive {
    background-color: #fde8e8;
    color: #9b1c1c;
}

/* RESPONSIVE */
@media(max-width:900px) {
    #mobileMenuToggle {
        display: block !important;
    }

    .sidebar {
        left: -280px;
    }

    .sidebar.mobile-open {
        left: 0;
    }

    .sidebar.collapsed {
        width: 270px;
    }

    .sidebar.collapsed .logo span,
    .sidebar.collapsed .menu a span,
    .sidebar.collapsed .admin-box div {
        display: inline;
    }

    .sidebar.collapsed .menu a {
        justify-content: flex-start;
        padding: 13px 15px;
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

    .search-box {
        max-width: 100%;
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
            <!-- Mobile Toggle Button (Only displays on small screens) -->
            <button id="mobileMenuToggle" class="toggle-btn">
                <i class="bi bi-list"></i>
            </button>
            <div>
                <h1 class="fw-bold mb-1">Student Directory</h1>
                <p class="text-muted mb-0">Manage student profiles, enrollments, registrations, and accounts.</p>
            </div>
        </div>
        <div>
            <a href="add_student.php" class="btn btn-gradient-success">
                <i class="bi bi-plus-circle me-2"></i>Add Student
            </a>
        </div>
    </div>

    <!-- TABLE CONTROL OPTIONS -->
    <div class="row align-items-center justify-content-between g-3 mt-1">
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text border-end-0 bg-white" style="border-radius:12px 0 0 12px; border-color:#d1d5db;"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="searchInput" class="form-control border-start-0 search-box" style="border-radius:0 12px 12px 0;" placeholder="Search students by name, registration ID, department...">
            </div>
        </div>
    </div>

    <!-- DATA CONTAINER CARD -->
    <div class="section-box">
        <div class="table-responsive">
            <table class="table custom-table table-hover alignment-middle" id="studentTable">
                <thead>
                    <tr>
                        <th style="width: 8%;">ID</th>
                        <th style="width: 25%;">Student Name</th>
                        <th style="width: 18%;">Reg. Number</th>
                        <th style="width: 20%;">Department</th>
                        <th style="width: 12%;">Enroll Year</th>
                        <th style="width: 10%;">Status</th>
                        <th style="width: 15%;" class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($result && $result->num_rows > 0) { ?>
                    <?php while($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td class="fw-bold text-secondary">#<?= $row['StudentID'] ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-light text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 14px;">
                                    <i class="bi bi-person"></i>
                                </div>
                                <span class="fw-semibold text-dark"><?= htmlspecialchars($row['full_name']) ?></span>
                            </div>
                        </td>
                        <td><code class="text-secondary"><?= htmlspecialchars($row['RegistrationNumber']) ?></code></td>
                        <td><span class="text-muted"><?= htmlspecialchars($row['DepartmentName'] ?? 'N/A') ?></span></td>
                        <td class="text-dark fw-medium"><?= htmlspecialchars($row['EnrollmentYear']) ?></td>
                        <td>
                            <?php if ($row['Status'] == 'Active') { ?>
                                <span class="badge-status badge-active"><i class="bi bi-check2-circle me-1"></i>Active</span>
                            <?php } else { ?>
                                <span class="badge-status badge-inactive"><i class="bi bi-x-circle me-1"></i>Inactive</span>
                            <?php } ?>
                        </td>
                        <td>
                            <div class="d-flex justify-content-center gap-2">
                                <a href="edit_student.php?id=<?= $row['StudentID'] ?>" class="btn btn-outline-primary btn-sm rounded-3 px-3">
                                    <i class="bi bi-pencil-square me-1"></i> Edit
                                </a>
                                <a href="delete_student.php?id=<?= $row['StudentID'] ?>" 
                                   class="btn btn-outline-danger btn-sm rounded-3 px-3"
                                   onclick="return confirm('Are you sure you want to delete this student profile?')">
                                    <i class="bi bi-trash me-1"></i> Delete
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No student profiles discovered in directory inventory.</td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

</main>

</div>

<script>
/* REAL-TIME FILTER LOGIC */
document.getElementById("searchInput").addEventListener("keyup", function() {
    let value = this.value.toLowerCase();
    let rows = document.querySelectorAll("#studentTable tbody tr");

    rows.forEach(row => {
        // Skip filtering if row displays the "no students found" fallback
        if(row.cells.length > 1) {
            row.style.display = row.innerText.toLowerCase().includes(value) ? "" : "none";
        }
    });
});

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
</script>

</body>
</html>