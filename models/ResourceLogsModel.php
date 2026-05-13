<?php
/**
 * Модель журналу показників ресурсів
 */
class ResourceLogsModel extends Model
{
    use ResourceFormatTrait;
    use ResourceChainTrait;

    private ResourceRatesModel $ratesModel;
    private ResourceTypesModel $typesModel;

    public function __construct(Database $db)
    {
        parent::__construct($db);
        $this->ratesModel = new ResourceRatesModel($db);
        $this->typesModel = new ResourceTypesModel($db);
    }

    /**
     * Отримати норми для ланцюжка (необхідно для ResourceChainTrait)
     */
    protected function getRatesForChain(int $warehouseId, int $resourceTypeId): array
    {
        return $this->ratesModel->getRates($warehouseId, $resourceTypeId);
    }

    /**
     * Отримати тип ресурсу для ланцюжка (необхідно для ResourceChainTrait)
     */
    protected function getTypeForChain(int $id): ?array
    {
        return $this->typesModel->getTypeById($id);
    }

    /**
     * Створити рознесення по днях (необхідно для ResourceChainTrait)
     */
    protected function createSpreadMovements(
        int $logId,
        int $warehouseId,
        array $rate,
        float $prevReading,
        float $totalDelta,
        float $totalQty,
        string $unit,
        string $fmt,
        string $rateStr,
        string $logNote,
        string $corrStr,
        float $correctionPct,
        string $fromDate,
        string $toDate,
        MovementModel $movementModel
    ): int {
        $days = [];
        $d = new \DateTime($fromDate);
        $end = new \DateTime($toDate);
        $d->modify('+1 day');
        while ($d <= $end) {
            $days[] = $d->format('Y-m-d');
            $d->modify('+1 day');
        }
        if (empty($days)) $days = [$toDate];

        $numDays = count($days);

        if ($fmt === 'hm') {
            $dailyDelta = round(($totalDelta / $numDays) * 6) / 6;
            $sumPrevDelta = $dailyDelta * ($numDays - 1);
            $lastDelta = round($totalDelta - $sumPrevDelta, 6);
        } elseif ($fmt === 'int') {
            $dailyDelta = round($totalDelta / $numDays);
            $sumPrevDelta = $dailyDelta * ($numDays - 1);
            $lastDelta = $totalDelta - $sumPrevDelta;
        } else {
            $dailyDelta = round($totalDelta / $numDays, 2);
            $sumPrevDelta = round($dailyDelta * ($numDays - 1), 2);
            $lastDelta = round($totalDelta - $sumPrevDelta, 6);
        }

        $created = 0;
        $sumQtyPrev = 0.0;
        $currentValue = $prevReading;

        foreach ($days as $i => $day) {
            $dayDelta = ($i === $numDays - 1) ? $lastDelta : $dailyDelta;
            if ($dayDelta <= 0) continue;

            if ($i === $numDays - 1) {
                $qty = round($totalQty - $sumQtyPrev, 2);
            } else {
                $qty = round($dayDelta * (float)$rate['rate'], 2);
                $sumQtyPrev += $qty;
            }
            if ($qty <= 0) continue;

            $currentValue += $dayDelta;

            $note = 'Розр. (' . $this->formatDelta($dayDelta, $fmt) . ' ' . $unit . ', Нр ' . $rateStr . $corrStr . ')';
            if ($logNote !== '') $note .= ', ' . $logNote;

            $movementModel->createFromResource([
                'movement_date' => $day,
                'warehouse_from_id' => $warehouseId,
                'warehouse_to_id' => $rate['source_warehouse_id'] ?: null,
                'material_id' => $rate['material_id'],
                'quantity' => $qty,
                'note' => $note,
                'resource_log_id' => $logId,
                'resource_value' => $currentValue,
                'resource_delta' => $dayDelta,
                'resource_rate' => (float)$rate['rate'],
                'resource_correction' => $correctionPct,
            ]);
            $created++;
        }
        return $created;
    }

    public function getLastReading(int $warehouseId, int $resourceTypeId): ?array
    {
        $r = $this->db->query(
            "SELECT * FROM resource_logs
             WHERE warehouse_id = ? AND resource_type_id = ?
             ORDER BY log_date DESC, id DESC LIMIT 1",
            [$warehouseId, $resourceTypeId]
        )->fetch();
        return $r ?: null;
    }

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
            "SELECT rl.*, w.name AS warehouse_name, rt.name AS type_name, rt.unit, rt.format
             FROM resource_logs rl
             JOIN warehouses w ON rl.warehouse_id = w.id
             JOIN resource_types rt ON rl.resource_type_id = rt.id
             {$whereClause}
             ORDER BY rl.log_date DESC, rl.id DESC",
            $params
        )->fetchAll();
    }

    public function getLogById(int $id): ?array
    {
        $r = $this->db->query(
            "SELECT rl.*, w.name AS warehouse_name, rt.name AS type_name, rt.unit, rt.format
             FROM resource_logs rl
             JOIN warehouses w ON rl.warehouse_id = w.id
             JOIN resource_types rt ON rl.resource_type_id = rt.id
             WHERE rl.id = ?",
            [$id]
        )->fetch();
        return $r ?: null;
    }

    public function addReading(int $warehouseId, int $resourceTypeId, string $date, float $reading, string $note, float $correctionPct, MovementModel $movementModel): array
    {
        $this->setCurrentUser();
        $this->db->query(
            "INSERT INTO resource_logs (warehouse_id, resource_type_id, log_date, reading, note, correction_pct, author)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$warehouseId, $resourceTypeId, $date, $reading, trim($note), $correctionPct, $this->authorStamp()]
        );

        $logId = $this->db->lastInsertId();
        return $this->recalculateChain($warehouseId, $resourceTypeId, $logId, $movementModel);
    }

    public function updateReading(int $logId, string $date, float $reading, string $note, float $correctionPct, MovementModel $movementModel): array
    {
        $this->setCurrentUser();
        $log = $this->getLogById($logId);
        if (!$log) {
            return ['success' => false, 'error' => 'Запис не знайдено'];
        }

        $this->db->query(
            "UPDATE resource_logs SET log_date = ?, reading = ?, note = ?, correction_pct = ?, author = ? WHERE id = ?",
            [$date, $reading, trim($note), $correctionPct, $this->authorStamp(), $logId]
        );

        return $this->recalculateChain(
            (int)$log['warehouse_id'],
            (int)$log['resource_type_id'],
            $logId,
            $movementModel
        );
    }

    public function deleteLogAndRecalculate(int $id, MovementModel $movementModel): void
    {
        $log = $this->db->query("SELECT * FROM resource_logs WHERE id = ?", [$id])->fetch();
        if (!$log) return;

        $warehouseId = (int)$log['warehouse_id'];
        $resourceTypeId = (int)$log['resource_type_id'];

        $this->db->query("DELETE FROM movements WHERE resource_log_id = ?", [$id]);
        $this->db->query("DELETE FROM resource_logs WHERE id = ?", [$id]);

        $next = $this->db->query(
            "SELECT id FROM resource_logs
             WHERE warehouse_id = ? AND resource_type_id = ?
               AND (log_date > ? OR (log_date = ? AND id > ?))
             ORDER BY log_date ASC, id ASC LIMIT 1",
            [$warehouseId, $resourceTypeId, $log['log_date'], $log['log_date'], $id]
        )->fetch();

        if ($next) {
            $this->recalculateChain($warehouseId, $resourceTypeId, (int)$next['id'], $movementModel);
        }
    }
}