<?php
// dashboard.php - Week 3-4: Basic dashboard showing user's tickets
session_start();

// Simple auth check - learned Week 2
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/db.php';

// Fetch user's tickets - Week 3
$stmt = $pdo->prepare("SELECT * FROM tickets WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$tickets = $stmt->fetchAll();

// Count totals - Week 4
$total    = count($tickets);
$pending  = 0;
$inprog   = 0;
$resolved = 0;

foreach ($tickets as $t) {
    if ($t['status'] == 'Pending')     $pending++;
    if ($t['status'] == 'In Progress') $inprog++;
    if ($t['status'] == 'Resolved')    $resolved++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - CTISMS v1</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php">CTISMS v1</a>
        <div class="ms-auto d-flex align-items-center gap-3">
            <span class="text-white small">
                Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?>
            </span>
            <a href="create_ticket.php" class="btn btn-sm btn-warning">+ New Ticket</a>
            <a href="logout.php" class="btn btn-sm btn-outline-light">Logout</a>
        </div>
    </div>
</nav>

<div class="container">

    <h5 class="mb-4">My Dashboard</h5>

    <!-- Simple stats - added Week 4 -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center p-3">
                <h3 class="mb-0" style="color:#003366"><?= $total ?></h3>
                <small class="text-muted">Total Tickets</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center p-3">
                <h3 class="mb-0 text-warning"><?= $pending ?></h3>
                <small class="text-muted">Pending</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center p-3">
                <h3 class="mb-0 text-info"><?= $inprog ?></h3>
                <small class="text-muted">In Progress</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center p-3">
                <h3 class="mb-0 text-success"><?= $resolved ?></h3>
                <small class="text-muted">Resolved</small>
            </div>
        </div>
    </div>

    <!-- Tickets Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>My Support Tickets</span>
            <a href="create_ticket.php" class="btn btn-sm btn-warning">Submit New Ticket</a>
        </div>
        <div class="card-body p-0">
            <?php if (empty($tickets)): ?>
                <div class="text-center py-5 text-muted">
                    <p>No tickets yet. <a href="create_ticket.php">Submit your first ticket</a></p>
                </div>
            <?php else: ?>
            <table class="table table-bordered mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($tickets as $t): ?>
                    <tr>
                        <td>#<?= $t['id'] ?></td>
                        <td><?= htmlspecialchars($t['title']) ?></td>
                        <td>
                            <?php
                            // Status badge - Week 4 addition
                            if ($t['status'] == 'Pending')
                                echo '<span class="badge-pending">' . $t['status'] . '</span>';
                            elseif ($t['status'] == 'In Progress')
                                echo '<span class="badge-inprogress">' . $t['status'] . '</span>';
                            else
                                echo '<span class="badge-resolved">' . $t['status'] . '</span>';
                            ?>
                        </td>
                        <td><?= date('d M Y', strtotime($t['created_at'])) ?></td>
                        <td>
                            <a href="view_ticket.php?id=<?= $t['id'] ?>"
                               class="btn btn-sm btn-primary">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Note: Admin features not yet implemented - Week 4 note -->
    <div class="alert alert-info mt-4">
        <strong>Development Note (Week 4):</strong>
        Admin features and technician assignment are planned for the next phase.
        Currently all users see only their own tickets.
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
