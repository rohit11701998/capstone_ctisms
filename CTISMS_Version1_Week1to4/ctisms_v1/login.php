<?php
// login.php - Week 2: User Login with Sessions
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

require_once 'config/db.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Session setup - Week 2
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];

            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Incorrect email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - CTISMS v1</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body style="background:#f0f2f5;">

<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">CTISMS</a>
    </div>
</nav>

<div class="auth-box">
    <h4 class="text-center mb-4" style="color:#003366;">
        IT Support Login
    </h4>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control" autofocus required>
        </div>

        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>

        <div class="d-grid mt-4">
            <button type="submit" class="btn btn-primary">Login</button>
        </div>
    </form>

    <hr>
    <!-- Demo credentials box - added for testing -->
    <div style="background:#f8f9fa;border:1px solid #dee2e6;border-radius:6px;padding:12px;font-size:13px;">
        <strong>Demo Credentials</strong><br>
        Email: test@ctisms.com<br>
        Password: Admin@1234<br>
        <small class="text-muted">Run setup.php first if login fails</small>
    </div>

    <p class="text-center mt-3 small">
        No account? <a href="register.php">Register here</a>
    </p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
