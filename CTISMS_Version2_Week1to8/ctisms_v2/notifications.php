<?php
// notifications.php — Week 7: notification system
require_once 'config/db.php';
require_once 'includes/auth.php';
requireLogin();

$db  = getDB();
$uid = userId();

// Mark all as read when page opens
$db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")
   ->execute([$uid]);

// Load all notifications
$stmt = $db->prepare(
    "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC"
);
$stmt->execute([$uid]);
$notifications = $stmt->fetchAll();

$pageTitle = 'Notifications';
include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-7">

        <div class="mb-4">
            <h4 class="fw-bold mb-0">
                <i class="bi bi-bell me-2 text-primary"></i>Notifications
            </h4>
            <small class="text-muted"><?= count($notifications) ?> total</small>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <?php if (empty($notifications)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-bell-slash fs-1 d-block mb-2 opacity-25"></i>
                    <p>No notifications yet.</p>
                </div>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                <?php foreach ($notifications as $n): ?>
                    <li class="list-group-item px-4 py-3">
                        <div class="d-flex gap-3 align-items-start">
                            <i class="bi bi-info-circle text-primary mt-1"></i>
                            <div>
                                <div class="small"><?= e($n['message']) ?></div>
                                <div class="text-muted" style="font-size:11px">
                                    <?= date('d M Y H:i', strtotime($n['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?php include 'includes/footer.php'; ?>
