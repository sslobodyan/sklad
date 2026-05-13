<?php
/**
 * Модель типів ресурсів
 */
class ResourceTypesModel extends Model
{
    public function __construct(Database $db)
    {
        parent::__construct($db);
    }

    public function getTypes(): array
    {
        return $this->db->query("SELECT * FROM resource_types ORDER BY name")->fetchAll();
    }

    public function getTypeById(int $id): ?array
    {
        $r = $this->db->query("SELECT * FROM resource_types WHERE id = ?", [$id])->fetch();
        return $r ?: null;
    }

    public function createType(string $name, string $unit, string $format = 'int'): int
    {
        $this->db->query(
            "INSERT INTO resource_types (name, unit, format, author) VALUES (?, ?, ?, ?)",
            [trim($name), trim($unit), $format, $this->authorStamp()]
        );
        return $this->db->lastInsertId();
    }

    public function updateType(int $id, string $name, string $unit, string $format = 'int'): void
    {
        $this->setCurrentUser();
        $this->db->query(
            "UPDATE resource_types SET name = ?, unit = ?, format = ?, author = ? WHERE id = ?",
            [trim($name), trim($unit), $format, $this->authorStamp(), $id]
        );
    }

    public function deleteType(int $id): bool
    {
        try {
            $this->setCurrentUser();
            $this->db->query("DELETE FROM resource_types WHERE id = ?", [$id]);
            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function isTypeUsed(int $id): bool
    {
        $r = $this->db->query("SELECT COUNT(*) as cnt FROM resource_logs WHERE resource_type_id = ?", [$id])->fetch();
        return $r['cnt'] > 0;
    }
}