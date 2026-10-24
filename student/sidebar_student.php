<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Active Page Detector
$current_page = basename($_SERVER['PHP_SELF']);

// Determine student user ID for unread counts
$my_id = $_SESSION['user_id'] ?? $_SESSION['student_id'] ?? $_SESSION['user']['user_id'] ?? null;

$total_unread_messages = 0;
if ($my_id && isset($conn)) {
    $unread_count_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM message WHERE ReceiverID = ? AND ReadStatus = 'Unread'");
    $unread_count_stmt->bind_param("i", $my_id);
    $unread_count_stmt->execute();
    $unread_res = $unread_count_stmt->get_result()->fetch_assoc();
    $total_unread_messages = $unread_res['total'] ?? 0;
}

// --- DYNAMIC STUDENT DISPLAY DATA FROM LOGIN SESSION ---
$student_name  = 'Student';
$student_email = 'Student Account';

// 1. Check if login session uses an array (e.g., $_SESSION['user']['name'])
if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
    $student_name  = $_SESSION['user']['full_name'] 
                  ?? $_SESSION['user']['name'] 
                  ?? $_SESSION['user']['username'] 
                  ?? $_SESSION['user']['first_name'] 
                  ?? 'Student';

    $student_email = $_SESSION['user']['email'] 
                  ?? 'Student Account';
} 
// 2. Direct session key checks (e.g., $_SESSION['user'], $_SESSION['student_name'], etc.)
else {
    $student_name = $_SESSION['student_name'] 
                   ?? $_SESSION['full_name'] 
                   ?? $_SESSION['name'] 
                   ?? $_SESSION['username'] 
                   ?? $_SESSION['user_name'] 
                   ?? (is_string($_SESSION['user'] ?? null) ? $_SESSION['user'] : null)
                   ?? 'Student';

    $student_email = $_SESSION['student_email'] 
                  ?? $_SESSION['email'] 
                  ?? 'Student Account';
}

// Generate Initials Avatar
$words = explode(" ", trim($student_name));
$initials = "";
foreach ($words as $w) {
    if (!empty($w)) {
        $initials .= strtoupper($w[0]);
    }
}
$initials = substr($initials, 0, 2) ?: 'ST';
?>

<!-- SIDEBAR COMPONENT STYLES -->
<style>
.sidebar {
    width: 290px;
    background: #0f172a; /* Dark Navy Theme */
    color: #f8fafc;
    height: 100vh;
    display: flex;
    flex-direction: column;
    position: fixed;
    top: 0;
    left: 0;
    z-index: 1050;
    transition: all 0.3s ease;
    box-shadow: 4px 0 15px rgba(0,0,0,0.15);
}

.sidebar.collapsed {
    width: 105px;
}

.sidebar.collapsed .sidebar-text,
.sidebar.collapsed .sidebar-profile-info {
    display: none !important;
}

.sidebar.collapsed .sidebar-brand {
    justify-content: center !important;
    padding: 20px 0 !important;
}

.sidebar.collapsed .nav-link {
    justify-content: center !important;
    padding: 14px 0 !important;
}

/* BRAND HEADER */
.sidebar-brand {
    padding: 20px 24px;
    display: flex;
    align-items: center;
    gap: 14px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    flex-shrink: 0;
}

.sidebar-brand-icon {
    width: 42px;
    height: 42px;
    background: linear-gradient(135deg, #6d35f5, #8b5cf6);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    color: white;
}

/* NAVIGATION MENU (Strict 1-Column Layout) */
.sidebar-nav {
    padding: 16px 14px;
    flex: 1 1 auto;
    overflow-y: auto;
    display: flex !important;
    flex-direction: column !important;
    flex-wrap: nowrap !important;
    width: 100%;
}

/* Custom Scrollbar */
.sidebar-nav::-webkit-scrollbar {
    width: 5px;
}
.sidebar-nav::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.15);
    border-radius: 10px;
}

.sidebar-nav .nav-item {
    width: 100% !important;
    margin-bottom: 4px;
}

.sidebar .nav-link {
    color: #94a3b8;
    padding: 11px 16px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 14px;
    font-weight: 500;
    font-size: 15px;
    text-decoration: none;
    transition: all 0.2s ease;
    width: 100%;
    position: relative;
}

.sidebar .nav-link i {
    font-size: 20px;
    transition: transform 0.2s ease;
}

.sidebar .nav-link:hover {
    color: #ffffff;
    background: rgba(255, 255, 255, 0.06);
}

.sidebar .nav-link.active {
    color: #ffffff;
    background: linear-gradient(135deg, #6d35f5, #8b5cf6);
    box-shadow: 0 4px 12px rgba(109, 53, 245, 0.35);
}

/* Red Hover Effect for Logout */
.sidebar .nav-link.logout-link {
    color: #f87171 !important;
}

.sidebar .nav-link.logout-link:hover {
    background: rgba(239, 68, 68, 0.15) !important;
    color: #ef4444 !important;
}

/* Text Visibility Fixes */
.sidebar .text-subhead {
    color: #94a3b8 !important;
}

/* FIXED FOOTER */
.sidebar-footer {
    padding: 16px 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(0, 0, 0, 0.25);
    flex-shrink: 0;
}

.sidebar-profile {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

/* CLICKABLE PROFILE LINK */
.sidebar-profile-link {
    display: flex;
    align-items: center;
    gap: 10px;
    overflow: hidden;
    text-decoration: none !important;
    transition: opacity 0.2s ease;
    cursor: pointer;
}

.sidebar-profile-link:hover {
    opacity: 0.85;
}

.profile-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6d35f5, #8b5cf6);
    color: white;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    flex-shrink: 0;
}

/* Mobile Sidebar Drawer */
@media(max-width: 900px) {
    .sidebar {
        margin-left: -290px;
    }
    .sidebar.mobile-open {
        margin-left: 0;
    }
}
</style>

<!-- SIDEBAR HTML NAVIGATION -->
<aside class="sidebar" id="sidebar">

    <!-- BRAND LOGO HEADER -->
    <div class="sidebar-brand">
        <div class="sidebar-brand-icon">
            <i class="bi bi-mortarboard-fill"></i>
        </div>
        <div class="sidebar-text">
            <h5 class="fw-bold mb-0 text-white">LMS Portal</h5>
            <small class="text-subhead">Student Panel</small>
        </div>
    </div>

    <!-- SINGLE-COLUMN MENU -->
    <ul class="nav sidebar-nav">

        <li class="nav-item">
            <a href="dashboard.php" class="nav-link <?= ($current_page == 'dashboard.php') ? 'active' : '' ?>">
                <i class="bi bi-grid-1x2-fill"></i>
                <span class="sidebar-text">Dashboard</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="my_courses.php" class="nav-link <?= ($current_page == 'my_courses.php') ? 'active' : '' ?>">
                <i class="bi bi-journal-bookmark-fill"></i>
                <span class="sidebar-text">My Courses</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="quiz_list.php" class="nav-link <?= ($current_page == 'quiz_list.php') ? 'active' : '' ?>">
                <i class="bi bi-patch-question-fill"></i>
                <span class="sidebar-text">Quizzes</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="attendance.php" class="nav-link <?= ($current_page == 'attendance.php') ? 'active' : '' ?>">
                <i class="bi bi-calendar-check-fill"></i>
                <span class="sidebar-text">Attendance</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="results.php" class="nav-link <?= ($current_page == 'results.php') ? 'active' : '' ?>">
                <i class="bi bi-trophy-fill"></i>
                <span class="sidebar-text">Results</span>
            </a>
        </li>

        <!-- UPDATED CHAT / MESSAGING LINK WITH UNREAD NOTIFICATION BADGE -->
        <li class="nav-item">
            <a href="chat.php" class="nav-link <?= ($current_page == 'chat.php') ? 'active' : '' ?>">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-chat-dots-fill"></i>
                    <span class="sidebar-text">Messages</span>
                </div>
                <?php if ($total_unread_messages > 0): ?>
                    <span class="badge bg-danger rounded-pill ms-auto sidebar-text"><?= $total_unread_messages ?></span>
                <?php endif; ?>
            </a>
        </li>

        <li class="nav-item">
            <a href="announcements.php" class="nav-link <?= ($current_page == 'announcements.php') ? 'active' : '' ?>">
                <i class="bi bi-megaphone-fill"></i>
                <span class="sidebar-text">Announcements</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="profile.php" class="nav-link <?= ($current_page == 'profile.php') ? 'active' : '' ?>">
                <i class="bi bi-person-circle"></i>
                <span class="sidebar-text">My Profile</span>
            </a>
        </li>

        <!-- LOGOUT LINK -->
        <li class="nav-item border-top border-secondary border-opacity-25 pt-2 mt-2">
            <a href="../public/login.php" class="nav-link logout-link">
                <i class="bi bi-box-arrow-right"></i>
                <span class="sidebar-text fw-semibold">Logout</span>
            </a>
        </li>

    </ul>

    <!-- USER PROFILE FOOTER -->
    <div class="sidebar-footer">
        <div class="sidebar-profile">
            <!-- CLICKABLE PROFILE AREA -->
            <a href="profile.php" class="sidebar-profile-link" title="Open My Profile">
                <div class="profile-avatar">
                    <?= htmlspecialchars($initials) ?>
                </div>
                <div class="sidebar-profile-info overflow-hidden">
                    <h6 class="mb-0 text-white text-truncate fw-semibold" style="max-width: 120px;">
                        <?= htmlspecialchars($student_name) ?>
                    </h6>
                    <small class="text-subhead text-truncate d-block" style="max-width: 120px;">
                        <?= htmlspecialchars($student_email) ?>
                    </small>
                </div>
            </a>

            <!-- FOOTER QUICK LOGOUT ICON -->
            <a href="../public/login.php" class="btn btn-sm p-1 text-danger sidebar-text" title="Logout">
                <i class="bi bi-box-arrow-right fs-5"></i>
            </a>
        </div>
    </div>

</aside>