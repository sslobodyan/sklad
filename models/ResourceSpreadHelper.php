<?php
/**
 * ResourceSpreadHelper
 * Рознесення витрати ресурсу по днях
 */
class ResourceSpreadHelper
{
    use ResourceFormatTrait;

    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Рознести ресурс і кількість матеріалу по днях рівномірно.
     */
    public function createSpreadMovements(
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

        // Розбиваємо РЕСУРС по днях
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
}