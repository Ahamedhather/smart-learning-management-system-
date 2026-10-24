<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Identify the current page filename to dynamically highlight the active menu item
$current_page = basename($_SERVER['PHP_SELF']);

// Automatically fetch logged-in user profile details if not already loaded
if (!isset($current_user) && isset($_SESSION['user_id'])) {
    if (isset($conn)) {
        $user_id = intval($_SESSION['user_id']);
        $stmt = $conn->prepare("SELECT full_name, role FROM users WHERE user_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user_res = $stmt->get_result();
            if ($user_data = $user_res->fetch_assoc()) {
                $current_user = $user_data;
            }
        }
    } elseif (isset($_SESSION['full_name'])) {
        $current_user = [
            'full_name' => $_SESSION['full_name'],
            'role' => $_SESSION['role'] ?? 'User'
        ];
    }
}
?>

<!-- SIDEBAR STYLES (Self-contained) -->
<style>
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
    transition: all 0.2s ease;
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

/* RESPONSIVE LAYOUT BEHAVIOR */
@media(max-width:900px) {
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
}
</style>

<!-- SIDEBAR MARKUP -->
<aside class="sidebar" id="sidebar">

    <div class="logo">
        <i class="bi bi-mortarboard-fill"></i>
        <span>A to Z LMS</span>
    </div>

    <ul class="menu">
        <li>
            <a href="dashboard.php" class="<?= ($current_page == 'dashboard.php') ? 'active' : '' ?>">
                <i class="bi bi-house-door"></i><span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="manage_users.php" class="<?= ($current_page == 'manage_users.php') ? 'active' : '' ?>">
                <i class="bi bi-people"></i><span>Users</span>
            </a>
        </li>
        <li>
            <a href="manage_departments.php" class="<?= ($current_page == 'manage_departments.php') ? 'active' : '' ?>">
                <i class="bi bi-building"></i><span>Departments</span>
            </a>
        </li>
        <li>
            <a href="manage_students.php" class="<?= in_array($current_page, ['manage_students.php', 'add_student.php', 'edit_student.php']) ? 'active' : '' ?>">
                <i class="bi bi-mortarboard"></i><span>Students</span>
            </a>
        </li>
        <li>
            <a href="manage_teachers.php" class="<?= ($current_page == 'manage_teachers.php') ? 'active' : '' ?>">
                <i class="bi bi-person-workspace"></i><span>Teachers</span>
            </a>
        </li>
        <li>
            <a href="manage_subjects.php" class="<?= ($current_page == 'manage_subjects.php') ? 'active' : '' ?>">
                <i class="bi bi-book"></i><span>Modules / Subjects</span>
            </a>
        </li>
        <li>
            <a href="manage_courses.php" class="<?= ($current_page == 'manage_courses.php') ? 'active' : '' ?>">
                <i class="bi bi-journal-bookmark"></i><span>Courses</span>
            </a>
        </li>
        <li>
            <a href="manage_enrollment.php" class="<?= ($current_page == 'manage_enrollment.php') ? 'active' : '' ?>">
                <i class="bi bi-card-checklist"></i><span>Enrollments</span>
            </a>
        </li>
        <li>
            <a href="attendance_report.php" class="<?= ($current_page == 'attendance_report.php') ? 'active' : '' ?>">
                <i class="bi bi-calendar-check"></i><span>Attendance</span>
            </a>
        </li>
        <li>
            <a href="performance_report.php" class="<?= ($current_page == 'performance_report.php') ? 'active' : '' ?>">
                <i class="bi bi-bar-chart"></i><span>Performance</span>
            </a>
        </li>
        <li>
            <a href="announcements.php" class="<?= ($current_page == 'announcements.php') ? 'active' : '' ?>">
                <i class="bi bi-megaphone"></i><span>Announcements</span>
            </a>
        </li>
        <li>
            <a href="profile.php" class="<?= ($current_page == 'profile.php') ? 'active' : '' ?>">
                <i class="bi bi-person-gear"></i><span>My Profile</span>
            </a>
        </li>
        <li>
            <a href="../public/login.php">
                <i class="bi bi-box-arrow-right"></i><span>Logout</span>
            </a>
        </li>
    </ul>

    <div class="admin-box" style="cursor: pointer;" onclick="window.location.href='profile.php';">
        <i class="bi bi-person-circle"></i>
        <div>
            <strong><?= htmlspecialchars($current_user['full_name'] ?? 'Guest User') ?></strong><br>
            <small class="text-capitalize"><?= htmlspecialchars($current_user['role'] ?? 'User') ?></small>
        </div>
    </div>

</aside>

<!-- SIDEBAR COLLAPSE SCRIPT -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const toggleBtn = document.getElementById("menuToggle");
    const sidebar = document.getElementById("sidebar");
    const content = document.getElementById("content");

    if (toggleBtn && sidebar && content) {
        toggleBtn.addEventListener("click", function() {
            if (window.innerWidth <= 900) {
                sidebar.classList.toggle("mobile-open");
            } else {
                sidebar.classList.toggle("collapsed");
                content.classList.toggle("expanded");
            }
        });
    }
});
</script>