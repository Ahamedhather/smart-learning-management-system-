<?php  
session_start();

// Already logged in redirect
// if(isset($_SESSION['role'])){
//     if($_SESSION['role'] == 'student'){
//         header("Location: student/dashboard.php");
//         exit();
//     } elseif($_SESSION['role'] == 'teacher'){
//         header("Location: teacher/dashboard.php");
//         exit();
//     } elseif($_SESSION['role'] == 'admin'){
//         header("Location: admin/dashboard.php");
//         exit();
//     }
// }
// ?>

<!DOCTYPE html>
<html>
<head>
<title>LMS Login</title>

<!-- Bootstrap 5 & Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<style>
body {
    min-height: 100vh;
    background: linear-gradient(120deg, #2563eb, #7c3aed);
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: Arial, sans-serif;
}
.login-card {
    width: 390px;
    background: #fff;
    padding: 32px;
    border-radius: 18px;
    box-shadow: 0 15px 35px rgba(0,0,0,0.25);
    animation: fadeUp 0.6s ease-in-out;
}
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(22px); }
    to { opacity: 1; transform: translateY(0); }
}
.form-control {
    border-radius: 10px;
}
.btn {
    border-radius: 10px;
}
.logo {
    width: 65px;
    height: 65px;
    border-radius: 50%;
    background: #2563eb;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
    font-size: 28px;
    font-weight: bold;
}
</style>
</head>

<body>

<div class="login-card">

<div class="logo">L</div>

<h3 class="text-center mb-1">LMS Login</h3>
<p class="text-center text-muted mb-4">Smart Learning Management System</p>

<?php if(isset($_GET['error'])){ ?>
<div class="alert alert-danger">
    Invalid login or account not setup properly!
</div>
<?php } ?>

<form action="login_process.php" method="POST" onsubmit="return validateLogin()">

<label class="form-label">Username</label>
<input type="text" name="username" id="username" class="form-control mb-3" placeholder="Enter username" required>

<label class="form-label">Password</label>
<div class="input-group mb-3">
    <input type="password" name="password" id="password" class="form-control" placeholder="Enter password" required>
    <button class="btn btn-outline-secondary" type="button" id="togglePassword" style="border-top-right-radius: 10px; border-bottom-right-radius: 10px;">
        <i class="bi bi-eye" id="toggleIcon"></i>
    </button>
</div>

<button type="submit" class="btn btn-primary w-100">Login</button>

</form>

</div>

<script>
// Toggle Password Visibility Function
const togglePassword = document.getElementById("togglePassword");
const passwordInput = document.getElementById("password");
const toggleIcon = document.getElementById("toggleIcon");

togglePassword.addEventListener("click", function () {
    // Toggle the type attribute
    const type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
    passwordInput.setAttribute("type", type);
    
    // Toggle the eye / eye-slash icon
    toggleIcon.classList.toggle("bi-eye");
    toggleIcon.classList.toggle("bi-eye-slash");
});

function validateLogin() {
    let username = document.getElementById("username").value.trim();
    let password = document.getElementById("password").value.trim();

    if (username.length < 3) {
        alert("Username must be at least 3 characters.");
        return false;
    }

    if (password.length < 4) {
        alert("Password must be at least 4 characters.");
        return false;
    }

    return true;
}
</script>

</body>
</html>