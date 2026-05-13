<?php
/**
 * Модель звітів по ресурсах
 */
class ResourceReportModel extends Model
{
    use ResourceFormatTrait;

    public function __construct(Database $db)
    {
        parent::__construct($db);
    }

    /**
     * Детальний звіт про витрачання ресурсів
     */
    public function getResourceUsageReportDetailed(
        string $dateFrom,
        string $dateTo,
        array $warehouseIds,
        int $resourceTypeId
    ): array {
        $type = $this->getTypeById($resourceTypeId);
        if (!$type) return [];
        
        $fmt = $type['format'] ?? 'dec2';
        $unit = $type['unit'] ?? '';

        $warehouses = $this->getReportWarehouses($warehouseIds, $resourceTypeId);
        $report = [];

        foreach ($warehouses as $wh) {
            $whId = (int)$wh['id'];
            $rates = $this->getRates($whId, $resourceTypeId);
            if (empty($rates)) continue;

            $logs = $this->getLogsInRange($whId, $resourceTypeId, $dateFrom, $dateTo);
            $openingReading = $this->getOpeningReading($whId, $resourceTypeId, $dateFrom);
            $closingReading = $this->getClosingReading($whId, $resourceTypeId, $dateTo);
            $totalDelta = $this->calculateTotalDelta($logs);

            $materials = $this->buildMaterialsReport($whId, $rates, $logs, $dateFrom, $dateTo, $fmt, $unit);
            if (empty($materials)) continue;

            $report[] = [
                'warehouse_id'    => $whId,
                'warehouse_name'  => $wh['name'],
                'opening_reading' => $openingReading,
                'closing_reading' => $closingReading,
                'total_delta'     => $totalDelta,
                'resource_unit'   => $unit,
                'resource_format' => $fmt,
                'materials'       => $materials,
            ];
        }

        return $report;
    }

    private function getReportWarehouses(array $warehouseIds, int $resourceTypeId): array
    {
        $db = $this->db;
        
        if (empty($warehouseIds)) {
            return $db->query(
                "SELECT w.id, w.name
                 FROM warehouse_resources wr
                 JOIN warehouses w ON wr.warehouse_id = w.id
                 WHERE wr.resource_type_id = ?
                 ORDER BY w.name",
                [$resourceTypeId]
            )->fetchAll();
        }
        
        $ph = implode(',', array_fill(0, count($warehouseIds), '?'));
        return $db->query(
            "SELECT w.id, w.name
             FROM warehouse_resources wr
             JOIN warehouses w ON wr.warehouse_id = w.id
             WHERE wr.resource_type_id = ? AND wr.warehouse_id IN ({$ph})
             ORDER BY w.name",
            array_merge([$resourceTypeId], $warehouseIds)
        )->fetchAll();
    }

    private function getLogsInRange(int $whId, int $rtId, string $dateFrom, string $dateTo): array
    {
        return $this->db->query(
            "SELECT id, log_date, reading, prev_reading, delta, correction_pct, note
             FROM resource_logs
             WHERE warehouse_id = ? AND resource_type_id = ?
               AND log_date >= ? AND log_date <= ?
               AND delta IS NOT NULL AND delta > 0
             ORDER BY log_date ASC, id ASC",
            [$whId, $rtId, $dateFrom, $dateTo]
        )->fetchAll();
    }

    private function getOpeningReading(int $whId, int $rtId, string $dateFrom): ?float
    {
        $openingLog = $this->db->query(
            "SELECT reading FROM resource_logs
             WHERE warehouse_id = ? AND resource_type_id = ? AND log_date < ?
             ORDER BY log_date DESC, id DESC LIMIT 1",
            [$whId, $rtId, $dateFrom]
        )->fetch();
        return $openingLog ? (float)$openingLog['reading'] : null;
    }

    private function getClosingReading(int $whId, int $rtId, string $dateTo): ?float
    {
        $lastLog = $this->db->query(
            "SELECT reading FROM resource_logs
             WHERE warehouse_id = ? AND resource_type_id = ? AND log_date <= ?
             ORDER BY log_date DESC, id DESC LIMIT 1",
            [$whId, $rtId, $dateTo]
        )->fetch();
        return $lastLog ? (float)$lastLog['reading'] : null;
    }

    private function calculateTotalDelta(array $logs): float
    {
        $total = 0;
        foreach ($logs as $l) {
            $total += (float)$l['delta'];
        }
        return $total;
    }

    private function buildMaterialsReport(int $whId, array $rates, array $logs, string $dateFrom, string $dateTo, string $fmt, string $unit): array
    {
        $materials = [];

        foreach ($rates as $rate) {
            $matId = (int)$rate['material_id'];
            $rateValue = (float)$rate['rate'];

            $openingBalance = $this->calculateOpeningBalance($matId, $whId, $dateFrom);
            $receivedPeriod = $this->calculateReceivedPeriod($matId, $whId, $dateFrom, $dateTo);
            
            $rows = [];
            $totalConsumed = 0;

            foreach ($logs as $l) {
                $logId = (int)$l['id'];
                $delta = (float)$l['delta'];
                $corrPct = (float)($l['correction_pct'] ?? 0);
                $corrMul = 1 + $corrPct / 100;

                $consumedRow = (float)($this->db->query(
                    "SELECT COALESCE(SUM(quantity), 0) AS s
                     FROM movements
                     WHERE resource_log_id = ? AND material_id = ? AND warehouse_from_id = ?",
                    [$logId, $matId, $whId]
                )->fetch()['s'] ?? 0);

                if ($consumedRow > 0 || ($delta > 0 && $rateValue > 0)) {
                    $consumedCalc = $consumedRow > 0 ? $consumedRow : round($delta * $rateValue * $corrMul, 2);
                    
                    $rows[] = [
                        'log_id'        => $logId,
                        'log_date'      => $l['log_date'],
                        'reading'       => (float)$l['reading'],
                        'prev_reading'  => $l['prev_reading'] !== null ? (float)$l['prev_reading'] : null,
                        'delta'         => $delta,
                        'rate'          => $rateValue,
                        'correction_pct' => $corrPct,
                        'consumed'      => $consumedCalc,
                        'note'          => $l['note'] ?? '',
                    ];
                    $totalConsumed += $consumedCalc;
                }
            }

            if (empty($rows) && $openingBalance == 0 && $receivedPeriod == 0) continue;

            $closingBalance = $openingBalance + $receivedPeriod - $totalConsumed;

            $materials[$matId] = [
                'name'             => $rate['material_name'],
                'rate'             => $rateValue,
                'opening_balance'  => $openingBalance,
                'received'         => $receivedPeriod,
                'consumed_total'   => $totalConsumed,
                'closing_balance'  => $closingBalance,
                'rows'             => $rows,
            ];
        }

        return $materials;
    }

    private function calculateOpeningBalance(int $matId, int $whId, string $dateFrom): float
    {
        $receivedBefore = (float)($this->db->query(
            "SELECT COALESCE(SUM(quantity), 0) AS s
             FROM movements
             WHERE material_id = ? AND warehouse_to_id = ? AND warehouse_from_id IS NULL
               AND movement_date < ?",
            [$matId, $whId, $dateFrom]
        )->fetch()['s'] ?? 0);

        $consumedBefore = (float)($this->db->query(
            "SELECT COALESCE(SUM(quantity), 0) AS s
             FROM movements
             WHERE material_id = ? AND warehouse_from_id = ? AND resource_log_id IS NOT NULL
               AND movement_date < ?",
            [$matId, $whId, $dateFrom]
        )->fetch()['s'] ?? 0);

        $consumedManualBefore = (float)($this->db->query(
            "SELECT COALESCE(SUM(quantity), 0) AS s
             FROM movements
             WHERE material_id = ? AND warehouse_from_id = ? AND resource_log_id IS NULL
               AND movement_date < ?",
            [$matId, $whId, $dateFrom]
        )->fetch()['s'] ?? 0);

        return $receivedBefore - $consumedBefore - $consumedManualBefore;
    }

    private function calculateReceivedPeriod(int $matId, int $whId, string $dateFrom, string $dateTo): float
    {
        return (float)($this->db->query(
            "SELECT COALESCE(SUM(quantity), 0) AS s
             FROM movements
             WHERE material_id = ? AND warehouse_to_id = ? AND warehouse_from_id IS NULL
               AND movement_date >= ? AND movement_date <= ?",
            [$matId, $whId, $dateFrom, $dateTo]
        )->fetch()['s'] ?? 0);
    }

    // Методи-заглушки для сумісності
    public function getRates(int $warehouseId, int $resourceTypeId): array
    {
        $ratesModel = new ResourceRatesModel($this->db);
        return $ratesModel->getRates($warehouseId, $resourceTypeId);
    }

    public function getTypeById(int $id): ?array
    {
        $typesModel = new ResourceTypesModel($this->db);
        return $typesModel->getTypeById($id);
    }
}