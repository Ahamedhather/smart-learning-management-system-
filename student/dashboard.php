<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("../config/db.php");

// 🔒 SECURITY & AUTH CHECK
if (!isset($_SESSION['student_id']) || !isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$student_id = intval($_SESSION['student_id']);
$user_id    = intval($_SESSION['user_id']);

// ==========================================
// PREPARED METRIC QUERIES
// ==========================================

// 1. TOTAL ENROLLED COURSES
$stmt_courses = $conn->prepare("SELECT COUNT(*) as total FROM Enrollment WHERE StudentID = ?");
$stmt_courses->bind_param("i", $student_id);
$stmt_courses->execute();
$courses = $stmt_courses->get_result()->fetch_assoc()['total'] ?? 0;

// 2. TOTAL ASSIGNMENTS
$stmt_assign = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM Assignment a
    JOIN Enrollment e ON a.CourseID = e.CourseID
    WHERE e.StudentID = ?
");
$stmt_assign->bind_param("i", $student_id);
$stmt_assign->execute();
$assignments = $stmt_assign->get_result()->fetch_assoc()['total'] ?? 0;

// 3. TOTAL QUIZZES
$stmt_quizzes = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM Quiz q
    JOIN Enrollment e ON q.CourseID = e.CourseID
    WHERE e.StudentID = ?
");
$stmt_quizzes->bind_param("i", $student_id);
$stmt_quizzes->execute();
$quizzes = $stmt_quizzes->get_result()->fetch_assoc()['total'] ?? 0;

// 4. ATTENDANCE %
$stmt_att = $conn->prepare("
    SELECT COUNT(*) as total,
           SUM(CASE WHEN Status='Present' THEN 1 ELSE 0 END) as present
    FROM Attendance 
    WHERE StudentID = ?
");
$stmt_att->bind_param("i", $student_id);
$stmt_att->execute();
$att = $stmt_att->get_result()->fetch_assoc();
$attendance = ($att['total'] > 0) ? round(($att['present'] / $att['total']) * 100) : 0;

// 5. AVERAGE MARKS
$stmt_marks = $conn->prepare("
    SELECT AVG(Marks) as avg_marks 
    FROM AssignmentSubmission 
    WHERE StudentID = ?
");
$stmt_marks->bind_param("i", $student_id);
$stmt_marks->execute();
$avg_raw = $stmt_marks->get_result()->fetch_assoc()['avg_marks'];
$marks = ($avg_raw !== null) ? round($avg_raw) : 0;

// 6. UNREAD MESSAGES
$stmt_msg = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM Message 
    WHERE ReceiverID = ? AND ReadStatus = 'Unread'
");
$stmt_msg->bind_param("i", $user_id);
$stmt_msg->execute();
$msg = $stmt_msg->get_result()->fetch_assoc()['total'] ?? 0;

// 7. ANNOUNCEMENTS
$stmt_ann = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM Announcement 
    WHERE CourseID IN (SELECT CourseID FROM Enrollment WHERE StudentID = ?)
");
$stmt_ann->bind_param("i", $student_id);
$stmt_ann->execute();
$ann = $stmt_ann->get_result()->fetch_assoc()['total'] ?? 0;

// Student Display Name
$student_name = $_SESSION['full_name'] ?? 'Student';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Dashboard | LMS</title>
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

.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
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

#mobileMenuToggle {
    display: none;
}

/* STAT CARDS */
.stat-card {
    border: none;
    border-radius: 18px;
    padding: 24px;
    color: white;
    box-shadow: 0 8px 20px rgba(0,0,0,0.06);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 25px rgba(0,0,0,0.1);
}

.stat-card.primary { background: linear-gradient(135deg, #6d35f5, #8b5cf6); }
.stat-card.success { background: linear-gradient(135deg, #10b981, #059669); }
.stat-card.warning { background: linear-gradient(135deg, #f59e0b, #d97706); }
.stat-card.danger  { background: linear-gradient(135deg, #ef4444, #dc2626); }

.stat-card .icon-box {
    width: 50px;
    height: 50px;
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

/* SECTION CARDS */
.section-card {
    background: white;
    border-radius: 18px;
    padding: 28px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.04);
    height: 100%;
}

/* QUICK ACTION TILES */
.action-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 20px;
    text-align: center;
    color: #1e293b;
    text-decoration: none;
    display: block;
    transition: all 0.2s ease;
}

.action-card:hover {
    border-color: #6d35f5;
    background: #f4f0ff;
    color: #6d35f5;
    transform: translateY(-3px);
}

.action-card i {
    font-size: 28px;
    margin-bottom: 8px;
    display: block;
}

@media(max-width:900px) {
    #mobileMenuToggle {
        display: block !important;
    }
    .content, .content.expanded {
        margin-left: 0;
        width: 100%;
        padding: 20px;
    }
}
</style>
</head>

<body>

<div class="wrapper">

    <!-- SIDEBAR INCLUDE -->
    <?php 
        if (file_exists("sidebar_student.php")) {
            include("sidebar_student.php"); 
        }
    ?>

    <!-- MAIN CONTENT AREA -->
    <main class="content" id="content">

        <!-- TOPBAR -->
        <div class="topbar">
            <div class="d-flex align-items-center gap-3">
                <button id="mobileMenuToggle" class="toggle-btn">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    <h1 class="fw-bold mb-1">🎓 Welcome, <?= htmlspecialchars($student_name) ?>!</h1>
                    <p class="text-muted mb-0">Here is your academic overview and quick actions for today.</p>
                </div>
            </div>
            <div>
                <!-- <a href="../login.php" class="btn btn-outline-danger rounded-3 px-3">
                    <i class="bi bi-box-arrow-right me-1"></i> Logout
                </a> -->
            </div>
        </div>

        <!-- METRIC STAT CARDS -->
        <div class="row g-4 mb-4">
            <!-- MY COURSES -->
            <div class="col-xl-3 col-md-6">
                <div class="stat-card primary">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="fw-bold mb-1"><?= $courses ?></h2>
                            <p class="mb-0 text-white-50 small fw-medium">Enrolled Courses</p>
                        </div>
                        <div class="icon-box">
                            <i class="bi bi-journal-bookmark-fill"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ASSIGNMENTS -->
            <div class="col-xl-3 col-md-6">
                <div class="stat-card success">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="fw-bold mb-1"><?= $assignments ?></h2>
                            <p class="mb-0 text-white-50 small fw-medium">Assignments</p>
                        </div>
                        <div class="icon-box">
                            <i class="bi bi-file-earmark-text-fill"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- QUIZZES -->
            <div class="col-xl-3 col-md-6">
                <div class="stat-card warning">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="fw-bold mb-1"><?= $quizzes ?></h2>
                            <p class="mb-0 text-white-50 small fw-medium">Available Quizzes</p>
                        </div>
                        <div class="icon-box">
                            <i class="bi bi-patch-question-fill"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ATTENDANCE -->
            <div class="col-xl-3 col-md-6">
                <div class="stat-card danger">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="fw-bold mb-1"><?= $attendance ?>%</h2>
                            <p class="mb-0 text-white-50 small fw-medium">Attendance Rate</p>
                        </div>
                        <div class="icon-box">
                            <i class="bi bi-calendar2-check-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ACADEMIC & COMMUNICATION SUMMARY -->
        <div class="row g-4 mb-4">
            <!-- ACADEMIC SUMMARY -->
            <div class="col-lg-6">
                <div class="section-card">
                    <h5 class="fw-bold mb-4 text-dark">
                        <i class="bi bi-bar-chart-line text-primary me-2"></i> Academic Summary
                    </h5>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-semibold text-secondary">Average Assignment Marks</span>
                            <span class="fw-bold text-dark"><?= $marks ?>%</span>
                        </div>
                        <div class="progress" style="height: 10px; border-radius: 8px;">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: <?= min(100, max(0, $marks)) ?>%"></div>
                        </div>
                    </div>

                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-semibold text-secondary">Attendance Rate</span>
                            <span class="fw-bold text-dark"><?= $attendance ?>%</span>
                        </div>
                        <div class="progress" style="height: 10px; border-radius: 8px;">
                            <div class="progress-bar <?= $attendance >= 75 ? 'bg-success' : 'bg-danger' ?>" role="progressbar" style="width: <?= min(100, max(0, $attendance)) ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- COMMUNICATIONS -->
            <div class="col-lg-6">
                <div class="section-card">
                    <h5 class="fw-bold mb-4 text-dark">
                        <i class="bi bi-bell text-primary me-2"></i> Communication Overview
                    </h5>

                    <div class="d-flex align-items-center justify-content-between p-3 rounded-3 bg-light mb-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="p-2 bg-primary bg-opacity-10 text-primary rounded-3 fs-4">
                                <i class="bi bi-chat-dots-fill"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0">Unread Messages</h6>
                                <small class="text-muted">Messages from your instructors</small>
                            </div>
                        </div>
                        <span class="badge bg-primary rounded-pill px-3 py-2 fs-6"><?= $msg ?></span>
                    </div>

                    <div class="d-flex align-items-center justify-content-between p-3 rounded-3 bg-light">
                        <div class="d-flex align-items-center gap-3">
                            <div class="p-2 bg-warning bg-opacity-10 text-warning rounded-3 fs-4">
                                <i class="bi bi-megaphone-fill"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0">Course Announcements</h6>
                                <small class="text-muted">Updates from enrolled courses</small>
                            </div>
                        </div>
                        <span class="badge bg-warning text-dark rounded-pill px-3 py-2 fs-6"><?= $ann ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- QUICK ACTIONS GRID -->
        <div class="section-card">
            <h5 class="fw-bold mb-3 text-dark">
                <i class="bi bi-lightning-charge text-primary me-2"></i> Quick Actions
            </h5>

            <div class="row g-3">
                <div class="col-6 col-md-4 col-lg-3">
                    <a href="my_courses.php" class="action-card">
                        <i class="bi bi-book text-primary"></i>
                        <span class="fw-semibold">My Courses</span>
                    </a>
                </div>

                <div class="col-6 col-md-4 col-lg-3">
                    <a href="quiz_list.php" class="action-card">
                        <i class="bi bi-journal-check text-warning"></i>
                        <span class="fw-semibold">Quizzes</span>
                    </a>
                </div>

                <div class="col-6 col-md-4 col-lg-3">
                    <a href="attendance.php" class="action-card">
                        <i class="bi bi-calendar-check text-info"></i>
                        <span class="fw-semibold">Attendance</span>
                    </a>
                </div>

                <div class="col-6 col-md-4 col-lg-3">
                    <a href="results.php" class="action-card">
                        <i class="bi bi-trophy text-success"></i>
                        <span class="fw-semibold">Results</span>
                    </a>
                </div>

                <div class="col-6 col-md-4 col-lg-3">
                    <!-- <a href="inbox.php" class="action-card">
                        <i class="bi bi-inbox text-secondary"></i>
                        <span class="fw-semibold">Inbox</span>
                    </a> -->
                </div>

                <div class="col-6 col-md-4 col-lg-3">
                    <a href="chat.php" class="action-card">
                        <i class="bi bi-send text-secondary"></i>
                        <span class="fw-semibold">Send Message</span>
                    </a>
                </div>

                <div class="col-6 col-md-4 col-lg-3">
                    <a href="announcements.php" class="action-card">
                        <i class="bi bi-megaphone text-danger"></i>
                        <span class="fw-semibold">Announcements</span>
                    </a>
                </div>

                <div class="col-6 col-md-4 col-lg-3">
                    <a href="profile.php" class="action-card">
                        <i class="bi bi-person-circle text-dark"></i>
                        <span class="fw-semibold">My Profile</span>
                    </a>
                </div>
            </div>
        </div>

    </main>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Mobile Sidebar Handler
const mobileToggleBtn = document.getElementById("mobileMenuToggle");
const sidebar = document.getElementById("sidebar");
const content = document.getElementById("content");

if (mobileToggleBtn && sidebar) {
    mobileToggleBtn.addEventListener("click", () => {
        if (window.innerWidth <= 900) {
            sidebar.classList.toggle("mobile-open");
        } else {
            sidebar.classList.toggle("collapsed");
            content.classList.toggle("expanded");
        }
    });
}
</script>

</body>
</html>