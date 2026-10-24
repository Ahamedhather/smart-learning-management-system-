<?php
// delete_quiz_process.php

// Ensure session is started if not already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check for delete_quiz parameter in the URL
if (isset($_GET['delete_quiz'])) {
    $quiz_id_to_delete = intval($_GET['delete_quiz']);
    $course_id = intval($_GET['course'] ?? 0);

    if ($quiz_id_to_delete > 0) {
        // Optional: Verify that the quiz belongs to a course owned/managed by the logged-in teacher if needed, 
        // or directly delete since page security protects the parent script.
        
        // 1. Delete associated questions first (if foreign keys don't cascade automatically)
        $stmt_q = $conn->prepare("DELETE FROM QuizQuestion WHERE QuizID = ?");
        $stmt_q->bind_param("i", $quiz_id_to_delete);
        $stmt_q->execute();
        $stmt_q->close();

        // 2. Delete the quiz itself
        $stmt_quiz = $conn->prepare("DELETE FROM Quiz WHERE QuizID = ?");
        $stmt_quiz->bind_param("i", $quiz_id_to_delete);

        if ($stmt_quiz->execute()) {
            $_SESSION['success'] = "Quiz and its questions deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete the quiz.";
        }
        $stmt_quiz->close();
    }

    // Redirect back to the quiz list for the specific course
    $redirect_url = ($course_id > 0) ? "quiz_list.php?course=" . $course_id : "my_courses.php";
    header("Location: " . $redirect_url);
    exit();
}
?>