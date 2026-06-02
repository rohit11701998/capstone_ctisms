<?php
// setup.php - Run once to set demo passwords
require_once 'config/db.php';

$password = 'Admin@1234';
$done     = false;
$error    = '';

try {
    $pdo->exec("DELETE FROM users WHERE email = 'test@ctisms.com'");
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->execute(['Test User', 'test@ctisms.com', $hash, 'customer']);

    if (password_verify($password, $pdo->query("SELECT password FROM users WHERE email='test@ctisms.com'")->fetchColumn())) {
        $done = true;
    }
} catch (PDOException $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Setup - CTISMS v1</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100">
<div class="card p-4 shadow" style="max-width:460px;width:100%;border-radius:10px">
    <h5 class="mb-3">CTISMS v1 — Setup</h5>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <p>Did you import <code>database.sql</code> into phpMyAdmin first?</p>
    <?php elseif ($done): ?>
        <div class="alert alert-success">✅ Setup complete!</div>
        <table class="table table-bordered table-sm">
            <tr><th>Email</th><td>test@ctisms.com</td></tr>
            <tr><th>Password</th><td>Admin@1234</td></tr>
        </table>
        <a href="login.php" class="btn btn-primary w-100 mt-2">Go to Login</a>
        <p class="text-muted small mt-2">Delete setup.php after logging in.</p>
    <?php endif; ?>
</div>
</body>
</html>
