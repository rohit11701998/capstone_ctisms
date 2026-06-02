<?php
// customer_dashboard.php — Week 7: search + filter added
require_once 'config/db.php';
require_once 'includes/auth.php';
requireRole('customer');

$db  = getDB();
$uid = userId();

// Week 7 — search/filter
$search   = trim($_GET['search']   ?? '');
$filterStatus = $_GET['status'] ?? '';

$sql    = "SELECT t.*, u.name AS tech_name
           FROM tickets t LEFT JOIN users u ON u.id=t.technician_id
           WHERE t.customer_id=?";
$params = [$uid];

if ($search) {
    $sql .= " AND (t.title LIKE ? OR t.description LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%";
}
if ($filterStatus) {
    $sql .= " AND t.status=?";
    $params[] = $filterStatus;
}
$sql .= " ORDER BY t.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

$total = count($tickets);
$open  = count(array_filter($tickets, fn($t)=>$t['status']==='Open'));
$inpg  = count(array_filter($tickets, fn($t)=>$t['status']==='In Progress'));
$done  = count(array_filter($tickets, fn($t)=>in_array($t['status'],['Completed','Closed'])));

function sBadge(string $s): string {
    $map=['Open'=>'badge-open','In Progress'=>'badge-inprog',
          'Awaiting Parts'=>'badge-awaiting','Completed'=>'badge-completed','Closed'=>'badge-closed'];
    return '<span class="'.($map[$s]??'badge-open').'">'.htmlspecialchars($s).'</span>';
}

$pageTitle = 'My Tickets';
include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="fw-bold mb-0">My Support Tickets</h4>
    <small class="text-muted">Welcome, <?= e(userName()) ?></small>
  </div>
  <a href="/ctisms_v2/create_ticket.php" class="btn btn-primary">
    <i class="bi bi-plus-circle me-1"></i>New Ticket
  </a>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
  <?php foreach ([['Total',$total,'primary'],['Open',$open,'warning'],
                  ['In Progress',$inpg,'info'],['Resolved',$done,'success']] as [$lbl,$val,$c]): ?>
  <div class="col-6 col-md-3">
    <div class="card text-center p-3">
      <div class="fs-2 fw-bold text-<?= $c ?>"><?= $val ?></div>
      <div class="text-muted small"><?= $lbl ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Week 7: Search + filter row -->
<div class="card mb-3">
  <div class="card-body py-2">
    <form method="GET" class="row g-2 align-items-center">
      <div class="col-md-6">
        <input type="text" name="search" id="liveSearch" class="form-control form-control-sm"
               placeholder="Search by title…" value="<?= e($search) ?>">
      </div>
      <div class="col-md-3">
        <select name="status" class="form-select form-select-sm">
          <option value="">All Statuses</option>
          <?php foreach(['Open','In Progress','Awaiting Parts','Completed','Closed'] as $s): ?>
          <option value="<?= $s ?>" <?= $filterStatus===$s?'selected':'' ?>><?= $s ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filter</button>
        <a href="/ctisms_v2/customer_dashboard.php" class="btn btn-outline-secondary btn-sm">Clear</a>
      </div>
    </form>
  </div>
</div>

<!-- Tickets table -->
<div class="card">
  <div class="card-body p-0">
    <?php if (empty($tickets)): ?>
    <div class="text-center py-5 text-muted">
      <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
      No tickets found. <a href="/ctisms_v2/create_ticket.php">Submit one →</a>
    </div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>#</th><th>Title</th><th>Priority</th>
            <th>Status</th><th>Assigned To</th><th>Date</th><th></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach($tickets as $t): ?>
          <tr>
            <td class="text-primary fw-bold">#<?= $t['id'] ?></td>
            <td><?= e($t['title']) ?></td>
            <td>
              <?php
              $pc=['High'=>'danger','Medium'=>'warning','Low'=>'success'];
              echo '<span class="badge bg-'.($pc[$t['priority']]??'secondary').'">'
                   .e($t['priority']).'</span>';
              ?>
            </td>
            <td><?= sBadge($t['status']) ?></td>
            <td class="text-muted small">
              <?= $t['tech_name'] ? e($t['tech_name']) : '<em>Unassigned</em>' ?>
            </td>
            <td class="text-muted small"><?= date('d M Y',strtotime($t['created_at'])) ?></td>
            <td>
              <a href="/ctisms_v2/view_ticket.php?id=<?= $t['id'] ?>"
                 class="btn btn-sm btn-outline-primary">View</a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
