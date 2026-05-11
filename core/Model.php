<?php
/**
 * Базова модель
 */

abstract class Model
{
    protected Database $db;
    protected string $table;
    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Поточний автор зміни: "2026.21.01, Сергій Слободян"
     */
    protected function authorStamp(): string
    {
        $name = '';
        if (defined('NC_DISPLAY_NAME') && NC_DISPLAY_NAME !== '') {
            $name = NC_DISPLAY_NAME;
        } elseif (defined('NC_USER') && NC_USER !== '') {
            $name = NC_USER;
        } elseif (!empty($_SESSION['nc_display_name'])) {
            $name = $_SESSION['nc_display_name'];
        } elseif (!empty($_SESSION['nc_user'])) {
            $name = $_SESSION['nc_user'];
        } else {
            $name = 'ip '.$_SERVER['REMOTE_ADDR'];
        }
        return date('Y.m.d') . ', ' . $name;
    }

    /**
     * Отримати всі записи
     */
    public function getAll(string $orderBy = 'id ASC'): array
    {
        return $this->db->query(
            "SELECT * FROM {$this->table} ORDER BY {$orderBy}"
        )->fetchAll();
    }

    /**
     * Отримати запис за ID
     */
    public function getById(int $id): ?array
    {
        $result = $this->db->query(
            "SELECT * FROM {$this->table} WHERE id = ?",
            [$id]
        )->fetch();

        return $result ?: null;
    }

    /**
     * Видалити запис
     */
    public function delete(int $id): bool
    {
        try {
            $this->db->query(
                "DELETE FROM {$this->table} WHERE id = ?",
                [$id]
            );
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Підрахунок записів
     */
    public function count(): int
    {
        return (int) $this->db->query(
            "SELECT COUNT(*) as cnt FROM {$this->table}"
        )->fetch()['cnt'];
    }
}
