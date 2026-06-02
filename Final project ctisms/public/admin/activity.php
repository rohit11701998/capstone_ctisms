<?php
require_once __DIR__ . '/../../app/bootstrap.php';
Auth::requireRole('admin');

$page   = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * ITEMS_PER_PAGE;
$db     = Database::getInstance();

$search = $_GET['search'] ?? '';
$where  = $search ? "WHERE al.description LIKE ? OR u.name LIKE ? OR t.ticket_number LIKE ?" : '';
$params = $search ? ["%$search%", "%$search%", "%$search%"] : [];

$total = (int)$db->fetchValue(
    "SELECT COUNT(*) FROM activity_logs al
     LEFT JOIN users u ON u.id = al.user_id
     LEFT JOIN tickets t ON t.id = al.ticket_id $where", $params
);

$logs = $db->fetchAll(
    "SELECT al.*, u.name AS user_name, u.role AS user_role, t.ticket_number
     FROM activity_logs al
     LEFT JOIN users u ON u.id = al.user_id
     LEFT JOIN tickets t ON t.id = al.ticket_id
     $where
     ORDER BY al.created_at DESC
     LIMIT ? OFFSET ?",
    array_merge($params, [ITEMS_PER_PAGE, $offset])
);

$pagination = [
    'total' => $total, 'per_page' => ITEMS_PER_PAGE,
    'current_page' => $page, 'last_page' => max(1, (int)ceil($total / ITEMS_PER_PAGE))
];

$actionIcons = [
    'login'          => ['bi-box-arrow-in-right', 'text-success'],
    'logout'         => ['bi-box-arrow-right',    'text-secondary'],
    'ticket_created' => ['bi-plus-circle',         'text-primary'],
    'ticket_assigned'=> ['bi-person-check',        'text-info'],
    'status_changed' => ['bi-arrow-repeat',        'text-warning'],
    'comment_added'  => ['bi-chat-dots',           'text-primary'],
    'user_created'   => ['bi-person-plus',         'text-success'],
    'user_deleted'   => ['bi-person-x',            'text-danger'],
];

$pageTitle = 'Activity Log';
include __DIR__ . '/../../app/views/layouts/header.php';
include __DIR__ . '/../../app/views/layouts/sidebar.php';
?>

<?= renderFlash() ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Activity Log</h1>
        <p class="page-subtitle"><?= number_format($total) ?> events recorded</p>
    </div>
</div>

<!-- Search -->
<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-2">
            <div class="col-md-8">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Search by description, user, or ticket number…"
                       value="<?= e($search) ?>">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Search</button>
                <a href="<?= APP_URL ?>/admin/activity.php" class="btn btn-outline-secondary btn-sm">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Log Table -->
<div class="card">
    <div class="card-body p-0">
        <?php if (empty($logs)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-clock-history fs-1 d-block mb-2 opacity-25"></i>
            <p>No activity found.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Action</th>
                        <th>Description</th>
                        <th>User</th>
                        <th>Ticket</th>
                        <th>IP Address</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($logs as $log): ?>
                    <?php
                    [$icon, $color] = $actionIcons[$log['action']] ?? ['bi-dot', 'text-muted'];
                    ?>
                    <tr>
                        <td>
                            <i class="bi <?= $icon ?> <?= $color ?> me-1"></i>
                            <span class="badge bg-light text-dark" style="font-size:10px">
                                <?= e(str_replace('_', ' ', $log['action'])) ?>
                            </span>
                        </td>
                        <td class="small"><?= e($log['description']) ?></td>
                        <td class="small">
                            <?php if ($log['user_name']): ?>
                            <span class="fw-500"><?= e($log['user_name']) ?></span>
                            <span class="text-muted">(<?= e($log['user_role'] ?? '') ?>)</span>
                            <?php else: ?>
                            <span class="text-muted">System</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($log['ticket_number']): ?>
                            <a href="<?= APP_URL ?>/admin/view.php?id=<?= $log['ticket_id'] ?>"
                               class="ticket-number text-decoration-none">
                                <?= e($log['ticket_number']) ?>
                            </a>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted small font-mono"><?= e($log['ip_address'] ?? '—') ?></td>
                        <td class="text-muted small">
                            <span data-bs-toggle="tooltip" title="<?= e(formatDate($log['created_at'])) ?>">
                                <?= timeAgo($log['created_at']) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="p-3 d-flex justify-content-between align-items-center">
            <span class="text-muted small">
                Showing <?= count($logs) ?> of <?= number_format($total) ?> events
            </span>
            <?= paginationLinks($pagination, APP_URL . '/admin/activity.php' . ($search ? '?search=' . urlencode($search) : '')) ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../app/views/layouts/footer.php'; ?>
