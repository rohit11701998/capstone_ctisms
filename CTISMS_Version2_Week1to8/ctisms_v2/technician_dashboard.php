<?php
// technician_dashboard.php — Week 6: technician role added
require_once 'config/db.php';
require_once 'includes/auth.php';
requireRole('technician');

$db  = getDB();
$uid = userId();

// Week 7 — filter
$filterStatus = $_GET['status'] ?? '';
$sql = "SELECT t.*, c.name AS customer_name
        FROM tickets t JOIN users c ON c.id=t.customer_id
        WHERE t.technician_id=?";
$params = [$uid];
if ($filterStatus) { $sql .= " AND t.status=?"; $params[] = $filterStatus; }
$sql .= " ORDER BY t.created_at DESC";
$stmt = $db->prepare($sql); $stmt->execute($params);
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

$pageTitle = 'Technician Dashboard';
include 'includes/header.php';
?>

<div class="mb-4">
  <h4 class="fw-bold mb-0">My Assigned Tickets</h4>
  <small class="text-muted">Welcome, <?= e(userName()) ?></small>
</div>

<div class="row g-3 mb-4">
  <?php foreach([['Assigned',$total,'primary'],['Open',$open,'warning'],
                 ['In Progress',$inpg,'info'],['Resolved',$done,'success']] as [$l,$v,$c]): ?>
  <div class="col-6 col-md-3">
    <div class="card text-center p-3">
      <div class="fs-2 fw-bold text-<?= $c ?>"><?= $v ?></div>
      <div class="text-muted small"><?= $l ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Filter -->
<div class="card mb-3">
  <div class="card-body py-2">
    <form method="GET" class="d-flex gap-2 align-items-center">
      <select name="status" class="form-select form-select-sm" style="max-width:220px">
        <option value="">All Statuses</option>
        <?php foreach(['Open','In Progress','Awaiting Parts','Completed','Closed'] as $s): ?>
        <option value="<?= $s ?>" <?= $filterStatus===$s?'selected':'' ?>><?= $s ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-primary btn-sm">Filter</button>
      <a href="/ctisms_v2/technician_dashboard.php" class="btn btn-outline-secondary btn-sm">Clear</a>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body p-0">
    <?php if (empty($tickets)): ?>
    <div class="text-center py-5 text-muted">
      <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>No tickets assigned yet.
    </div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr><th>#</th><th>Title</th><th>Customer</th><th>Priority</th><th>Status</th><th>Updated</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach($tickets as $t): ?>
          <tr>
            <td class="text-primary fw-bold">#<?= $t['id'] ?></td>
            <td><?= e($t['title']) ?></td>
            <td class="text-muted small"><?= e($t['customer_name']) ?></td>
            <td><span class="badge bg-<?= ['High'=>'danger','Medium'=>'warning','Low'=>'success'][$t['priority']]??'secondary' ?>"><?= e($t['priority']) ?></span></td>
            <td><?= sBadge($t['status']) ?></td>
            <td class="text-muted small"><?= date('d M Y',strtotime($t['updated_at'])) ?></td>
            <td class="d-flex gap-1">
              <a href="/ctisms_v2/view_ticket.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
              <?php if($t['status']!=='Closed'): ?>
              <a href="/ctisms_v2/update_status.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-warning">Update</a>
              <?php endif; ?>
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
