<?php
require_once __DIR__ . '/../../app/bootstrap.php';
Auth::requireAuth();

$notifModel    = new NotificationModel();
$notifications = $notifModel->getAll(Auth::id(), 50);
$notifModel->markAllRead(Auth::id());

$pageTitle = 'Notifications';
include __DIR__ . '/../../app/views/layouts/header.php';
include __DIR__ . '/../../app/views/layouts/sidebar.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Notifications</h1>
        <p class="page-subtitle">Your recent alerts and updates</p>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
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
                    <?php
                    $typeIcons = [
                        'ticket_created'  => ['bi-ticket-detailed', 'text-primary'],
                        'ticket_assigned' => ['bi-person-check',    'text-success'],
                        'status_changed'  => ['bi-arrow-repeat',    'text-info'],
                        'comment_added'   => ['bi-chat-dots',       'text-primary'],
                        'sla_warning'     => ['bi-exclamation-triangle', 'text-danger'],
                    ];
                    [$icon, $color] = $typeIcons[$n['type']] ?? ['bi-bell', 'text-muted'];
                    ?>
                    <li class="list-group-item px-4 py-3 <?= !$n['is_read'] ? 'bg-light-blue' : '' ?>">
                        <div class="d-flex align-items-start gap-3">
                            <div class="mt-1">
                                <i class="bi <?= $icon ?> <?= $color ?> fs-5"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-600 small"><?= e($n['title']) ?></div>
                                <div class="text-muted small"><?= e($n['message']) ?></div>
                                <div class="text-muted mt-1" style="font-size:11px">
                                    <?= formatDate($n['created_at']) ?>
                                </div>
                            </div>
                            <?php if ($n['ticket_id']): ?>
                            <a href="<?= APP_URL ?>/<?= Auth::role() ?>/view.php?id=<?= $n['ticket_id'] ?>"
                               class="btn btn-sm btn-outline-primary text-nowrap">
                                View Ticket
                            </a>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../app/views/layouts/footer.php'; ?>
