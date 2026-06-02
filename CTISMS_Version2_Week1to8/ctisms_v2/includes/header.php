<?php
// includes/header.php — Week 7: improved Bootstrap UI
$pageTitle = $pageTitle ?? 'CTISMS v2';

// Notification count
$notifCount = 0;
if (isLoggedIn()) {
    $db = getDB();
    $n  = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0");
    $n->execute([userId()]);
    $notifCount = (int)$n->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> — CTISMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<link href="/ctisms_v2/assets/css/style.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark" style="background-color:#1a237e;">
  <div class="container">
    <a class="navbar-brand fw-bold" href="/ctisms_v2/">
      <i class="bi bi-headset me-2"></i>CTISMS
      <span class="badge bg-warning text-dark ms-2" style="font-size:10px">v2 Beta</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-1">
      <?php if (isLoggedIn()): ?>
        <li class="nav-item">
          <a class="nav-link" href="<?= dashboardUrl() ?>">
            <i class="bi bi-speedometer2 me-1"></i>Dashboard
          </a>
        </li>
        <?php if (userRole() === 'customer'): ?>
        <li class="nav-item">
          <a class="nav-link" href="/ctisms_v2/create_ticket.php">
            <i class="bi bi-plus-circle me-1"></i>New Ticket
          </a>
        </li>
        <?php endif; ?>
        <li class="nav-item position-relative">
          <a class="nav-link" href="/ctisms_v2/notifications.php">
            <i class="bi bi-bell me-1"></i>
            <?php if ($notifCount > 0): ?>
            <span class="badge bg-danger rounded-pill" style="font-size:10px"><?= $notifCount ?></span>
            <?php endif; ?>
          </a>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
            <i class="bi bi-person-circle me-1"></i><?= e(userName()) ?>
            <span class="badge bg-secondary ms-1" style="font-size:10px"><?= ucfirst(userRole()) ?></span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item text-danger" href="/ctisms_v2/logout.php">
              <i class="bi bi-box-arrow-right me-2"></i>Logout
            </a></li>
          </ul>
        </li>
      <?php else: ?>
        <li class="nav-item"><a class="nav-link" href="/ctisms_v2/login.php">Login</a></li>
        <li class="nav-item"><a class="nav-link" href="/ctisms_v2/register.php">Register</a></li>
      <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<div class="container py-4">
<?php showFlash(); ?>
