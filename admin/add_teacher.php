<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("../config/db.php");
include("../config/functions.php");

$users = $conn->query("SELECT * FROM users WHERE role='teacher' ORDER BY full_name ASC");
$departments = $conn->query("SELECT * FROM Department ORDER BY DepartmentName ASC");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $stmt = $conn->prepare("
        INSERT INTO Teacher (UserID, Qualification, ExperienceYears, DepartmentID, JoinedDate)
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "isiis",
        $_POST['user_id'],
        $_POST['qualification'],
        $_POST['experience'],
        $_POST['department'],
        $_POST['joined_date']
    );

    if ($stmt->execute()) {
        header("Location: manage_teachers.php");
        exit();
    } else {
        $error = "Teacher could not be added. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Teacher | A too Z LMS</title>
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
                    <h1 class="fw-bold mb-1">Add Teacher</h1>
                    <p class="text-muted mb-0">Assign qualifications and department associations to teacher accounts.</p>
                </div>
            </div>
            <div>
                <a href="manage_teachers.php" class="btn-cancel">
                    <i class="bi bi-arrow-left me-2"></i>Back to List
                </a>
            </div>
        </div>

        <!-- SERVER SIDE ERROR MESSAGES DISPLAY -->
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert" style="max-width: 850px; border-radius: 12px;">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- STYLED FORM CONTAINER -->
        <div class="section-box">
            <form method="POST" onsubmit="return validateForm()" class="needs-validation" novalidate>
                <div class="row g-4">

                    <!-- Search Teacher Helper Field (Full Width) -->
                    <div class="col-12">
                        <label class="form-label">Search Teacher</label>
                        <div class="input-group">
                            <span class="input-group-text border-end-0 bg-white" style="border-radius:10px 0 0 10px; border-color:#d1d5db;">
                                <i class="bi bi-search text-muted"></i>
                            </span>
                            <input type="text" id="searchTeacher" class="form-control border-start-0" style="border-radius:0 10px 10px 0;" placeholder="Type teacher name to filter selection below...">
                        </div>
                    </div>

                    <!-- Select Teacher Dropdown -->
                    <div class="col-md-6">
                        <label class="form-label">Select Teacher User</label>
                        <select name="user_id" id="user_id" class="form-select" required>
                            <option value="" disabled selected>Choose a teacher user account...</option>
                            <?php while($u = $users->fetch_assoc()) { ?>
                                <option value="<?= htmlspecialchars($u['user_id']) ?>">
                                    <?= htmlspecialchars($u['full_name']) ?>
                                </option>
                            <?php } ?>
                        </select>
                        <div class="invalid-feedback">Please select an unmapped teacher account user.</div>
                    </div>

                    <!-- Qualification -->
                    <div class="col-md-6">
                        <label class="form-label">Qualification</label>
                        <input type="text" name="qualification" id="qualification" class="form-control" placeholder="e.g. MSc in Computer Science, PhD" required>
                        <div class="invalid-feedback">Please enter valid qualifications.</div>
                    </div>

                    <!-- Experience Years -->
                    <div class="col-md-6">
                        <label class="form-label">Experience (Years)</label>
                        <input type="number" name="experience" id="experience" class="form-control" placeholder="e.g. 5" min="0" required>
                        <div class="invalid-feedback">Please enter experience years (0 or more).</div>
                    </div>

                    <!-- Department Select -->
                    <div class="col-md-6">
                        <label class="form-label">Department</label>
                        <select name="department" id="department" class="form-select" required>
                            <option value="" disabled selected>Choose a department...</option>
                            <?php while($d = $departments->fetch_assoc()) { ?>
                                <option value="<?= htmlspecialchars($d['DepartmentID']) ?>">
                                    <?= htmlspecialchars($d['DepartmentName']) ?>
                                </option>
                            <?php } ?>
                        </select>
                        <div class="invalid-feedback">Please select a department alignment.</div>
                    </div>

                    <!-- Joined Date -->
                    <div class="col-md-6">
                        <label class="form-label">Joined Date</label>
                        <input type="date" name="joined_date" id="joined_date" class="form-control" required>
                        <div class="invalid-feedback">Please supply a valid date of enrollment.</div>
                    </div>

                    <!-- Form Action Buttons -->
                    <div class="col-12 mt-4 pt-2">
                        <button type="submit" class="btn-gradient me-2">
                            <i class="bi bi-check-circle-fill me-2"></i>Save Teacher Profile
                        </button>
                        <a href="manage_teachers.php" class="btn-cancel">Cancel</a>
                    </div>

                </div>
            </form>
        </div>

    </main>
</div>

<!-- Bootstrap JS Bundle for Alert Dismissal -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
/* FRONTEND FORM VALIDATION */
function validateForm() {
    let user = document.getElementById("user_id").value;
    let qualification = document.getElementById("qualification").value.trim();
    let experience = document.getElementById("experience").value;
    let department = document.getElementById("department").value;
    let joinedDate = document.getElementById("joined_date").value;

    if (user === "" || qualification === "" || experience === "" || department === "" || joinedDate === "") {
        alert("Please fill all fields.");
        return false;
    }

    if (experience < 0) {
        alert("Experience years cannot be negative.");
        return false;
    }

    return true;
}

/* REAL-TIME FILTER LOGIC FOR TEACHER SELECTION */
document.getElementById("searchTeacher").addEventListener("keyup", function() {
    let searchValue = this.value.toLowerCase();
    let select = document.getElementById("user_id");
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