<?php
include("../config/db.php");

$stmt = $conn->prepare("
INSERT INTO QuizQuestion (QuizID, Question, OptionA, OptionB, OptionC, OptionD, CorrectAnswer)
VALUES (?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param("issssss",
$_POST['quiz'],
$_POST['question'],
$_POST['a'],
$_POST['b'],
$_POST['c'],
$_POST['d'],
$_POST['correct']
);

$stmt->execute();

header("Location: add_question.php?quiz=" . $_POST['quiz']);
?>