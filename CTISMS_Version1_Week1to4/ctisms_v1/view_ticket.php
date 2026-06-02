<?php
// view_ticket.php - Week 4: View ticket details + basic status update
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/db.php';

$id = (int)($_GET['id'] ?? 0);

// Get ticket
$stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = ?");
$stmt->execute([$id]);
$ticket = $stmt->fetch();

// Check ticket exists and belongs to this user
if (!$ticket || $ticket['user_id'] != $_SESSION['user_id']) {
    echo "<div style='padding:30px;font-family:Arial'>
          <h4>Ticket not found or access denied.</h4>
          <a href='dashboard.php'>Back to Dashboard</a></div>";
    exit();
}

$message = "";

// Week 4 - basic status update (user can mark as resolved)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $allowed    = ['Pending', 'In Progress', 'Resolved'];

    if (in_array($new_status, $allowed)) {
        $upd = $pdo->prepare("UPDATE tickets SET status = ? WHERE id = ?");
        $upd->execute([$new_status, $id]);
        $ticket['status'] = $new_status;
        $message = "Status updated to: $new_status";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ticket #<?= $id ?> - CTISMS v1</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php">CTISMS v1</a>
        <div class="ms-auto">
            <a href="dashboard.php" class="btn btn-sm btn-outline-light me-2">Dashboard</a>
            <a href="logout.php" class="btn btn-sm btn-outline-light">Logout</a>
        </div>
    </div>
</nav>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">

            <a href="dashboard.php" class="btn btn-sm btn-outline-secondary mb-3">
                ← Back to Dashboard
            </a>

            <?php if ($message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-header">
                    Ticket #<?= $ticket['id'] ?> — <?= htmlspecialchars($ticket['title']) ?>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th style="width:150px">Status</th>
                            <td>
                                <?php
                                if ($ticket['status'] == 'Pending')
                                    echo '<span class="badge-pending">' . $ticket['status'] . '</span>';
                                elseif ($ticket['status'] == 'In Progress')
                                    echo '<span class="badge-inprogress">' . $ticket['status'] . '</span>';
                                else
                                    echo '<span class="badge-resolved">' . $ticket['status'] . '</span>';
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Submitted</th>
                            <td><?= date('d M Y, H:i', strtotime($ticket['created_at'])) ?></td>
                        </tr>
                        <tr>
                            <th>Description</th>
                            <td><?= nl2br(htmlspecialchars($ticket['description'])) ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Basic status update - Week 4 -->
            <?php if ($ticket['status'] !== 'Resolved'): ?>
            <div class="card">
                <div class="card-header">Update Status</div>
                <div class="card-body">
                    <form method="POST" class="d-flex align-items-center gap-3">
                        <label class="mb-0 fw-bold">Change Status:</label>
                        <select name="status" class="form-select" style="width:200px">
                            <option <?= $ticket['status']=='Pending'     ? 'selected' : '' ?>>Pending</option>
                            <option <?= $ticket['status']=='In Progress' ? 'selected' : '' ?>>In Progress</option>
                            <option <?= $ticket['status']=='Resolved'    ? 'selected' : '' ?>>Resolved</option>
                        </select>
                        <button type="submit" name="update_status" class="btn btn-primary">
                            Update
                        </button>
                    </form>
                    <small class="text-muted mt-2 d-block">
                        Note: In the next version, only technicians and admins will be able to update status.
                    </small>
                </div>
            </div>
            <?php else: ?>
            <div class="alert alert-success">
                This ticket has been resolved. <a href="dashboard.php">Go back</a>
            </div>
            <?php endif; ?>

            <!-- TODO comment box - planned for Week 5+ -->
            <div class="card mt-4">
                <div class="card-header">Comments</div>
                <div class="card-body text-muted text-center py-4">
                    <em>Comment system coming in the next version (Week 5+)</em>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
