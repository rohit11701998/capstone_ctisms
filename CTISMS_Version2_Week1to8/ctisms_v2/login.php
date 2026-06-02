<?php
// login.php
require_once 'config/db.php';
require_once 'includes/auth.php';
if (isLoggedIn()) { header('Location: ' . dashboardUrl()); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!$email || !$password) {
        $error = 'Please fill in all fields.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            header('Location: ' . dashboardUrl()); exit;
        } else {
            $error = 'Incorrect email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login — CTISMS v2</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<link href="/ctisms_v2/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card">
    <div class="text-center mb-4">
      <i class="bi bi-headset fs-1 text-primary"></i>
      <h4 class="fw-bold mt-1 mb-0">CTISMS <small class="badge bg-warning text-dark">v2</small></h4>
      <p class="text-muted small">IT Support Management System</p>
    </div>
    <?php if ($error): ?>
    <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
      <?= csrfField() ?>
      <div class="mb-3">
        <label class="form-label fw-semibold">Email</label>
        <input type="email" name="email" class="form-control" value="<?= e($_POST['email']??'') ?>" autofocus required>
      </div>
      <div class="mb-4">
        <label class="form-label fw-semibold">Password</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
        <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
      </button>
    </form>
    <div class="mt-3 p-3 rounded bg-light border small">
      <strong>Demo Accounts</strong> — Password: <code>Admin@1234</code><br>
      <span class="badge bg-danger">Admin</span> admin@ctisms.com<br>
      <span class="badge bg-success">Tech</span> tech@ctisms.com<br>
      <span class="badge bg-primary">Customer</span> customer@ctisms.com<br>
      <a href="/ctisms_v2/setup.php" class="d-block mt-1 fw-semibold">⚙ First time? Run setup.php</a>
    </div>
    <p class="text-center small mt-3 mb-0">No account? <a href="/ctisms_v2/register.php">Register</a></p>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
