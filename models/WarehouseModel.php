<?php
/**
 * Модель складів
 */

class WarehouseModel extends Model
{
    protected string $table = 'warehouses';

    public function create(string $name): int
    {
        $this->db->query(
            "INSERT INTO warehouses (name, author) VALUES (?, ?)",
            [trim($name), $this->authorStamp()]
        );
        return $this->db->lastInsertId();
    }

    public function update(int $id, string $name): void
    {
        $this->db->query(
            "UPDATE warehouses SET name = ?, author = ? WHERE id = ?",
            [trim($name), $this->authorStamp(), $id]
        );
    }

    public function isUsed(int $id): bool
    {
        $result = $this->db->query(
            "SELECT COUNT(*) as cnt FROM movements 
             WHERE warehouse_from_id = ? OR warehouse_to_id = ?",
            [$id, $id]
        )->fetch();
        return $result['cnt'] > 0;
    }

    /**
     * Отримати всі ID складів, що використовуються в русі
     */
    public function getUsedIds(): array
    {
        $rows = $this->db->query(
            "SELECT DISTINCT warehouse_from_id AS id FROM movements WHERE warehouse_from_id IS NOT NULL
             UNION
             SELECT DISTINCT warehouse_to_id AS id FROM movements WHERE warehouse_to_id IS NOT NULL"
        )->fetchAll();
        return array_column($rows, 'id');
    }

    /**
     * Знайти або створити склад за назвою
     */
    public function findOrCreate(string $name): int
    {
        $name = trim($name);
        $row = $this->db->query(
            "SELECT id FROM warehouses WHERE LOWER(name) = LOWER(?)",
            [$name]
        )->fetch();
        if ($row) return (int)$row['id'];
        return $this->create($name);
    }
}
