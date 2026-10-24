<?php
include("../config/db.php");

// validate POST
if(!isset($_POST['course']) || !isset($_POST['status']) || !isset($_POST['attendance_date'])){
    echo "Invalid data!";
    exit();
}

$course = intval($_POST['course']);
$date = $_POST['attendance_date']; // Fetches the selected date from the form input instead of hardcoding today's date

foreach($_POST['status'] as $student_id => $status){
    $student_id = intval($student_id);

    // Check if an attendance record already exists for this specific student, course, and date
    $stmt = $conn->prepare("
        SELECT AttendanceID FROM Attendance 
        WHERE StudentID=? AND CourseID=? AND AttendanceDate=?
    ");
    $stmt->bind_param("iis", $student_id, $course, $date);
    $stmt->execute();
    $check = $stmt->get_result();

    if($check->num_rows > 0){
        // UPDATE existing record if it already exists for this date
        $stmt = $conn->prepare("
            UPDATE Attendance 
            SET Status = ? 
            WHERE StudentID = ? AND CourseID = ? AND AttendanceDate = ?
        ");
        $stmt->bind_param("siis", $status, $student_id, $course, $date);
        $stmt->execute();
    } else {
        // INSERT new record if it does not exist for this date
        $stmt = $conn->prepare("
            INSERT INTO Attendance (StudentID, CourseID, AttendanceDate, Status)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("iiss", $student_id, $course, $date, $status);
        $stmt->execute();
    }
}

echo "<script>alert('Attendance Saved Successfully'); window.location='view_attendance.php?course=$course';</script>";
?>