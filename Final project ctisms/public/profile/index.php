<?php
require_once __DIR__ . '/../../app/bootstrap.php';
Auth::requireAuth();

$userModel = new UserModel();
$user      = $userModel->find(Auth::id());
$errors    = [];
$success   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $name       = trim($_POST['name'] ?? '');
        $phone      = trim($_POST['phone'] ?? '');
        $department = trim($_POST['department'] ?? '');

        if (strlen($name) < 2) {
            $errors[] = 'Name must be at least 2 characters.';
        } else {
            $userModel->update(Auth::id(), [
                'name' => $name, 'phone' => $phone, 'department' => $department
            ]);
            $_SESSION['user_name'] = $name;
            $user = $userModel->find(Auth::id());
            $success = 'Profile updated successfully.';
        }
    }

    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $newPwd  = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!$userModel->verifyPassword($current, $user['password'])) {
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($newPwd) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        } elseif ($newPwd !== $confirm) {
            $errors[] = 'Passwords do not match.';
        } else {
            $userModel->update(Auth::id(), [
                'password' => password_hash($newPwd, PASSWORD_BCRYPT, ['cost' => 12])
            ]);
            $success = 'Password changed successfully.';
            $log = new ActivityLogModel();
            $log->log('password_changed', Auth::name() . ' changed their password');
        }
    }
}

$pageTitle = 'My Profile';
include __DIR__ . '/../../app/views/layouts/header.php';
include __DIR__ . '/../../app/views/layouts/sidebar.php';
?>

<?= renderFlash() ?>

<?php if ($success): ?>
<div class="alert alert-success auto-dismiss"><i class="bi bi-check-circle me-2"></i><?= e($success) ?></div>
<?php endif; ?>
<?php if ($errors): ?>
<div class="alert alert-danger">
    <ul class="mb-0 ps-3"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<div class="page-header">
    <h1 class="page-title">My Profile</h1>
</div>

<div class="row g-4">
    <!-- Profile Card -->
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body py-5">
                <div class="comment-avatar mx-auto mb-3" style="width:80px;height:80px;font-size:32px">
                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                </div>
                <h5 class="fw-700 mb-0"><?= e($user['name']) ?></h5>
                <p class="text-muted small mb-2"><?= e($user['email']) ?></p>
                <span class="badge bg-<?= $user['role']==='admin' ? 'danger' : ($user['role']==='technician' ? 'success' : 'primary') ?> mb-3">
                    <?= ucfirst($user['role']) ?>
                </span>
                <div class="border-top pt-3">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="fw-700"><?= $user['department'] ? e($user['department']) : '—' ?></div>
                            <div class="text-muted" style="font-size:11px">Department</div>
                        </div>
                        <div class="col-6">
                            <div class="fw-700"><?= $user['last_login'] ? timeAgo($user['last_login']) : 'Never' ?></div>
                            <div class="text-muted" style="font-size:11px">Last Login</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <!-- Update Profile -->
        <div class="card mb-4">
            <div class="card-header"><i class="bi bi-person-gear me-2"></i>Edit Profile</div>
            <div class="card-body">
                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="update_profile">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="name" class="form-control" required value="<?= e($user['name']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email <span class="text-muted fw-normal">(read-only)</span></label>
                            <input type="email" class="form-control bg-light" value="<?= e($user['email']) ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone" class="form-control" value="<?= e($user['phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Department</label>
                            <input type="text" name="department" class="form-control" value="<?= e($user['department'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-2"></i>Save Changes
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Change Password -->
        <div class="card">
            <div class="card-header"><i class="bi bi-lock me-2"></i>Change Password</div>
            <div class="card-body">
                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="change_password">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Current Password *</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">New Password *</label>
                            <input type="password" name="new_password" class="form-control" required placeholder="Min. 8 characters">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirm New Password *</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-warning text-dark fw-600">
                                <i class="bi bi-key me-2"></i>Change Password
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../app/views/layouts/footer.php'; ?>
