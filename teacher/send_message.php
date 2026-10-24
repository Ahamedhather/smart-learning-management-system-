<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("../config/db.php");
include("../includes/auth.php");
include("../config/functions.php");

// 🔒 SECURITY CHECK
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'teacher') {
    header("Location: ../login.php");
    exit();
}

// FETCH ACTIVE STUDENTS
$students_query = $conn->query("
    SELECT s.StudentID, u.user_id, u.full_name, s.RegistrationNumber
    FROM Student s
    JOIN users u ON s.UserID = u.user_id
    WHERE u.status = 'active'
    ORDER BY u.full_name ASC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Send Message | LMS</title>
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

.section-box {
    background: white;
    border-radius: 18px;
    padding: 30px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.04);
    max-width: 750px;
    margin: 0 auto;
}

.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
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

    <!-- SIDEBAR INCLUSION -->
    <?php include("sidebar_teacher.php"); ?>

    <!-- MAIN CONTENT -->
    <main class="content" id="content">

        <!-- TOPBAR -->
        <div class="topbar">
            <div class="d-flex align-items-center gap-3">
                <button id="mobileMenuToggle" class="toggle-btn">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    <h1 class="fw-bold mb-1">Send Message</h1>
                    <p class="text-muted mb-0">Communicate directly with registered students</p>
                </div>
            </div>
            <div>
                <!-- <a href="dashboard.php" class="btn btn-outline-secondary rounded-3 px-3">
                    <i class="bi bi-arrow-left me-1"></i> Dashboard
                </a> -->
            </div>
        </div>

        <div class="section-box">

            <!-- SESSION MESSAGES -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($_SESSION['success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($_SESSION['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- CLIENT-SIDE ERROR ALERT BANNER -->
            <div id="error-alert" class="alert alert-danger d-none rounded-3 mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <span id="error-message"></span>
            </div>

            <form action="save_message.php" method="POST" onsubmit="return validateMessage()">

                <!-- SELECT RECIPIENT STUDENT -->
                <div class="mb-4">
                    <label class="form-label fw-bold">Select Student <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-person-badge"></i></span>
                        <select name="receiver" id="receiver" class="form-select rounded-end p-2.5" required>
                            <option value="">-- Choose Student --</option>
                            <?php while ($s = $students_query->fetch_assoc()): ?>
                                <option value="<?= $s['user_id'] ?>">
                                    <?= htmlspecialchars($s['full_name']) ?> (Reg: <?= htmlspecialchars($s['RegistrationNumber']) ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <!-- MESSAGE BODY -->
                <div class="mb-4">
                    <label class="form-label fw-bold">Message Content <span class="text-danger">*</span></label>
                    <textarea name="message" id="message" class="form-control rounded-3 p-2.5" rows="6" placeholder="Type your message here..." required></textarea>
                    <small class="text-muted mt-1 d-block">Minimum 5 characters required.</small>
                </div>

                <!-- ACTION BUTTONS -->
                <div class="d-flex gap-3 pt-2">
                    <button type="submit" class="btn btn-primary px-4 rounded-3 fw-semibold">
                        <i class="bi bi-send me-1"></i> Send Message
                    </button>
                    <a href="dashboard.php" class="btn btn-light border px-4 rounded-3">Cancel</a>
                </div>

            </form>

        </div>

    </main>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
const errorAlert = document.getElementById("error-alert");
const errorMessage = document.getElementById("error-message");

function showError(msg) {
    errorMessage.textContent = msg;
    errorAlert.classList.remove("d-none");
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function validateMessage() {
    let receiver = document.getElementById("receiver").value;
    let message = document.getElementById("message").value.trim();

    errorAlert.classList.add("d-none");

    if (receiver === "") {
        showError("Please select a student from the dropdown list.");
        return false;
    }

    if (message.length < 5) {
        showError("Your message must contain at least 5 characters.");
        return false;
    }

    return true;
}

// Mobile Sidebar Handler
const mobileToggleBtn = document.getElementById("mobileMenuToggle");
const sidebar = document.getElementById("sidebar");
const content = document.getElementById("content");

if (mobileToggleBtn) {
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