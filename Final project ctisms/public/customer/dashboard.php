<?php
require_once __DIR__ . '/../../app/bootstrap.php';
Auth::requireRole('customer');

$ticketModel = new TicketModel();
$stats       = $ticketModel->getStats(Auth::id());
$recent      = $ticketModel->getList(['customer_id' => Auth::id()], 1);
$recentData  = array_slice($recent['data'], 0, 5);

$pageTitle = 'My Dashboard';
include __DIR__ . '/../../app/views/layouts/header.php';
include __DIR__ . '/../../app/views/layouts/sidebar.php';
?>

<!-- Flash -->
<?= renderFlash() ?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">Welcome back, <?= e(explode(' ', Auth::name())[0]) ?> 👋</h1>
        <p class="page-subtitle">Here's an overview of your support tickets</p>
    </div>
    <a href="<?= APP_URL ?>/customer/create.php" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i>New Ticket
    </a>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card stat-primary">
            <div class="stat-icon"><i class="bi bi-ticket-detailed"></i></div>
            <div class="stat-value"><?= (int)($stats['total'] ?? 0) ?></div>
            <div class="stat-label">Total Tickets</div>
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
        <div class="stat-card stat-warning">
            <div class="stat-icon"><i class="bi bi-clock"></i></div>
            <div class="stat-value"><?= (int)(($stats['submitted'] ?? 0) + ($stats['open'] ?? 0)) ?></div>
            <div class="stat-label">Awaiting Action</div>
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

<div class="row g-4">
    <!-- Recent Tickets -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-clock-history me-2 text-primary"></i>Recent Tickets</span>
                <a href="<?= APP_URL ?>/customer/tickets.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentData)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
                    <p>No tickets yet</p>
                    <a href="<?= APP_URL ?>/customer/create.php" class="btn btn-primary btn-sm">Submit your first ticket</a>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Ticket #</th>
                                <th>Title</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($recentData as $t): ?>
                            <tr data-href="<?= APP_URL ?>/customer/view.php?id=<?= $t['id'] ?>" style="cursor:pointer">
                                <td><span class="ticket-number"><?= e($t['ticket_number']) ?></span></td>
                                <td><?= e($t['title']) ?></td>
                                <td><?= statusBadge($t['status']) ?></td>
                                <td><?= priorityBadge($t['priority']) ?></td>
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

    <!-- Quick Actions + Status Summary -->
    <div class="col-lg-4">
        <!-- Quick Actions -->
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-lightning me-2 text-warning"></i>Quick Actions</div>
            <div class="card-body d-grid gap-2">
                <a href="<?= APP_URL ?>/customer/create.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Submit New Ticket
                </a>
                <a href="<?= APP_URL ?>/customer/tickets.php?status=open" class="btn btn-outline-primary">
                    <i class="bi bi-eye me-2"></i>View Open Tickets
                </a>
                <a href="<?= APP_URL ?>/notifications/index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-bell me-2"></i>Notifications
                </a>
            </div>
        </div>

        <!-- Status breakdown -->
        <div class="card">
            <div class="card-header"><i class="bi bi-pie-chart me-2 text-info"></i>Status Breakdown</div>
            <div class="card-body">
                <canvas id="statusChart" height="220"></canvas>
            </div>
        </div>
    </div>
</div>

<?php
$chartData = [
    'Submitted'      => (int)($stats['submitted']      ?? 0),
    'Open'           => (int)($stats['open']           ?? 0),
    'In Progress'    => (int)($stats['in_progress']    ?? 0),
    'Awaiting Parts' => (int)($stats['awaiting_parts'] ?? 0),
    'Completed'      => (int)($stats['completed']      ?? 0),
    'Closed'         => (int)($stats['closed']         ?? 0),
];
$pageScripts = "
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: " . json_encode(array_keys($chartData)) . ",
        datasets: [{
            data: " . json_encode(array_values($chartData)) . ",
            backgroundColor: ['#546e7a','#1565c0','#0097a7','#f57f17','#2e7d32','#37474f'],
            borderWidth: 2
        }]
    },
    options: { cutout: '65%', plugins: { legend: { position: 'bottom' } } }
});
";
include __DIR__ . '/../../app/views/layouts/footer.php';
