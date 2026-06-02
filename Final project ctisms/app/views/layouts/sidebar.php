<?php
// Build navigation items based on role
$role = Auth::role();
$currentPath = $_SERVER['PHP_SELF'] ?? '';

function navItem(string $href, string $icon, string $label, string $currentPath, string $badge = ''): string {
    $active = (strpos($currentPath, basename($href, '.php')) !== false) ? 'active' : '';
    $badgeHtml = $badge ? '<span class="nav-badge">'.$badge.'</span>' : '';
    return '<li><a href="'.$href.'" class="sidebar-link '.$active.'">
        <i class="bi bi-'.$icon.'"></i><span>'.$label.'</span>'.$badgeHtml.'</a></li>';
}

$base = APP_URL;
?>
<aside class="ctisms-sidebar" id="sidebar">
    <div class="sidebar-inner">
        <!-- Role indicator -->
        <div class="sidebar-role-badge">
            <i class="bi bi-shield-check"></i>
            <?= ucfirst($role) ?> Portal
        </div>

        <nav class="sidebar-nav">
            <?php if ($role === 'admin'): ?>
            <div class="nav-section-title">Overview</div>
            <ul>
                <?= navItem("$base/admin/dashboard.php",  'speedometer2', 'Dashboard', $currentPath) ?>
                <?= navItem("$base/admin/analytics.php",  'bar-chart',    'Analytics', $currentPath) ?>
            </ul>

            <div class="nav-section-title">Tickets</div>
            <ul>
                <?= navItem("$base/admin/tickets.php",         'ticket-detailed',  'All Tickets',   $currentPath) ?>
                <?= navItem("$base/admin/tickets.php?status=submitted", 'inbox', 'Unassigned', $currentPath) ?>
                <?= navItem("$base/admin/tickets.php?priority=critical", 'exclamation-triangle', 'Critical', $currentPath) ?>
            </ul>

            <div class="nav-section-title">Management</div>
            <ul>
                <?= navItem("$base/admin/users.php",        'people',       'Users',         $currentPath) ?>
                <?= navItem("$base/admin/activity.php",     'clock-history','Activity Log',  $currentPath) ?>
            </ul>

            <?php elseif ($role === 'technician'): ?>
            <div class="nav-section-title">Overview</div>
            <ul>
                <?= navItem("$base/technician/dashboard.php", 'speedometer2', 'Dashboard', $currentPath) ?>
            </ul>

            <div class="nav-section-title">My Tickets</div>
            <ul>
                <?= navItem("$base/technician/tickets.php",                   'ticket-detailed', 'All Assigned',  $currentPath) ?>
                <?= navItem("$base/technician/tickets.php?status=open",       'circle',          'Open',          $currentPath) ?>
                <?= navItem("$base/technician/tickets.php?status=in_progress",'arrow-repeat',    'In Progress',   $currentPath) ?>
                <?= navItem("$base/technician/tickets.php?status=completed",  'check-circle',    'Completed',     $currentPath) ?>
            </ul>

            <?php else: /* customer */ ?>
            <div class="nav-section-title">Support</div>
            <ul>
                <?= navItem("$base/customer/dashboard.php",  'speedometer2',    'Dashboard',    $currentPath) ?>
                <?= navItem("$base/customer/create.php",     'plus-circle',     'New Ticket',   $currentPath) ?>
                <?= navItem("$base/customer/tickets.php",    'ticket-detailed', 'My Tickets',   $currentPath) ?>
            </ul>

            <div class="nav-section-title">Account</div>
            <ul>
                <?= navItem("$base/notifications/index.php", 'bell',  'Notifications', $currentPath) ?>
                <?= navItem("$base/profile/index.php",       'person','My Profile',    $currentPath) ?>
            </ul>
            <?php endif; ?>

            <!-- Common -->
            <ul class="mt-auto">
                <li><a href="<?= $base ?>/auth/logout.php" class="sidebar-link text-danger-soft">
                    <i class="bi bi-box-arrow-right"></i><span>Logout</span></a></li>
            </ul>
        </nav>
    </div>
</aside>

<!-- Main Content Area -->
<main class="ctisms-main" id="mainContent">
    <div class="container-fluid py-4 px-4">
