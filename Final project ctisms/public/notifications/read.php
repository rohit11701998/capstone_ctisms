<?php
/**
 * Mark single notification as read and redirect to ticket
 */
require_once __DIR__ . '/../../app/bootstrap.php';
Auth::requireAuth();

$id   = (int)($_GET['id'] ?? 0);
$ajax = !empty($_GET['ajax']);

$notifModel = new NotificationModel();
$notif      = $notifModel->find($id);

if ($notif && $notif['user_id'] == Auth::id()) {
    $notifModel->markRead($id, Auth::id());
    if ($ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }
    if ($notif['ticket_id']) {
        redirect(APP_URL . '/' . Auth::role() . '/view.php?id=' . $notif['ticket_id']);
    }
}

redirect(APP_URL . '/notifications/index.php');
