<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Dashboard') ?> — <?= APP_NAME ?></title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= APP_URL ?>/css/app.css" rel="stylesheet">
</head>
<body class="ctisms-body">

<!-- Top Navbar -->
<nav class="navbar navbar-expand-lg ctisms-navbar fixed-top">
    <div class="container-fluid px-4">
        <!-- Brand -->
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= Auth::dashboardUrl() ?>">
            <div class="brand-icon">
                <i class="bi bi-headset"></i>
            </div>
            <div class="brand-text">
                <span class="brand-name"><?= APP_NAME ?></span>
                <span class="brand-sub">IT Support</span>
            </div>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <i class="bi bi-list text-white fs-5"></i>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <!-- Search (staff only) -->
            <?php if (Auth::isStaff()): ?>
            <form class="d-flex ms-4 flex-grow-1" style="max-width:400px" action="<?= APP_URL ?>/<?= Auth::role() ?>/tickets.php" method="GET">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input class="form-control border-start-0 ps-0" type="search" name="search"
                           placeholder="Search tickets..."
                           value="<?= e($_GET['search'] ?? '') ?>">
                </div>
            </form>
            <?php endif; ?>

            <div class="ms-auto d-flex align-items-center gap-3">
                <!-- Notifications -->
                <?php
                $notifModel   = new NotificationModel();
                $unreadCount  = Auth::check() ? $notifModel->countUnread(Auth::id()) : 0;
                $notifications = Auth::check() ? $notifModel->getUnread(Auth::id(), 5) : [];
                ?>
                <div class="dropdown">
                    <button class="btn btn-sm position-relative notif-btn" data-bs-toggle="dropdown">
                        <i class="bi bi-bell fs-5 text-white"></i>
                        <?php if ($unreadCount > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?= $unreadCount > 9 ? '9+' : $unreadCount ?>
                        </span>
                        <?php endif; ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end notif-dropdown shadow-lg p-0">
                        <div class="notif-header d-flex justify-content-between align-items-center px-3 py-2">
                            <span class="fw-600">Notifications</span>
                            <?php if ($unreadCount > 0): ?>
                            <a href="<?= APP_URL ?>/notifications/mark-read.php" class="text-primary text-decoration-none small">Mark all read</a>
                            <?php endif; ?>
                        </div>
                        <?php if (empty($notifications)): ?>
                        <div class="p-3 text-center text-muted small">
                            <i class="bi bi-bell-slash d-block fs-4 mb-1"></i>No new notifications
                        </div>
                        <?php else: ?>
                        <?php foreach ($notifications as $n): ?>
                        <a href="<?= APP_URL ?>/notifications/read.php?id=<?= $n['id'] ?>" class="notif-item d-block px-3 py-2 text-decoration-none">
                            <div class="notif-title fw-500"><?= e($n['title']) ?></div>
                            <div class="notif-msg text-muted small"><?= e($n['message']) ?></div>
                            <div class="notif-time text-muted" style="font-size:11px"><?= timeAgo($n['created_at']) ?></div>
                        </a>
                        <?php endforeach; ?>
                        <?php endif; ?>
                        <div class="notif-footer text-center py-2">
                            <a href="<?= APP_URL ?>/notifications/index.php" class="text-primary text-decoration-none small">View all</a>
                        </div>
                    </div>
                </div>

                <!-- User menu -->
                <div class="dropdown">
                    <button class="btn user-menu-btn d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                        <div class="user-avatar">
                            <?= strtoupper(substr(Auth::name() ?? 'U', 0, 1)) ?>
                        </div>
                        <div class="d-none d-md-block text-start">
                            <div class="user-name"><?= e(Auth::name()) ?></div>
                            <div class="user-role"><?= ucfirst(Auth::role()) ?></div>
                        </div>
                        <i class="bi bi-chevron-down text-white-50 small"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li><a class="dropdown-item" href="<?= APP_URL ?>/profile/index.php">
                            <i class="bi bi-person me-2"></i>My Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= APP_URL ?>/auth/logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- Page Wrapper -->
<div class="ctisms-wrapper">
