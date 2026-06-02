<?php
/**
 * Comment Model
 */
class CommentModel extends Model
{
    protected string $table = 'comments';

    public function getByTicket(int $ticketId, bool $includeInternal = false): array
    {
        $where = $includeInternal ? '' : 'AND c.is_internal = 0';
        return $this->db->fetchAll("
            SELECT c.*, u.name AS user_name, u.role AS user_role
            FROM comments c
            JOIN users u ON u.id = c.user_id
            WHERE c.ticket_id = ? {$where}
            ORDER BY c.created_at ASC
        ", [$ticketId]);
    }

    public function create(int $ticketId, int $userId, string $body, bool $isInternal = false): int
    {
        $this->db->query(
            "INSERT INTO comments (ticket_id, user_id, body, is_internal) VALUES (?, ?, ?, ?)",
            [$ticketId, $userId, $body, (int)$isInternal]
        );
        return (int) $this->db->lastInsertId();
    }
}

/**
 * Notification Model
 */
class NotificationModel extends Model
{
    protected string $table = 'notifications';

    public function create(int $userId, string $type, string $title, string $message, ?int $ticketId = null): void
    {
        $this->db->query(
            "INSERT INTO notifications (user_id, ticket_id, type, title, message) VALUES (?, ?, ?, ?, ?)",
            [$userId, $ticketId, $type, $title, $message]
        );
    }

    public function getUnread(int $userId, int $limit = 10): array
    {
        return $this->db->fetchAll("
            SELECT * FROM notifications
            WHERE user_id = ? AND is_read = 0
            ORDER BY created_at DESC
            LIMIT ?
        ", [$userId, $limit]);
    }

    public function getAll(int $userId, int $limit = 30): array
    {
        return $this->db->fetchAll("
            SELECT * FROM notifications
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT ?
        ", [$userId, $limit]);
    }

    public function countUnread(int $userId): int
    {
        return (int) $this->db->fetchValue(
            "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0",
            [$userId]
        );
    }

    public function markAllRead(int $userId): void
    {
        $this->db->query("UPDATE notifications SET is_read = 1 WHERE user_id = ?", [$userId]);
    }

    public function markRead(int $id, int $userId): void
    {
        $this->db->query("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?", [$id, $userId]);
    }
}

/**
 * Activity Log Model
 */
class ActivityLogModel extends Model
{
    protected string $table = 'activity_logs';

    public function log(string $action, string $description, ?int $userId = null, ?int $ticketId = null): void
    {
        $this->db->query(
            "INSERT INTO activity_logs (user_id, ticket_id, action, description, ip_address) VALUES (?, ?, ?, ?, ?)",
            [
                $userId ?? Auth::id(),
                $ticketId,
                $action,
                $description,
                $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            ]
        );
    }

    public function getByTicket(int $ticketId): array
    {
        return $this->db->fetchAll("
            SELECT al.*, u.name AS user_name, u.role AS user_role
            FROM activity_logs al
            LEFT JOIN users u ON u.id = al.user_id
            WHERE al.ticket_id = ?
            ORDER BY al.created_at ASC
        ", [$ticketId]);
    }

    public function getRecent(int $limit = 50): array
    {
        return $this->db->fetchAll("
            SELECT al.*, u.name AS user_name, t.ticket_number
            FROM activity_logs al
            LEFT JOIN users u ON u.id = al.user_id
            LEFT JOIN tickets t ON t.id = al.ticket_id
            ORDER BY al.created_at DESC
            LIMIT ?
        ", [$limit]);
    }
}

/**
 * Category Model
 */
class CategoryModel extends Model
{
    protected string $table = 'categories';

    public function getActive(): array
    {
        return $this->db->fetchAll("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");
    }
}

/**
 * Attachment Model
 */
class AttachmentModel extends Model
{
    protected string $table = 'attachments';

    public function getByTicket(int $ticketId): array
    {
        return $this->db->fetchAll("
            SELECT a.*, u.name AS uploader_name
            FROM attachments a
            JOIN users u ON u.id = a.uploaded_by
            WHERE a.ticket_id = ?
            ORDER BY a.created_at DESC
        ", [$ticketId]);
    }

    public function create(int $ticketId, int $userId, array $fileInfo): int
    {
        $this->db->query(
            "INSERT INTO attachments (ticket_id, uploaded_by, filename, original_name, file_size, mime_type)
             VALUES (?, ?, ?, ?, ?, ?)",
            [$ticketId, $userId, $fileInfo['stored_name'], $fileInfo['original_name'],
             $fileInfo['size'], $fileInfo['mime_type']]
        );
        return (int) $this->db->lastInsertId();
    }
}
