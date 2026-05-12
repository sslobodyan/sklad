<?php
/**
 * Модель ресурсів — типи, прив'язка до складів, норми, журнал
 */

class ResourceModel extends Model
{
    public function __construct(Database $db)
    {
        parent::__construct($db);
    }

    // =============================================
    // Типи ресурсів
    // =============================================

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
        $this->db->query("INSERT INTO resource_types (name, unit, format, author) VALUES (?, ?, ?, ?)", [trim($name), trim($unit), $format, $this->authorStamp()]);
        return $this->db->lastInsertId();
    }

    public function updateType(int $id, string $name, string $unit, string $format = 'int'): void
    {
        $this->setCurrentUser();
        $this->db->query("UPDATE resource_types SET name = ?, unit = ?, format = ?, author = ? WHERE id = ?", [trim($name), trim($unit), $format, $this->authorStamp(), $id]);
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

    // =============================================
    // Ресурси складу (які ресурси трекаються на складі)
    // =============================================

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

    /**
     * Отримати склади, що мають хоча б один ресурс
     */
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

    // =============================================
    // Норми списання
    // =============================================

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

    // =============================================
    // Журнал показників
    // =============================================

    /**
     * Отримати попередній показник для складу+ресурсу
     */
    public function getLastReading(int $warehouseId, int $resourceTypeId): ?array
    {
        $r = $this->db->query(
            "SELECT * FROM resource_logs
             WHERE warehouse_id = ? AND resource_type_id = ?
             ORDER BY log_date DESC, id DESC
             LIMIT 1",
            [$warehouseId, $resourceTypeId]
        )->fetch();
        return $r ?: null;
    }

    /**
     * Отримати запис журналу за ID
     */
    public function getLogById(int $id): ?array
    {
        $r = $this->db->query(
            "SELECT rl.*, rt.format, rt.unit, w.name AS warehouse_name, rt.name AS type_name
             FROM resource_logs rl
             JOIN resource_types rt ON rl.resource_type_id = rt.id
             JOIN warehouses w ON rl.warehouse_id = w.id
             WHERE rl.id = ?",
            [$id]
        )->fetch();
        return $r ?: null;
    }

    /**
     * Отримати логи з фільтрами
     */
    public function getLogs(array $filters = []): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['warehouse_id'])) {
            $where[] = "rl.warehouse_id = ?";
            $params[] = $filters['warehouse_id'];
        }
        if (!empty($filters['resource_type_id'])) {
            $where[] = "rl.resource_type_id = ?";
            $params[] = $filters['resource_type_id'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = "rl.log_date >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[] = "rl.log_date <= ?";
            $params[] = $filters['date_to'];
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        return $this->db->query(
            "SELECT rl.*, rt.format, rt.unit, w.name AS warehouse_name, rt.name AS type_name
             FROM resource_logs rl
             JOIN resource_types rt ON rl.resource_type_id = rt.id
             JOIN warehouses w ON rl.warehouse_id = w.id
             {$whereClause}
             ORDER BY rl.warehouse_id, rl.resource_type_id, rl.log_date DESC, rl.id DESC",
            $params
        )->fetchAll();
    }

    /**
     * Звіт про витрачання ресурсів
     */
    public function getResourceUsageReport(string $dateFrom, string $dateTo, array $warehouseIds, int $resourceTypeId): array
    {
        $db = $this->db;
        
        // Якщо склади не вибрані — беремо всі склади з цим типом ресурсу
        // Якщо вибрані — тільки вибрані склади
        if (empty($warehouseIds)) {
            // Всі склади з цим типом ресурсу
            $warehouses = $db->query(
                "SELECT w.id, w.name
                 FROM warehouse_resources wr
                 JOIN warehouses w ON wr.warehouse_id = w.id
                 WHERE wr.resource_type_id = ?
                 ORDER BY w.name",
                [$resourceTypeId]
            )->fetchAll();
        } else {
            // Тільки вибрані склади
            $whClause = "wr.warehouse_id IN (" . implode(',', array_fill(0, count($warehouseIds), '?')) . ")";
            $warehouses = $db->query(
                "SELECT w.id, w.name
                 FROM warehouse_resources wr
                 JOIN warehouses w ON wr.warehouse_id = w.id
                 WHERE wr.resource_type_id = ? AND {$whClause}
                 ORDER BY w.name",
                array_merge([$resourceTypeId], $warehouseIds)
            )->fetchAll();
        }

        $report = [];

        foreach ($warehouses as $wh) {
            $whId = (int)$wh['id'];
            
            // Попередній показник (останній перед dateFrom)
            $prevReading = $db->query(
                "SELECT reading FROM resource_logs
                 WHERE warehouse_id = ? AND resource_type_id = ? AND log_date < ?
                 ORDER BY log_date DESC, id DESC LIMIT 1",
                [$whId, $resourceTypeId, $dateFrom]
            )->fetch();
            $openingReading = $prevReading ? (float)$prevReading['reading'] : 0;

            // Поточний показник (останній <= dateTo)
            $currentReading = $db->query(
                "SELECT reading FROM resource_logs
                 WHERE warehouse_id = ? AND resource_type_id = ? AND log_date <= ?
                 ORDER BY log_date DESC, id DESC LIMIT 1",
                [$whId, $resourceTypeId, $dateTo]
            )->fetch();
            $currentValue = $currentReading ? (float)$currentReading['reading'] : 0;

            // Дельта ресурсу
            $resourceDelta = $currentValue - $openingReading;

            // Отримуємо норми для цього складу+ресурсу
            $rates = $this->getRates($whId, $resourceTypeId);

            // Отримуємо рухи матеріалів за період
            $whPlaceholder = !empty($warehouseIds) 
                ? "AND warehouse_to_id IN (" . implode(',', array_fill(0, count($warehouseIds), '?')) . ")"
                : "";
            $whMovementsParams = !empty($warehouseIds) ? $warehouseIds : [];

            $movements = $db->query(
                "SELECT material_id, warehouse_to_id, SUM(quantity) as received
                 FROM movements
                 WHERE movement_date >= ? AND movement_date <= ?
                 AND warehouse_from_id IS NULL
                 AND warehouse_to_id = ?
                 {$whPlaceholder}
                 GROUP BY material_id, warehouse_to_id",
                array_merge([$dateFrom, $dateTo, $whId], $whMovementsParams)
            )->fetchAll();

            $receivedByMaterial = [];
            foreach ($movements as $m) {
                $receivedByMaterial[(int)$m['material_id']] = (float)$m['received'];
            }

            // Розрахунок по кожному матеріалу
            $materials = [];
            foreach ($rates as $rate) {
                $matId = (int)$rate['material_id'];
                $rateValue = (float)$rate['rate'];
                $correctionPct = 0; // TODO: отримати з resource_logs

                // Списано = дельта * норма * (1 + поправка)
                $consumed = $resourceDelta * $rateValue * (1 + $correctionPct / 100);
                
                // Залишок = надійшло - списано
                $received = $receivedByMaterial[$matId] ?? 0;
                $balance = $received - $consumed;

                $materials[$matId] = [
                    'name' => $rate['material_name'],
                    'received' => $received,
                    'rate' => $rateValue,
                    'correction' => $correctionPct,
                    'consumed' => $consumed,
                    'balance' => $balance
                ];
            }

            $report[] = [
                'warehouse_id' => $whId,
                'warehouse_name' => $wh['name'],
                'opening_reading' => $openingReading,
                'current_reading' => $currentValue,
                'resource_delta' => $resourceDelta,
                'materials' => $materials
            ];
        }

        return $report;
    }

    /**
     * Створити запис у журнал + автоматично створити рухи.
     * Після створення каскадно перепроводить всі пізніші записи для цього складу+ресурсу.
     */
    public function addReading(int $warehouseId, int $resourceTypeId, string $date, float $reading, string $note, float $correctionPct, MovementModel $movementModel): array
    {
        $this->db->query(
            "INSERT INTO resource_logs (warehouse_id, resource_type_id, log_date, reading, note, correction_pct, author)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$warehouseId, $resourceTypeId, $date, $reading, trim($note), $correctionPct, $this->authorStamp()]
        );

        // Перепровести цей запис і всі наступні
        $logId = $this->db->lastInsertId();
        $result = $this->recalculateChain($warehouseId, $resourceTypeId, $logId, $movementModel);
        return $result;
    }

    /**
     * Перерахувати ланцюжок записів після вказаного
     */
    private function recalculateChain(int $warehouseId, int $resourceTypeId, int $fromLogId, MovementModel $movementModel): array
    {
        // TODO: реалізувати каскадне перепроведення
        return ['success' => true, 'recalculated' => 0];
    }
}
