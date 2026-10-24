<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Active page identification
$current_page = basename($_SERVER['PHP_SELF']);

// Default fallback values from session
$teacher_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Teacher Account';
$teacher_role =$_SESSION['role'] ?? 'Teacher';

// Fetch actual full_name directly from 'users' table using session user_id
if (isset($conn) && isset($_SESSION['user_id'])) {
    $u_id = intval($_SESSION['user_id']);
    $stmt =$conn->prepare("SELECT full_name, role FROM users WHERE user_id = ?");
    if ($stmt) {$stmt->bind_param("i", $u_id);$stmt->execute();
        $res =$stmt->get_result();
        if ($row =$res->fetch_assoc()) {
            if (!empty($row['full_name'])) {
                $teacher_name =$row['full_name'];
            }
            if (!empty($row['role'])) {
                $teacher_role =$row['role'];
            }
        }
        $stmt->close();
    }
}
?>

<!-- SIDEBAR STYLES -->
<style>
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
    margin-bottom: 25px;
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: 10px;
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
    margin-bottom: 8px;
}

.menu a {
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 13px;
    color: #1f2937;
    padding: 12px 15px;
    border-radius: 12px;
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
    margin-top: 25px;
    background: #f8fafc;
    padding: 14px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    transition: all 0.2s ease;
}

.admin-box:hover {
    background: #f1f5f9;
}

.admin-box i {
    font-size: 32px;
    color: #5b35f5;
}

.sidebar.collapsed .admin-box div {
    display: none;
}

.sidebar.collapsed .admin-box {
    justify-content: center;
    padding: 12px;
}

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
            <a href="dashboard.php" class="<?= in_array($current_page, ['dashboard.php']) ? 'active' : '' ?>">
                <i class="bi bi-house-door"></i><span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="my_courses.php" class="<?= ($current_page == 'my_courses.php') ? 'active' : '' ?>">
                <i class="bi bi-journal-bookmark"></i><span>My Courses</span>
            </a>
        </li>
        <li>
            <a href="attendance_courses.php" class="<?= in_array($current_page, ['attendance_courses.php', 'mark_attendance.php', 'view_attendance.php']) ? 'active' : '' ?>">
                <i class="bi bi-calendar-check"></i><span>Attendance</span>
            </a>
        </li>
        <li>
            <a href="view_materials.php" class="<?= in_array($current_page, ['view_materials.php', 'upload_material.php']) ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-text"></i><span>Study Materials</span>
            </a>
        </li>
        <li>
            <a href="grade_submission.php" class="<?= in_array($current_page, ['grade_submission.php', 'add_assignment.php']) ? 'active' : '' ?>">
                <i class="bi bi-card-checklist"></i><span>Assignments</span>
            </a>
        </li>
        <li>
            <!-- Updated to point to chat.php -->
            <a href="chat.php" class="<?= in_array($current_page, ['chat.php', 'send_message.php']) ? 'active' : '' ?>">
                <i class="bi bi-chat-dots"></i><span>Messages</span>
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

    <!-- TEACHER USER PROFILE CARD -->
    <div class="admin-box" style="cursor: pointer;" onclick="window.location.href='profile.php';">
        <i class="bi bi-person-circle"></i>
        <div>
            <strong><?= htmlspecialchars($teacher_name) ?></strong><br>
            <small class="text-capitalize text-muted"><?= htmlspecialchars($teacher_role) ?></small>
        </div>
    </div>

</aside>

<!-- SIDEBAR TOGGLE SCRIPT -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const toggleBtn = document.getElementById("mobileMenuToggle") || document.getElementById("menuToggle");
    const sidebar = document.getElementById("sidebar");
    const content = document.getElementById("content");

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener("click", function() {
            if (window.innerWidth <= 900) {
                sidebar.classList.toggle("mobile-open");
            } else if (content) {
                sidebar.classList.toggle("collapsed");
                content.classList.toggle("expanded");
            }
        });
    }
});
</script>