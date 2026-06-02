<?php
// create_ticket.php — Week 5: priority field added
require_once 'config/db.php';
require_once 'includes/auth.php';
requireRole('customer');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $title    = trim($_POST['title'] ?? '');
    $desc     = trim($_POST['description'] ?? '');
    $priority = $_POST['priority'] ?? 'Medium';

    if (strlen($title) < 5)  $errors[] = 'Title must be at least 5 characters.';
    if (strlen($desc) < 10)  $errors[] = 'Description is too short.';
    if (!in_array($priority, ['Low','Medium','High'])) $errors[] = 'Invalid priority.';

    if (empty($errors)) {
        $db = getDB();
        $db->prepare("INSERT INTO tickets (title,description,priority,customer_id) VALUES(?,?,?,?)")
           ->execute([$title,$desc,$priority,userId()]);
        $newId = $db->lastInsertId();

        // Notify admins — Week 7
        $admins = $db->query("SELECT id FROM users WHERE role='admin'")->fetchAll();
        $notif  = $db->prepare("INSERT INTO notifications (user_id,message) VALUES(?,?)");
        foreach ($admins as $a) {
            $notif->execute([$a['id'], "New ticket #$newId submitted: $title"]);
        }

        setFlash('success',"Ticket #$newId submitted successfully!");
        header('Location: /ctisms_v2/customer_dashboard.php'); exit;
    }
}

$pageTitle = 'New Ticket';
include 'includes/header.php';
?>

<div class="row justify-content-center">
  <div class="col-lg-7">
    <div class="d-flex align-items-center mb-4 gap-3">
      <a href="/ctisms_v2/customer_dashboard.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
      </a>
      <div>
        <h4 class="fw-bold mb-0">Submit a New Ticket</h4>
        <small class="text-muted">Describe your issue clearly</small>
      </div>
    </div>

    <?php if ($errors): ?>
    <div class="alert alert-danger">
      <ul class="mb-0 ps-3"><?php foreach($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul>
    </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-body p-4">
        <form method="POST">
          <?= csrfField() ?>
          <div class="mb-3">
            <label class="form-label fw-semibold">Issue Title <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control"
                   placeholder="Brief summary of the problem"
                   value="<?= e($_POST['title']??'') ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Priority</label>
            <select name="priority" class="form-select">
              <?php foreach(['Low','Medium','High'] as $p): ?>
              <option value="<?= $p ?>" <?= (($_POST['priority']??'Medium')===$p)?'selected':'' ?>><?= $p ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-4">
            <label class="form-label fw-semibold">Description <span class="text-danger">*</span></label>
            <textarea name="description" class="form-control" rows="6"
                      placeholder="Explain in detail: what happened, when, and what you've tried..."
                      required><?= e($_POST['description']??'') ?></textarea>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary px-4">
              <i class="bi bi-send me-1"></i>Submit Ticket
            </button>
            <a href="/ctisms_v2/customer_dashboard.php" class="btn btn-outline-secondary">Cancel</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
