<?php
// assign_ticket.php — Week 6: admin assigns tickets to technicians
require_once 'config/db.php';
require_once 'includes/auth.php';
requireRole('admin');

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM tickets WHERE id = ?");
$stmt->execute([$id]);
$ticket = $stmt->fetch();

if (!$ticket) {
    setFlash('error', 'Ticket not found.');
    header('Location: /ctisms_v2/admin_dashboard.php'); exit;
}

$technicians = $db->query(
    "SELECT id, name FROM users WHERE role = 'technician' ORDER BY name"
)->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $techId = (int)($_POST['technician_id'] ?? 0);

    if (!$techId) {
        $errors[] = 'Please select a technician.';
    } else {
        $chk = $db->prepare("SELECT id, name FROM users WHERE id = ? AND role = 'technician'");
        $chk->execute([$techId]);
        $tech = $chk->fetch();

        if (!$tech) {
            $errors[] = 'Invalid technician selected.';
        } else {
            $db->prepare("UPDATE tickets SET technician_id = ? WHERE id = ?")
               ->execute([$techId, $id]);

            // Notify technician — Week 7
            $db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")
               ->execute([$techId,
                         "Ticket #$id assigned to you: " . $ticket['title']]);

            setFlash('success', "Ticket #$id assigned to {$tech['name']}.");
            header('Location: /ctisms_v2/admin_dashboard.php'); exit;
        }
    }
}

$pageTitle = 'Assign Ticket #' . $id;
include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-5">

        <div class="d-flex align-items-center mb-4 gap-3">
            <a href="/ctisms_v2/admin_dashboard.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <h5 class="fw-bold mb-0">Assign Technician</h5>
                <small class="text-muted">
                    Ticket <span class="text-primary fw-bold">#<?= $id ?></span>
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

                <?php if ($ticket['technician_id']): ?>
                <div class="alert alert-info small">
                    <i class="bi bi-info-circle me-1"></i>
                    This ticket is already assigned. Choosing a new technician will reassign it.
                </div>
                <?php endif; ?>

                <form method="POST">
                    <?= csrfField() ?>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            Select Technician <span class="text-danger">*</span>
                        </label>
                        <?php if (empty($technicians)): ?>
                            <div class="alert alert-warning">
                                No technicians found.
                                <a href="/ctisms_v2/register.php">Register a technician →</a>
                            </div>
                        <?php else: ?>
                        <select name="technician_id" class="form-select" required>
                            <option value="">— Choose a technician —</option>
                            <?php foreach ($technicians as $t): ?>
                            <option value="<?= $t['id'] ?>"
                                <?= ($ticket['technician_id'] == $t['id']) ? 'selected' : '' ?>>
                                <?= e($t['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($technicians)): ?>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success px-4">
                            <i class="bi bi-person-check me-1"></i>Assign
                        </button>
                        <a href="/ctisms_v2/admin_dashboard.php"
                           class="btn btn-outline-secondary">Cancel</a>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

    </div>
</div>

<?php include 'includes/footer.php'; ?>
