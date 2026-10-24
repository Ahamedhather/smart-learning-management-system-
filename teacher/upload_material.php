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
$stmt = $conn->prepare("SELECT CourseTitle, CourseDescription FROM Course WHERE CourseID = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();

if (!$course) {
    header("Location: my_courses.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Upload Material | LMS</title>
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

/* Custom Dropzone Area */
.dropzone-area {
    border: 2px dashed #cbd5e1;
    border-radius: 14px;
    background: #f8fafc;
    padding: 30px;
    text-align: center;
    transition: all 0.2s ease;
    cursor: pointer;
}

.dropzone-area:hover, .dropzone-area.dragover {
    border-color: #6d35f5;
    background: rgba(109, 53, 245, 0.04);
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
                    <h1 class="fw-bold mb-1">Upload Study Material</h1>
                    <p class="text-muted mb-0">Course: <span class="fw-semibold text-primary"><?= htmlspecialchars($course['CourseTitle']) ?></span></p>
                </div>
            </div>
            <div>
                <!-- <a href="view_materials.php?course=<?= $course_id ?>" class="btn btn-outline-secondary rounded-3 px-3">
                    <i class="bi bi-arrow-left me-1"></i> View Materials
                </a> -->
            </div>
        </div>

        <div class="section-box">

            <!-- ERROR NOTIFICATION CONTAINER -->
            <div id="error-alert" class="alert alert-danger d-none rounded-3 mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <span id="error-message"></span>
            </div>

            <form action="save_material.php" method="POST" enctype="multipart/form-data" onsubmit="return validateUpload()">

                <input type="hidden" name="course" value="<?= $course_id ?>">

                <!-- MATERIAL TITLE -->
                <div class="mb-4">
                    <label class="form-label fw-bold">Material Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" id="title" class="form-control rounded-3 p-2.5" placeholder="e.g. Chapter 1 - Introduction to Course Mechanics" required>
                </div>

                <!-- DRAG AND DROP FILE INPUT -->
                <div class="mb-4">
                    <label class="form-label fw-bold">Select File Document <span class="text-danger">*</span></label>
                    
                    <div class="dropzone-area" id="dropzone" onclick="document.getElementById('file').click()">
                        <i class="bi bi-cloud-arrow-up fs-1 text-primary d-block mb-2"></i>
                        <h6 class="fw-bold mb-1">Click to browse or drag & drop file here</h6>
                        <span class="text-muted small d-block mb-3">Allowed extensions: <strong>.pdf, .doc, .docx, .ppt, .pptx</strong> (Max: 20MB)</span>
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-3 px-3">Browse File</button>
                    </div>

                    <input type="file" name="file" id="file" class="d-none" accept=".pdf,.doc,.docx,.ppt,.pptx" required>

                    <!-- SELECTED FILE DETAILS CONTAINER -->
                    <div id="file-info" class="mt-3 text-start bg-light p-3 rounded-3 border d-none">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bi bi-file-earmark-check-fill text-success fs-3"></i>
                                <div>
                                    <strong id="file-name" class="d-block text-truncate text-dark" style="max-width: 320px;"></strong>
                                    <small id="file-size" class="text-muted"></small>
                                </div>
                            </div>
                            <button type="button" class="btn-close" onclick="resetFile()"></button>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info border-0 bg-info-subtle text-info-emphasis rounded-3 p-3 mb-4">
                    <i class="bi bi-info-circle me-1"></i>
                    Ensure your documents do not exceed 20MB. Uploaded materials will instantly become available for enrolled students.
                </div>

                <!-- SUBMIT CONTROL BUTTONS -->
                <div class="d-flex gap-3">
                    <button type="submit" class="btn btn-success px-4 rounded-3 fw-semibold">
                        <i class="bi bi-upload me-1"></i> Upload Material
                    </button>
                    <a href="view_materials.php?course=<?= $course_id ?>" class="btn btn-light border px-4 rounded-3">Cancel</a>
                </div>

            </form>

        </div>

    </main>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
const fileInput = document.getElementById("file");
const dropzone = document.getElementById("dropzone");
const fileInfo = document.getElementById("file-info");
const fileName = document.getElementById("file-name");
const fileSize = document.getElementById("file-size");
const errorAlert = document.getElementById("error-alert");
const errorMessage = document.getElementById("error-message");

// Display chosen file details
fileInput.addEventListener("change", function() {
    if (this.files && this.files[0]) {
        showFileDetails(this.files[0]);
    }
});

// Drag and drop handlers
['dragenter', 'dragover'].forEach(eventName => {
    dropzone.addEventListener(eventName, (e) => {
        e.preventDefault();
        dropzone.classList.add('dragover');
    }, false);
});

['dragleave', 'drop'].forEach(eventName => {
    dropzone.addEventListener(eventName, (e) => {
        e.preventDefault();
        dropzone.classList.remove('dragover');
    }, false);
});

dropzone.addEventListener('drop', (e) => {
    const dt = e.dataTransfer;
    const files = dt.files;
    if (files && files.length > 0) {
        fileInput.files = files;
        showFileDetails(files[0]);
    }
});

function showFileDetails(file) {
    fileName.textContent = file.name;
    fileSize.textContent = (file.size / (1024 * 1024)).toFixed(2) + " MB";
    fileInfo.classList.remove("d-none");
    errorAlert.classList.add("d-none");
}

function resetFile() {
    fileInput.value = "";
    fileInfo.classList.add("d-none");
}

function showError(msg) {
    errorMessage.textContent = msg;
    errorAlert.classList.remove("d-none");
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function validateUpload() {
    let title = document.getElementById("title").value.trim();
    let file = fileInput.files[0];

    errorAlert.classList.add("d-none");

    if (title.length < 3) {
        showError("Material title must be at least 3 characters long.");
        return false;
    }

    if (!file) {
        showError("Please select a file to upload.");
        return false;
    }

    let allowed = ["pdf", "doc", "docx", "ppt", "pptx"];
    let ext = file.name.split(".").pop().toLowerCase();

    if (!allowed.includes(ext)) {
        showError("Invalid file type. Only PDF, DOC, DOCX, PPT, and PPTX formats are allowed.");
        return false;
    }

    if (file.size > 20 * 1024 * 1024) {
        showError("File size exceeds 20MB limit. Please upload a smaller file.");
        return false;
    }

    return true;
}

// Mobile Menu Handler
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