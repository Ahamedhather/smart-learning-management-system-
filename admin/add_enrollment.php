<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("../config/db.php");
include("../config/functions.php");

$error = "";
$selected_student = 0;
$selected_course = 0;

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $student = intval($_POST['student']);
    $course = intval($_POST['course']);

    $selected_student = $student;
    $selected_course = $course;

    if ($student <= 0 || $course <= 0) {
        $error = "Please select both a student and a course.";
    } else {

        // Check if student is already enrolled in this course
        $check = $conn->prepare("
            SELECT EnrollmentID FROM Enrollment 
            WHERE StudentID = ? AND CourseID = ?
        ");
        $check->bind_param("ii", $student, $course);
        $check->execute();
        $checkResult = $check->get_result();

        if ($checkResult->num_rows > 0) {
            $error = "This student is already enrolled in the selected course.";
        } else {
            // Insert new enrollment record
            $stmt = $conn->prepare("
                INSERT INTO Enrollment (StudentID, CourseID, EnrollmentDate)
                VALUES (?, ?, NOW())
            ");

            $stmt->bind_param("ii", $student, $course);

            if ($stmt->execute()) {
                header("Location: manage_enrollment.php");
                exit();
            } else {
                $error = "Enrollment failed. Please try again.";
            }
        }
    }
}

// Fetch Active Students
$students = $conn->query("
    SELECT s.StudentID, s.RegistrationNumber, u.full_name
    FROM Student s
    JOIN users u ON s.UserID = u.user_id
    WHERE s.Status = 'Active'
    ORDER BY u.full_name ASC
");

// Fetch Available Courses
$courses = $conn->query("
    SELECT CourseID, CourseTitle, Semester
    FROM Course
    ORDER BY CourseTitle ASC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Enrollment | LMS</title>
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

.form-select {
    border-radius: 10px;
    padding: 10px 14px;
}

.input-group-text {
    border-radius: 10px 0 0 10px;
}

.form-select:focus {
    box-shadow: 0 0 0 0.25rem rgba(109, 53, 245, 0.15);
    border-color: #8b5cf6;
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
                    <h1 class="fw-bold mb-1">Add Course Enrollment</h1>
                    <p class="text-muted mb-0">Assign an active student to an academic course offering.</p>
                </div>
            </div>
            <div>
                <a href="manage_enrollment.php" class="btn btn-outline-secondary rounded-3 px-3">
                    <i class="bi bi-arrow-left me-1"></i> Back to Enrollments
                </a>
            </div>
        </div>

        <!-- FORM CONTAINER -->
        <div class="section-box">

            <!-- ERROR ALERT -->
            <?php if($error != "") { ?>
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                    <div><?= htmlspecialchars($error) ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php } ?>

            <form method="POST" onsubmit="return validateForm()">

                <div class="row g-3">
                    
                    <!-- Student Selection Dropdown -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Select Student <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-person-vcard text-primary"></i></span>
                            <select name="student" id="student" class="form-select" required>
                                <option value="">-- Choose Student --</option>
                                <?php while($s = $students->fetch_assoc()) { ?>
                                    <option value="<?= $s['StudentID'] ?>" <?= ($s['StudentID'] == $selected_student) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s['full_name']) ?> (Reg: <?= htmlspecialchars($s['RegistrationNumber']) ?>)
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>

                    <!-- Course Selection Dropdown -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Select Course <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-journal-bookmark text-primary"></i></span>
                            <select name="course" id="course" class="form-select" required>
                                <option value="">-- Choose Course --</option>
                                <?php while($c = $courses->fetch_assoc()) { ?>
                                    <option value="<?= $c['CourseID'] ?>" <?= ($c['CourseID'] == $selected_course) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['CourseTitle']) ?> - <?= htmlspecialchars($c['Semester'] ?? 'N/A') ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="col-12 pt-3 d-flex gap-2">
                        <button type="submit" class="btn btn-success rounded-3 px-4 py-2 fw-semibold">
                            <i class="bi bi-check-circle me-1"></i> Enroll Student
                        </button>
                        <a href="manage_enrollment.php" class="btn btn-light border rounded-3 px-4 py-2">
                            Cancel
                        </a>
                    </div>

                </div>

            </form>
        </div>

    </main>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
/* FORM VALIDATION SCRIPT */
function validateForm() {
    let student = document.getElementById("student").value;
    let course = document.getElementById("course").value;

    if (student === "") {
        alert("Please select a student.");
        return false;
    }

    if (course === "") {
        alert("Please select a course.");
        return false;
    }

    return true;
}

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