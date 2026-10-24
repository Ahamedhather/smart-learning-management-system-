<?php
include("../config/db.php");

$id = $_GET['id'];

$conn->query("DELETE FROM Teacher WHERE TeacherID=$id");

header("Location: manage_teachers.php");
?>