<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("../config/db.php");
include("../config/functions.php");

/* FETCH DEPARTMENTS FOR SELECT DROPDOWN */
$departments = $conn->query("SELECT * FROM Department ORDER BY DepartmentName ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Subject | A too Z LMS</title>
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

/* MAIN CONTENT */
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

/* TOPBAR */
.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    gap: 20px;
}

.left-title {
    display: flex;
    align-items: center;
    gap: 15px;
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

/* Mobile Topbar Toggle Hidden on Desktop */
#mobileMenuToggle {
    display: none;
}

/* SECTIONS & FORMS */
.section-box {
    background: white;
    border-radius: 18px;
    padding: 35px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.04);
    max-width: 850px;
}

.form-label {
    font-weight: 600;
    color: #4b5563;
    margin-bottom: 8px;
}

.form-control, .form-select {
    padding: 12px 15px;
    border: 1px solid #d1d5db;
    border-radius: 10px;
    background-color: #f9fafb;
    transition: all 0.2s ease;
}

.form-control:focus, .form-select:focus {
    background-color: #fff;
    border-color: #6d35f5;
    box-shadow: 0 0 0 4px rgba(109, 53, 245, 0.1);
    outline: none;
}

.btn-gradient {
    background: linear-gradient(135deg, #6d35f5, #8b5cf6);
    color: white;
    padding: 12px 25px;
    border-radius: 10px;
    font-weight: 600;
    border: none;
    transition: all 0.3s ease;
}

.btn-gradient:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(109, 53, 245, 0.3);
    color: white;
}

.btn-cancel {
    background-color: #f3f4f6;
    color: #4b5563;
    padding: 12px 25px;
    border-radius: 10px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
    display: inline-block;
}

.btn-cancel:hover {
    background-color: #e5e7eb;
    color: #1f2937;
}

/* Style validation errors slightly cleaner */
.invalid-feedback {
    font-size: 0.85rem;
    margin-top: 5px;
}

/* RESPONSIVE */
@media(max-width:900px) {
    #mobileMenuToggle {
        display: block !important;
    }

    .content,
    .content.expanded {
        margin-left: 0;
        width: 100%;
        padding: 20px;
    }

    .topbar {
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
    }
}
</style>
</head>

<body>

<div class="wrapper">

    <!-- SIDEBAR INCLUSION -->
    <?php include("sidebar.php"); ?>

    <!-- MAIN CONTENT CONTAINER -->
    <main class="content" id="content">

        <!-- HEADER SECTION -->
        <div class="topbar">
            <div class="left-title">
                <!-- Mobile Toggle Button (Only displays on small screens) -->
                <button id="mobileMenuToggle" class="toggle-btn">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    <h1 class="fw-bold mb-1">Modules / Add Subject</h1>
                    <p class="text-muted mb-0">Create academic Modules / subjects and align them to school departments.</p>
                </div>
            </div>
            <div>
                <a href="manage_subjects.php" class="btn-cancel">
                    <i class="bi bi-arrow-left me-2"></i>Back to List
                </a>
            </div>
        </div>

        <!-- STYLED FORM CONTAINER -->
        <div class="section-box">
            <form action="insert_subject.php" method="POST" onsubmit="return validateForm()" class="needs-validation" novalidate>
                <div class="row g-4">

                    <!-- Subject Name -->
                    <div class="col-md-6">
                        <label class="form-label">Subject Name</label>
                        <input type="text" name="name" id="name" class="form-control" placeholder="e.g. Introduction to Programming" required>
                        <div class="invalid-feedback">Please enter a valid subject name.</div>
                    </div>

                    <!-- Subject Code -->
                    <div class="col-md-6">
                        <label class="form-label">Subject Code</label>
                        <input type="text" name="code" id="code" class="form-control" placeholder="e.g. CS101" required>
                        <div class="invalid-feedback">Please enter a unique subject code reference.</div>
                    </div>

                    <!-- Search Department Helper Field (Full Width) -->
                    <div class="col-12">
                        <label class="form-label">Search Department</label>
                        <div class="input-group">
                            <span class="input-group-text border-end-0 bg-white" style="border-radius:10px 0 0 10px; border-color:#d1d5db;">
                                <i class="bi bi-search text-muted"></i>
                            </span>
                            <input type="text" id="searchDept" class="form-control border-start-0" style="border-radius:0 10px 10px 0;" placeholder="Type department name to filter selections below...">
                        </div>
                    </div>

                    <!-- Select Department -->
                    <div class="col-md-12">
                        <label class="form-label">Select Department Alignment</label>
                        <select name="dept" id="dept" class="form-select" required>
                            <option value="" disabled selected>Choose a department mapping...</option>
                            <?php while($d = $departments->fetch_assoc()) { ?>
                                <option value="<?= htmlspecialchars($d['DepartmentID']) ?>">
                                    <?= htmlspecialchars($d['DepartmentName']) ?>
                                </option>
                            <?php } ?>
                        </select>
                        <div class="invalid-feedback">Please link this subject to a department mapping.</div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="col-12 mt-4 pt-2">
                        <button type="submit" class="btn-gradient me-2">
                            <i class="bi bi-check-circle-fill me-2"></i>Save Subject
                        </button>
                        <a href="manage_subjects.php" class="btn-cancel">Cancel</a>
                    </div>

                </div>
            </form>
        </div>

    </main>
</div>

<!-- Bootstrap JS Bundle for Responsive Interactivity -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
/* FRONTEND FORM VALIDATION ENGINE */
function validateForm() {
    let name = document.getElementById("name").value.trim();
    let code = document.getElementById("code").value.trim();
    let dept = document.getElementById("dept").value;

    if (name === "" || code === "" || dept === "") {
        alert("Please fill all required fields.");
        return false;
    }

    return true;
}

/* REAL-TIME FILTER LOGIC FOR DEPARTMENT SEARCH */
document.getElementById("searchDept").addEventListener("keyup", function() {
    let searchValue = this.value.toLowerCase();
    let select = document.getElementById("dept");
    let options = select.options;

    for (let i = 1; i < options.length; i++) {
        let text = options[i].text.toLowerCase();
        options[i].style.display = text.includes(searchValue) ? "" : "none";
    }
});

/* COLLAPSIBLE SIDEBAR CONFIGURATION */
const toggleBtn = document.getElementById("menuToggle");
const mobileToggleBtn = document.getElementById("mobileMenuToggle");
const sidebar = document.getElementById("sidebar");
const content = document.getElementById("content");

function handleToggle() {
    if (window.innerWidth <= 900) {
        sidebar.classList.toggle("mobile-open");
    } else {
        sidebar.classList.toggle("collapsed");
        content.classList.toggle("expanded");
    }
}

if (toggleBtn) {
    toggleBtn.addEventListener("click", handleToggle);
}
if (mobileToggleBtn) {
    mobileToggleBtn.addEventListener("click", handleToggle);
}

/* Bootstrap Client Side Validation */
(() => {
  'use strict'
  const forms = document.querySelectorAll('.needs-validation')
  Array.from(forms).forEach(form => {
    form.addEventListener('submit', event => {
      if (!form.checkValidity()) {
        event.preventDefault()
        event.stopPropagation()
      }
      form.classList.add('was-validated')
    }, false)
  })
})()
</script>

</body>
</html>