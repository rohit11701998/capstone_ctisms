<?php
require_once __DIR__ . '/../../app/bootstrap.php';
Auth::requireAuth();

$id          = (int)($_GET['id'] ?? 0);
$ticketModel = new TicketModel();
$ticket      = $ticketModel->getDetail($id);

if (!$ticket) {
    http_response_code(404);
    die('Ticket not found.');
}

// Access control: customers can only see their own tickets
if (Auth::isCustomer() && $ticket['customer_id'] !== Auth::id()) {
    http_response_code(403);
    die('Access denied.');
}

// Detect role and set base path for action forms
$role     = Auth::role();
$basePath = match($role) {
    'admin'      => APP_URL . '/admin',
    'technician' => APP_URL . '/technician',
    default      => APP_URL . '/customer',
};

// ---- POST: Add comment / Update status (staff) / Add resolution ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add_comment') {
        $body       = trim($_POST['body'] ?? '');
        $isInternal = Auth::isStaff() && !empty($_POST['is_internal']);
        if (strlen($body) >= 2) {
            $commentModel = new CommentModel();
            $commentModel->create($id, Auth::id(), $body, $isInternal);

            // Notify customer of new comment (if staff commenting)
            if (Auth::isStaff() && !$isInternal) {
                $notif = new NotificationModel();
                $notif->create($ticket['customer_id'], 'comment_added',
                    'New Reply on ' . $ticket['ticket_number'],
                    Auth::name() . ' replied to your ticket.', $id);
                MailService::statusChanged($ticket,
                    ['name' => $ticket['customer_name'], 'email' => $ticket['customer_email']],
                    $ticket['status']);
            }

            $log = new ActivityLogModel();
            $log->log('comment_added', Auth::name() . ' added a comment', Auth::id(), $id);
        }
        redirectTo(APP_URL . '/' . $role . '/view.php?id=' . $id);
    }

    if ($action === 'update_status' && Auth::isStaff()) {
        $status     = $_POST['status'] ?? '';
        $resolution = trim($_POST['resolution'] ?? '');
        if (array_key_exists($status, TicketModel::STATUSES)) {
            $ticketModel->updateStatus($id, $status, $resolution ?: null);

            // Notify customer
            $notif = new NotificationModel();
            $notif->create($ticket['customer_id'], 'status_changed',
                'Ticket Status Updated',
                "Your ticket {$ticket['ticket_number']} is now: " . TicketModel::STATUSES[$status]['label'],
                $id);
            MailService::statusChanged($ticket,
                ['name' => $ticket['customer_name'], 'email' => $ticket['customer_email']], $status);

            $log = new ActivityLogModel();
            $log->log('status_changed', "Status changed to {$status} for ticket {$ticket['ticket_number']}", Auth::id(), $id);

            redirectTo(APP_URL . '/' . $role . '/view.php?id=' . $id, 'success', 'Status updated.');
        }
    }
}

// Reload ticket after any updates
$ticket   = $ticketModel->getDetail($id);
$isStaff  = Auth::isStaff();
$comments = (new CommentModel())->getByTicket($id, $isStaff);
$activity = (new ActivityLogModel())->getByTicket($id);
$attachments = (new AttachmentModel())->getByTicket($id);

$pageTitle = $ticket['ticket_number'] . ' — ' . $ticket['title'];
include __DIR__ . '/../../app/views/layouts/header.php';
include __DIR__ . '/../../app/views/layouts/sidebar.php';
?>

<?= renderFlash() ?>

<!-- Ticket Header -->
<div class="ticket-detail-header mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <div class="ticket-detail-number"><?= e($ticket['ticket_number']) ?></div>
            <div class="ticket-detail-title"><?= e($ticket['title']) ?></div>
            <div class="d-flex gap-2 mt-2 flex-wrap">
                <?= statusBadge($ticket['status']) ?>
                <?= priorityBadge($ticket['priority']) ?>
                <span class="badge bg-light text-dark"><?= e($ticket['category_name']) ?></span>
                <?php if ($ticket['sla_deadline']): ?>
                    <?= slaBadge($ticket['sla_deadline'], $ticket['sla_breached']) ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button id="printTicket" class="btn btn-sm btn-light"><i class="bi bi-printer me-1"></i>Print</button>
            <?php if (Auth::isAdmin() && !in_array($ticket['status'], ['closed'])): ?>
            <a href="<?= APP_URL ?>/admin/edit.php?id=<?= $ticket['id'] ?>" class="btn btn-sm btn-warning text-dark">
                <i class="bi bi-pencil me-1"></i>Edit
            </a>
            <?php endif; ?>
            <a href="<?= $basePath ?>/tickets.php" class="btn btn-sm btn-light">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Main Column -->
    <div class="col-lg-8">

        <!-- Description -->
        <div class="card mb-4">
            <div class="card-header"><i class="bi bi-file-text me-2"></i>Description</div>
            <div class="card-body">
                <p class="mb-0" style="white-space:pre-wrap"><?= e($ticket['description']) ?></p>
            </div>
        </div>

        <!-- Resolution (if any) -->
        <?php if ($ticket['resolution']): ?>
        <div class="card mb-4 border-success">
            <div class="card-header bg-success text-white">
                <i class="bi bi-check-circle me-2"></i>Resolution
            </div>
            <div class="card-body">
                <p class="mb-0" style="white-space:pre-wrap"><?= e($ticket['resolution']) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Attachments -->
        <?php if (!empty($attachments)): ?>
        <div class="card mb-4">
            <div class="card-header"><i class="bi bi-paperclip me-2"></i>Attachments</div>
            <div class="card-body">
                <?php foreach ($attachments as $att): ?>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-file-earmark text-primary fs-5"></i>
                    <div>
                        <div class="fw-500 small"><?= e($att['original_name']) ?></div>
                        <div class="text-muted" style="font-size:11px">
                            <?= formatBytes($att['file_size']) ?> · Uploaded by <?= e($att['uploader_name']) ?>
                        </div>
                    </div>
                    <a href="<?= APP_URL ?>/uploads/attachments/<?= e($att['filename']) ?>"
                       target="_blank" class="btn btn-sm btn-outline-primary ms-auto">
                        <i class="bi bi-download"></i>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Update Status (Staff Only) -->
        <?php if ($isStaff && !in_array($ticket['status'], ['closed'])): ?>
        <div class="card mb-4">
            <div class="card-header"><i class="bi bi-arrow-repeat me-2 text-info"></i>Update Status</div>
            <div class="card-body">
                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="update_status">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">New Status</label>
                            <select name="status" class="form-select" required>
                                <?php foreach (TicketModel::STATUSES as $key => $s): ?>
                                <option value="<?= $key ?>" <?= $ticket['status'] === $key ? 'selected' : '' ?>>
                                    <?= e($s['label']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Resolution Notes <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="text" name="resolution" class="form-control"
                                   value="<?= e($ticket['resolution'] ?? '') ?>"
                                   placeholder="What was done to resolve this?">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-info text-white">
                                <i class="bi bi-check2 me-2"></i>Update Status
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Comments -->
        <div class="card mb-4">
            <div class="card-header"><i class="bi bi-chat-dots me-2"></i>Comments (<?= count($comments) ?>)</div>
            <div class="card-body">
                <?php if (empty($comments)): ?>
                <p class="text-muted mb-3">No comments yet.</p>
                <?php endif; ?>

                <div class="timeline mb-4">
                <?php foreach ($comments as $c): ?>
                    <div class="timeline-item">
                        <div class="d-flex gap-2 mb-1 align-items-center">
                            <div class="comment-avatar" style="width:30px;height:30px;font-size:12px">
                                <?= strtoupper(substr($c['user_name'], 0, 1)) ?>
                            </div>
                            <strong class="small"><?= e($c['user_name']) ?></strong>
                            <span class="badge bg-light text-dark" style="font-size:10px"><?= ucfirst($c['user_role']) ?></span>
                            <?php if ($c['is_internal']): ?>
                            <span class="badge bg-warning text-dark" style="font-size:10px">Internal</span>
                            <?php endif; ?>
                            <span class="text-muted ms-auto" style="font-size:11px"><?= timeAgo($c['created_at']) ?></span>
                        </div>
                        <div class="timeline-body <?= $c['is_internal'] ? 'internal' : '' ?>">
                            <?= nl2br(e($c['body'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>

                <!-- Add Comment -->
                <?php if ($ticket['status'] !== 'closed'): ?>
                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="add_comment">
                    <div class="mb-2">
                        <textarea name="body" class="form-control" rows="3" required
                                  data-maxlength="2000"
                                  placeholder="Add a comment or update..."></textarea>
                    </div>
                    <?php if ($isStaff): ?>
                    <div class="form-check mb-2">
                        <input type="checkbox" name="is_internal" class="form-check-input" id="internalChk" value="1">
                        <label class="form-check-label small text-muted" for="internalChk">
                            Internal note (not visible to customer)
                        </label>
                    </div>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-send me-2"></i>Post Comment
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Sidebar Info -->
    <div class="col-lg-4">

        <!-- Ticket Info -->
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-info-circle me-2"></i>Ticket Info</div>
            <div class="card-body">
                <dl class="row small mb-0">
                    <dt class="col-5 text-muted">Ticket #</dt>
                    <dd class="col-7 font-mono"><?= e($ticket['ticket_number']) ?></dd>

                    <dt class="col-5 text-muted">Created</dt>
                    <dd class="col-7"><?= formatDate($ticket['created_at']) ?></dd>

                    <dt class="col-5 text-muted">Updated</dt>
                    <dd class="col-7"><?= formatDate($ticket['updated_at']) ?></dd>

                    <?php if ($ticket['closed_at']): ?>
                    <dt class="col-5 text-muted">Closed</dt>
                    <dd class="col-7"><?= formatDate($ticket['closed_at']) ?></dd>
                    <?php endif; ?>

                    <dt class="col-5 text-muted">SLA Due</dt>
                    <dd class="col-7"><?= $ticket['sla_deadline'] ? formatDate($ticket['sla_deadline'], 'd M Y H:i') : '—' ?></dd>
                </dl>
            </div>
        </div>

        <!-- People -->
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-people me-2"></i>People</div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="text-muted small mb-1">Submitted by</div>
                    <div class="d-flex align-items-center gap-2">
                        <div class="comment-avatar" style="width:28px;height:28px;font-size:11px">
                            <?= strtoupper(substr($ticket['customer_name'], 0, 1)) ?>
                        </div>
                        <div>
                            <div class="fw-500 small"><?= e($ticket['customer_name']) ?></div>
                            <div class="text-muted" style="font-size:11px"><?= e($ticket['customer_email']) ?></div>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="text-muted small mb-1">Assigned to</div>
                    <?php if ($ticket['technician_name']): ?>
                    <div class="d-flex align-items-center gap-2">
                        <div class="comment-avatar" style="width:28px;height:28px;font-size:11px;background:linear-gradient(135deg,#1b5e20,#2e7d32)">
                            <?= strtoupper(substr($ticket['technician_name'], 0, 1)) ?>
                        </div>
                        <div>
                            <div class="fw-500 small"><?= e($ticket['technician_name']) ?></div>
                            <div class="text-muted" style="font-size:11px"><?= e($ticket['technician_email']) ?></div>
                        </div>
                    </div>
                    <?php else: ?>
                    <span class="text-muted small">Unassigned</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Admin: Assign Ticket -->
        <?php if (Auth::isAdmin() && !in_array($ticket['status'], ['closed'])): ?>
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-person-check me-2 text-success"></i>Assign Ticket</div>
            <div class="card-body">
                <form method="POST" action="<?= APP_URL ?>/admin/assign.php">
                    <?= csrfField() ?>
                    <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                    <select name="technician_id" class="form-select form-select-sm mb-2" required>
                        <option value="">— Select Technician —</option>
                        <?php foreach ((new UserModel())->getTechnicians() as $tech): ?>
                        <option value="<?= $tech['id'] ?>" <?= $ticket['technician_id'] == $tech['id'] ? 'selected' : '' ?>>
                            <?= e($tech['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-success btn-sm w-100">
                        <i class="bi bi-person-plus me-1"></i>Assign
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Activity Log -->
        <?php if ($isStaff && !empty($activity)): ?>
        <div class="card">
            <div class="card-header"><i class="bi bi-clock-history me-2"></i>Activity Log</div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush" style="font-size:12px">
                    <?php foreach (array_slice($activity, -8) as $a): ?>
                    <li class="list-group-item py-2 px-3">
                        <div class="text-muted"><?= timeAgo($a['created_at']) ?></div>
                        <div><?= e($a['description']) ?></div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../app/views/layouts/footer.php'; ?>
