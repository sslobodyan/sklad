<?php
/**
 * Модель норм списання ресурсів
 */
class ResourceRatesModel extends Model
{
    public function __construct(Database $db)
    {
        parent::__construct($db);
    }

    public function getRates(int $warehouseId, int $resourceTypeId): array
    {
        return $this->db->query(
            "SELECT rr.*, m.name AS material_name, sw.name AS source_warehouse_name
             FROM resource_rates rr
             JOIN materials m ON rr.material_id = m.id
             LEFT JOIN warehouses sw ON rr.source_warehouse_id = sw.id
             WHERE rr.warehouse_id = ? AND rr.resource_type_id = ?
             ORDER BY m.name",
            [$warehouseId, $resourceTypeId]
        )->fetchAll();
    }

    public function saveRate(int $warehouseId, int $resourceTypeId, int $materialId, float $rate, ?int $sourceWarehouseId, bool $spreadByDay = false): void
    {
        $this->setCurrentUser();
        $this->db->query(
            "INSERT INTO resource_rates (warehouse_id, resource_type_id, material_id, rate, source_warehouse_id, spread_by_day, author)
             VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE rate = VALUES(rate), source_warehouse_id = VALUES(source_warehouse_id), spread_by_day = VALUES(spread_by_day), author = VALUES(author)",
            [$warehouseId, $resourceTypeId, $materialId, $rate, $sourceWarehouseId ?: null, $spreadByDay ? 1 : 0, $this->authorStamp()]
        );
    }

    public function deleteRate(int $id): void
    {
        $this->setCurrentUser();
        $this->db->query("DELETE FROM resource_rates WHERE id = ?", [$id]);
    }

    public function getWarehouseResources(int $warehouseId): array
    {
        return $this->db->query(
            "SELECT wr.*, rt.name AS type_name, rt.unit
             FROM warehouse_resources wr
             JOIN resource_types rt ON wr.resource_type_id = rt.id
             WHERE wr.warehouse_id = ?
             ORDER BY rt.name",
            [$warehouseId]
        )->fetchAll();
    }

    public function addWarehouseResource(int $warehouseId, int $resourceTypeId): void
    {
        $this->db->query(
            "INSERT IGNORE INTO warehouse_resources (warehouse_id, resource_type_id, author) VALUES (?, ?, ?)",
            [$warehouseId, $resourceTypeId, $this->authorStamp()]
        );
    }

    public function removeWarehouseResource(int $warehouseId, int $resourceTypeId): void
    {
        $this->setCurrentUser();
        $this->db->query(
            "DELETE FROM warehouse_resources WHERE warehouse_id = ? AND resource_type_id = ?",
            [$warehouseId, $resourceTypeId]
        );
    }

    public function getWarehousesWithResources(): array
    {
        return $this->db->query(
            "SELECT DISTINCT w.id, w.name,
                    GROUP_CONCAT(rt.name ORDER BY rt.name SEPARATOR ', ') AS resource_names
             FROM warehouse_resources wr
             JOIN warehouses w ON wr.warehouse_id = w.id
             JOIN resource_types rt ON wr.resource_type_id = rt.id
             GROUP BY w.id, w.name
             ORDER BY w.name"
        )->fetchAll();
    }

    /**
     * Отримати склади, що мають норми для вказаного типу ресурсу
     */
    public function getWarehousesByResourceType(int $resourceTypeId): array
    {
        return $this->db->query(
            "SELECT DISTINCT w.id, w.name
             FROM resource_rates rr
             JOIN warehouses w ON rr.warehouse_id = w.id
             WHERE rr.resource_type_id = ?
             ORDER BY w.name",
            [$resourceTypeId]
        )->fetchAll();
    }

    /**
     * Отримати матеріали, що використовуються в нормах для вказаного типу ресурсу
     */
    public function getMaterialsByResourceType(int $resourceTypeId): array
    {
        return $this->db->query(
            "SELECT DISTINCT m.id, m.name
             FROM resource_rates rr
             JOIN materials m ON rr.material_id = m.id
             WHERE rr.resource_type_id = ?
             ORDER BY m.name",
            [$resourceTypeId]
        )->fetchAll();
    }

}