<?php
/**
 * Admin: Export Tickets to CSV
 * Usage: /admin/export.php  (exports all)
 *        /admin/export.php?status=open&priority=high  (filtered)
 */
require_once __DIR__ . '/../../app/bootstrap.php';
Auth::requireRole('admin');

$db = Database::getInstance();

// Build filter clauses — same parameters as tickets.php
$where  = ['1=1'];
$params = [];

if (!empty($_GET['status']) && array_key_exists($_GET['status'], TicketModel::STATUSES)) {
    $where[]  = 't.status = ?';
    $params[] = $_GET['status'];
}
if (!empty($_GET['priority']) && array_key_exists($_GET['priority'], TicketModel::PRIORITIES)) {
    $where[]  = 't.priority = ?';
    $params[] = $_GET['priority'];
}
if (!empty($_GET['category_id'])) {
    $where[]  = 't.category_id = ?';
    $params[] = (int)$_GET['category_id'];
}
if (!empty($_GET['search'])) {
    $where[]  = '(t.title LIKE ? OR t.ticket_number LIKE ?)';
    $term     = '%' . $_GET['search'] . '%';
    $params[] = $term;
    $params[] = $term;
}

$whereClause = implode(' AND ', $where);

$tickets = $db->fetchAll("
    SELECT
        t.ticket_number        AS 'Ticket #',
        t.title                AS 'Title',
        c.name                 AS 'Category',
        t.priority             AS 'Priority',
        t.status               AS 'Status',
        cu.name                AS 'Customer',
        cu.email               AS 'Customer Email',
        cu.department          AS 'Department',
        COALESCE(te.name,'')   AS 'Assigned Technician',
        t.sla_deadline         AS 'SLA Deadline',
        IF(t.sla_breached,'Yes','No') AS 'SLA Breached',
        COALESCE(t.resolution,'')     AS 'Resolution',
        t.created_at           AS 'Created At',
        t.updated_at           AS 'Updated At',
        COALESCE(t.closed_at,'')      AS 'Closed At',
        CASE WHEN t.closed_at IS NOT NULL
             THEN CONCAT(TIMESTAMPDIFF(HOUR, t.created_at, t.closed_at),' hrs')
             ELSE ''
        END                    AS 'Resolution Time'
    FROM tickets t
    JOIN categories c  ON c.id  = t.category_id
    JOIN users cu      ON cu.id = t.customer_id
    LEFT JOIN users te ON te.id = t.technician_id
    WHERE $whereClause
    ORDER BY t.created_at DESC
", $params);

// Log the export
$log = new ActivityLogModel();
$log->log('export_csv', Auth::name() . ' exported tickets to CSV', Auth::id());

// Stream CSV response
$filename = 'ctisms_tickets_' . date('Y-m-d_His') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// BOM for Excel UTF-8 compatibility
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');

if (!empty($tickets)) {
    // Header row
    fputcsv($out, array_keys($tickets[0]));
    // Data rows
    foreach ($tickets as $row) {
        fputcsv($out, $row);
    }
} else {
    fputcsv($out, ['No tickets found matching the selected filters.']);
}

fclose($out);
exit;
