<?php
require_once __DIR__ . '/../../app/bootstrap.php';
Auth::requireRole('admin');

$ticketModel = new TicketModel();
$userModel   = new UserModel();

$stats        = $ticketModel->getStats();
$statusChart  = $ticketModel->getStatusChart();
$dailyTrend   = $ticketModel->getDailyTrend(14);
$recentTickets= $ticketModel->getRecent(8);
$techPerf     = $userModel->getTechnicianPerformance();
$totalUsers   = $userModel->count();
$recentLogs   = (new ActivityLogModel())->getRecent(8);

// Check SLA breaches
(new TicketModel())->checkSlaBreaches();

$pageTitle = 'Admin Dashboard';
include __DIR__ . '/../../app/views/layouts/header.php';
include __DIR__ . '/../../app/views/layouts/sidebar.php';
?>

<?= renderFlash() ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Admin Dashboard</h1>
        <p class="page-subtitle"><?= date('l, d F Y') ?> — System Overview</p>
    </div>
    <a href="<?= APP_URL ?>/admin/tickets.php" class="btn btn-primary">
        <i class="bi bi-ticket-detailed me-2"></i>All Tickets
    </a>
</div>

<!-- Stats Row 1 -->
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="stat-card stat-primary">
            <div class="stat-icon"><i class="bi bi-ticket-detailed"></i></div>
            <div class="stat-value"><?= (int)($stats['total'] ?? 0) ?></div>
            <div class="stat-label">Total Tickets</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-warning">
            <div class="stat-icon"><i class="bi bi-inbox"></i></div>
            <div class="stat-value"><?= (int)(($stats['submitted'] ?? 0) + ($stats['open'] ?? 0)) ?></div>
            <div class="stat-label">Open / Unassigned</div>
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

<!-- Stats Row 2 -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card stat-danger">
            <div class="stat-icon"><i class="bi bi-exclamation-triangle"></i></div>
            <div class="stat-value"><?= (int)($stats['sla_breached'] ?? 0) ?></div>
            <div class="stat-label">SLA Breached</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-cyan">
            <div class="stat-icon"><i class="bi bi-lightning-charge"></i></div>
            <div class="stat-value"><?= (int)($stats['critical'] ?? 0) ?></div>
            <div class="stat-label">Critical Priority</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-purple">
            <div class="stat-icon"><i class="bi bi-people"></i></div>
            <div class="stat-value"><?= $totalUsers ?></div>
            <div class="stat-label">Total Users</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-teal">
            <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
            <div class="stat-value"><?= (int)($stats['awaiting_parts'] ?? 0) ?></div>
            <div class="stat-label">Awaiting Parts</div>
        </div>
    </div>
</div>



<div class="row g-4 mb-4">
    <!-- Recent Tickets -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-clock-history me-2"></i>Recent Tickets</span>
                <a href="<?= APP_URL ?>/admin/tickets.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Ticket #</th>
                                <th>Title</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Technician</th>
                                <th>Age</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($recentTickets as $t): ?>
                            <tr data-href="<?= APP_URL ?>/admin/view.php?id=<?= $t['id'] ?>">
                                <td><span class="ticket-number"><?= e($t['ticket_number']) ?></span></td>
                                <td class="fw-500 small"><?= e(mb_strimwidth($t['title'], 0, 40, '…')) ?></td>
                                <td><?= priorityBadge($t['priority']) ?></td>
                                <td><?= statusBadge($t['status']) ?></td>
                                <td class="text-muted small"><?= $t['technician_name'] ? e($t['technician_name']) : '<em>Unassigned</em>' ?></td>
                                <td class="text-muted small"><?= timeAgo($t['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Technician Performance -->
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><i class="bi bi-award me-2 text-warning"></i>Technician Performance</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Technician</th>
                                <th class="text-center">Assigned</th>
                                <th class="text-center">Resolved</th>
                                <th class="text-center">Avg Time</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($techPerf as $tp): ?>
                            <tr>
                                <td class="fw-500 small"><?= e($tp['name']) ?></td>
                                <td class="text-center small"><?= (int)$tp['total_assigned'] ?></td>
                                <td class="text-center">
                                    <span class="badge bg-success"><?= (int)$tp['resolved'] ?></span>
                                </td>
                                <td class="text-center text-muted small">
                                    <?= $tp['avg_resolution_hours'] ? $tp['avg_resolution_hours'] . 'h' : '—' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($techPerf)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">No data yet</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity Log -->
<div class="card">
    <div class="card-header d-flex justify-content-between">
        <span><i class="bi bi-activity me-2"></i>Recent Activity</span>
        <a href="<?= APP_URL ?>/admin/activity.php" class="btn btn-sm btn-outline-secondary">View All</a>
    </div>
    <div class="card-body p-0">
        <ul class="list-group list-group-flush">
        <?php foreach ($recentLogs as $log): ?>
            <li class="list-group-item d-flex justify-content-between align-items-start py-2 px-3" style="font-size:13px">
                <div>
                    <i class="bi bi-dot text-primary"></i>
                    <?= e($log['description']) ?>
                    <?php if ($log['ticket_number']): ?>
                    — <a href="<?= APP_URL ?>/admin/view.php?id=<?= $log['ticket_id'] ?>" class="text-primary"><?= e($log['ticket_number']) ?></a>
                    <?php endif; ?>
                </div>
                <span class="text-muted ms-3 text-nowrap"><?= timeAgo($log['created_at']) ?></span>
            </li>
        <?php endforeach; ?>
        </ul>
    </div>
</div>

<?php
// Build JS chart data
$trendLabels = [];
$trendValues = [];
$trendMap    = array_column($dailyTrend, 'cnt', 'day');
for ($i = 13; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $trendLabels[] = date('d M', strtotime($day));
    $trendValues[] = (int)($trendMap[$day] ?? 0);
}

$statusLabels = [];
$statusValues = [];
$statusColors = ['submitted'=>'#546e7a','open'=>'#1565c0','in_progress'=>'#0097a7','awaiting_parts'=>'#f57f17','completed'=>'#2e7d32','closed'=>'#37474f'];
foreach ($statusChart as $sc) {
    $statusLabels[] = TicketModel::STATUSES[$sc['status']]['label'] ?? $sc['status'];
    $statusValues[] = (int)$sc['cnt'];
    $scColors[]     = $statusColors[$sc['status']] ?? '#999';
}

$pageScripts = "
// Trend line chart
new Chart(document.getElementById('trendChart'), {
    type: 'bar',
    data: {
        labels: " . json_encode($trendLabels) . ",
        datasets: [{
            label: 'Tickets Created',
            data: " . json_encode($trendValues) . ",
            backgroundColor: 'rgba(26,35,126,0.7)',
            borderColor: '#1a237e',
            borderWidth: 1,
            borderRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: 'rgba(0,0,0,0.05)' } },
            x: { grid: { display: false } }
        }
    }
});

// Status donut
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: " . json_encode($statusLabels) . ",
        datasets: [{
            data: " . json_encode($statusValues) . ",
            backgroundColor: " . json_encode($scColors ?? []) . ",
            borderWidth: 2
        }]
    },
    options: { cutout: '60%', plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } } }
});
";
include __DIR__ . '/../../app/views/layouts/footer.php';
?>
