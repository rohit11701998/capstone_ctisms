<?php
/**
 * Ticket Model
 */

class TicketModel extends Model
{
    protected string $table = 'tickets';

    public const STATUSES = [
        'submitted'      => ['label' => 'Submitted',       'color' => 'secondary'],
        'open'           => ['label' => 'Open',            'color' => 'primary'],
        'in_progress'    => ['label' => 'In Progress',     'color' => 'info'],
        'awaiting_parts' => ['label' => 'Awaiting Parts',  'color' => 'warning'],
        'completed'      => ['label' => 'Completed',       'color' => 'success'],
        'closed'         => ['label' => 'Closed',          'color' => 'dark'],
    ];

    public const PRIORITIES = [
        'low'      => ['label' => 'Low',      'color' => 'secondary'],
        'medium'   => ['label' => 'Medium',   'color' => 'primary'],
        'high'     => ['label' => 'High',     'color' => 'warning'],
        'critical' => ['label' => 'Critical', 'color' => 'danger'],
    ];

    /**
     * Generate next ticket number
     */
    private function generateTicketNumber(): string
    {
        $count = (int) $this->db->fetchValue("SELECT COUNT(*) FROM tickets");
        return 'TKT-' . str_pad($count + 1, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Create a new ticket
     */
    public function create(array $data): int|false
    {
        // Get SLA hours from category
        $category = $this->db->fetchOne(
            "SELECT sla_hours FROM categories WHERE id = ?",
            [$data['category_id']]
        );

        // Adjust SLA for priority
        $slaMultiplier = match($data['priority']) {
            'critical' => 0.25,
            'high'     => 0.5,
            'low'      => 2.0,
            default    => 1.0,
        };

        $slaHours    = ($category['sla_hours'] ?? 24) * $slaMultiplier;
        $slaDeadline = date('Y-m-d H:i:s', time() + ($slaHours * 3600));

        $this->db->query(
            "INSERT INTO tickets (ticket_number, title, description, category_id, priority, status, customer_id, sla_deadline)
             VALUES (?, ?, ?, ?, ?, 'submitted', ?, ?)",
            [
                $this->generateTicketNumber(),
                $data['title'],
                $data['description'],
                $data['category_id'],
                $data['priority'],
                $data['customer_id'],
                $slaDeadline,
            ]
        );
        return (int) $this->db->lastInsertId();
    }

    /**
     * Get full ticket detail with joins
     */
    public function getDetail(int $id): array|false
    {
        return $this->db->fetchOne("
            SELECT t.*,
                   c.name  AS category_name,
                   c.sla_hours,
                   cu.name AS customer_name,
                   cu.email AS customer_email,
                   cu.phone AS customer_phone,
                   te.name AS technician_name,
                   te.email AS technician_email
            FROM tickets t
            JOIN categories c ON c.id = t.category_id
            JOIN users cu      ON cu.id = t.customer_id
            LEFT JOIN users te ON te.id = t.technician_id
            WHERE t.id = ?
        ", [$id]);
    }

    /**
     * Get paginated ticket list with filters
     */
    public function getList(array $filters = [], int $page = 1): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['customer_id'])) {
            $where[] = 't.customer_id = ?';
            $params[] = $filters['customer_id'];
        }
        if (!empty($filters['technician_id'])) {
            $where[] = 't.technician_id = ?';
            $params[] = $filters['technician_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = 't.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['priority'])) {
            $where[] = 't.priority = ?';
            $params[] = $filters['priority'];
        }
        if (!empty($filters['category_id'])) {
            $where[] = 't.category_id = ?';
            $params[] = $filters['category_id'];
        }
        if (!empty($filters['search'])) {
            $where[] = '(t.title LIKE ? OR t.ticket_number LIKE ? OR t.description LIKE ?)';
            $term = '%' . $filters['search'] . '%';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        $whereClause = implode(' AND ', $where);
        $total = (int) $this->db->fetchValue(
            "SELECT COUNT(*) FROM tickets t WHERE {$whereClause}",
            $params
        );

        $offset = ($page - 1) * ITEMS_PER_PAGE;
        $paramsWithLimit = array_merge($params, [ITEMS_PER_PAGE, $offset]);

        $rows = $this->db->fetchAll("
            SELECT t.id, t.ticket_number, t.title, t.status, t.priority,
                   t.created_at, t.updated_at, t.sla_deadline, t.sla_breached,
                   c.name AS category_name,
                   cu.name AS customer_name,
                   te.name AS technician_name
            FROM tickets t
            JOIN categories c  ON c.id  = t.category_id
            JOIN users cu      ON cu.id = t.customer_id
            LEFT JOIN users te ON te.id = t.technician_id
            WHERE {$whereClause}
            ORDER BY t.created_at DESC
            LIMIT ? OFFSET ?
        ", $paramsWithLimit);

        return [
            'data'        => $rows,
            'total'       => $total,
            'per_page'    => ITEMS_PER_PAGE,
            'current_page'=> $page,
            'last_page'   => max(1, (int) ceil($total / ITEMS_PER_PAGE)),
        ];
    }

    /**
     * Assign ticket to technician
     */
    public function assign(int $ticketId, int $technicianId): bool
    {
        $stmt = $this->db->query(
            "UPDATE tickets SET technician_id = ?, status = IF(status='submitted','open',status), updated_at = NOW()
             WHERE id = ?",
            [$technicianId, $ticketId]
        );
        return $stmt->rowCount() > 0;
    }

    /**
     * Update ticket status
     */
    public function updateStatus(int $ticketId, string $status, ?string $resolution = null): bool
    {
        $closedAt = in_array($status, ['completed','closed']) ? date('Y-m-d H:i:s') : null;
        $stmt = $this->db->query(
            "UPDATE tickets SET status = ?, resolution = COALESCE(?, resolution),
             closed_at = COALESCE(?, closed_at), updated_at = NOW()
             WHERE id = ?",
            [$status, $resolution, $closedAt, $ticketId]
        );
        return $stmt->rowCount() > 0;
    }

    /**
     * Update SLA breached flags
     */
    public function checkSlaBreaches(): int
    {
        $stmt = $this->db->query(
            "UPDATE tickets SET sla_breached = 1
             WHERE sla_deadline < NOW()
               AND sla_breached = 0
               AND status NOT IN ('completed','closed')"
        );
        return $stmt->rowCount();
    }

    /**
     * Get dashboard stats
     */
    public function getStats(?int $customerId = null, ?int $technicianId = null): array
    {
        $where  = '1=1';
        $params = [];

        if ($customerId) {
            $where  = 'customer_id = ?';
            $params = [$customerId];
        } elseif ($technicianId) {
            $where  = 'technician_id = ?';
            $params = [$technicianId];
        }

        return $this->db->fetchOne("
            SELECT
                COUNT(*) AS total,
                SUM(status = 'submitted')       AS submitted,
                SUM(status = 'open')            AS open,
                SUM(status = 'in_progress')     AS in_progress,
                SUM(status = 'awaiting_parts')  AS awaiting_parts,
                SUM(status = 'completed')        AS completed,
                SUM(status = 'closed')          AS closed,
                SUM(sla_breached = 1)           AS sla_breached,
                SUM(priority = 'critical')       AS critical
            FROM tickets
            WHERE {$where}
        ", $params) ?: [];
    }

    /**
     * Get ticket counts by status for charts
     */
    public function getStatusChart(): array
    {
        return $this->db->fetchAll("
            SELECT status, COUNT(*) AS cnt FROM tickets GROUP BY status
        ");
    }

    /**
     * Get tickets created per day for past 30 days
     */
    public function getDailyTrend(int $days = 30): array
    {
        return $this->db->fetchAll("
            SELECT DATE(created_at) AS day, COUNT(*) AS cnt
            FROM tickets
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            GROUP BY DATE(created_at)
            ORDER BY day ASC
        ", [$days]);
    }

    /**
     * Get recent tickets
     */
    public function getRecent(int $limit = 10): array
    {
        return $this->db->fetchAll("
            SELECT t.id, t.ticket_number, t.title, t.status, t.priority, t.created_at,
                   cu.name AS customer_name, te.name AS technician_name
            FROM tickets t
            JOIN users cu      ON cu.id = t.customer_id
            LEFT JOIN users te ON te.id = t.technician_id
            ORDER BY t.created_at DESC
            LIMIT ?
        ", [$limit]);
    }
}
