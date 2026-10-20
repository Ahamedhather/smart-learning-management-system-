<?php
session_start();
include("../config/db.php");

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $username = $_POST['username'];
    $password = $_POST['password'];

    // Step 1: check user
    $stmt = $conn->prepare("SELECT * FROM users WHERE username=? AND status='active'");
    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();

    if($result->num_rows == 1){

        $user = $result->fetch_assoc();

        // Step 2: verify password
        if(password_verify($password, $user['password'])){

            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role']    = $user['role'];
            $_SESSION['username'] = $user['username'];

            // Step 3: check role
            if($user['role'] == 'student'){

                // verify student exists
                $res = $conn->query("SELECT StudentID FROM Student WHERE UserID=".$user['user_id']);

                if($res && $res->num_rows > 0){
                    $stu = $res->fetch_assoc();
                    $_SESSION['student_id'] = $stu['StudentID'];

                    header("Location: ../student/dashboard.php");
                    exit();

                } else {
                    // ❌ student record missing
                    session_destroy();
                    header("Location: login.php?error=1");
                    exit();
                }

            } elseif($user['role'] == 'teacher'){

                // verify teacher exists
                $res = $conn->query("SELECT TeacherID FROM Teacher WHERE UserID=".$user['user_id']);

                if($res && $res->num_rows > 0){
                    $t = $res->fetch_assoc();
                    $_SESSION['teacher_id'] = $t['TeacherID'];

                    header("Location: ../teacher/dashboard.php");
                    exit();

                } else {
                    session_destroy();
                    header("Location: login.php?error=1");
                    exit();
                }

            } elseif($user['role'] == 'admin'){

                header("Location: ../admin/dashboard.php");
                exit();
            }

        } else {
            // wrong password
            header("Location: login.php?error=1");
            exit();
        }

    } else {
        // user not found
        header("Location: login.php?error=1");
        exit();
    }
}
?>