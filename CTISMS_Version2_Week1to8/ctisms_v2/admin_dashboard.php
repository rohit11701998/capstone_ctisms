<?php
// admin_dashboard.php — Week 6: admin role, assignment, user management
require_once 'config/db.php';
require_once 'includes/auth.php';
requireRole('admin');

$db = getDB();

// Week 7 — search and filter
$search = trim($_GET['search'] ?? '');
$fStatus = $_GET['status'] ?? '';

$sql    = "SELECT t.*, c.name AS customer_name, u.name AS tech_name
           FROM tickets t
           JOIN users c ON c.id=t.customer_id
           LEFT JOIN users u ON u.id=t.technician_id WHERE 1=1";
$params = [];
if ($search)  { $sql.=" AND (t.title LIKE ? OR c.name LIKE ?)"; $params[]="%$search%"; $params[]="%$search%"; }
if ($fStatus) { $sql.=" AND t.status=?"; $params[]=$fStatus; }
$sql .= " ORDER BY t.created_at DESC";

$stmt = $db->prepare($sql); $stmt->execute($params);
$tickets = $stmt->fetchAll();

$allTickets = $db->query("SELECT * FROM tickets")->fetchAll();
$total    = count($allTickets);
$open     = count(array_filter($allTickets,fn($t)=>$t['status']==='Open'));
$inpg     = count(array_filter($allTickets,fn($t)=>$t['status']==='In Progress'));
$done     = count(array_filter($allTickets,fn($t)=>in_array($t['status'],['Completed','Closed'])));
$unassign = count(array_filter($allTickets,fn($t)=>!$t['technician_id']));

$users = $db->query("SELECT * FROM users ORDER BY role,name")->fetchAll();
$totalUsers = count($users);

function sBadge(string $s): string {
    $map=['Open'=>'badge-open','In Progress'=>'badge-inprog',
          'Awaiting Parts'=>'badge-awaiting','Completed'=>'badge-completed','Closed'=>'badge-closed'];
    return '<span class="'.($map[$s]??'badge-open').'">'.htmlspecialchars($s).'</span>';
}

$pageTitle = 'Admin Dashboard';
include 'includes/header.php';
?>

<div class="mb-4">
  <h4 class="fw-bold mb-0">Admin Dashboard</h4>
  <small class="text-muted">Logged in as <?= e(userName()) ?></small>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
  <?php foreach([['All Tickets',$total,'primary'],['Open',$open,'warning'],
                 ['In Progress',$inpg,'info'],['Resolved',$done,'success'],
                 ['Unassigned',$unassign,'danger'],['Users',$totalUsers,'secondary']] as [$l,$v,$c]): ?>
  <div class="col-6 col-md-2">
    <div class="card text-center p-3">
      <div class="fs-3 fw-bold text-<?= $c ?>"><?= $v ?></div>
      <div class="text-muted small"><?= $l ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-3">
  <li class="nav-item">
    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabTickets">
      <i class="bi bi-ticket-detailed me-1"></i>Tickets
    </button>
  </li>
  <li class="nav-item">
    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabUsers">
      <i class="bi bi-people me-1"></i>Users
    </button>
  </li>
</ul>

<div class="tab-content">
  <!-- Tickets tab -->
  <div class="tab-pane fade show active" id="tabTickets">
    <!-- Search/Filter — Week 7 -->
    <div class="card mb-3">
      <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-center">
          <div class="col-md-5">
            <input type="text" name="search" class="form-control form-control-sm"
                   placeholder="Search by title or customer…" value="<?= e($search) ?>">
          </div>
          <div class="col-md-3">
            <select name="status" class="form-select form-select-sm">
              <option value="">All Statuses</option>
              <?php foreach(['Open','In Progress','Awaiting Parts','Completed','Closed'] as $s): ?>
              <option value="<?= $s ?>" <?= $fStatus===$s?'selected':'' ?>><?= $s ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filter</button>
            <a href="/ctisms_v2/admin_dashboard.php" class="btn btn-outline-secondary btn-sm">Clear</a>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr><th>#</th><th>Title</th><th>Customer</th><th>Priority</th>
                  <th>Status</th><th>Assigned To</th><th>Date</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php if(empty($tickets)): ?>
              <tr><td colspan="8" class="text-center text-muted py-4">No tickets found.</td></tr>
            <?php endif; ?>
            <?php foreach($tickets as $t): ?>
              <tr>
                <td class="text-primary fw-bold">#<?= $t['id'] ?></td>
                <td><?= e($t['title']) ?></td>
                <td class="text-muted small"><?= e($t['customer_name']) ?></td>
                <td><span class="badge bg-<?= ['High'=>'danger','Medium'=>'warning','Low'=>'success'][$t['priority']]??'secondary' ?>"><?= e($t['priority']) ?></span></td>
                <td><?= sBadge($t['status']) ?></td>
                <td class="small"><?= $t['tech_name']?e($t['tech_name']):'<span class="text-danger fw-semibold">Unassigned</span>' ?></td>
                <td class="text-muted small"><?= date('d M Y',strtotime($t['created_at'])) ?></td>
                <td>
                  <div class="d-flex gap-1 flex-wrap">
                    <a href="/ctisms_v2/view_ticket.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
                    <a href="/ctisms_v2/assign_ticket.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-success">Assign</a>
                    <?php if($t['status']!=='Closed'): ?>
                    <a href="/ctisms_v2/update_status.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-warning">Status</a>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Users tab -->
  <div class="tab-pane fade" id="tabUsers">
    <div class="card">
      <div class="card-header bg-white d-flex justify-content-between">
        <span class="fw-semibold">Registered Users</span>
        <a href="/ctisms_v2/register.php" class="btn btn-sm btn-primary">Add User</a>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr><th>#</th><th>Name</th><th>Email</th><th>Role</th><th>Joined</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach($users as $u): ?>
              <tr>
                <td class="text-muted"><?= $u['id'] ?></td>
                <td class="fw-semibold"><?= e($u['name']) ?></td>
                <td class="text-muted"><?= e($u['email']) ?></td>
                <td>
                  <?php $rc=['admin'=>'danger','technician'=>'success','customer'=>'primary'];
                  echo '<span class="badge bg-'.($rc[$u['role']]??'secondary').'">'.ucfirst($u['role']).'</span>'; ?>
                </td>
                <td class="text-muted small"><?= date('d M Y',strtotime($u['created_at'])) ?></td>
                <td>
                  <?php if($u['id']!==userId()): ?>
                  <a href="/ctisms_v2/manage_users.php?delete=<?= $u['id'] ?>"
                     class="btn btn-sm btn-outline-danger"
                     data-confirm="Delete <?= e($u['name']) ?>?">Delete</a>
                  <?php else: ?><span class="text-muted small">(you)</span><?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
