<?php
session_start();
include("../config/db.php");

$quiz = $_POST['quiz_id'];
$score = 0;

$questions = $conn->query("SELECT * FROM QuizQuestion WHERE QuizID=$quiz");

while($q = $questions->fetch_assoc()) {
    $qid = $q['QuestionID'];

    if(isset($_POST["q$qid"]) && $_POST["q$qid"] == $q['CorrectAnswer']){
        $score++;
    }
}

$stmt = $conn->prepare("
INSERT INTO QuizSubmission (QuizID, StudentID, Score)
VALUES (?, ?, ?)
");

$stmt->bind_param("iii", $quiz, $_SESSION['student_id'], $score);
$stmt->execute();

echo "<h2>Your Score: $score</h2>";
?>