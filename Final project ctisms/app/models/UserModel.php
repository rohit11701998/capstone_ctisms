<?php
/**
 * User Model
 */

class UserModel extends Model
{
    protected string $table = 'users';

    public function findByEmail(string $email): array|false
    {
        return $this->db->fetchOne(
            "SELECT * FROM users WHERE email = ? AND is_active = 1",
            [$email]
        );
    }

    public function create(array $data): int|false
    {
        $this->db->query(
            "INSERT INTO users (name, email, password, role, phone, department) VALUES (?, ?, ?, ?, ?, ?)",
            [
                $data['name'],
                $data['email'],
                password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]),
                $data['role'] ?? 'customer',
                $data['phone'] ?? null,
                $data['department'] ?? null,
            ]
        );
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [];
        foreach ($data as $key => $val) {
            $fields[] = "`{$key}` = ?";
            $params[] = $val;
        }
        $params[] = $id;
        $stmt = $this->db->query(
            "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?",
            $params
        );
        return $stmt->rowCount() > 0;
    }

    public function updateLastLogin(int $id): void
    {
        $this->db->query("UPDATE users SET last_login = NOW() WHERE id = ?", [$id]);
    }

    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public function emailExists(string $email, int $excludeId = 0): bool
    {
        $count = $this->db->fetchValue(
            "SELECT COUNT(*) FROM users WHERE email = ? AND id != ?",
            [$email, $excludeId]
        );
        return (int)$count > 0;
    }

    public function getTechnicians(): array
    {
        return $this->db->fetchAll(
            "SELECT id, name, email, department FROM users WHERE role = 'technician' AND is_active = 1 ORDER BY name"
        );
    }

    public function getAllWithStats(): array
    {
        return $this->db->fetchAll("
            SELECT u.*,
                COUNT(DISTINCT t.id) AS total_tickets,
                SUM(CASE WHEN t.status NOT IN ('completed','closed') AND u.role='technician' THEN 1 ELSE 0 END) AS open_tickets
            FROM users u
            LEFT JOIN tickets t ON (u.role = 'customer' AND t.customer_id = u.id)
                               OR  (u.role = 'technician' AND t.technician_id = u.id)
            GROUP BY u.id
            ORDER BY u.created_at DESC
        ");
    }

    public function getTechnicianPerformance(): array
    {
        return $this->db->fetchAll("
            SELECT u.id, u.name,
                COUNT(t.id)                                                        AS total_assigned,
                SUM(CASE WHEN t.status IN ('completed','closed') THEN 1 ELSE 0 END) AS resolved,
                SUM(CASE WHEN t.status NOT IN ('completed','closed') THEN 1 ELSE 0 END) AS open,
                ROUND(AVG(CASE WHEN t.closed_at IS NOT NULL
                    THEN TIMESTAMPDIFF(HOUR, t.created_at, t.closed_at) END), 1)   AS avg_resolution_hours
            FROM users u
            LEFT JOIN tickets t ON t.technician_id = u.id
            WHERE u.role = 'technician'
            GROUP BY u.id
            ORDER BY resolved DESC
        ");
    }
}
