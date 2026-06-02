<?php
require_once __DIR__ . '/../../app/bootstrap.php';
Auth::requireRole('technician');

$ticketModel = new TicketModel();
$page        = max(1, (int)($_GET['page'] ?? 1));

$filters = [
    'technician_id' => Auth::id(),
    'status'        => $_GET['status']   ?? '',
    'priority'      => $_GET['priority'] ?? '',
    'search'        => $_GET['search']   ?? '',
];

$result = $ticketModel->getList($filters, $page);

$pageTitle = 'My Assigned Tickets';
include __DIR__ . '/../../app/views/layouts/header.php';
include __DIR__ . '/../../app/views/layouts/sidebar.php';
?>

<?= renderFlash() ?>

<div class="page-header">
    <div>
        <h1 class="page-title">My Assigned Tickets</h1>
        <p class="page-subtitle"><?= $result['total'] ?> ticket(s) assigned to you</p>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Search by title or number…" value="<?= e($filters['search']) ?>">
            </div>
            <div class="col-md-3">
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
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filter</button>
                <a href="<?= APP_URL ?>/technician/tickets.php" class="btn btn-outline-secondary btn-sm">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Status quick-filter pills -->
<div class="d-flex gap-2 mb-3 flex-wrap">
    <a href="<?= APP_URL ?>/technician/tickets.php" class="btn btn-sm <?= !$filters['status'] ? 'btn-primary' : 'btn-outline-secondary' ?>">All</a>
    <?php foreach (TicketModel::STATUSES as $key => $s): ?>
    <a href="<?= APP_URL ?>/technician/tickets.php?status=<?= $key ?>"
       class="btn btn-sm btn-<?= $s['color'] ?> <?= $filters['status'] === $key ? '' : 'opacity-75' ?>">
        <?= e($s['label']) ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Tickets Table -->
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
                        <th>SLA</th>
                        <th>Updated</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($result['data'] as $t): ?>
                    <tr data-href="<?= APP_URL ?>/technician/view.php?id=<?= $t['id'] ?>">
                        <td><span class="ticket-number"><?= e($t['ticket_number']) ?></span></td>
                        <td class="fw-500"><?= e(mb_strimwidth($t['title'], 0, 55, '…')) ?></td>
                        <td class="text-muted small"><?= e($t['category_name']) ?></td>
                        <td><?= priorityBadge($t['priority']) ?></td>
                        <td><?= statusBadge($t['status']) ?></td>
                        <td class="text-muted small"><?= e($t['customer_name']) ?></td>
                        <td><?= $t['sla_deadline'] ? slaBadge($t['sla_deadline'], $t['sla_breached']) : '—' ?></td>
                        <td class="text-muted small"><?= timeAgo($t['updated_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="p-3">
            <?= paginationLinks($result, APP_URL . '/technician/tickets.php?' . http_build_query(array_filter($filters))) ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../app/views/layouts/footer.php'; ?>
