<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("../config/db.php");
include("../config/functions.php");

// Fetch Enrollment Records with Student, User, and Course Details
$result = $conn->query("
    SELECT e.EnrollmentID, e.EnrollmentDate,
           s.RegistrationNumber,
           u.full_name,
           c.CourseTitle
    FROM Enrollment e
    JOIN Student s ON e.StudentID = s.StudentID
    JOIN users u ON s.UserID = u.user_id
    JOIN Course c ON e.CourseID = c.CourseID
    ORDER BY e.EnrollmentID DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Enrollments | LMS</title>
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
    box-shadow: 0 6px 18px rgba(0,0,0,0.07);
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

.search-input {
    border-radius: 10px;
    padding: 10px 14px;
}

.table thead th {
    background-color: #f8fafc;
    color: #475569;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.8rem;
    letter-spacing: 0.5px;
    border-bottom: 2px solid #e2e8f0;
}

.table tbody tr:hover {
    background-color: #f1f5f9;
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
    <?php include("sidebar.php"); ?>

    <!-- MAIN CONTENT CONTAINER -->
    <main class="content" id="content">

        <!-- TOPBAR -->
        <div class="topbar">
            <div class="d-flex align-items-center gap-3">
                <button id="mobileMenuToggle" class="toggle-btn">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    <h1 class="fw-bold mb-1">Manage Enrollments</h1>
                    <p class="text-muted mb-0">View, search, and manage student course enrollments.</p>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="add_enrollment.php" class="btn btn-success rounded-3 px-3">
                    <i class="bi bi-plus-circle me-1"></i> Add Enrollment
                </a>
                <!-- <a href="dashboard.php" class="btn btn-outline-secondary rounded-3 px-3">
                    <i class="bi bi-house me-1"></i> Dashboard
                </a> -->
            </div>
        </div>

        <!-- MAIN TABLE CONTAINER -->
        <div class="section-box">
            
            <!-- SEARCH TOOLBAR -->
            <div class="row mb-4">
                <div class="col-md-6 col-lg-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" id="searchInput" class="form-control border-start-0 search-input" placeholder="Search by student, reg no, or course...">
                    </div>
                </div>
            </div>

            <!-- ENROLLMENT DATA TABLE -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="enrollmentTable">
                    <thead>
                        <tr>
                            <th scope="col">Enrollment ID</th>
                            <th scope="col">Student Name</th>
                            <th scope="col">Reg Number</th>
                            <th scope="col">Course Title</th>
                            <th scope="col">Enrollment Date</th>
                            <th scope="col" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($result && $result->num_rows > 0){ ?>
                            <?php while($row = $result->fetch_assoc()) { ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-light text-dark border">#<?= htmlspecialchars($row['EnrollmentID']) ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($row['full_name']) ?></div>
                                    </td>
                                    <td>
                                        <span class="text-secondary"><?= htmlspecialchars($row['RegistrationNumber']) ?></span>
                                    </td>
                                    <td>
                                        <span class="fw-medium text-primary"><?= htmlspecialchars($row['CourseTitle']) ?></span>
                                    </td>
                                    <td>
                                        <small class="text-muted"><i class="bi bi-calendar3 me-1"></i><?= htmlspecialchars($row['EnrollmentDate']) ?></small>
                                    </td>
                                    <td class="text-center">
                                        <a href="delete_enrollment.php?id=<?= $row['EnrollmentID'] ?>" 
                                           class="btn btn-outline-danger btn-sm rounded-2 px-3"
                                           onclick="return confirm('Are you sure you want to delete this enrollment record?')">
                                            <i class="bi bi-trash me-1"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-3 d-block mb-2 text-secondary"></i>
                                    No enrollment records found.
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

        </div>

    </main>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
/* REAL-TIME TABLE SEARCH FILTER */
document.getElementById("searchInput").addEventListener("keyup", function() {
    let value = this.value.toLowerCase().trim();
    let rows = document.querySelectorAll("#enrollmentTable tbody tr");

    rows.forEach(function(row) {
        // Skip searching inside "No records found" row
        if (row.children.length === 1) return;
        
        let text = row.innerText.toLowerCase();
        row.style.display = text.includes(value) ? "" : "none";
    });
});

/* SIDEBAR TOGGLE HANDLER */
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