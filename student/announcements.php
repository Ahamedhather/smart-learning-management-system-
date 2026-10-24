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

$student_id = $_SESSION['student_id'];

// Get Announcements (Supports both Course-specific and Global announcements using correct `course` table name)
$stmt = $conn->prepare("
    SELECT DISTINCT
        a.AnnouncementID,
        a.Title,
        a.Message,
        a.CreatedDate,
        u.full_name,
        COALESCE(c.CourseTitle, 'General / System-wide') AS CourseTitle,
        COALESCE(d.DepartmentName, 'All Departments') AS DepartmentName
    FROM Announcement a
    JOIN users u ON a.UserID = u.user_id
    LEFT JOIN course c ON a.CourseID = c.CourseID
    LEFT JOIN subject s ON c.SubjectID = s.SubjectID
    LEFT JOIN department d ON s.DepartmentID = d.DepartmentID
    WHERE a.CourseID IS NULL 
       OR a.CourseID IN (SELECT CourseID FROM Enrollment WHERE StudentID = ?)
    ORDER BY a.CreatedDate DESC
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements | Student Portal</title>

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

        /* ANNOUNCEMENT CARD */
        .announcement-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
            padding: 28px;
            margin-bottom: 24px;
            animation: fadeUp 0.4s ease-out;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .meta-tag {
            background: #f1f5f9;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 500;
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
        <h3 class="fw-bold mb-4">📢 Latest Announcements</h3>

        <?php if ($result->num_rows == 0) { ?>
            <div class="card p-4 text-center text-muted">
                <i class="bi bi-megaphone fs-1 d-block mb-2"></i>
                <p>No new announcements at this time.</p>
            </div>
        <?php } else { ?>
            <?php while ($row = $result->fetch_assoc()) { ?>
                <div class="announcement-card">
                    <h4 class="fw-bold text-primary"><?= htmlspecialchars($row['Title']) ?></h4>
                    <p class="text-secondary mt-3 mb-4"><?= nl2br(htmlspecialchars($row['Message'])) ?></p>
                    
                    <div class="d-flex flex-wrap gap-2 pt-3 border-top">
                        <span class="meta-tag"><i class="bi bi-book me-1"></i> <?= htmlspecialchars($row['CourseTitle']) ?></span>
                        <span class="meta-tag"><i class="bi bi-building me-1"></i> <?= htmlspecialchars($row['DepartmentName']) ?></span>
                        <span class="meta-tag"><i class="bi bi-person-fill me-1"></i> <?= htmlspecialchars($row['full_name']) ?></span>
                        <span class="meta-tag"><i class="bi bi-calendar3 me-1"></i> <?= date("d M Y, h:i A", strtotime($row['CreatedDate'])) ?></span>
                    </div>
                </div>
            <?php } ?>
        <?php } ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('sidebarToggle')?.addEventListener('click', () => {
            document.getElementById('sidebar')?.classList.toggle('mobile-open');
        });
    </script>
</body>
</html>