<?php
// view_ticket.php — Week 5: comments system added, Week 6: role-based controls
require_once 'config/db.php';
require_once 'includes/auth.php';
requireLogin();

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

// Load ticket with joins
$stmt = $db->prepare("
    SELECT t.*, c.name AS customer_name, c.id AS cust_id,
           u.name AS tech_name
    FROM tickets t
    JOIN users c ON c.id = t.customer_id
    LEFT JOIN users u ON u.id = t.technician_id
    WHERE t.id = ?
");
$stmt->execute([$id]);
$ticket = $stmt->fetch();

if (!$ticket) {
    setFlash('error', 'Ticket not found.');
    header('Location: ' . dashboardUrl()); exit;
}

// Customers can only see their own tickets
if (userRole() === 'customer' && $ticket['cust_id'] !== userId()) {
    echo '<div class="container py-5 text-center"><h4>Access Denied.</h4>
          <a href="/ctisms_v2/customer_dashboard.php">Back</a></div>'; exit;
}

// Handle add comment — Week 5
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    verifyCsrf();
    $msg = trim($_POST['message'] ?? '');
    if (strlen($msg) >= 2) {
        $db->prepare("INSERT INTO comments (ticket_id, user_id, message) VALUES (?, ?, ?)")
           ->execute([$id, userId(), $msg]);

        // Notify customer if staff commented — Week 7
        if (userRole() !== 'customer') {
            $db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")
               ->execute([$ticket['cust_id'],
                         "New reply on your ticket #$id: " . $ticket['title']]);
        }
        setFlash('success', 'Comment posted.');
        header("Location: /ctisms_v2/view_ticket.php?id=$id"); exit;
    }
}

// Load comments
$cmts = $db->prepare("
    SELECT c.*, u.name AS author, u.role AS author_role
    FROM comments c
    JOIN users u ON u.id = c.user_id
    WHERE c.ticket_id = ?
    ORDER BY c.created_at ASC
");
$cmts->execute([$id]);
$comments = $cmts->fetchAll();

function sBadge(string $s): string {
    $map = ['Open'=>'badge-open','In Progress'=>'badge-inprog',
            'Awaiting Parts'=>'badge-awaiting','Completed'=>'badge-completed','Closed'=>'badge-closed'];
    return '<span class="'.($map[$s]??'badge-open').'">'.htmlspecialchars($s).'</span>';
}

$backUrl = dashboardUrl();
$pageTitle = 'Ticket #' . $id;
include 'includes/header.php';
?>

<div class="d-flex align-items-center mb-4 gap-3">
    <a href="<?= $backUrl ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h5 class="mb-0 fw-bold">
            <span class="text-primary">#<?= $id ?></span> <?= e($ticket['title']) ?>
        </h5>
        <small class="text-muted">
            Submitted by <?= e($ticket['customer_name']) ?>
            on <?= date('d M Y H:i', strtotime($ticket['created_at'])) ?>
        </small>
    </div>
</div>

<div class="row g-4">

    <!-- Left: Description + Comments -->
    <div class="col-lg-8">

        <!-- Description -->
        <div class="card mb-4">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-file-text me-2 text-primary"></i>Description
            </div>
            <div class="card-body">
                <p class="mb-0" style="white-space:pre-wrap"><?= e($ticket['description']) ?></p>
            </div>
        </div>

        <!-- Comments — Week 5 addition -->
        <div class="card mb-4">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-chat-dots me-2 text-primary"></i>
                Comments (<?= count($comments) ?>)
            </div>
            <div class="card-body">
                <?php if (empty($comments)): ?>
                    <p class="text-muted small">No comments yet. Be the first to reply.</p>
                <?php endif; ?>

                <?php foreach ($comments as $c): ?>
                <div class="d-flex gap-3 mb-3">
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center
                                justify-content-center flex-shrink-0 fw-bold"
                         style="width:36px;height:36px;font-size:14px">
                        <?= strtoupper(substr($c['author'], 0, 1)) ?>
                    </div>
                    <div class="flex-grow-1">
                        <div class="comment-bubble <?= $c['author_role'] !== 'customer' ? 'staff' : '' ?>">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <strong class="small"><?= e($c['author']) ?></strong>
                                <span class="badge bg-<?= $c['author_role']==='customer'?'primary':'success' ?> small">
                                    <?= ucfirst($c['author_role']) ?>
                                </span>
                            </div>
                            <p class="mb-0 small" style="white-space:pre-wrap"><?= e($c['message']) ?></p>
                        </div>
                        <small class="text-muted">
                            <?= date('d M Y H:i', strtotime($c['created_at'])) ?>
                        </small>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- Add comment form -->
                <?php if ($ticket['status'] !== 'Closed'): ?>
                <form method="POST" class="mt-3 pt-3 border-top">
                    <?= csrfField() ?>
                    <label class="form-label fw-semibold small">Add a Comment</label>
                    <textarea name="message" class="form-control mb-2" rows="3"
                              placeholder="Write your comment here..." required></textarea>
                    <button type="submit" name="add_comment" class="btn btn-primary btn-sm">
                        <i class="bi bi-send me-1"></i>Post Comment
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Right: Ticket info sidebar -->
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-info-circle me-2 text-primary"></i>Ticket Info
            </div>
            <div class="card-body">
                <dl class="row small mb-0">
                    <dt class="col-5 text-muted">Status</dt>
                    <dd class="col-7"><?= sBadge($ticket['status']) ?></dd>

                    <dt class="col-5 text-muted">Priority</dt>
                    <dd class="col-7">
                        <?php
                        $pc = ['High'=>'text-danger fw-bold','Medium'=>'text-warning fw-bold',
                               'Low'=>'text-success fw-bold'];
                        echo '<span class="'.($pc[$ticket['priority']]??'').'">'
                             . e($ticket['priority']) . '</span>';
                        ?>
                    </dd>

                    <dt class="col-5 text-muted">Customer</dt>
                    <dd class="col-7"><?= e($ticket['customer_name']) ?></dd>

                    <dt class="col-5 text-muted">Technician</dt>
                    <dd class="col-7">
                        <?= $ticket['tech_name']
                            ? e($ticket['tech_name'])
                            : '<em class="text-muted">Unassigned</em>' ?>
                    </dd>

                    <dt class="col-5 text-muted">Created</dt>
                    <dd class="col-7"><?= date('d M Y', strtotime($ticket['created_at'])) ?></dd>

                    <dt class="col-5 text-muted">Updated</dt>
                    <dd class="col-7"><?= date('d M Y', strtotime($ticket['updated_at'])) ?></dd>
                </dl>

                <!-- Action buttons for staff -->
                <?php if (in_array(userRole(), ['technician', 'admin'])
                          && $ticket['status'] !== 'Closed'): ?>
                <hr>
                <a href="/ctisms_v2/update_status.php?id=<?= $id ?>"
                   class="btn btn-warning text-dark btn-sm w-100 mb-2">
                    <i class="bi bi-arrow-repeat me-1"></i>Update Status
                </a>
                <?php endif; ?>

                <?php if (userRole() === 'admin'): ?>
                <a href="/ctisms_v2/assign_ticket.php?id=<?= $id ?>"
                   class="btn btn-outline-success btn-sm w-100">
                    <i class="bi bi-person-check me-1"></i>Assign Technician
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<?php include 'includes/footer.php'; ?>
