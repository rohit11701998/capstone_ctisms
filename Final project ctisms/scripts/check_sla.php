<?php
/**
 * CTISMS — SLA Breach Checker (Cron Script)
 *
 * Run every 15 minutes via cron:
 *   * /15 * * * * php /path/to/ctisms/scripts/check_sla.php >> /var/log/ctisms_sla.log 2>&1
 *
 * Or manually:
 *   php scripts/check_sla.php
 */

define('RUNNING_FROM_CLI', php_sapi_name() === 'cli');

// Load app — adjust path if script location changes
require_once __DIR__ . '/../app/bootstrap.php';

$start   = microtime(true);
$db      = Database::getInstance();
$notif   = new NotificationModel();
$log     = new ActivityLogModel();

echo "[" . date('Y-m-d H:i:s') . "] SLA checker starting...\n";

// ── Step 1: Mark newly breached tickets ──────────────────────────
$breachStmt = $db->query("
    UPDATE tickets
    SET sla_breached = 1
    WHERE sla_deadline < NOW()
      AND sla_breached = 0
      AND status NOT IN ('completed','closed')
");
$newBreaches = $breachStmt->rowCount();
echo "[INFO] Newly breached tickets: $newBreaches\n";

// ── Step 2: Notify customers + technicians for new breaches ─────
if ($newBreaches > 0) {
    $breachedTickets = $db->fetchAll("
        SELECT t.id, t.ticket_number, t.title, t.customer_id, t.technician_id,
               cu.name AS customer_name, cu.email AS customer_email,
               te.name AS tech_name, te.email AS tech_email
        FROM tickets t
        JOIN users cu ON cu.id = t.customer_id
        LEFT JOIN users te ON te.id = t.technician_id
        WHERE t.sla_breached = 1
          AND t.status NOT IN ('completed','closed')
          AND t.id NOT IN (
              SELECT DISTINCT ticket_id FROM notifications
              WHERE type = 'sla_warning' AND ticket_id IS NOT NULL
          )
    ");

    foreach ($breachedTickets as $ticket) {
        // Notify customer
        $notif->create(
            $ticket['customer_id'],
            'sla_warning',
            'SLA Breached — ' . $ticket['ticket_number'],
            "Your ticket \"{$ticket['title']}\" has exceeded the agreed response time. We apologise for the delay.",
            $ticket['id']
        );

        // Notify technician if assigned
        if ($ticket['technician_id']) {
            $notif->create(
                $ticket['technician_id'],
                'sla_warning',
                'SLA Breached — ' . $ticket['ticket_number'],
                "Ticket {$ticket['ticket_number']} has breached SLA: \"{$ticket['title']}\"",
                $ticket['id']
            );
        }

        // Notify all admins
        $admins = $db->fetchAll("SELECT id FROM users WHERE role='admin' AND is_active=1");
        foreach ($admins as $admin) {
            $notif->create(
                $admin['id'],
                'sla_warning',
                'SLA Breach — ' . $ticket['ticket_number'],
                "Ticket {$ticket['ticket_number']} has breached SLA. Technician: " . ($ticket['tech_name'] ?? 'Unassigned'),
                $ticket['id']
            );
        }

        $log->log('sla_breached', "SLA breached for ticket {$ticket['ticket_number']}", null, $ticket['id']);
        echo "[BREACH] Ticket {$ticket['ticket_number']} — notifications sent.\n";
    }
}

// ── Step 3: Warn about tickets nearing SLA (within 1 hour) ──────
$warnTickets = $db->fetchAll("
    SELECT t.id, t.ticket_number, t.title, t.customer_id, t.technician_id,
           t.sla_deadline,
           TIMESTAMPDIFF(MINUTE, NOW(), t.sla_deadline) AS minutes_left
    FROM tickets t
    WHERE t.sla_deadline BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 1 HOUR)
      AND t.sla_breached = 0
      AND t.status NOT IN ('completed','closed')
");

foreach ($warnTickets as $wt) {
    echo "[WARN]  Ticket {$wt['ticket_number']} — {$wt['minutes_left']} minutes to SLA breach.\n";
    // Optionally add more targeted warnings here
}

$elapsed = round(microtime(true) - $start, 3);
echo "[" . date('Y-m-d H:i:s') . "] SLA checker complete in {$elapsed}s. "
   . "Breaches marked: $newBreaches, Near-breach warnings: " . count($warnTickets) . "\n";
