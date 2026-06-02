<?php
require_once __DIR__ . '/../../app/bootstrap.php';
Auth::requireRole('admin');

$id          = (int)($_GET['id'] ?? 0);
$ticketModel = new TicketModel();
$ticket      = $ticketModel->getDetail($id);

if (!$ticket) {
    http_response_code(404);
    include __DIR__ . '/../../app/views/errors/404.php';
    exit;
}

$errors     = [];
$categories = (new CategoryModel())->getActive();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $data = [
        'title'       => trim($_POST['title'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'category_id' => (int)($_POST['category_id'] ?? 0),
        'priority'    => $_POST['priority'] ?? '',
    ];

    if (strlen($data['title']) < 5)        $errors[] = 'Title must be at least 5 characters.';
    if (strlen($data['description']) < 10) $errors[] = 'Description must be at least 10 characters.';
    if (!$data['category_id'])             $errors[] = 'Please select a category.';
    if (!array_key_exists($data['priority'], TicketModel::PRIORITIES)) $errors[] = 'Invalid priority.';

    if (empty($errors)) {
        $db = Database::getInstance();
        $db->query(
            "UPDATE tickets SET title=?, description=?, category_id=?, priority=?, updated_at=NOW() WHERE id=?",
            [$data['title'], $data['description'], $data['category_id'], $data['priority'], $id]
        );

        $log = new ActivityLogModel();
        $log->log('ticket_edited',
            Auth::name() . " edited ticket {$ticket['ticket_number']} (title/category/priority)",
            Auth::id(), $id);

        redirectTo(APP_URL . '/admin/view.php?id=' . $id, 'success', 'Ticket updated successfully.');
    }
}

$pageTitle = 'Edit Ticket — ' . $ticket['ticket_number'];
include __DIR__ . '/../../app/views/layouts/header.php';
include __DIR__ . '/../../app/views/layouts/sidebar.php';
?>

<?= renderFlash() ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Edit Ticket</h1>
        <p class="page-subtitle"><?= e($ticket['ticket_number']) ?> — <?= e($ticket['title']) ?></p>
    </div>
    <a href="<?= APP_URL ?>/admin/view.php?id=<?= $id ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Ticket
    </a>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger">
    <ul class="mb-0 ps-3"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><i class="bi bi-pencil me-2 text-primary"></i>Edit Ticket Details</div>
            <div class="card-body p-4">
                <form method="POST">
                    <?= csrfField() ?>

                    <div class="mb-3">
                        <label class="form-label">Ticket Number</label>
                        <input type="text" class="form-control bg-light" value="<?= e($ticket['ticket_number']) ?>" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Title *</label>
                        <input type="text" name="title" class="form-control" required maxlength="255"
                               value="<?= e($_POST['title'] ?? $ticket['title']) ?>">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Category *</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">— Select Category —</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"
                                    <?= (($_POST['category_id'] ?? $ticket['category_id']) == $cat['id']) ? 'selected' : '' ?>>
                                    <?= e($cat['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Priority *</label>
                            <select name="priority" class="form-select" required>
                                <?php foreach (TicketModel::PRIORITIES as $key => $p): ?>
                                <option value="<?= $key ?>"
                                    <?= (($_POST['priority'] ?? $ticket['priority']) === $key) ? 'selected' : '' ?>>
                                    <?= e($p['label']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Description *</label>
                        <textarea name="description" class="form-control" rows="8" required
                                  data-maxlength="5000"><?= e($_POST['description'] ?? $ticket['description']) ?></textarea>
                    </div>

                    <div class="alert alert-warning py-2 px-3 mb-4" style="font-size:13px;">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Note:</strong> Changing the category or priority will <strong>not</strong> automatically recalculate the SLA deadline.
                        To reset SLA, update the ticket's <code>sla_deadline</code> directly in the database.
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-save me-2"></i>Save Changes
                        </button>
                        <a href="<?= APP_URL ?>/admin/view.php?id=<?= $id ?>" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../app/views/layouts/footer.php'; ?>
