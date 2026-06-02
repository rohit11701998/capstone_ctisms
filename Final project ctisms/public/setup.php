<?php
/**
 * CTISMS — Database Setup & Password Fixer
 * =========================================
 * Run this ONCE in your browser after importing schema.sql
 * URL: http://localhost/ctisms/public/setup.php
 *
 * This script will:
 *  1. Connect to your database
 *  2. Generate correct bcrypt hashes using YOUR PHP installation
 *  3. Update all demo user passwords to: Admin@1234
 *  4. Confirm everything is working
 */

// ── Load config ──────────────────────────────────────────────────
$configPath = __DIR__ . '/../config/config.php';
if (!file_exists($configPath)) {
    die('<h2 style="color:red">ERROR: config/config.php not found.<br>Make sure you are running from ctisms/public/setup.php</h2>');
}
require_once $configPath;

// ── Connect to DB ─────────────────────────────────────────────────
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die('
    <div style="font-family:Arial;padding:30px;background:#fff3f3;border:2px solid #e00;border-radius:8px;max-width:600px;margin:40px auto">
      <h2 style="color:#c00;margin:0 0 10px">❌ Database Connection Failed</h2>
      <p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>
      <p><strong>Fix:</strong> Open <code>config/config.php</code> and check your DB_HOST, DB_NAME, DB_USER, DB_PASS settings.</p>
      <p>For XAMPP: DB_USER = <code>root</code>, DB_PASS = <code></code> (blank)</p>
    </div>');
}

// ── Generate fresh hashes for the demo password ───────────────────
$demoPassword = 'Admin@1234';
$hash = password_hash($demoPassword, PASSWORD_BCRYPT, ['cost' => 10]);

$messages = [];
$errors   = [];

// ── Check users table exists ──────────────────────────────────────
try {
    $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $messages[] = "✅ Database connected. Found <strong>{$count}</strong> user(s) in users table.";
} catch (Exception $e) {
    $errors[] = "❌ Users table not found. Did you import database/schema.sql? Error: " . $e->getMessage();
}

// ── Update all demo user passwords ───────────────────────────────
if (empty($errors)) {
    $emails = [
        'admin@ctisms.com',
        'tech1@ctisms.com',
        'tech2@ctisms.com',
        'customer1@ctisms.com',
        'customer2@ctisms.com',
        'customer3@ctisms.com',
    ];

    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
    $updated = 0;
    foreach ($emails as $email) {
        $newHash = password_hash($demoPassword, PASSWORD_BCRYPT, ['cost' => 10]);
        $stmt->execute([$newHash, $email]);
        if ($stmt->rowCount() > 0) {
            $updated++;
            $messages[] = "✅ Password updated for <strong>{$email}</strong>";
        } else {
            $errors[] = "⚠️ User not found in DB: <strong>{$email}</strong> (may not have been imported)";
        }
    }

    if ($updated > 0) {
        $messages[] = "<br><strong>🎉 {$updated} user passwords successfully set to: <code>{$demoPassword}</code></strong>";
    }
}

// ── Verify login works ────────────────────────────────────────────
if (empty($errors)) {
    $user = $pdo->prepare("SELECT password FROM users WHERE email = 'admin@ctisms.com'");
    $user->execute();
    $row = $user->fetch(PDO::FETCH_ASSOC);
    if ($row && password_verify($demoPassword, $row['password'])) {
        $messages[] = "✅ Verification passed — admin login will work correctly.";
    } else {
        $errors[] = "❌ Verification failed — something went wrong with the hash.";
    }
}

// ── Delete this file reminder ─────────────────────────────────────
$selfPath = __FILE__;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>CTISMS Setup</title>
<style>
  * { box-sizing:border-box; margin:0; padding:0 }
  body { font-family:Arial,sans-serif; background:#f0f2f8; display:flex; align-items:center; justify-content:center; min-height:100vh; padding:20px }
  .card { background:#fff; border-radius:12px; box-shadow:0 4px 24px rgba(0,0,0,0.1); padding:40px; max-width:680px; width:100% }
  h1 { font-size:22px; color:#1a237e; margin-bottom:6px }
  .sub { color:#666; font-size:14px; margin-bottom:28px }
  .msg { padding:10px 14px; border-radius:6px; margin-bottom:8px; font-size:14px; line-height:1.6 }
  .msg-ok  { background:#e8f5e9; border-left:4px solid #2e7d32; color:#1b5e20 }
  .msg-err { background:#ffebee; border-left:4px solid #c62828; color:#b71c1c }
  .creds { background:#f8f9ff; border:1px solid #c5cae9; border-radius:8px; padding:20px; margin:24px 0 }
  .creds h3 { color:#1a237e; font-size:15px; margin-bottom:12px }
  table { width:100%; border-collapse:collapse; font-size:13px }
  th { text-align:left; padding:7px 10px; background:#e8eaf6; color:#3949ab; font-weight:600 }
  td { padding:7px 10px; border-bottom:1px solid #e0e0e0 }
  tr:last-child td { border-bottom:none }
  code { background:#eee; padding:2px 6px; border-radius:4px; font-size:12px }
  .btn { display:inline-block; background:#1a237e; color:#fff; text-decoration:none; padding:12px 28px; border-radius:6px; font-size:15px; font-weight:600; margin-top:20px }
  .btn:hover { background:#283593 }
  .warn { background:#fff3e0; border:1px solid #ffb74d; border-radius:6px; padding:14px; font-size:13px; color:#e65100; margin-top:20px }
</style>
</head>
<body>
<div class="card">
  <h1>🔧 CTISMS — Setup Complete</h1>
  <p class="sub">Password fixer ran on your PHP <?= PHP_VERSION ?> installation</p>

  <?php foreach ($messages as $m): ?>
  <div class="msg msg-ok"><?= $m ?></div>
  <?php endforeach; ?>

  <?php foreach ($errors as $e): ?>
  <div class="msg msg-err"><?= $e ?></div>
  <?php endforeach; ?>

  <?php if (empty($errors)): ?>
  <div class="creds">
    <h3>🔐 Login Credentials (all updated)</h3>
    <table>
      <thead><tr><th>Role</th><th>Email</th><th>Password</th></tr></thead>
      <tbody>
        <tr><td><strong>Admin</strong></td><td>admin@ctisms.com</td><td><code>Admin@1234</code></td></tr>
        <tr><td>Technician</td><td>tech1@ctisms.com</td><td><code>Admin@1234</code></td></tr>
        <tr><td>Technician</td><td>tech2@ctisms.com</td><td><code>Admin@1234</code></td></tr>
        <tr><td>Customer</td><td>customer1@ctisms.com</td><td><code>Admin@1234</code></td></tr>
        <tr><td>Customer</td><td>customer2@ctisms.com</td><td><code>Admin@1234</code></td></tr>
        <tr><td>Customer</td><td>customer3@ctisms.com</td><td><code>Admin@1234</code></td></tr>
      </tbody>
    </table>
  </div>

  <a href="<?= defined('APP_URL') ? APP_URL : '/ctisms/public' ?>/auth/login.php" class="btn">
    → Go to Login Page
  </a>

  <div class="warn">
    ⚠️ <strong>Security:</strong> Delete or rename <code>public/setup.php</code> after you have successfully logged in.
    This file should not be accessible on a live/production server.
  </div>
  <?php endif; ?>
</div>
</body>
</html>
