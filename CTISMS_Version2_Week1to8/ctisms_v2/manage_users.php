<?php
// manage_users.php — admin delete user handler
require_once 'config/db.php';
require_once 'includes/auth.php';
requireRole('admin');

$db = getDB();

if (isset($_GET['delete'])) {
    $uid = (int)$_GET['delete'];
    if ($uid === userId()) {
        setFlash('error', 'You cannot delete your own account.');
    } else {
        $db->prepare("DELETE FROM users WHERE id = ?")->execute([$uid]);
        setFlash('success', 'User deleted.');
    }
}

header('Location: /ctisms_v2/admin_dashboard.php');
exit;
