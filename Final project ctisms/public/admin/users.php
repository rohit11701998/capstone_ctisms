<?php
require_once __DIR__ . '/../../app/bootstrap.php';
Auth::requireRole('admin');

$userModel = new UserModel();
$errors    = [];

// Handle create/toggle/delete actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $data = [
            'name'       => trim($_POST['name'] ?? ''),
            'email'      => trim($_POST['email'] ?? ''),
            'password'   => $_POST['password'] ?? '',
            'role'       => $_POST['role'] ?? 'customer',
            'phone'      => trim($_POST['phone'] ?? ''),
            'department' => trim($_POST['department'] ?? ''),
        ];
        if (strlen($data['name']) < 2)    $errors[] = 'Name too short.';
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email.';
        if (strlen($data['password']) < 8) $errors[] = 'Password must be 8+ chars.';
        if (!in_array($data['role'], ['customer','technician','admin'])) $errors[] = 'Invalid role.';

        if (empty($errors)) {
            if ($userModel->emailExists($data['email'])) {
                $errors[] = 'Email already in use.';
            } else {
                $newId = $userModel->create($data);
                $log = new ActivityLogModel();
                $log->log('user_created', "Admin created user {$data['name']} ({$data['role']})", Auth::id());
                redirectTo(APP_URL . '/admin/users.php', 'success', 'User created successfully.');
            }
        }
    }

    if ($action === 'toggle') {
        $uid  = (int)$_POST['user_id'];
        $user = $userModel->find($uid);
        if ($user && $uid !== Auth::id()) {
            $userModel->update($uid, ['is_active' => $user['is_active'] ? 0 : 1]);
            redirectTo(APP_URL . '/admin/users.php', 'success', 'User status updated.');
        }
    }

    if ($action === 'delete') {
        $uid = (int)$_POST['user_id'];
        if ($uid !== Auth::id()) {
            $userModel->delete($uid);
            $log = new ActivityLogModel();
            $log->log('user_deleted', "Admin deleted user ID $uid", Auth::id());
            redirectTo(APP_URL . '/admin/users.php', 'success', 'User deleted.');
        }
    }
}

$users     = $userModel->getAllWithStats();
$pageTitle = 'User Management';
include __DIR__ . '/../../app/views/layouts/header.php';
include __DIR__ . '/../../app/views/layouts/sidebar.php';
?>

<?= renderFlash() ?>

<div class="page-header">
    <div>
        <h1 class="page-title">User Management</h1>
        <p class="page-subtitle"><?= count($users) ?> users registered</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
        <i class="bi bi-person-plus me-2"></i>Add User
    </button>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger">
    <ul class="mb-0 ps-3"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<!-- Users Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Department</th>
                        <th>Phone</th>
                        <th>Tickets</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="comment-avatar" style="width:32px;height:32px;font-size:13px;
                                background:<?= $u['role']==='admin' ? 'linear-gradient(135deg,#b71c1c,#c62828)' : ($u['role']==='technician' ? 'linear-gradient(135deg,#1b5e20,#2e7d32)' : 'linear-gradient(135deg,#1a237e,#283593)') ?>">
                                <?= strtoupper(substr($u['name'], 0, 1)) ?>
                            </div>
                            <div>
                                <div class="fw-500 small"><?= e($u['name']) ?></div>
                                <div class="text-muted" style="font-size:11px"><?= e($u['email']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge bg-<?= $u['role']==='admin' ? 'danger' : ($u['role']==='technician' ? 'success' : 'primary') ?>">
                            <?= ucfirst($u['role']) ?>
                        </span>
                    </td>
                    <td class="text-muted small"><?= e($u['department'] ?? '—') ?></td>
                    <td class="text-muted small"><?= e($u['phone'] ?? '—') ?></td>
                    <td class="text-center">
                        <span class="badge bg-light text-dark"><?= (int)$u['total_tickets'] ?></span>
                    </td>
                    <td>
                        <?php if ($u['is_active']): ?>
                            <span class="badge bg-success">Active</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-muted small"><?= $u['last_login'] ? timeAgo($u['last_login']) : 'Never' ?></td>
                    <td>
                        <div class="d-flex gap-1">
                            <?php if ($u['id'] !== Auth::id()): ?>
                            <form method="POST" class="d-inline">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-<?= $u['is_active'] ? 'warning' : 'success' ?>"
                                    data-bs-toggle="tooltip" title="<?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                    <i class="bi bi-<?= $u['is_active'] ? 'pause' : 'play' ?>"></i>
                                </button>
                            </form>
                            <form method="POST" class="d-inline">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                    data-confirm="Delete user '<?= e($u['name']) ?>'? This cannot be undone."
                                    data-bs-toggle="tooltip" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            <?php else: ?>
                            <span class="text-muted small">(you)</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create User Modal -->
<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Create New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address *</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Role *</label>
                            <select name="role" class="form-select" required>
                                <option value="customer">Customer</option>
                                <option value="technician">Technician</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Department</label>
                            <input type="text" name="department" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password *</label>
                            <input type="password" name="password" class="form-control" required placeholder="Min. 8 characters">
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-info mb-0 py-2 small">
                                <i class="bi bi-info-circle me-1"></i>
                                The user will receive login credentials and can change their password after first login.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-person-check me-2"></i>Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../app/views/layouts/footer.php'; ?>
