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

// Validate Course Parameter
if (!isset($_GET['course']) || !filter_var($_GET['course'], FILTER_VALIDATE_INT)) {
    header("Location: my_courses.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$course_id = intval($_GET['course']);

// SECURITY: Check enrollment & fetch course title
$stmt = $conn->prepare("
    SELECT c.CourseTitle
    FROM Enrollment e
    JOIN Course c ON e.CourseID = c.CourseID
    WHERE e.StudentID = ? AND e.CourseID = ?
");
$stmt->bind_param("ii", $student_id, $course_id);
$stmt->execute();
$check = $stmt->get_result();

if ($check->num_rows == 0) {
    header("Location: my_courses.php?error=access_denied");
    exit();
}

$courseData = $check->fetch_assoc();

// Get study materials for this course
$stmt = $conn->prepare("
    SELECT MaterialID, Title, FilePath, FileType, UploadDate
    FROM StudyMaterial
    WHERE CourseID = ?
    ORDER BY UploadDate DESC
");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();

// Helper Function for File Type Styling
function getFileTypeConfig($fileType) {
    $ext = strtolower(trim($fileType));
    switch ($ext) {
        case 'pdf':
            return ['class' => 'bg-danger-subtle text-danger border-danger-subtle', 'icon' => 'bi-file-earmark-pdf-fill'];
        case 'doc':
        case 'docx':
            return ['class' => 'bg-primary-subtle text-primary border-primary-subtle', 'icon' => 'bi-file-earmark-word-fill'];
        case 'ppt':
        case 'pptx':
            return ['class' => 'bg-warning-subtle text-warning-emphasis border-warning-subtle', 'icon' => 'bi-file-earmark-slides-fill'];
        case 'zip':
        case 'rar':
            return ['class' => 'bg-secondary-subtle text-secondary border-secondary-subtle', 'icon' => 'bi-file-earmark-zip-fill'];
        default:
            return ['class' => 'bg-info-subtle text-info-emphasis border-info-subtle', 'icon' => 'bi-file-earmark-text-fill'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Materials | Student Portal</title>

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
            min-height: 100vh;
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

        /* SEARCH BAR */
        .search-box {
            position: relative;
        }
        .search-box i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 18px;
        }
        .search-box .form-control {
            padding-left: 46px;
            height: 48px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
            font-weight: 500;
        }
        .search-box .form-control:focus {
            background-color: #ffffff;
            border-color: #6d35f5;
            box-shadow: 0 0 0 4px rgba(109, 53, 245, 0.1);
        }

        /* TABLE STYLES */
        .custom-table {
            border-collapse: separate;
            border-spacing: 0 8px;
        }
        .custom-table thead th {
            background: #f1f5f9;
            color: #475569;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
            padding: 14px 18px;
        }
        .custom-table thead th:first-child { border-radius: 10px 0 0 10px; }
        .custom-table thead th:last-child { border-radius: 0 10px 10px 0; }

        .custom-table tbody tr {
            background: #ffffff;
            transition: all 0.2s ease;
        }
        .custom-table tbody tr:hover {
            background: #f8fafc;
            transform: translateY(-1px);
        }
        .custom-table td {
            padding: 16px 18px;
            border-top: 1px solid #f1f5f9;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        .custom-table td:first-child { border-left: 1px solid #f1f5f9; border-radius: 12px 0 0 12px; }
        .custom-table td:last-child { border-right: 1px solid #f1f5f9; border-radius: 0 12px 12px 0; }

        /* BUTTONS & BADGES */
        .btn-action {
            border-radius: 10px;
            font-weight: 600;
            font-size: 13px;
            padding: 7px 16px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }
        .btn-action:hover {
            transform: translateY(-2px);
        }

        .file-type-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            border: 1px solid;
        }

        /* MOBILE HEADER BAR */
        .mobile-header {
            display: none;
            background: #0f172a;
            padding: 14px 20px;
            color: white;
            align-items: center;
            justify-content: space-between;
        }

        /* RESPONSIVE BREAKPOINTS */
        @media (max-width: 900px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
            .mobile-header {
                display: flex;
            }
        }
    </style>
</head>

<body>

    <!-- MOBILE NAVBAR HEADER -->
    <div class="mobile-header">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-mortarboard-fill fs-4 text-primary"></i>
            <span class="fw-bold fs-5">LMS Portal</span>
        </div>
        <button class="btn btn-outline-light btn-sm" id="sidebarToggle">
            <i class="bi bi-list fs-5"></i>
        </button>
    </div>

    <!-- INCLUDED STUDENT SIDEBAR -->
    <?php include("sidebar_student.php"); ?>

    <!-- MAIN PAGE CONTENT -->
    <main class="main-content">
        
        <!-- PAGE HEADER -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <h3 class="fw-bold mb-1">Course Materials</h3>
                <p class="text-muted mb-0">Course: <strong class="text-dark"><?= htmlspecialchars($courseData['CourseTitle']) ?></strong></p>
            </div>
            <a href="my_courses.php" class="btn btn-outline-secondary btn-action">
                <i class="bi bi-arrow-left"></i> Back to Courses
            </a>
        </div>

        <!-- CONTENT CARD -->
        <div class="content-card">
            
            <!-- SEARCH BAR -->
            <div class="row mb-4">
                <div class="col-md-6 col-lg-5">
                    <div class="search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" id="searchInput" class="form-control" placeholder="Search materials by title or file type...">
                    </div>
                </div>
            </div>

            <!-- MATERIALS TABLE -->
            <div class="table-responsive">
                <table class="table custom-table align-middle mb-0" id="materialsTable">
                    <thead>
                        <tr>
                            <th>Material Title</th>
                            <th>File Format</th>
                            <th>Upload Date</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows == 0) { ?>
                            <tr>
                                <td colspan="4" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-folder-x fs-1 d-block mb-2 text-secondary"></i>
                                        <h6 class="fw-semibold">No Materials Found</h6>
                                        <p class="small mb-0">There are no study materials uploaded for this course yet.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>

                        <?php 
                        while ($row = $result->fetch_assoc()) { 
                            $filePath = "../uploads/" . $row['FilePath'];
                            $fileExists = file_exists($filePath);
                            $fileConfig = getFileTypeConfig($row['FileType']);
                        ?>
                            <tr class="material-row">
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="p-2 rounded bg-light text-primary fs-5">
                                            <i class="bi bi-file-earmark-text"></i>
                                        </div>
                                        <div>
                                            <span class="fw-bold text-dark fs-6 d-block">
                                                <?= htmlspecialchars($row['Title']) ?>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="file-type-badge <?= $fileConfig['class'] ?>">
                                        <i class="<?= $fileConfig['icon'] ?>"></i>
                                        <?= htmlspecialchars($row['FileType']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2 text-secondary fw-medium">
                                        <i class="bi bi-calendar3 text-primary"></i>
                                        <span><?= date("d M Y, h:i A", strtotime($row['UploadDate'])) ?></span>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <?php if ($fileExists) { ?>
                                        <a href="../uploads/<?= rawurlencode($row['FilePath']) ?>" 
                                           class="btn btn-sm btn-primary btn-action" 
                                           target="_blank"
                                           download>
                                            <i class="bi bi-download"></i> Download
                                        </a>
                                    <?php } else { ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 rounded fw-semibold">
                                            <i class="bi bi-exclamation-triangle-fill me-1"></i> File Missing
                                        </span>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

        </div>

    </main>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Interactive Scripts -->
    <script>
        // Real-time Search Filter
        document.getElementById("searchInput").addEventListener("keyup", function() {
            let filterValue = this.value.toLowerCase().trim();
            let rows = document.querySelectorAll("#materialsTable tbody tr.material-row");

            rows.forEach(function(row) {
                let text = row.innerText.toLowerCase();
                row.style.display = text.includes(filterValue) ? "" : "none";
            });
        });

        // Mobile Sidebar Drawer Toggle
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');

        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('mobile-open');
            });
        }
    </script>

</body>
</html>