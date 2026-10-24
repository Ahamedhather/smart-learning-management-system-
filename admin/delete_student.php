<?php
include("../config/db.php");

$id = $_GET['id'];

$conn->query("DELETE FROM Student WHERE StudentID=$id");

header("Location: manage_students.php");
?>