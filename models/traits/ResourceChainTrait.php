<?php
/**
 * Trait ResourceChainTrait
 * Перепроведення ланцюжка записів ресурсів
 */
trait ResourceChainTrait
{
    use ResourceFormatTrait;

    /**
     * Перепровести запис logId та всі наступні записи для складу+ресурсу.
     */
    protected function recalculateChain(int $warehouseId, int $resourceTypeId, int $startLogId, MovementModel $movementModel): array
    {
        $startLog = $this->db->query(
            "SELECT * FROM resource_logs WHERE id = ?", [$startLogId]
        )->fetch();
        if (!$startLog) {
            return ['success' => false, 'error' => 'Запис не знайдено'];
        }

        $prev = $this->db->query(
            "SELECT * FROM resource_logs
             WHERE warehouse_id = ? AND resource_type_id = ?
               AND (log_date < ? OR (log_date = ? AND id < ?))
             ORDER BY log_date DESC, id DESC LIMIT 1",
            [$warehouseId, $resourceTypeId, $startLog['log_date'], $startLog['log_date'], $startLogId]
        )->fetch();

        $chain = $this->db->query(
            "SELECT * FROM resource_logs
             WHERE warehouse_id = ? AND resource_type_id = ?
               AND (log_date > ? OR (log_date = ? AND id >= ?))
             ORDER BY log_date ASC, id ASC",
            [$warehouseId, $resourceTypeId, $startLog['log_date'], $startLog['log_date'], $startLogId]
        )->fetchAll();

        $totalMovements = 0;
        $totalRecalculated = 0;
        $firstDelta = 0;
        $firstIsFirst = false;

        foreach ($chain as $i => $log) {
            $logId = (int)$log['id'];
            $reading = (float)$log['reading'];
            $logNote = $log['note'] ?? '';
            $correctionPct = (float)($log['correction_pct'] ?? 0);

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

            if (!$isFirst && $delta < 0) {
                if ($logId === $startLogId) {
                    return ['success' => false, 'error' => "Показник ({$reading}) не може бути меншим за попередній ({$prevReading})"];
                }
                continue;
            }

            $this->db->query(
                "UPDATE resource_logs SET prev_reading = ?, delta = ?, author = ? WHERE id = ?",
                [$isFirst ? null : $prevReading, $isFirst ? null : $delta, $this->authorStamp(), $logId]
            );

            $this->db->query("DELETE FROM movements WHERE resource_log_id = ?", [$logId]);

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

        if ($firstIsFirst) $result['is_first'] = true;
        if ($totalRecalculated > 1) $result['recalculated'] = $totalRecalculated - 1;

        return $result;
    }

    /**
     * Створити рухи для запису журналу за нормами
     */
    protected function createMovementsForLog(
        int $logId, int $warehouseId, int $resourceTypeId, string $date,
        float $reading, float $prevReading, float $delta, string $logNote,
        string $prevDate, float $correctionPct, MovementModel $movementModel
    ): int {
        // Використовуємо методи, які будуть реалізовані в класі
        $rates = $this->getRatesForChain($warehouseId, $resourceTypeId);
        $type = $this->getTypeForChain($resourceTypeId);
        
        $unit = $type['unit'] ?? '';
        $fmt = $type['format'] ?? 'dec2';
        $correctionMul = 1 + $correctionPct / 100;
        $created = 0;
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
                if ($logNote !== '') $noteBase .= ', ' . $logNote;

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
}