<?php
require_once __DIR__ . '/../../app/bootstrap.php';

if (Auth::check()) redirect(Auth::dashboardUrl());

$errors = [];
$data   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $data = [
        'name'       => trim($_POST['name'] ?? ''),
        'email'      => trim($_POST['email'] ?? ''),
        'password'   => $_POST['password'] ?? '',
        'confirm'    => $_POST['confirm'] ?? '',
        'phone'      => trim($_POST['phone'] ?? ''),
        'department' => trim($_POST['department'] ?? ''),
        'role'       => 'customer', // Customers self-register only
    ];

    if (strlen($data['name']) < 2)     $errors[] = 'Name must be at least 2 characters.';
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';
    if (strlen($data['password']) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($data['password'] !== $data['confirm']) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $userModel = new UserModel();
        if ($userModel->emailExists($data['email'])) {
            $errors[] = 'This email is already registered.';
        } else {
            $userId = $userModel->create($data);
            $user   = $userModel->find($userId);
            Auth::login($user);

            $log = new ActivityLogModel();
            $log->log('register', "New customer {$data['name']} registered", $userId);

            redirectTo(APP_URL . '/customer/dashboard.php', 'success', 'Welcome! Your account has been created.');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?= APP_URL ?>/css/app.css" rel="stylesheet">
</head>
<body class="ctisms-body auth-page">

<div class="auth-card" style="max-width:520px">
    <div class="auth-logo"><i class="bi bi-person-plus"></i></div>
    <h1 class="auth-title">Create Account</h1>
    <p class="auth-subtitle">Register to submit support tickets</p>

    <?php if ($errors): ?>
    <div class="alert alert-danger mb-3">
        <ul class="mb-0 ps-3">
            <?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <form method="POST">
        <?= csrfField() ?>
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label">Full Name *</label>
                <input type="text" name="name" class="form-control" required
                       value="<?= e($data['name'] ?? '') ?>" placeholder="John Smith">
            </div>
            <div class="col-12">
                <label class="form-label">Email Address *</label>
                <input type="email" name="email" class="form-control" required
                       value="<?= e($data['email'] ?? '') ?>" placeholder="john@company.com">
            </div>
            <div class="col-md-6">
                <label class="form-label">Phone</label>
                <input type="tel" name="phone" class="form-control"
                       value="<?= e($data['phone'] ?? '') ?>" placeholder="+61 4XX XXX XXX">
            </div>
            <div class="col-md-6">
                <label class="form-label">Department</label>
                <input type="text" name="department" class="form-control"
                       value="<?= e($data['department'] ?? '') ?>" placeholder="Finance">
            </div>
            <div class="col-md-6">
                <label class="form-label">Password *</label>
                <input type="password" name="password" class="form-control" required placeholder="Min 8 characters">
            </div>
            <div class="col-md-6">
                <label class="form-label">Confirm Password *</label>
                <input type="password" name="confirm" class="form-control" required placeholder="Repeat password">
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary w-100 py-2 fw-600">
                    <i class="bi bi-person-check me-2"></i>Create Account
                </button>
            </div>
        </div>
    </form>

    <div class="text-center mt-3">
        <span class="text-muted small">Already have an account?</span>
        <a href="login.php" class="text-primary fw-600 small ms-1">Sign in</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
