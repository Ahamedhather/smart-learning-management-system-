<?php
include("../config/db.php");
include("../includes/auth.php");
include("../config/functions.php");

/* SECURITY CHECK */
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    die("Access Denied");
}

/* FETCH SUBJECTS WITH DEPARTMENT DETAILS */
$result = $conn->query("
SELECT s.SubjectID, s.SubjectName, s.SubjectCode, d.DepartmentName
FROM Subject s
LEFT JOIN Department d ON s.DepartmentID = d.DepartmentID
ORDER BY s.SubjectID DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Subject Management | A too Z LMS</title>
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

/* SECTIONS & TABLES */
.section-box {
    background: white;
    border-radius: 18px;
    padding: 25px;
    margin-top: 25px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.07);
}

.search-box {
    width: 100%;
    max-width: 380px;
    border: 1px solid #d1d5db;
    border-radius: 12px;
    padding: 11px 16px;
    background-color: white;
    transition: all 0.2s;
}

.search-box:focus {
    border-color: #6d35f5;
    box-shadow: 0 0 0 4px rgba(109, 53, 245, 0.1);
    outline: none;
}

.btn-gradient-success {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    font-weight: 600;
    border: none;
    border-radius: 10px;
    padding: 10px 20px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    transition: all 0.2s ease;
}

.btn-gradient-success:hover {
    color: white;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    transform: translateY(-1px);
}

.custom-table th {
    background-color: #f8fafc;
    color: #4b5563;
    font-weight: 600;
    border-bottom: 2px solid #e2e8f0;
    padding: 15px;
}

.custom-table td {
    padding: 15px;
    vertical-align: middle;
}

.table tbody tr:hover {
    background-color: #f8fafc;
}

.badge-code {
    background-color: #f1f5f9;
    color: #334155;
    padding: 5px 10px;
    border-radius: 8px;
    font-family: monospace;
    font-size: 13px;
    font-weight: 600;
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

    .search-box {
        max-width: 100%;
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

        <!-- TOP HEADER AREA -->
        <div class="topbar">
            <div class="left-title">
                <!-- Mobile Toggle Button (Only displays on small screens) -->
                <button id="mobileMenuToggle" class="toggle-btn">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    <h1 class="fw-bold mb-1">Modules / Subject Management</h1>
                    <p class="text-muted mb-0">Configure academic subjects, syllabus indexes, and department mappings.</p>
                </div>
            </div>
            <div>
                <a href="add_subject.php" class="btn-gradient-success">
                    <i class="bi bi-plus-circle me-2"></i>Modules / Add Subject
                </a>
            </div>
        </div>

        <!-- TABLE CONTROL OPTIONS -->
        <div class="row align-items-center justify-content-between g-3 mt-1">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text border-end-0 bg-white" style="border-radius:12px 0 0 12px; border-color:#d1d5db;"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" id="searchInput" class="form-control border-start-0 search-box" style="border-radius:0 12px 12px 0;" placeholder="Search subjects by name, code, department...">
                </div>
            </div>
        </div>

        <!-- STYLED LIST CONTAINER -->
        <div class="section-box">
            <div class="table-responsive">
                <table class="table custom-table table-hover alignment-middle" id="subjectTable">
                    <thead>
                        <tr>
                            <th style="width: 10%;">ID</th>
                            <th style="width: 35%;">Subject Name</th>
                            <th style="width: 20%;">Subject Code</th>
                            <th style="width: 20%;">Department</th>
                            <th style="width: 15%;" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($result && $result->num_rows > 0) { ?>
                        <?php while($row = $result->fetch_assoc()) { ?>
                        <tr>
                            <td class="fw-bold text-secondary">#<?= htmlspecialchars($row['SubjectID']) ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="bg-light text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 14px;">
                                        <i class="bi bi-book"></i>
                                    </div>
                                    <span class="fw-semibold text-dark"><?= htmlspecialchars($row['SubjectName']) ?></span>
                                </div>
                            </td>
                            <td><span class="badge-code"><?= htmlspecialchars($row['SubjectCode']) ?></span></td>
                            <td><span class="text-muted"><?= htmlspecialchars($row['DepartmentName'] ?? 'N/A') ?></span></td>
                            <td>
                                <div class="d-flex justify-content-center gap-2">
                                    <a href="edit_subject.php?id=<?= $row['SubjectID'] ?>" class="btn btn-outline-primary btn-sm rounded-3 px-3">
                                        <i class="bi bi-pencil-square me-1"></i> Edit
                                    </a>
                                    <button onclick="confirmDelete(<?= $row['SubjectID'] ?>)" class="btn btn-outline-danger btn-sm rounded-3 px-3">
                                        <i class="bi bi-trash me-1"></i> Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No subject records discovered in database inventory.</td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- FOOTER NAVIGATION -->
        <div class="mt-4">
            <!-- <a href="dashboard.php" class="btn btn-secondary rounded-3 px-4">
                <i class="bi bi-speedometer2 me-2"></i>Back to Dashboard
            </a> -->
        </div>

    </main>

</div>

<!-- Bootstrap JS Bundle for Responsive Interactivity -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
/* DELETE CONFIRMATION ACTION */
function confirmDelete(id) {
    if (confirm("Are you sure you want to delete this subject profile?")) {
        window.location.href = "delete_subject.php?id=" + id;
    }
}

/* REAL-TIME FILTER LOGIC */
document.getElementById("searchInput").addEventListener("keyup", function() {
    let value = this.value.toLowerCase();
    let rows = document.querySelectorAll("#subjectTable tbody tr");

    rows.forEach(row => {
        // Skip filtering if row displays the "no subjects found" fallback
        if (row.cells.length > 1) {
            row.style.display = row.innerText.toLowerCase().includes(value) ? "" : "none";
        }
    });
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
</script>

</body>
</html>