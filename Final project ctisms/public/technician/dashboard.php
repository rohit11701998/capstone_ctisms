<?php
require_once __DIR__ . '/../../app/bootstrap.php';
Auth::requireRole('technician');

$ticketModel = new TicketModel();
$stats       = $ticketModel->getStats(null, Auth::id());
$recent      = $ticketModel->getList(['technician_id' => Auth::id()], 1);
$recentData  = array_slice($recent['data'], 0, 8);

$pageTitle = 'Technician Dashboard';
include __DIR__ . '/../../app/views/layouts/header.php';
include __DIR__ . '/../../app/views/layouts/sidebar.php';
?>

<?= renderFlash() ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Technician Dashboard</h1>
        <p class="page-subtitle">Welcome back, <?= e(Auth::name()) ?> — here are your assigned tickets</p>
    </div>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card stat-primary">
            <div class="stat-icon"><i class="bi bi-ticket-detailed"></i></div>
            <div class="stat-value"><?= (int)($stats['total'] ?? 0) ?></div>
            <div class="stat-label">Total Assigned</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-warning">
            <div class="stat-icon"><i class="bi bi-circle"></i></div>
            <div class="stat-value"><?= (int)(($stats['open'] ?? 0) + ($stats['submitted'] ?? 0)) ?></div>
            <div class="stat-label">Open</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-info">
            <div class="stat-icon"><i class="bi bi-arrow-repeat"></i></div>
            <div class="stat-value"><?= (int)($stats['in_progress'] ?? 0) ?></div>
            <div class="stat-label">In Progress</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-success">
            <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
            <div class="stat-value"><?= (int)(($stats['completed'] ?? 0) + ($stats['closed'] ?? 0)) ?></div>
            <div class="stat-label">Resolved</div>
        </div>
    </div>
</div>

<?php if (($stats['sla_breached'] ?? 0) > 0): ?>
<div class="alert alert-danger d-flex align-items-center mb-4">
    <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
    <div>
        <strong><?= (int)$stats['sla_breached'] ?> ticket(s) have breached SLA.</strong>
        Please prioritise these immediately.
        <a href="<?= APP_URL ?>/technician/tickets.php?sla=breached" class="alert-link ms-2">View now →</a>
    </div>
</div>
<?php endif; ?>

<div class="row g-4">
    <!-- Assigned Tickets Table -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-list-check me-2 text-primary"></i>My Assigned Tickets</span>
                <a href="<?= APP_URL ?>/technician/tickets.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentData)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
                    <p>No tickets assigned yet.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Ticket #</th>
                                <th>Title</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>SLA</th>
                                <th>Age</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($recentData as $t): ?>
                            <tr data-href="<?= APP_URL ?>/technician/view.php?id=<?= $t['id'] ?>">
                                <td><span class="ticket-number"><?= e($t['ticket_number']) ?></span></td>
                                <td class="fw-500"><?= e($t['title']) ?></td>
                                <td><?= priorityBadge($t['priority']) ?></td>
                                <td><?= statusBadge($t['status']) ?></td>
                                <td><?= $t['sla_deadline'] ? slaBadge($t['sla_deadline'], $t['sla_breached']) : '—' ?></td>
                                <td class="text-muted"><?= timeAgo($t['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Column -->
    <div class="col-lg-4">
        <!-- Status Chart -->
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-pie-chart me-2 text-info"></i>My Ticket Status</div>
            <div class="card-body">
                <canvas id="statusChart" height="220"></canvas>
            </div>
        </div>

        <!-- Critical Tickets -->
        <?php
        $criticalTickets = $ticketModel->getList(['technician_id' => Auth::id(), 'priority' => 'critical'], 1);
        if (!empty($criticalTickets['data'])):
        ?>
        <div class="card border-danger">
            <div class="card-header bg-danger text-white">
                <i class="bi bi-exclamation-triangle me-2"></i>Critical Tickets
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                <?php foreach (array_slice($criticalTickets['data'], 0, 3) as $ct): ?>
                    <li class="list-group-item">
                        <a href="<?= APP_URL ?>/technician/view.php?id=<?= $ct['id'] ?>" class="text-decoration-none">
                            <div class="ticket-number"><?= e($ct['ticket_number']) ?></div>
                            <div class="small text-muted"><?= e(mb_strimwidth($ct['title'], 0, 50, '…')) ?></div>
                        </a>
                    </li>
                <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
$pageScripts = "
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: ['Open','In Progress','Awaiting Parts','Completed','Closed'],
        datasets: [{
            data: [
                " . (int)($stats['open'] ?? 0) . ",
                " . (int)($stats['in_progress'] ?? 0) . ",
                " . (int)($stats['awaiting_parts'] ?? 0) . ",
                " . (int)($stats['completed'] ?? 0) . ",
                " . (int)($stats['closed'] ?? 0) . "
            ],
            backgroundColor:['#1565c0','#0097a7','#f57f17','#2e7d32','#37474f'],
            borderWidth:2
        }]
    },
    options:{cutout:'65%',plugins:{legend:{position:'bottom'}}}
});
";
include __DIR__ . '/../../app/views/layouts/footer.php';
?>
