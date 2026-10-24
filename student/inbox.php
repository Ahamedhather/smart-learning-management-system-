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

$user_id = $_SESSION['student_id'];

// Fetch Messages
$stmt = $conn->prepare("
    SELECT m.MessageID, m.MessageText, m.SentDate, m.ReadStatus, u.full_name 
    FROM Message m
    JOIN users u ON m.SenderID = u.user_id
    WHERE m.ReceiverID = ?
    ORDER BY m.SentDate DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Mark All As Read
$update_stmt = $conn->prepare("UPDATE Message SET ReadStatus = 'Read' WHERE ReceiverID = ?");
$update_stmt->bind_param("i", $user_id);
$update_stmt->execute();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inbox | Student Portal</title>

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

        /* CARD STYLES */
        .content-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
            padding: 28px;
            animation: fadeUp 0.4s ease-out;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* MESSAGE LIST */
        .message-item {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            padding: 20px;
            border-bottom: 1px solid #f1f5f9;
            transition: all 0.2s ease;
        }
        .message-item:last-child { border-bottom: none; }
        .message-item:hover { background: #f8fafc; }
        
        .avatar-box {
            width: 45px;
            height: 45px;
            background: #e2e8f0;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            font-weight: 700;
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
        <h3 class="fw-bold mb-4">📥 My Inbox</h3>

        <div class="content-card p-0">
            <?php if ($result->num_rows == 0) { ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-envelope-open fs-1 d-block mb-2"></i>
                    <p>No messages found in your inbox.</p>
                </div>
            <?php } else { ?>
                <div class="list-group list-group-flush">
                    <?php while ($row = $result->fetch_assoc()) { ?>
                        <div class="message-item">
                            <div class="avatar-box">
                                <?= substr(htmlspecialchars($row['full_name']), 0, 1) ?>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between">
                                    <h6 class="fw-bold mb-1"><?= htmlspecialchars($row['full_name']) ?></h6>
                                    <small class="text-muted"><?= date("d M Y, h:i A", strtotime($row['SentDate'])) ?></small>
                                </div>
                                <p class="text-secondary mb-0 small"><?= htmlspecialchars($row['MessageText']) ?></p>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('sidebarToggle')?.addEventListener('click', () => {
            document.getElementById('sidebar')?.classList.toggle('mobile-open');
        });
    </script>
</body>
</html>