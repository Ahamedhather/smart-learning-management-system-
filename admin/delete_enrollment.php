<?php
include("../config/db.php");

$id = $_GET['id'];

$conn->query("DELETE FROM Enrollment WHERE EnrollmentID=$id");

header("Location: manage_enrollment.php");
?>