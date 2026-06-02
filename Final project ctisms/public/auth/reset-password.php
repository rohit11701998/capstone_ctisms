<?php
/**
 * Reset Password — Step 2: Enter new password
 */
require_once __DIR__ . '/../../app/bootstrap.php';

if (Auth::check()) redirect(Auth::dashboardUrl());

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$error = '';
$done  = false;

if (!$token || strlen($token) !== 64) {
    $error = 'Invalid or missing reset token.';
} else {
    $db    = Database::getInstance();
    $reset = $db->fetchOne(
        "SELECT pr.*, u.name, u.email
         FROM password_resets pr
         JOIN users u ON u.id = pr.user_id
         WHERE pr.token = ? AND pr.used = 0 AND pr.expires_at > NOW()
         LIMIT 1",
        [$token]
    );

    if (!$reset) {
        $error = 'This reset link is invalid or has expired. Please <a href="forgot-password.php">request a new one</a>.';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrf();

        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm']  ?? '';

        if (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $userModel = new UserModel();
            $userModel->update($reset['user_id'], [
                'password' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12])
            ]);

            // Mark token as used
            $db->query("UPDATE password_resets SET used=1 WHERE token=?", [$token]);

            $log = new ActivityLogModel();
            $log->log('password_reset_completed', "Password reset completed for {$reset['email']}", $reset['user_id']);

            $done = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?= APP_URL ?>/css/app.css" rel="stylesheet">
</head>
<body class="ctisms-body auth-page">

<div class="auth-card">
    <div class="auth-logo"><i class="bi bi-shield-lock"></i></div>
    <h1 class="auth-title">Reset Password</h1>

    <?php if ($done): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle me-2"></i>
            Your password has been reset successfully.
        </div>
        <a href="login.php" class="btn btn-primary w-100 py-2 fw-600 mt-2">
            <i class="bi bi-box-arrow-in-right me-2"></i>Sign In Now
        </a>

    <?php elseif ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
        <div class="text-center mt-3">
            <a href="login.php" class="text-primary fw-600 small">← Back to Login</a>
        </div>

    <?php else: ?>
        <p class="auth-subtitle">Hello <?= e($reset['name']) ?>, enter your new password below</p>

        <?php if ($error): ?>
        <div class="alert alert-danger mb-3"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="token" value="<?= e($token) ?>">

            <div class="mb-3">
                <label class="form-label">New Password *</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" id="password" class="form-control"
                           required placeholder="Min. 8 characters" autofocus>
                    <button type="button" class="btn btn-outline-secondary" onclick="togglePwd('password','eye1')">
                        <i class="bi bi-eye" id="eye1"></i>
                    </button>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Confirm Password *</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                    <input type="password" name="confirm" id="confirm" class="form-control"
                           required placeholder="Repeat new password">
                    <button type="button" class="btn btn-outline-secondary" onclick="togglePwd('confirm','eye2')">
                        <i class="bi bi-eye" id="eye2"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-600">
                <i class="bi bi-shield-check me-2"></i>Reset Password
            </button>
        </form>
        <div class="text-center mt-3">
            <a href="login.php" class="text-primary fw-600 small">← Back to Login</a>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePwd(fieldId, iconId) {
    const f = document.getElementById(fieldId);
    const i = document.getElementById(iconId);
    f.type = f.type === 'password' ? 'text' : 'password';
    i.className = f.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}
</script>
</body>
</html>
