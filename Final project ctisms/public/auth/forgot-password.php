<?php
/**
 * Forgot Password — Step 1: Request reset link
 */
require_once __DIR__ . '/../../app/bootstrap.php';

if (Auth::check()) redirect(Auth::dashboardUrl());

$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $userModel = new UserModel();
        $user      = $userModel->findByEmail($email);

        // Always show success message to prevent user enumeration
        if ($user) {
            // Generate secure token
            $token   = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour

            // Store token in DB
            $db = Database::getInstance();
            // Create table if not exists (first run)
            $db->query("
                CREATE TABLE IF NOT EXISTS `password_resets` (
                    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `user_id`    INT UNSIGNED NOT NULL,
                    `token`      VARCHAR(64)  NOT NULL,
                    `expires_at` DATETIME     NOT NULL,
                    `used`       TINYINT(1)   NOT NULL DEFAULT 0,
                    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `idx_token` (`token`),
                    CONSTRAINT `fk_pr_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // Invalidate previous tokens for this user
            $db->query("UPDATE password_resets SET used=1 WHERE user_id=?", [$user['id']]);

            // Insert new token
            $db->query(
                "INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)",
                [$user['id'], $token, $expires]
            );

            $resetUrl = APP_URL . '/auth/reset-password.php?token=' . $token;

            // Send email
            $emailBody = "
                <p>Dear {$user['name']},</p>
                <p>We received a request to reset your password for your CTISMS account.</p>
                <p style='margin:20px 0'>
                    <a href='$resetUrl' style='background:#1a237e;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:bold;'>
                        Reset My Password
                    </a>
                </p>
                <p>Or copy this link into your browser:</p>
                <p><code>$resetUrl</code></p>
                <p>This link expires in <strong>1 hour</strong>. If you did not request a password reset, please ignore this email.</p>
            ";

            MailService::send($user['email'], $user['name'], '[' . APP_NAME . '] Password Reset Request', $emailBody);

            $log = new ActivityLogModel();
            $log->log('password_reset_requested', "Password reset requested for {$user['email']}", $user['id']);
        }

        $message = 'If that email address is registered, you will receive a password reset link shortly. Please check your inbox (and spam folder).';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?= APP_URL ?>/css/app.css" rel="stylesheet">
</head>
<body class="ctisms-body auth-page">

<div class="auth-card">
    <div class="auth-logo"><i class="bi bi-key"></i></div>
    <h1 class="auth-title">Forgot Password</h1>
    <p class="auth-subtitle">Enter your email and we'll send you a reset link</p>

    <?php if ($error): ?>
    <div class="alert alert-danger mb-3"><i class="bi bi-exclamation-circle me-2"></i><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($message): ?>
    <div class="alert alert-success mb-3"><i class="bi bi-check-circle me-2"></i><?= e($message) ?></div>
    <div class="text-center mt-3">
        <a href="login.php" class="btn btn-primary">← Back to Login</a>
    </div>
    <?php else: ?>
    <form method="POST">
        <?= csrfField() ?>
        <div class="mb-4">
            <label class="form-label">Email Address</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" name="email" class="form-control" required
                       value="<?= e($_POST['email'] ?? '') ?>" placeholder="your@email.com" autofocus>
            </div>
        </div>
        <button type="submit" class="btn btn-primary w-100 py-2 fw-600">
            <i class="bi bi-send me-2"></i>Send Reset Link
        </button>
    </form>
    <div class="text-center mt-4">
        <a href="login.php" class="text-primary fw-600 small">← Back to Login</a>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
