<?php
require_once __DIR__ . '/../../app/bootstrap.php';
Auth::requireRole('admin');

$ticketModel  = new TicketModel();
$categoryModel= new CategoryModel();
$page         = max(1, (int)($_GET['page'] ?? 1));

$filters = [
    'status'      => $_GET['status']      ?? '',
    'priority'    => $_GET['priority']    ?? '',
    'category_id' => (int)($_GET['category_id'] ?? 0),
    'search'      => $_GET['search']      ?? '',
];

$result     = $ticketModel->getList($filters, $page);
$categories = $categoryModel->getActive();
$stats      = $ticketModel->getStats();

$pageTitle = 'All Tickets';
include __DIR__ . '/../../app/views/layouts/header.php';
include __DIR__ . '/../../app/views/layouts/sidebar.php';
?>

<?= renderFlash() ?>

<div class="page-header">
    <div>
        <h1 class="page-title">All Tickets</h1>
        <p class="page-subtitle"><?= $result['total'] ?> total tickets in the system</p>
    </div>
    <a href="<?= APP_URL ?>/admin/export.php?<?= http_build_query(array_filter($filters)) ?>"
       class="btn btn-outline-success">
        <i class="bi bi-file-earmark-spreadsheet me-2"></i>Export CSV
    </a>
</div>

<!-- Quick Stats Strip -->
<div class="d-flex gap-3 mb-4 flex-wrap">
    <?php foreach (TicketModel::STATUSES as $key => $s): ?>
    <a href="<?= APP_URL ?>/admin/tickets.php?status=<?= $key ?>"
       class="badge bg-<?= $s['color'] ?> text-decoration-none p-2 fs-6">
        <?= e($s['label']) ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Search tickets…" value="<?= e($filters['search']) ?>">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <?php foreach (TicketModel::STATUSES as $key => $s): ?>
                    <option value="<?= $key ?>" <?= $filters['status'] === $key ? 'selected' : '' ?>><?= e($s['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="priority" class="form-select form-select-sm">
                    <option value="">All Priorities</option>
                    <?php foreach (TicketModel::PRIORITIES as $key => $p): ?>
                    <option value="<?= $key ?>" <?= $filters['priority'] === $key ? 'selected' : '' ?>><?= e($p['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="category_id" class="form-select form-select-sm">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $filters['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                <a href="<?= APP_URL ?>/admin/tickets.php" class="btn btn-outline-secondary btn-sm">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-body p-0">
        <?php if (empty($result['data'])): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
            <p>No tickets match your filters.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Ticket #</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Customer</th>
                        <th>Assigned To</th>
                        <th>SLA</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($result['data'] as $t): ?>
                    <tr>
                        <td><span class="ticket-number"><?= e($t['ticket_number']) ?></span></td>
                        <td class="fw-500" style="max-width:200px">
                            <a href="<?= APP_URL ?>/admin/view.php?id=<?= $t['id'] ?>" class="text-decoration-none text-dark">
                                <?= e(mb_strimwidth($t['title'], 0, 45, '…')) ?>
                            </a>
                        </td>
                        <td class="text-muted small"><?= e($t['category_name']) ?></td>
                        <td><?= priorityBadge($t['priority']) ?></td>
                        <td><?= statusBadge($t['status']) ?></td>
                        <td class="text-muted small"><?= e($t['customer_name']) ?></td>
                        <td class="small"><?= $t['technician_name'] ? e($t['technician_name']) : '<span class="text-warning">Unassigned</span>' ?></td>
                        <td><?= $t['sla_deadline'] ? slaBadge($t['sla_deadline'], $t['sla_breached']) : '—' ?></td>
                        <td class="text-muted small"><?= timeAgo($t['created_at']) ?></td>
                        <td>
                            <a href="<?= APP_URL ?>/admin/view.php?id=<?= $t['id'] ?>"
                               class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="p-3 d-flex justify-content-between align-items-center">
            <span class="text-muted small">
                Showing <?= count($result['data']) ?> of <?= $result['total'] ?> tickets
                (Page <?= $result['current_page'] ?> of <?= $result['last_page'] ?>)
            </span>
            <?= paginationLinks($result, APP_URL . '/admin/tickets.php?' . http_build_query(array_filter($filters))) ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../app/views/layouts/footer.php'; ?>
