<?php
include("../config/db.php");
include("../config/functions.php");

// Attendance Report
$attendance = $conn->query("
SELECT u.FullName, c.CourseTitle,
SUM(CASE WHEN a.Status='Present' THEN 1 ELSE 0 END) AS PresentDays,
COUNT(*) AS TotalDays
FROM Attendance a
JOIN Student s ON a.StudentID = s.StudentID
JOIN Users u ON s.UserID = u.UserID
JOIN Course c ON a.CourseID = c.CourseID
GROUP BY a.StudentID, a.CourseID
");

// Performance Report
$performance = $conn->query("
SELECT u.FullName, AVG(asub.Marks) AS AvgMarks
FROM AssignmentSubmission asub
JOIN Student s ON asub.StudentID = s.StudentID
JOIN Users u ON s.UserID = u.UserID
GROUP BY asub.StudentID
");
?>

<h2>Attendance Report</h2>
<table class="table">
<tr><th>Name</th><th>Course</th><th>Present</th><th>Total</th></tr>

<?php while($row = $attendance->fetch_assoc()) { ?>
<tr>
<td><?= $row['FullName'] ?></td>
<td><?= $row['CourseTitle'] ?></td>
<td><?= $row['PresentDays'] ?></td>
<td><?= $row['TotalDays'] ?></td>
</tr>
<?php } ?>
</table>

<h2>Performance Report</h2>
<table class="table">
<tr><th>Name</th><th>Average Marks</th></tr>

<?php while($row = $performance->fetch_assoc()) { ?>
<tr>
<td><?= $row['FullName'] ?></td>
<td><?= number_format($row['AvgMarks'],2) ?></td>
</tr>
<?php } ?>
</table>