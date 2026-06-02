<?php
// update_status.php — Week 6: technician/admin update ticket status
require_once 'config/db.php';
require_once 'includes/auth.php';
requireRole('technician', 'admin');

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("
    SELECT t.*, u.id AS cust_id, u.name AS customer_name
    FROM tickets t
    JOIN users u ON u.id = t.customer_id
    WHERE t.id = ?
");
$stmt->execute([$id]);
$ticket = $stmt->fetch();

if (!$ticket) {
    setFlash('error', 'Ticket not found.');
    header('Location: ' . dashboardUrl()); exit;
}

// Technicians can only update their own assigned tickets
if (userRole() === 'technician' && $ticket['technician_id'] !== userId()) {
    setFlash('error', 'You can only update tickets assigned to you.');
    header('Location: /ctisms_v2/technician_dashboard.php'); exit;
}

$statuses = ['Open', 'In Progress', 'Awaiting Parts', 'Completed', 'Closed'];
$errors   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $newStatus = $_POST['status']  ?? '';
    $comment   = trim($_POST['comment'] ?? '');

    if (!in_array($newStatus, $statuses)) {
        $errors[] = 'Please select a valid status.';
    }

    if (empty($errors)) {
        // Update ticket status
        $db->prepare("UPDATE tickets SET status = ? WHERE id = ?")
           ->execute([$newStatus, $id]);

        // Save optional comment
        if ($comment !== '') {
            $db->prepare("INSERT INTO comments (ticket_id, user_id, message) VALUES (?, ?, ?)")
               ->execute([$id, userId(), $comment]);
        }

        // Notify customer — Week 7
        $db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")
           ->execute([$ticket['cust_id'],
                     "Your ticket #$id status changed to: $newStatus"]);

        setFlash('success', "Status updated to: $newStatus");
        header('Location: /ctisms_v2/view_ticket.php?id=' . $id); exit;
    }
}

$pageTitle = 'Update Status — Ticket #' . $id;
include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-6">

        <div class="d-flex align-items-center mb-4 gap-3">
            <a href="/ctisms_v2/view_ticket.php?id=<?= $id ?>"
               class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <h5 class="fw-bold mb-0">Update Ticket Status</h5>
                <small class="text-muted">
                    <span class="text-primary fw-bold">#<?= $id ?></span>
                    — <?= e($ticket['title']) ?>
                </small>
            </div>
        </div>

        <?php if ($errors): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $e): ?>
                <div><?= htmlspecialchars($e) ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body p-4">

                <div class="p-3 bg-light rounded mb-3">
                    <span class="text-muted small">Current Status:</span>
                    <strong class="ms-2"><?= e($ticket['status']) ?></strong>
                </div>

                <form method="POST">
                    <?= csrfField() ?>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            New Status <span class="text-danger">*</span>
                        </label>
                        <select name="status" class="form-select" required>
                            <?php foreach ($statuses as $s): ?>
                            <option value="<?= $s ?>"
                                <?= ($ticket['status'] === $s) ? 'selected' : '' ?>>
                                <?= $s ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            Add a Note
                            <span class="text-muted fw-normal">(optional)</span>
                        </label>
                        <textarea name="comment" class="form-control" rows="4"
                                  placeholder="Describe what was done or any additional notes..."></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check2 me-1"></i>Save Update
                        </button>
                        <a href="/ctisms_v2/view_ticket.php?id=<?= $id ?>"
                           class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<?php include 'includes/footer.php'; ?>
