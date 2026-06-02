<?php
// register.php — Week 5: role-based registration
require_once 'config/db.php';
require_once 'includes/auth.php';
if (isLoggedIn()) { header('Location: ' . dashboardUrl()); exit; }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';
    $role     = $_POST['role'] ?? 'customer';

    if (strlen($name) < 2)                           $errors[] = 'Name is too short.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))  $errors[] = 'Invalid email address.';
    if (strlen($password) < 6)                       $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm)                      $errors[] = 'Passwords do not match.';
    if (!in_array($role, ['customer','technician','admin'])) $errors[] = 'Invalid role.';

    if (empty($errors)) {
        $db  = getDB();
        $chk = $db->prepare("SELECT id FROM users WHERE email=?");
        $chk->execute([$email]);
        if ($chk->fetch()) {
            $errors[] = 'Email already registered.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost'=>10]);
            $db->prepare("INSERT INTO users (name,email,password,role) VALUES(?,?,?,?)")
               ->execute([$name,$email,$hash,$role]);
            setFlash('success','Account created! Please log in.');
            header('Location: /ctisms_v2/login.php'); exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Register — CTISMS v2</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<link href="/ctisms_v2/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card">
    <div class="text-center mb-4">
      <i class="bi bi-headset fs-1 text-primary"></i>
      <h4 class="fw-bold mt-1">CTISMS <small class="badge bg-warning text-dark">v2</small></h4>
      <p class="text-muted small">Create your account</p>
    </div>
    <?php if ($errors): ?>
    <div class="alert alert-danger py-2">
      <ul class="mb-0 ps-3"><?php foreach($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul>
    </div>
    <?php endif; ?>
    <form method="POST">
      <?= csrfField() ?>
      <div class="mb-3">
        <label class="form-label fw-semibold">Full Name</label>
        <input type="text" name="name" class="form-control" value="<?= e($_POST['name']??'') ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold">Email</label>
        <input type="email" name="email" class="form-control" value="<?= e($_POST['email']??'') ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold">Role</label>
        <select name="role" class="form-select">
          <option value="customer"   <?= (($_POST['role']??'')=='customer')   ?'selected':'' ?>>Customer</option>
          <option value="technician" <?= (($_POST['role']??'')=='technician') ?'selected':'' ?>>Technician</option>
          <option value="admin"      <?= (($_POST['role']??'')=='admin')      ?'selected':'' ?>>Admin</option>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold">Password</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <div class="mb-4">
        <label class="form-label fw-semibold">Confirm Password</label>
        <input type="password" name="confirm" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Create Account</button>
    </form>
    <p class="text-center small mt-3 mb-0">Already registered? <a href="/ctisms_v2/login.php">Login</a></p>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
