<?php
/**
 * Базова модель з підтримкою аудиту
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
     * Встановлює поточного користувача для аудиту в тригерах
     * Викликати перед UPDATE/DELETE операціями
     */
    public function setCurrentUser(): void
    {
        $username = $this->authorStamp();
        $this->db->query("SET @current_user = ?", [$username]);
    }

    /**
     * Поточний автор зміни: "2026.05.11, Сергій Слободян" або "2026.05.11, ip 192.168.1.1"
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
            // Юзер не визначений — пишемо IP
            $name = 'ip ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        }
        return $name;
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
     * Видалити запис (з автоматичним аудитом)
     */
    public function delete(int $id): bool
    {
        try {
            $this->setCurrentUser();
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

    /**
     * Отримати історію змін для запису
     */
    public function getHistory(int $id): array
    {
        $historyTable = $this->table . '_history';
        
        try {
            return $this->db->query(
                "SELECT * FROM {$historyTable} WHERE id = ? ORDER BY changed_at DESC",
                [$id]
            )->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
}
