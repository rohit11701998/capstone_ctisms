<?php
/**
 * Base Model
 */

abstract class Model
{
    protected Database $db;
    protected string $table;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Find record by primary key
     */
    public function find(int $id): array|false
    {
        return $this->db->fetchOne(
            "SELECT * FROM `{$this->table}` WHERE id = ?",
            [$id]
        );
    }

    /**
     * Get all records
     */
    public function all(string $orderBy = 'id DESC'): array
    {
        return $this->db->fetchAll("SELECT * FROM `{$this->table}` ORDER BY {$orderBy}");
    }

    /**
     * Count all records
     */
    public function count(string $where = '', array $params = []): int
    {
        $sql = "SELECT COUNT(*) FROM `{$this->table}`";
        if ($where) $sql .= " WHERE {$where}";
        return (int) $this->db->fetchValue($sql, $params);
    }

    /**
     * Delete record by ID
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->query(
            "DELETE FROM `{$this->table}` WHERE id = ?",
            [$id]
        );
        return $stmt->rowCount() > 0;
    }
}
