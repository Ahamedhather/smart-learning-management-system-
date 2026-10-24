<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once("../config/db.php");

// Session Protection
if (!isset($_SESSION['student_id'])) {
    header("Location: ../login.php");
    exit();
}

// Fetch Teachers
$teachers = $conn->query("
    SELECT t.TeacherID, u.user_id, u.full_name
    FROM Teacher t
    JOIN users u ON t.UserID = u.user_id
    WHERE u.status = 'active'
    ORDER BY u.full_name ASC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Message | Student Portal</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            min-height: 100vh;
        }

        /* MAIN CONTENT LAYOUT */
        .main-content {
            margin-left: 290px;
            padding: 30px 40px;
            transition: all 0.3s ease;
        }

        /* FORM CARD */
        .form-card {
            max-width: 600px;
            background: #ffffff;
            padding: 35px;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
            animation: fadeUp 0.4s ease-out;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-control, .form-select {
            padding: 12px 16px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
        }
        .form-control:focus, .form-select:focus {
            background-color: #ffffff;
            border-color: #6d35f5;
            box-shadow: 0 0 0 4px rgba(109, 53, 245, 0.1);
        }

        .btn-action {
            padding: 12px;
            border-radius: 10px;
            font-weight: 600;
        }

        /* MOBILE HEADER */
        .mobile-header {
            display: none;
            background: #0f172a;
            padding: 14px 20px;
            color: white;
            align-items: center;
            justify-content: space-between;
        }

        @media (max-width: 900px) {
            .main-content { margin-left: 0; padding: 20px; }
            .mobile-header { display: flex; }
        }
    </style>
</head>

<body>

    <div class="mobile-header">
        <span class="fw-bold fs-5">LMS Portal</span>
        <button class="btn btn-outline-light btn-sm" id="sidebarToggle"><i class="bi bi-list"></i></button>
    </div>

    <?php include("sidebar_student.php"); ?>

    <main class="main-content">
        <div class="form-card mx-auto">
            <h4 class="fw-bold mb-4 text-center">
                <i class="bi bi-send-fill text-primary me-2"></i>Send Message
            </h4>

            <form action="save_message.php" method="POST" onsubmit="return validateMessage()">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Select Teacher</label>
                    <select name="receiver" id="receiver" class="form-select" required>
                        <option value="">-- Choose a recipient --</option>
                        <?php while($t = $teachers->fetch_assoc()) { ?>
                            <option value="<?= $t['user_id'] ?>"><?= htmlspecialchars($t['full_name']) ?></option>
                        <?php } ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Message</label>
                    <textarea name="message" id="message" class="form-control" rows="6" placeholder="Enter your academic query here..." required></textarea>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-action w-100">
                        <i class="bi bi-send"></i> Send Message
                    </button>
                    <a href="student_dashboard.php" class="btn btn-outline-secondary btn-action w-100">Cancel</a>
                </div>
            </form>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function validateMessage() {
            let receiver = document.getElementById("receiver").value;
            let message = document.getElementById("message").value.trim();

            if (!receiver) {
                alert("Please select a teacher from the list.");
                return false;
            }
            if (message.length < 5) {
                alert("Message is too short. Please provide more detail (at least 5 characters).");
                return false;
            }
            return true;
        }

        document.getElementById('sidebarToggle')?.addEventListener('click', () => {
            document.getElementById('sidebar')?.classList.toggle('mobile-open');
        });
    </script>
</body>
</html>