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
     * Створити рухи для запису журналу за нормами
     * Логіка: списуємо З складу (warehouseId), відносимо НА target (якщо вказано)
     */
    private function createMovementsForLog_old(int $logId, int $warehouseId, int $resourceTypeId, string $date, float $reading, float $prevReading, float $delta, string $logNote, string $prevDate, MovementModel $movementModel): int
    {
        $rates = $this->getRates($warehouseId, $resourceTypeId);
        $type = $this->getTypeById($resourceTypeId);
        $unit = $type['unit'] ?? '';
        $fmt = $type['format'] ?? 'dec2';
        $created = 0;

        foreach ($rates as $rate) {
            $totalQty = round($delta * (float)$rate['rate'], 2);
            if ($totalQty <= 0) continue;

            $rateStr = rtrim(rtrim(number_format((float)$rate['rate'], 6, '.', ''), '0'), '.');
            $spread = !empty($rate['spread_by_day']) && $prevDate;

            if ($spread) {
                // Рознести по днях: і ресурс, і матеріал
                $created += $this->createSpreadMovements(
                    $logId,
                    $warehouseId,
                    $rate,
                    $prevReading,
                    $delta,
                    $totalQty,
                    $unit,
                    $fmt,
                    $rateStr,
                    $logNote,
                    $prevDate,
                    $date,
                    $movementModel
                );
            } else {
                // Один рух з повною дельтою
                $noteBase = 'Розр. (' . $this->formatDelta($delta, $fmt) . ' ' . $unit . ', Нр ' . $rateStr . ')';
                if ($logNote !== '') {
                    $noteBase .= ', ' . $logNote;
                }

                $movementModel->createFromResource([
                    'movement_date' => $date,
                    'warehouse_from_id' => $warehouseId,
                    'warehouse_to_id' => $rate['source_warehouse_id'] ?: null,
                    'material_id' => $rate['material_id'],
                    'quantity' => $totalQty,
                    'note' => $noteBase,
                    'resource_log_id' => $logId,
                    'resource_value' => $reading,   // поточний показник ресурсу
                    'resource_delta' => $delta,
                    'resource_rate' => (float)$rate['rate'],
                ]);
                $created++;
            }
        }

        return $created;
    }

    private function createMovementsForLog(int $logId, int $warehouseId, int $resourceTypeId, string $date, float $reading, float $prevReading, float $delta, string $logNote, string $prevDate, float $correctionPct, MovementModel $movementModel): int
    {

        $rates = $this->getRates($warehouseId, $resourceTypeId);
        $type = $this->getTypeById($resourceTypeId);
        $unit = $type['unit'] ?? '';
        $fmt = $type['format'] ?? 'dec2';
        $correctionMul = 1 + $correctionPct / 100;
        $created = 0;
        // Формат поправки для примітки
        $corrStr = '';
        if ($correctionPct != 0) {
            $corrStr = ',' . ($correctionPct > 0 ? '+' : '') . rtrim(rtrim(number_format($correctionPct, 2, '.', ''), '0'), '.') . '%';
        }
        foreach ($rates as $rate) {
            $baseQty = round($delta * (float)$rate['rate'], 2);
            $totalQty = round($baseQty * $correctionMul, 2);
            if ($totalQty <= 0) continue;
            $rateStr = rtrim(rtrim(number_format((float)$rate['rate'], 6, '.', ''), '0'), '.');
            $spread = !empty($rate['spread_by_day']) && $prevDate;
            if ($spread) {
                $created += $this->createSpreadMovements(
                    $logId, $warehouseId, $rate, $prevReading,
                    $delta, $totalQty, $unit, $fmt, $rateStr,
                    $logNote, $corrStr, $correctionPct,
                    $prevDate, $date, $movementModel
                );
            } else {
                $noteBase = 'Розр. (' . $this->formatDelta($delta, $fmt) . ' ' . $unit . ', Нр ' . $rateStr . $corrStr . ')';
                if ($logNote !== '') {
                    $noteBase .= ', ' . $logNote;
                }

                $movementModel->createFromResource([
                    'movement_date' => $date,
                    'warehouse_from_id' => $warehouseId,
                    'warehouse_to_id' => $rate['source_warehouse_id'] ?: null,
                    'material_id' => $rate['material_id'],
                    'quantity' => $totalQty,
                    'note' => $noteBase,
                    'resource_log_id' => $logId,
                    'resource_value' => $reading,
                    'resource_delta' => $delta,
                    'resource_rate' => (float)$rate['rate'],
                    'resource_correction' => $correctionPct,
                ]);
                $created++;
            }
        }

        return $created;
    }

    /**
     * Рознести ресурс і кількість матеріалу по днях рівномірно.
     * Ресурс розподіляється по формату ресурсу, останній день отримує різницю.
     * Кількість матеріалу рахується від денної дельти, а різниця від заокруглень
     * теж додається в останній день.
     */
    private function createSpreadMovements(
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
    ): int
    {
        // Список днів: від наступного дня після попереднього показника до поточної дати включно
        $days = [];
        $d = new \DateTime($fromDate);
        $end = new \DateTime($toDate);
        $d->modify('+1 day');
        while ($d <= $end) {
            $days[] = $d->format('Y-m-d');
            $d->modify('+1 day');
        }
        if (empty($days)) {
            $days = [$toDate];
        }

        $numDays = count($days);

        // 1) Розбиваємо РЕСУРС по днях
        if ($fmt === 'hm') {
            // 10 хвилин = 1/6 години
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

        // 2) Кількість матеріалу: від денної дельти, з корекцією на останній день
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
            if ($logNote !== '') {
                $note .= ', ' . $logNote;
            }
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

    private function formatDelta(float $delta, string $fmt): string
    {
        if ($fmt === 'hm') {
            $h = (int)floor($delta);
            $m = (int)round(($delta - $h) * 60);
            return $h . ':' . str_pad($m, 2, '0', STR_PAD_LEFT);
        } elseif ($fmt === 'int') {
            return (string)(int)$delta;
        }
        return number_format($delta, 2, '.', '');
    }

    /**
     * Журнал показників з фільтрами
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
            "SELECT rl.*, w.name AS warehouse_name, rt.name AS type_name, rt.unit, rt.format
             FROM resource_logs rl
             JOIN warehouses w ON rl.warehouse_id = w.id
             JOIN resource_types rt ON rl.resource_type_id = rt.id
             {$whereClause}
             ORDER BY rl.log_date DESC, rl.id DESC",
            $params
        )->fetchAll();
    }

    public function deleteLog(int $id): void
    {
        $this->db->query("DELETE FROM movements WHERE resource_log_id = ?", [$id]);
        $this->db->query("DELETE FROM resource_logs WHERE id = ?", [$id]);
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

    /**
     * Оновити показник: перерахувати і каскадно оновити наступні
     */
    public function updateReading(int $logId, string $date, float $reading, string $note, float $correctionPct, MovementModel $movementModel): array
    {

        $this->setCurrentUser();
        $log = $this->getLogById($logId);
        if (!$log) {
            return ['success' => false, 'error' => 'Запис не знайдено'];
        }

        // Оновити дані запису
        $this->db->query(
            "UPDATE resource_logs SET log_date = ?, reading = ?, note = ?, correction_pct = ?, author = ? WHERE id = ?",
            [$date, $reading, trim($note), $correctionPct, $this->authorStamp(), $logId]
        );

        // Перепровести цей запис і всі наступні
        return $this->recalculateChain(
            (int)$log['warehouse_id'],
            (int)$log['resource_type_id'],
            $logId,
            $movementModel
        );
    }

    /**
     * Перепровести запис logId та всі наступні записи для складу+ресурсу.
     * 
     * Алгоритм:
     * 1. Знайти попередній запис перед logId → визначити prevReading
     * 2. Перепровести logId: оновити prev_reading, delta, перестворити рухи
     * 3. Знайти всі наступні записи (за датою/id) → перепровести кожен по черзі
     */
    private function recalculateChain(int $warehouseId, int $resourceTypeId, int $startLogId, MovementModel $movementModel): array
    {

        // Знайти запис що ми вставили/оновили
        $startLog = $this->db->query(
            "SELECT * FROM resource_logs WHERE id = ?", [$startLogId]
        )->fetch();
        if (!$startLog) {
            return ['success' => false, 'error' => 'Запис не знайдено'];
        }

        // Знайти ВСІ записи для цього складу+ресурсу, починаючи від startLog і далі (за датою, потім за id)
        // Але спочатку потрібен попередній перед startLog
        $prev = $this->db->query(
            "SELECT * FROM resource_logs
             WHERE warehouse_id = ? AND resource_type_id = ?
               AND (log_date < ? OR (log_date = ? AND id < ?))
             ORDER BY log_date DESC, id DESC LIMIT 1",
            [$warehouseId, $resourceTypeId, $startLog['log_date'], $startLog['log_date'], $startLogId]
        )->fetch();

        // Знайти startLog + всі пізніші
        $chain = $this->db->query(
            "SELECT * FROM resource_logs
             WHERE warehouse_id = ? AND resource_type_id = ?
               AND (log_date > ? OR (log_date = ? AND id >= ?))
             ORDER BY log_date ASC, id ASC",
            [$warehouseId, $resourceTypeId, $startLog['log_date'], $startLog['log_date'], $startLogId]
        )->fetchAll();

        // Додамо ще записи пізніші за дату startLog, щоб покрити всі
        // (chain вже включає startLog та все після нього)

        $totalMovements = 0;
        $totalRecalculated = 0;
        $firstDelta = 0;
        $firstIsFirst = false;

        foreach ($chain as $i => $log) {
            $logId = (int)$log['id'];
            $reading = (float)$log['reading'];
            $logNote = $log['note'] ?? '';
            $correctionPct = (float)($log['correction_pct'] ?? 0);

            // Попередній показник
            if ($i === 0) {
                $isFirst = !$prev;
                $prevReading = $prev ? (float)$prev['reading'] : 0;
                $prevDate = $prev ? $prev['log_date'] : $log['log_date'];
            } else {
                $isFirst = false;
                $prevReading = (float)$chain[$i - 1]['reading'];
                $prevDate = $chain[$i - 1]['log_date'];
            }

            $delta = $isFirst ? 0 : ($reading - $prevReading);

            // Валідація: показник не може бути меншим за попередній (крім першого)
            // delta == 0 допускається (склад не працював)
            if (!$isFirst && $delta < 0) {
                if ($logId === $startLogId) {
                    return ['success' => false, 'error' => "Показник ({$reading}) не може бути меншим за попередній ({$prevReading})"];
                }
                continue;
            }

            // Оновити prev_reading, delta
            $this->db->query(
                "UPDATE resource_logs SET prev_reading = ?, delta = ?, author = ? WHERE id = ?",
                [$isFirst ? null : $prevReading, $isFirst ? null : $delta, $this->authorStamp(), $logId]
            );

            // Видалити старі рухи
            $this->db->query("DELETE FROM movements WHERE resource_log_id = ?", [$logId]);

            // Створити нові (якщо не перший)
            if (!$isFirst && $delta > 0) {

                $created = $this->createMovementsForLog(
                    $logId, $warehouseId, $resourceTypeId,
                    $log['log_date'], $reading, $prevReading, $delta,
                    trim($logNote), $prevDate, $correctionPct, $movementModel
                );

                $totalMovements += $created;
            }

            if ($logId === $startLogId) {
                $firstDelta = $delta;
                $firstIsFirst = $isFirst;
            }
            $totalRecalculated++;
        }

        $result = [
            'success' => true,
            'log_id' => $startLogId,
            'delta' => $firstDelta,
            'movements_created' => $totalMovements,
        ];

        if ($firstIsFirst) {
            $result['is_first'] = true;
        }

        if ($totalRecalculated > 1) {
            $result['recalculated'] = $totalRecalculated - 1; // скільки пізніших перепроведено
        }

        return $result;
    }

    /**
     * Видалити запис журналу і перепровести наступні
     */
    public function deleteLogAndRecalculate(int $id, MovementModel $movementModel): void
    {
        $log = $this->db->query("SELECT * FROM resource_logs WHERE id = ?", [$id])->fetch();
        if (!$log) return;

        $warehouseId = (int)$log['warehouse_id'];
        $resourceTypeId = (int)$log['resource_type_id'];

        // Видалити рухи і сам запис
        $this->db->query("DELETE FROM movements WHERE resource_log_id = ?", [$id]);
        $this->db->query("DELETE FROM resource_logs WHERE id = ?", [$id]);

        // Знайти наступний запис після видаленого
        $next = $this->db->query(
            "SELECT id FROM resource_logs
             WHERE warehouse_id = ? AND resource_type_id = ?
               AND (log_date > ? OR (log_date = ? AND id > ?))
             ORDER BY log_date ASC, id ASC LIMIT 1",
            [$warehouseId, $resourceTypeId, $log['log_date'], $log['log_date'], $id]
        )->fetch();

        // Якщо є наступний — перепровести ланцюжок починаючи з нього
        if ($next) {
            $this->recalculateChain($warehouseId, $resourceTypeId, (int)$next['id'], $movementModel);
        }
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

}
