<?php
/**
 * Модель матеріалів
 */

class MaterialModel extends Model
{
    protected string $table = 'materials';

    public function create(string $name): int
    {
        $this->db->query(
            "INSERT INTO materials (name, author) VALUES (?, ?)",
            [trim($name), $this->authorStamp()]
        );
        return $this->db->lastInsertId();
    }

    public function update(int $id, string $name): void
    {
        $this->db->query(
            "UPDATE materials SET name = ?, author = ? WHERE id = ?",
            [trim($name), $this->authorStamp(), $id]
        );
    }

    public function isUsed(int $id): bool
    {
        $result = $this->db->query(
            "SELECT COUNT(*) as cnt FROM movements WHERE material_id = ?",
            [$id]
        )->fetch();
        return $result['cnt'] > 0;
    }

    /**
     * Отримати всі ID матеріалів, що використовуються в русі
     */
    public function getUsedIds(): array
    {
        $rows = $this->db->query(
            "SELECT DISTINCT material_id AS id FROM movements"
        )->fetchAll();
        return array_column($rows, 'id');
    }

    /**
     * Знайти або створити матеріал за назвою
     */
    public function findOrCreate(string $name): int
    {
        $name = trim($name);
        $row = $this->db->query(
            "SELECT id FROM materials WHERE LOWER(name) = LOWER(?)",
            [$name]
        )->fetch();
        if ($row) return (int)$row['id'];
        return $this->create($name);
    }
}
