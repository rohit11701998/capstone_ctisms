<?php
require_once __DIR__ . '/../../app/bootstrap.php';
Auth::requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/admin/tickets.php');
}

verifyCsrf();

$ticketId     = (int)($_POST['ticket_id']     ?? 0);
$technicianId = (int)($_POST['technician_id'] ?? 0);

if (!$ticketId || !$technicianId) {
    redirectBack('error', 'Invalid assignment data.');
}

$ticketModel = new TicketModel();
$userModel   = new UserModel();

$ticket     = $ticketModel->getDetail($ticketId);
$technician = $userModel->find($technicianId);

if (!$ticket || !$technician || $technician['role'] !== 'technician') {
    redirectBack('error', 'Ticket or technician not found.');
}

$db = Database::getInstance();
$db->beginTransaction();
try {
    $ticketModel->assign($ticketId, $technicianId);

    // Notifications
    $notif = new NotificationModel();
    $notif->create($technicianId, 'ticket_assigned',
        'New Ticket Assigned',
        "You have been assigned ticket {$ticket['ticket_number']}: {$ticket['title']}",
        $ticketId);
    $notif->create($ticket['customer_id'], 'ticket_assigned',
        'Ticket Assigned',
        "Your ticket {$ticket['ticket_number']} has been assigned to {$technician['name']}.",
        $ticketId);

    // Email
    MailService::ticketAssigned($ticket, $technician);

    // Log
    $log = new ActivityLogModel();
    $log->log('ticket_assigned',
        "Admin " . Auth::name() . " assigned {$ticket['ticket_number']} to {$technician['name']}",
        Auth::id(), $ticketId);

    $db->commit();
    redirectTo(APP_URL . '/admin/view.php?id=' . $ticketId, 'success',
        "Ticket assigned to {$technician['name']} successfully.");
} catch (Exception $e) {
    $db->rollBack();
    redirectBack('error', 'Assignment failed. Please try again.');
}
