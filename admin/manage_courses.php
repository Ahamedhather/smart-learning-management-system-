<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("../config/db.php");
include("../config/functions.php");

// 1. Get filter inputs safely
$search_title   = isset($_GET['search_title']) ? trim($_GET['search_title']) : '';
$filter_subject = isset($_GET['subject_id']) ? trim($_GET['subject_id']) : '';
$filter_teacher = isset($_GET['teacher_id']) ? trim($_GET['teacher_id']) : '';
$filter_semester= isset($_GET['semester']) ? trim($_GET['semester']) : '';

// 2. Build Dynamic SQL Query with Student Names & Registration Numbers
$query = "
SELECT c.*, s.SubjectName, u.full_name,
       (SELECT COUNT(e.CourseID) FROM Enrollment e WHERE e.CourseID = c.CourseID) as TotalStudents,
       (SELECT GROUP_CONCAT(CONCAT(us.full_name, '||', COALESCE(st.RegistrationNumber, 'N/A')) SEPARATOR ';;') 
        FROM Enrollment e 
        JOIN Student st ON e.StudentID = st.StudentID
        JOIN users us ON st.UserID = us.user_id 
        WHERE e.CourseID = c.CourseID) as StudentDetails
FROM Course c
LEFT JOIN Subject s ON c.SubjectID = s.SubjectID
LEFT JOIN Teacher t ON c.TeacherID = t.TeacherID
LEFT JOIN users u ON t.UserID = u.user_id
WHERE 1=1
";

// Apply backend filters
if (!empty($search_title)) {
    $query .= " AND c.CourseTitle LIKE '%" . $conn->real_escape_string($search_title) . "%'";
}
if (!empty($filter_subject)) {
    $query .= " AND c.SubjectID = '" . $conn->real_escape_string($filter_subject) . "'";
}
if (!empty($filter_teacher)) {
    $query .= " AND c.TeacherID = '" . $conn->real_escape_string($filter_teacher) . "'";
}
if (!empty($filter_semester)) {
    $query .= " AND c.Semester = '" . $conn->real_escape_string($filter_semester) . "'";
}

$query .= " ORDER BY c.CourseID DESC";
$result = $conn->query($query);

// Fetch dynamic dropdown choices for the filter UI
$subjects  = $conn->query("SELECT * FROM Subject ORDER BY SubjectName ASC");
$teachers  = $conn->query("SELECT t.TeacherID, u.full_name FROM Teacher t JOIN users u ON t.UserID = u.user_id ORDER BY u.full_name ASC");
$semesters = $conn->query("SELECT DISTINCT Semester FROM Course WHERE Semester IS NOT NULL AND Semester != '' ORDER BY Semester ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Course Management | A too Z LMS</title>
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

.badge-semester {
    background-color: #eff6ff;
    color: #2563eb;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
}

/* Print Header Title (Hidden on screen) */
.print-only-header {
    display: none;
}

/* PRINT STYLESHEET */
@media print {
    #sidebar, 
    .topbar, 
    .filter-card, 
    .action-column, 
    .action-buttons,
    .no-print,
    .btn, 
    #mobileMenuToggle {
        display: none !important;
    }

    body {
        background: #fff !important;
        color: #000 !important;
    }

    .wrapper {
        display: block !important;
    }

    .content, .content.expanded {
        margin-left: 0 !important;
        width: 100% !important;
        padding: 0 !important;
    }

    .section-box {
        box-shadow: none !important;
        border: none !important;
        padding: 0 !important;
        margin-top: 0 !important;
    }

    .print-only-header {
        display: block !important;
        margin-bottom: 20px;
        border-bottom: 2px solid #333;
        padding-bottom: 10px;
    }

    .custom-table {
        width: 100% !important;
        border-collapse: collapse !important;
    }

    .custom-table th, .custom-table td {
        border: 1px solid #ddd !important;
        padding: 8px 10px !important;
        font-size: 12px !important;
    }

    .print-student-list {
        display: block !important;
        font-size: 11px;
        color: #333;
        margin-top: 5px;
    }
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

        <!-- PRINT ONLY HEADER -->
        <div class="print-only-header text-center">
            <h2 class="fw-bold mb-1">A to Z LMS - Course & Student Registration Report</h2>
            <p class="mb-0">Generated Date: <?= date('Y-m-d H:i') ?> | Applied Filters: 
               Title: "<?= $search_title ?: 'All' ?>", 
               Subject ID: "<?= $filter_subject ?: 'All' ?>", 
               Semester: "<?= $filter_semester ?: 'All' ?>"
            </p>
        </div>

        <!-- TOP HEADER AREA -->
        <div class="topbar">
            <div class="left-title">
                <button id="mobileMenuToggle" class="toggle-btn">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    <h1 class="fw-bold mb-1">Course Management</h1>
                    <p class="text-muted mb-0">Create, provision, and audit specific educational course offerings.</p>
                </div>
            </div>
            <div>
                <a href="add_course.php" class="btn-gradient-success">
                    <i class="bi bi-plus-circle me-2"></i>Add Course
                </a>
            </div>
        </div>

        <!-- ADVANCED FILTERS BAR WITH PRINT BUTTON -->
        <div class="section-box filter-card mt-2">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-semibold mb-0"><i class="bi bi-funnel-fill text-primary me-2"></i>Filter Courses</h5>
                <!-- PRINT BUTTON -->
                <button onclick="window.print()" class="btn btn-outline-dark rounded-3 px-3">
                    <i class="bi bi-printer-fill me-2"></i>Print Filtered Report
                </button>
            </div>

            <form method="GET" action="" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-medium text-muted">Course Title</label>
                    <input type="text" name="search_title" class="form-control rounded-3" placeholder="Search by title..." value="<?= htmlspecialchars($search_title) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-medium text-muted">Subject Category</label>
                    <select name="subject_id" class="form-select rounded-3">
                        <option value="">All Subjects</option>
                        <?php 
                        $subjects->data_seek(0);
                        while($sub = $subjects->fetch_assoc()) { ?>
                            <option value="<?= $sub['SubjectID'] ?>" <?= ($filter_subject == $sub['SubjectID']) ? 'selected' : '' ?>><?= htmlspecialchars($sub['SubjectName']) ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-medium text-muted">Assigned Teacher</label>
                    <select name="teacher_id" class="form-select rounded-3">
                        <option value="">All Teachers</option>
                        <?php 
                        $teachers->data_seek(0);
                        while($tch = $teachers->fetch_assoc()) { ?>
                            <option value="<?= $tch['TeacherID'] ?>" <?= ($filter_teacher == $tch['TeacherID']) ? 'selected' : '' ?>><?= htmlspecialchars($tch['full_name']) ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-medium text-muted">Semester</label>
                    <select name="semester" class="form-select rounded-3">
                        <option value="">All Semesters</option>
                        <?php 
                        $semesters->data_seek(0);
                        while($sem = $semesters->fetch_assoc()) { ?>
                            <option value="<?= htmlspecialchars($sem['Semester']) ?>" <?= ($filter_semester == $sem['Semester']) ? 'selected' : '' ?>><?= htmlspecialchars($sem['Semester']) ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-1 d-flex align-items-end gap-1">
                    <button type="submit" class="btn btn-primary w-100 rounded-3 py-2" title="Apply Filters"><i class="bi bi-search"></i></button>
                    <a href="manage_courses.php" class="btn btn-light border w-100 rounded-3 py-2" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
                </div>
            </form>
        </div>

        <!-- STYLED LIST CONTAINER -->
        <div class="section-box">
            <div class="table-responsive">
                <table class="table custom-table table-hover align-middle" id="courseTable">
                    <thead>
                        <tr>
                            <th style="width: 6%;">ID</th>
                            <th style="width: 24%;">Course Title</th>
                            <th style="width: 15%;">Subject Category</th>
                            <th style="width: 15%;">Assigned Teacher</th>
                            <th style="width: 10%;">Semester</th>
                            <th style="width: 18%;">Enrolled Students</th>
                            <th style="width: 12%;" class="text-center action-column">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($result && $result->num_rows > 0) { ?>
                        <?php while($row = $result->fetch_assoc()) { 
                            $rawStudentDetails = !empty($row['StudentDetails']) ? $row['StudentDetails'] : '';
                        ?>
                        <tr>
                            <td class="fw-bold text-secondary">#<?= htmlspecialchars($row['CourseID']) ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="bg-light text-primary rounded-circle d-flex align-items-center justify-content-center no-print" style="width: 34px; height: 34px; font-size: 15px;">
                                        <i class="bi bi-journal-bookmark-fill"></i>
                                    </div>
                                    <span class="fw-semibold text-dark"><?= htmlspecialchars($row['CourseTitle']) ?></span>
                                </div>
                            </td>
                            <td><span class="text-dark fw-medium"><?= htmlspecialchars($row['SubjectName'] ?? 'Unassigned') ?></span></td>
                            <td>
                                <span class="text-muted">
                                    <i class="bi bi-person-badge me-1 no-print"></i><?= htmlspecialchars($row['full_name'] ?? 'Not Appointed') ?>
                                </span>
                            </td>
                            <td><span class="badge-semester"><?= htmlspecialchars($row['Semester']) ?></span></td>
                            <td>
                                <!-- Web View Modal Trigger Button -->
                                <button type="button" class="btn btn-sm btn-light border text-dark fw-bold rounded-3 px-2 no-print" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#studentModal" 
                                        data-course="<?= htmlspecialchars($row['CourseTitle']) ?>"
                                        data-count="<?= $row['TotalStudents'] ?>"
                                        data-details="<?= htmlspecialchars($rawStudentDetails) ?>">
                                    <i class="bi bi-people-fill text-primary me-1"></i><?= $row['TotalStudents'] ?> Students
                                </button>

                                <!-- Print View Output (Includes Name & Registration Number) -->
                                <div class="print-student-list d-none">
                                    <strong>Total: <?= $row['TotalStudents'] ?></strong><br>
                                    <?php 
                                    if(!empty($rawStudentDetails)) {
                                        $studentsArr = explode(';;', $rawStudentDetails);
                                        foreach($studentsArr as $stItem) {
                                            $parts = explode('||', $stItem);
                                            $sName = $parts[0] ?? '';
                                            $sReg  = $parts[1] ?? 'N/A';
                                            echo "&bull; " . htmlspecialchars($sName) . " (Reg: " . htmlspecialchars($sReg) . ")<br>";
                                        }
                                    } else {
                                        echo "<em>No registered students</em>";
                                    }
                                    ?>
                                </div>
                            </td>
                            <td class="action-column">
                                <div class="d-flex justify-content-center gap-1 action-buttons">
                                    <a href="edit_course.php?id=<?= $row['CourseID'] ?>" class="btn btn-outline-primary btn-sm rounded-3 px-2">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </a>
                                    <button onclick="confirmDelete(<?= $row['CourseID'] ?>)" class="btn btn-outline-danger btn-sm rounded-3 px-2">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No course records discovered in the system inventory.</td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- FOOTER NAVIGATION -->
        <div class="mt-4 no-print">
            <a href="dashboard.php" class="btn btn-secondary rounded-3 px-4">
                <i class="bi bi-speedometer2 me-2"></i>Back to Dashboard
            </a>
        </div>

    </main>
</div>

<!-- STUDENT LIST POPUP MODAL -->
<div class="modal fade no-print" id="studentModal" tabindex="-1" aria-labelledby="studentModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:18px;">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold" id="studentModalLabel">Enrolled Students List</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body py-4">
        <div class="mb-3">
            <small class="text-muted d-block">Selected Course:</small>
            <strong id="modalCourseTitle" class="fs-5 text-dark">-</strong>
        </div>
        <div class="p-3 bg-light rounded-3 mb-3 d-flex align-items-center justify-content-between">
            <span class="fw-medium text-secondary">Total Enrolled Count:</span>
            <span id="modalStudentCount" class="badge bg-primary fs-6 px-3 py-2 rounded-3">0</span>
        </div>
        <div>
            <small class="text-muted d-block mb-2 fw-medium">Student Registry Details:</small>
            <div id="modalStudentList" class="p-3 border rounded-3 bg-white text-secondary small" style="max-height: 220px; overflow-y: auto;">
                -
            </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap JS Bundle for Responsive Interactivity -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
/* DELETE CONFIRMATION ACTION */
function confirmDelete(id) {
    if (confirm("Are you sure you want to delete this course profile?")) {
        window.location.href = "delete_course.php?id=" + id;
    }
}

/* DYNAMIC STUDENT MODAL BINDING WITH REGISTRATION NUMBER */
const studentModal = document.getElementById('studentModal');
if (studentModal) {
    studentModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const course = button.getAttribute('data-course');
        const count = parseInt(button.getAttribute('data-count'));
        const rawDetails = button.getAttribute('data-details');

        document.getElementById('modalCourseTitle').textContent = course;
        document.getElementById('modalStudentCount').textContent = count;
        
        const listContainer = document.getElementById('modalStudentList');

        if(count > 0 && rawDetails) {
            const students = rawDetails.split(';;');
            let html = '';
            students.forEach(st => {
                const parts = st.split('||');
                const name = parts[0] || 'Unknown';
                const regNo = parts[1] || 'N/A';

                html += `
                <div class="py-2 border-bottom border-light d-flex justify-content-between align-items-center">
                    <div>
                        <i class="bi bi-person-fill text-primary me-2"></i>
                        <strong class="text-dark">${name}</strong>
                    </div>
                    <span class="badge bg-light text-dark border">Reg: ${regNo}</span>
                </div>`;
            });
            listContainer.innerHTML = html;
        } else {
            listContainer.innerHTML = '<div class="text-center py-2 text-muted">No students currently registered.</div>';
        }
    });
}

/* COLLAPSIBLE SIDEBAR CONFIGURATION */
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

if (mobileToggleBtn) {
    mobileToggleBtn.addEventListener("click", handleToggle);
}
</script>

</body>
</html>