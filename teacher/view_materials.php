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

$course_id = intval($_GET['course'] ?? 0);

if ($course_id <= 0) {
    header("Location: my_courses.php");
    exit();
}

// FETCH COURSE DETAILS
$stmt_course = $conn->prepare("SELECT CourseTitle, CourseDescription FROM Course WHERE CourseID = ?");
$stmt_course->bind_param("i", $course_id);
$stmt_course->execute();
$courseData = $stmt_course->get_result()->fetch_assoc();

if (!$courseData) {
    header("Location: my_courses.php");
    exit();
}

// FETCH STUDY MATERIALS FOR THIS COURSE
$stmt_materials = $conn->prepare("
    SELECT MaterialID, Title, FilePath, FileType, UploadDate
    FROM StudyMaterial 
    WHERE CourseID = ?
    ORDER BY UploadDate DESC
");
$stmt_materials->bind_param("i", $course_id);
$stmt_materials->execute();
$result = $stmt_materials->get_result();

// HELPER FUNCTION FOR FILE BADGES AND ICONS
function getFileTypeMeta($fileType, $filePath) {
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    if (empty($ext)) {
        $ext = strtolower($fileType);
    }

    switch ($ext) {
        case 'pdf':
            return ['icon' => 'bi-file-earmark-pdf-fill', 'class' => 'bg-danger text-white', 'label' => 'PDF'];
        case 'doc':
        case 'docx':
            return ['icon' => 'bi-file-earmark-word-fill', 'class' => 'bg-primary text-white', 'label' => 'DOCX'];
        case 'ppt':
        case 'pptx':
            return ['icon' => 'bi-file-earmark-ppt-fill', 'class' => 'bg-warning text-dark', 'label' => 'PPTX'];
        case 'xls':
        case 'xlsx':
            return ['icon' => 'bi-file-earmark-excel-fill', 'class' => 'bg-success text-white', 'label' => 'EXCEL'];
        case 'zip':
        case 'rar':
        case '7z':
            return ['icon' => 'bi-file-earmark-zip-fill', 'class' => 'bg-secondary text-white', 'label' => 'ARCHIVE'];
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif':
            return ['icon' => 'bi-file-earmark-image-fill', 'class' => 'bg-info text-dark', 'label' => 'IMAGE'];
        default:
            return ['icon' => 'bi-file-earmark-text-fill', 'class' => 'bg-dark text-white', 'label' => strtoupper($ext ?: 'FILE')];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Course Materials | LMS</title>
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

.search-box {
    position: relative;
    max-width: 400px;
}

.search-box .bi-search {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
}

.search-box input {
    padding-left: 42px;
    border-radius: 10px;
    border: 1px solid #cbd5e1;
}

.table-custom {
    border-radius: 12px;
    overflow: hidden;
}

.table-custom thead {
    background: #1e293b;
    color: white;
}

.table-custom th {
    padding: 14px;
    font-weight: 600;
    font-size: 0.9rem;
    white-space: nowrap;
}

.table-custom td {
    padding: 14px;
    vertical-align: middle;
}

.file-badge {
    padding: 6px 10px;
    border-radius: 8px;
    font-size: 0.78rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 5px;
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
                    <h1 class="fw-bold mb-1">Study Materials</h1>
                    <p class="text-muted mb-0">
                        Course: <span class="fw-semibold text-primary"><?= htmlspecialchars($courseData['CourseTitle']) ?></span>
                    </p>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="upload_material.php?course=<?= $course_id ?>" class="btn btn-success rounded-3 px-3">
                    <i class="bi bi-plus-lg me-1"></i> Upload Material
                </a>
                <!-- <a href="my_courses.php" class="btn btn-outline-secondary rounded-3 px-3">
                    <i class="bi bi-arrow-left me-1"></i> Back to Courses
                </a> -->
            </div>
        </div>

        <div class="section-box">

            <!-- SEARCH TOOLBAR -->
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <div class="search-box w-100">
                    <i class="bi bi-search"></i>
                    <input type="text" id="searchInput" class="form-control" placeholder="Search study materials by title or file format...">
                </div>
            </div>

            <!-- MATERIALS TABLE -->
            <div class="table-responsive table-custom">
                <table class="table table-hover align-middle mb-0" id="materialsTable">
                    <thead>
                        <tr>
                            <th>Document Title</th>
                            <th>File Format</th>
                            <th>Upload Date</th>
                            <th>Download</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows === 0): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-folder-x fs-1 text-secondary d-block mb-2"></i>
                                    No study materials uploaded for this course yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <?php 
                                    $meta = getFileTypeMeta($row['FileType'], $row['FilePath']); 
                                    $fullFilePath = "../uploads/" . $row['FilePath'];
                                    $fileExists = file_exists($fullFilePath);
                                ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="<?= $meta['icon'] ?> fs-4 text-secondary"></i>
                                            <span class="fw-bold text-dark"><?= htmlspecialchars($row['Title']) ?></span>
                                        </div>
                                    </td>

                                    <td>
                                        <span class="file-badge <?= $meta['class'] ?>">
                                            <i class="<?= $meta['icon'] ?>"></i>
                                            <?= htmlspecialchars($meta['label']) ?>
                                        </span>
                                    </td>

                                    <td class="text-muted small">
                                        <i class="bi bi-clock me-1"></i>
                                        <?= date("d M Y, h:i A", strtotime($row['UploadDate'])) ?>
                                    </td>

                                    <td>
                                        <?php if ($fileExists): ?>
                                            <a href="../uploads/<?= rawurlencode($row['FilePath']) ?>" target="_blank" class="btn btn-sm btn-primary rounded-2 px-3">
                                                <i class="bi bi-download me-1"></i> Download
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                                <i class="bi bi-exclamation-triangle me-1"></i> File Missing
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-end">
                                        <a href="delete_material.php?id=<?= $row['MaterialID'] ?>&course=<?= $course_id ?>" 
                                           class="btn btn-sm btn-outline-danger rounded-2"
                                           onclick="return confirm('Are you sure you want to delete this material? This action cannot be undone.')">
                                            <i class="bi bi-trash me-1"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>

    </main>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Live Search Filter
document.getElementById("searchInput")?.addEventListener("keyup", function() {
    const value = this.value.toLowerCase();
    const rows = document.querySelectorAll("#materialsTable tbody tr");

    rows.forEach(function(row) {
        row.style.display = row.innerText.toLowerCase().includes(value) ? "" : "none";
    });
});

// Mobile Drawer Navigation
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