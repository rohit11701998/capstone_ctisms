<?php
require_once __DIR__ . '/../../app/bootstrap.php';

// Already logged in
if (Auth::check()) redirect(Auth::dashboardUrl());

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Please enter your email and password.';
    } else {
        $userModel = new UserModel();
        $user = $userModel->findByEmail($email);

        if ($user && $userModel->verifyPassword($password, $user['password'])) {
            Auth::login($user);
            $userModel->updateLastLogin($user['id']);

            // Activity log
            $log = new ActivityLogModel();
            $log->log('login', "User {$user['name']} logged in", $user['id']);

            $redirect = $_GET['redirect'] ?? Auth::dashboardUrl();
            redirect($redirect);
        } else {
            $error = 'Invalid email or password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?= APP_URL ?>/css/app.css" rel="stylesheet">
</head>
<body class="ctisms-body auth-page">

<div class="auth-card">
    <div class="auth-logo">
        <i class="bi bi-headset"></i>
    </div>
    <h1 class="auth-title"><?= APP_NAME ?></h1>
    <p class="auth-subtitle">IT Support and Computer Repair Services</p>

    <?php if ($error): ?>
    <div class="alert alert-danger mb-3">
        <i class="bi bi-exclamation-circle me-2"></i><?= e($error) ?>
    </div>
    <?php endif; ?>

    <?= renderFlash() ?>

    <form method="POST" action="">
        <?= csrfField() ?>

        <div class="mb-3">
            <label class="form-label">Email Address</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" name="email" class="form-control" required
                       value="<?= e($_POST['email'] ?? '') ?>"
                       placeholder="your@email.com" autofocus>
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label d-flex justify-content-between">
                Password
                <a href="forgot-password.php" class="text-primary fw-600 small">Forgot password?</a>
            </label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" name="password" id="password" class="form-control" required placeholder="••••••••">
                <button type="button" class="btn btn-outline-secondary" onclick="togglePwd()">
                    <i class="bi bi-eye" id="eyeIcon"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2 fw-600">
            <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
        </button>
    </form>

    <div class="text-center mt-4">
        <span class="text-muted small">Don't have an account?</span>
        <a href="register.php" class="text-primary fw-600 small ms-1">Register here</a>
    </div>

    
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePwd() {
    const p = document.getElementById('password');
    const i = document.getElementById('eyeIcon');
    if (p.type === 'password') { p.type = 'text'; i.className = 'bi bi-eye-slash'; }
    else { p.type = 'password'; i.className = 'bi bi-eye'; }
}
</script>
</body>
</html>
