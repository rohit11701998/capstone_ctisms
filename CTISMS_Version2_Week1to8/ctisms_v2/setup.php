<?php
// setup.php — Run ONCE after importing database.sql
// URL: http://localhost/ctisms_v2/setup.php
require_once 'config/db.php';

$password = 'Admin@1234';
$done     = false;
$error    = '';

try {
    $db = getDB();
    $db->query("SELECT 1 FROM users LIMIT 1"); // confirm table exists

    $db->exec("DELETE FROM users WHERE email IN
               ('admin@ctisms.com','tech@ctisms.com','customer@ctisms.com')");

    $stmt = $db->prepare(
        "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)"
    );

    $accounts = [
        ['Admin User',    'admin@ctisms.com',    'admin'],
        ['Tech Alice',    'tech@ctisms.com',     'technician'],
        ['John Customer', 'customer@ctisms.com', 'customer'],
    ];

    foreach ($accounts as $a) {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
        $stmt->execute([$a[0], $a[1], $hash, $a[2]]);
    }

    // Verify
    $row = $db->query("SELECT password FROM users WHERE email='admin@ctisms.com'")->fetchColumn();
    $done = ($row && password_verify($password, $row));

} catch (PDOException $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Setup — CTISMS v2</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100">
<div class="card shadow p-4" style="max-width:500px;width:100%;border-radius:12px">
    <h5 class="mb-3 fw-bold">CTISMS v2 — First-Time Setup</h5>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <p class="small text-muted">
            Did you import <code>database.sql</code> into phpMyAdmin first?<br>
            Database name should be: <strong>ctisms_v2</strong>
        </p>
    <?php elseif ($done): ?>
        <div class="alert alert-success fw-semibold">✅ Setup complete!</div>
        <table class="table table-bordered table-sm mb-3">
            <thead class="table-light">
                <tr><th>Role</th><th>Email</th><th>Password</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="badge bg-danger">Admin</span></td>
                    <td>admin@ctisms.com</td>
                    <td><code>Admin@1234</code></td>
                </tr>
                <tr>
                    <td><span class="badge bg-success">Technician</span></td>
                    <td>tech@ctisms.com</td>
                    <td><code>Admin@1234</code></td>
                </tr>
                <tr>
                    <td><span class="badge bg-primary">Customer</span></td>
                    <td>customer@ctisms.com</td>
                    <td><code>Admin@1234</code></td>
                </tr>
            </tbody>
        </table>
        <a href="/ctisms_v2/login.php" class="btn btn-primary w-100">
            → Go to Login Page
        </a>
        <p class="text-muted small mt-3 mb-0">
            ⚠️ Delete or rename <code>setup.php</code> after logging in successfully.
        </p>
    <?php endif; ?>
</div>
</body>
</html>
