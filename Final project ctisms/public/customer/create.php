<?php
require_once __DIR__ . '/../../app/bootstrap.php';
Auth::requireRole('customer');

$errors     = [];
$categories = (new CategoryModel())->getActive();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $data = [
        'title'       => trim($_POST['title'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'category_id' => (int)($_POST['category_id'] ?? 0),
        'priority'    => $_POST['priority'] ?? 'medium',
        'customer_id' => Auth::id(),
    ];

    if (strlen($data['title']) < 5)       $errors[] = 'Title must be at least 5 characters.';
    if (strlen($data['description']) < 10) $errors[] = 'Description must be at least 10 characters.';
    if (!$data['category_id'])            $errors[] = 'Please select a category.';
    if (!array_key_exists($data['priority'], TicketModel::PRIORITIES)) $errors[] = 'Invalid priority.';

    if (empty($errors)) {
        $ticketModel = new TicketModel();
        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            $ticketId = $ticketModel->create($data);
            $ticket   = $ticketModel->getDetail($ticketId);

            // Handle file attachment
            if (!empty($_FILES['attachment']['name'])) {
                $fileInfo = handleFileUpload($_FILES['attachment'], UPLOAD_DIR);
                if ($fileInfo) {
                    (new AttachmentModel())->create($ticketId, Auth::id(), $fileInfo);
                }
            }

            // Notification
            $notif = new NotificationModel();
            $notif->create(Auth::id(), 'ticket_created', 'Ticket Created',
                "Your ticket {$ticket['ticket_number']} has been submitted.", $ticketId);

            // Activity log
            $log = new ActivityLogModel();
            $log->log('ticket_created', "Customer " . Auth::name() . " created ticket {$ticket['ticket_number']}", Auth::id(), $ticketId);

            // Email
            MailService::ticketCreated($ticket, [
                'name' => Auth::name(), 'email' => $_SESSION['user_email']
            ]);

            $db->commit();
            redirectTo(APP_URL . '/customer/view.php?id=' . $ticketId, 'success',
                "Ticket {$ticket['ticket_number']} submitted successfully!");
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = 'Failed to create ticket. Please try again.';
        }
    }
}

$pageTitle = 'Submit New Ticket';
include __DIR__ . '/../../app/views/layouts/header.php';
include __DIR__ . '/../../app/views/layouts/sidebar.php';
?>

<?= renderFlash() ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Submit New Ticket</h1>
        <p class="page-subtitle">Describe your issue and we'll get it resolved</p>
    </div>
    <a href="<?= APP_URL ?>/customer/tickets.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Tickets
    </a>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger mb-3">
    <ul class="mb-0 ps-3"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><i class="bi bi-ticket-detailed me-2 text-primary"></i>Ticket Details</div>
            <div class="card-body p-4">
                <form method="POST" enctype="multipart/form-data">
                    <?= csrfField() ?>

                    <div class="mb-3">
                        <label class="form-label">Issue Title *</label>
                        <input type="text" name="title" class="form-control" required maxlength="255"
                               value="<?= e($_POST['title'] ?? '') ?>"
                               placeholder="Brief summary of your issue">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Category *</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">— Select Category —</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"
                                    <?= (($_POST['category_id'] ?? '') == $cat['id']) ? 'selected' : '' ?>>
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
                                    <?= (($_POST['priority'] ?? 'medium') === $key) ? 'selected' : '' ?>>
                                    <?= e($p['label']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Detailed Description *</label>
                        <textarea name="description" class="form-control" rows="7" required
                                  data-maxlength="5000"
                                  placeholder="Please describe your issue in detail: what happened, when it started, what you've already tried..."><?= e($_POST['description'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Attachment <span class="text-muted fw-normal">(optional, max 10MB)</span></label>
                        <input type="file" name="attachment" id="attachment" class="form-control"
                               accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip">
                        <div id="file-preview" class="mt-2"></div>
                        <div class="form-text">Allowed: JPG, PNG, PDF, DOC, DOCX, XLS, XLSX, TXT, ZIP</div>
                    </div>

                    

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-send me-2"></i>Submit Ticket
                        </button>
                        <a href="<?= APP_URL ?>/customer/dashboard.php" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../app/views/layouts/footer.php'; ?>
