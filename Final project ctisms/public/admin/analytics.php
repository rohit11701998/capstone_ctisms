<?php
require_once __DIR__ . '/../../app/bootstrap.php';
Auth::requireRole('admin');

$ticketModel = new TicketModel();
$userModel   = new UserModel();

$stats       = $ticketModel->getStats();
$statusChart = $ticketModel->getStatusChart();
$dailyTrend  = $ticketModel->getDailyTrend(30);
$techPerf    = $userModel->getTechnicianPerformance();

// Priority breakdown
$db = Database::getInstance();
$priorityBreakdown = $db->fetchAll("SELECT priority, COUNT(*) as cnt FROM tickets GROUP BY priority ORDER BY FIELD(priority,'critical','high','medium','low')");

// Category breakdown
$categoryBreakdown = $db->fetchAll("
    SELECT c.name, COUNT(t.id) as cnt,
           AVG(CASE WHEN t.closed_at IS NOT NULL THEN TIMESTAMPDIFF(HOUR, t.created_at, t.closed_at) END) as avg_hours
    FROM categories c
    LEFT JOIN tickets t ON t.category_id = c.id
    GROUP BY c.id
    ORDER BY cnt DESC
");

// Avg resolution time overall
$avgResolution = $db->fetchValue("
    SELECT ROUND(AVG(TIMESTAMPDIFF(HOUR, created_at, closed_at)), 1)
    FROM tickets WHERE closed_at IS NOT NULL
");

// Monthly summary
$monthlySummary = $db->fetchAll("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month,
           COUNT(*) as total,
           SUM(status IN ('completed','closed')) as resolved,
           SUM(sla_breached = 1) as breached
    FROM tickets
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY month
    ORDER BY month ASC
");

$pageTitle = 'Analytics & Reports';
include __DIR__ . '/../../app/views/layouts/header.php';
include __DIR__ . '/../../app/views/layouts/sidebar.php';
?>

<?= renderFlash() ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Analytics & Reports</h1>
        <p class="page-subtitle">System-wide performance metrics and insights</p>
    </div>
    <button class="btn btn-outline-secondary" onclick="window.print()">
        <i class="bi bi-printer me-2"></i>Print Report
    </button>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card stat-primary">
            <div class="stat-icon"><i class="bi bi-ticket-detailed"></i></div>
            <div class="stat-value"><?= (int)($stats['total'] ?? 0) ?></div>
            <div class="stat-label">Total Tickets</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-success">
            <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
            <div class="stat-value"><?= (int)(($stats['completed'] ?? 0) + ($stats['closed'] ?? 0)) ?></div>
            <div class="stat-label">Resolved</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-danger">
            <div class="stat-icon"><i class="bi bi-exclamation-triangle"></i></div>
            <div class="stat-value"><?= (int)($stats['sla_breached'] ?? 0) ?></div>
            <div class="stat-label">SLA Breaches</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-info">
            <div class="stat-icon"><i class="bi bi-clock-history"></i></div>
            <div class="stat-value"><?= $avgResolution ? $avgResolution . 'h' : '—' ?></div>
            <div class="stat-label">Avg Resolution</div>
        </div>
    </div>
</div>


<!-- Row 2: Category breakdown + Monthly Summary -->
<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-tags me-2 text-info"></i>Tickets by Category</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th class="text-center">Total</th>
                                <th class="text-center">Avg Resolution</th>
                                <th>Volume</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $maxCat = max(array_column($categoryBreakdown, 'cnt') ?: [1]);
                        foreach ($categoryBreakdown as $cb):
                        ?>
                            <tr>
                                <td class="small fw-500"><?= e($cb['name']) ?></td>
                                <td class="text-center small"><?= (int)$cb['cnt'] ?></td>
                                <td class="text-center text-muted small">
                                    <?= $cb['avg_hours'] ? round($cb['avg_hours'], 1) . 'h' : '—' ?>
                                </td>
                                <td style="width:120px">
                                    <div class="sla-bar">
                                        <div class="sla-bar-fill" style="width:<?= $maxCat ? round(($cb['cnt']/$maxCat)*100) : 0 ?>%;background:#1a237e"></div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-calendar3 me-2 text-success"></i>Monthly Summary (Last 6 Months)</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th class="text-center">Created</th>
                                <th class="text-center">Resolved</th>
                                <th class="text-center">SLA Breaches</th>
                                <th class="text-center">Resolve %</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($monthlySummary as $ms): ?>
                            <tr>
                                <td class="small fw-500"><?= date('M Y', strtotime($ms['month'] . '-01')) ?></td>
                                <td class="text-center small"><?= (int)$ms['total'] ?></td>
                                <td class="text-center small text-success"><?= (int)$ms['resolved'] ?></td>
                                <td class="text-center small <?= $ms['breached'] > 0 ? 'text-danger' : 'text-muted' ?>">
                                    <?= (int)$ms['breached'] ?>
                                </td>
                                <td class="text-center small">
                                    <?php $pct = $ms['total'] > 0 ? round(($ms['resolved']/$ms['total'])*100) : 0; ?>
                                    <span class="badge bg-<?= $pct >= 80 ? 'success' : ($pct >= 50 ? 'warning' : 'danger') ?>">
                                        <?= $pct ?>%
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($monthlySummary)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-3">No data yet</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Row 3: Technician Performance -->
<div class="card mb-4">
    <div class="card-header"><i class="bi bi-award me-2 text-warning"></i>Technician Performance Leaderboard</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Technician</th>
                        <th class="text-center">Assigned</th>
                        <th class="text-center">Resolved</th>
                        <th class="text-center">Open</th>
                        <th class="text-center">Avg Resolution</th>
                        <th>Resolve Rate</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($techPerf as $i => $tp): ?>
                    <tr>
                        <td class="text-muted small"><?= $i + 1 ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="comment-avatar" style="width:28px;height:28px;font-size:11px">
                                    <?= strtoupper(substr($tp['name'], 0, 1)) ?>
                                </div>
                                <span class="fw-500 small"><?= e($tp['name']) ?></span>
                                <?php if ($i === 0 && $tp['resolved'] > 0): ?>
                                <span class="badge bg-warning text-dark">🏆 Top</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="text-center"><?= (int)$tp['total_assigned'] ?></td>
                        <td class="text-center"><span class="badge bg-success"><?= (int)$tp['resolved'] ?></span></td>
                        <td class="text-center"><span class="badge bg-warning text-dark"><?= (int)$tp['open'] ?></span></td>
                        <td class="text-center text-muted small">
                            <?= $tp['avg_resolution_hours'] ? $tp['avg_resolution_hours'] . 'h' : '—' ?>
                        </td>
                        <td style="width:160px">
                            <?php $rate = $tp['total_assigned'] > 0 ? round(($tp['resolved']/$tp['total_assigned'])*100) : 0; ?>
                            <div class="d-flex align-items-center gap-2">
                                <div class="flex-grow-1 sla-bar">
                                    <div class="sla-bar-fill <?= $rate >= 80 ? 'sla-good' : ($rate >= 50 ? 'sla-warn' : 'sla-breach') ?>"
                                         style="width:<?= $rate ?>%"></div>
                                </div>
                                <span class="small"><?= $rate ?>%</span>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($techPerf)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-3">No technicians yet</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
// Build chart data
$trendLabels = $trendValues = [];
$trendMap = array_column($dailyTrend, 'cnt', 'day');
for ($i = 29; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $trendLabels[] = date('d M', strtotime($day));
    $trendValues[] = (int)($trendMap[$day] ?? 0);
}

$prioColors = ['critical' => '#c62828','high' => '#f57f17','medium' => '#1565c0','low' => '#388e3c'];
$prioLabels = array_map(fn($r) => ucfirst($r['priority']), $priorityBreakdown);
$prioValues = array_column($priorityBreakdown, 'cnt');
$prioColors2= array_map(fn($r) => $prioColors[$r['priority']] ?? '#999', $priorityBreakdown);

$pageScripts = "
new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: " . json_encode($trendLabels) . ",
        datasets: [{
            label: 'Tickets',
            data: " . json_encode($trendValues) . ",
            borderColor: '#1a237e',
            backgroundColor: 'rgba(26,35,126,0.08)',
            fill: true,
            tension: 0.4,
            pointRadius: 3,
            pointBackgroundColor: '#1a237e'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: 'rgba(0,0,0,0.05)' } },
            x: { grid: { display: false }, ticks: { maxTicksLimit: 10 } }
        }
    }
});

new Chart(document.getElementById('priorityChart'), {
    type: 'doughnut',
    data: {
        labels: " . json_encode($prioLabels) . ",
        datasets: [{
            data: " . json_encode($prioValues) . ",
            backgroundColor: " . json_encode($prioColors2) . ",
            borderWidth: 2
        }]
    },
    options: { cutout: '55%', plugins: { legend: { position: 'bottom' } } }
});
";
include __DIR__ . '/../../app/views/layouts/footer.php';
?>
