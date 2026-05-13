<?php
/**
 * Модель звіту по використанню ресурсів
 */
class ResourceUsageReportModel extends Model
{
    private ResourceRatesModel $ratesModel;

    public function __construct(Database $db)
    {
        parent::__construct($db);
        $this->ratesModel = new ResourceRatesModel($db);
    }

    public function getDetailedReport(int $resourceTypeId, array $warehouseIds, string $dateFrom, string $dateTo): array
    {


    to_log('=== ResourceUsageReportModel::getDetailedReport ===');
    to_log('resourceTypeId: ' . $resourceTypeId);
    to_log('warehouseIds: ' . json_encode($warehouseIds));
    to_log('dateFrom: ' . $dateFrom);
    to_log('dateTo: ' . $dateTo);
    
    // Перевіряємо чи є resource_logs взагалі
    $allLogs = $this->db->query(
        "SELECT COUNT(*) as cnt FROM resource_logs"
    )->fetch();
    to_log('total resource_logs in DB: ' . ($allLogs['cnt'] ?? 0));
    
    // Перевіряємо конкретний запит
    if (!empty($warehouseIds)) {
        $placeholders = implode(',', array_fill(0, count($warehouseIds), '?'));
        $params = array_merge([$resourceTypeId, $dateFrom, $dateTo], $warehouseIds);
        
        $testLogs = $this->db->query(
            "SELECT rl.*, rt.format, rt.unit
             FROM resource_logs rl
             JOIN resource_types rt ON rl.resource_type_id = rt.id
             WHERE rl.resource_type_id = ? 
               AND rl.log_date >= ? 
               AND rl.log_date <= ?
               AND rl.warehouse_id IN ($placeholders)
               AND rl.delta IS NOT NULL
             LIMIT 5",
            $params
        )->fetchAll();
        
        to_log('test logs query result count: ' . count($testLogs));
        if (!empty($testLogs)) {
            to_log('first log: ' . json_encode($testLogs[0]));
        }
    }


        $report = [];
        
        foreach ($warehouseIds as $warehouseId) {
            // Отримуємо норми для складу та типу ресурсу
            $rates = $this->ratesModel->getRates($warehouseId, $resourceTypeId);
            if (empty($rates)) continue;
            
            foreach ($rates as $rate) {
                $materialId = $rate['material_id'];
                $materialName = $rate['material_name'];
                $rateValue = (float)$rate['rate'];
                
                // Шукаємо або створюємо запис для матеріалу
                $materialKey = $materialId;
                if (!isset($report[$materialKey])) {
                    $report[$materialKey] = [
                        'material_id' => $materialId,
                        'material_name' => $materialName,
                        'total_delta' => 0,
                        'total_opening' => 0,
                        'total_incoming' => 0,
                        'total_consumed' => 0,
                        'total_closing' => 0,
                        'warehouses' => []
                    ];
                }
                
                // Дані по складу для цього матеріалу
                $warehouseData = $this->getWarehouseData(
                    $warehouseId,
                    $materialId,
                    $resourceTypeId,
                    $dateFrom,
                    $dateTo,
                    $rateValue
                );
                
                if (!empty($warehouseData['rows']) || $warehouseData['opening_balance'] != 0) {
                    $report[$materialKey]['warehouses'][] = $warehouseData;
                    $report[$materialKey]['total_delta'] += $warehouseData['total_delta'];
                    $report[$materialKey]['total_opening'] += $warehouseData['opening_balance'];
                    $report[$materialKey]['total_incoming'] += $warehouseData['total_incoming'];
                    $report[$materialKey]['total_consumed'] += $warehouseData['total_consumed'];
                    $report[$materialKey]['total_closing'] += $warehouseData['closing_balance'];
                }
            }
        }
        
        // Видаляємо матеріали без даних
        $report = array_filter($report, fn($m) => !empty($m['warehouses']));
        
        // Сортуємо за назвою матеріалу
        usort($report, fn($a, $b) => strcmp($a['material_name'], $b['material_name']));
        
        return array_values($report);
    }
    
    private function getWarehouseData(int $warehouseId, int $materialId, int $resourceTypeId, string $dateFrom, string $dateTo, float $rate): array
    {
        // Отримуємо resource_logs за період
        $logs = $this->db->query(
            "SELECT rl.id, rl.log_date, rl.reading, rl.delta, rl.correction_pct, rl.note,
                    rt.format, rt.unit
             FROM resource_logs rl
             JOIN resource_types rt ON rl.resource_type_id = rt.id
             WHERE rl.warehouse_id = ? 
               AND rl.resource_type_id = ?
               AND rl.log_date >= ? 
               AND rl.log_date <= ?
               AND rl.delta IS NOT NULL
             ORDER BY rl.log_date ASC, rl.id ASC",
            [$warehouseId, $resourceTypeId, $dateFrom, $dateTo]
        )->fetchAll();
        
        // Вхідне сальдо на початок періоду
        $openingBalance = $this->getOpeningBalance($warehouseId, $materialId, $dateFrom);
        
        $rows = [];
        $runningBalance = $openingBalance;
        $totalDelta = 0;
        $totalIncoming = 0;
        $totalConsumed = 0;
        
        foreach ($logs as $log) {
            $logDate = $log['log_date'];
            $delta = (float)$log['delta'];
            $correctionPct = (float)$log['correction_pct'];
            $correctionMul = 1 + $correctionPct / 100;
            
            // Надходження матеріалу за цей день
            $incoming = $this->getIncomingQuantity($warehouseId, $materialId, $logDate);
            
            // Списана кількість матеріалу за цей день
            $consumed = $this->getConsumedQuantity($log['id'], $materialId, $warehouseId);
            if ($consumed == 0 && $delta > 0) {
                $consumed = round($delta * $rate * $correctionMul, 2);
            }
            
            $openingBalanceDay = $runningBalance;
            $closingBalance = $openingBalanceDay + $incoming - $consumed;
            
            $rows[] = [
                'date' => $logDate,
                'reading' => $this->formatValue($log['reading'], $log['format'] ?? 'dec2'),
                'delta' => $this->formatDelta($delta, $log['format'] ?? 'dec2'),
                'rate' => $rate,
                'correction_pct' => $correctionPct,
                'opening_balance' => $openingBalanceDay,
                'incoming' => $incoming,
                'consumed' => $consumed,
                'closing_balance' => $closingBalance,
                'note' => $log['note'] ?? ''
            ];
            
            $runningBalance = $closingBalance;
            $totalDelta += $delta;
            $totalIncoming += $incoming;
            $totalConsumed += $consumed;
        }
        
        $closingBalance = $this->getClosingBalance($warehouseId, $materialId, $dateTo);
        
        return [
            'warehouse_id' => $warehouseId,
            'warehouse_name' => $this->getWarehouseName($warehouseId),
            'rate' => $rate,
            'opening_balance' => $openingBalance,
            'closing_balance' => $closingBalance,
            'total_delta' => $totalDelta,
            'total_incoming' => $totalIncoming,
            'total_consumed' => $totalConsumed,
            'rows' => $rows
        ];
    }
    
    private function getWarehouseName(int $warehouseId): string
    {
        $result = $this->db->query("SELECT name FROM warehouses WHERE id = ?", [$warehouseId])->fetch();
        return $result['name'] ?? '';
    }
    
    private function getOpeningBalance(int $warehouseId, int $materialId, string $dateFrom): float
    {
        $result = $this->db->query(
            "SELECT COALESCE(SUM(CASE WHEN warehouse_to_id = ? THEN quantity ELSE 0 END), 0) -
                    COALESCE(SUM(CASE WHEN warehouse_from_id = ? THEN quantity ELSE 0 END), 0) AS balance
             FROM movements
             WHERE material_id = ? AND movement_date < ?",
            [$warehouseId, $warehouseId, $materialId, $dateFrom]
        )->fetch();
        return (float)($result['balance'] ?? 0);
    }
    
    private function getClosingBalance(int $warehouseId, int $materialId, string $dateTo): float
    {
        $result = $this->db->query(
            "SELECT COALESCE(SUM(CASE WHEN warehouse_to_id = ? THEN quantity ELSE 0 END), 0) -
                    COALESCE(SUM(CASE WHEN warehouse_from_id = ? THEN quantity ELSE 0 END), 0) AS balance
             FROM movements
             WHERE material_id = ? AND movement_date <= ?",
            [$warehouseId, $warehouseId, $materialId, $dateTo]
        )->fetch();
        return (float)($result['balance'] ?? 0);
    }
    
    private function getConsumedQuantity(int $logId, int $materialId, int $warehouseId): float
    {
        $result = $this->db->query(
            "SELECT COALESCE(SUM(quantity), 0) AS total
             FROM movements
             WHERE resource_log_id = ? AND material_id = ? AND warehouse_from_id = ?",
            [$logId, $materialId, $warehouseId]
        )->fetch();
        return (float)$result['total'];
    }
    
    private function getIncomingQuantity(int $warehouseId, int $materialId, string $date): float
    {
        $result = $this->db->query(
            "SELECT COALESCE(SUM(quantity), 0) AS total
             FROM movements
             WHERE warehouse_to_id = ? AND material_id = ? AND warehouse_from_id IS NULL AND movement_date = ?",
            [$warehouseId, $materialId, $date]
        )->fetch();
        return (float)$result['total'];
    }
    
    private function formatValue($value, string $format): string
    {
        if ($value === null) return '';
        $v = (float)$value;
        switch ($format) {
            case 'int': return (string)(int)$v;
            case 'hm':
                $h = (int)floor($v);
                $m = (int)round(($v - $h) * 60);
                return $h . ':' . str_pad($m, 2, '0', STR_PAD_LEFT);
            default: return number_format($v, 2, '.', '');
        }
    }
    
    private function formatDelta(float $delta, string $format): string
    {
        switch ($format) {
            case 'int': return (string)(int)$delta;
            case 'hm':
                $h = (int)floor($delta);
                $m = (int)round(($delta - $h) * 60);
                return $h . ':' . str_pad($m, 2, '0', STR_PAD_LEFT);
            default: return number_format($delta, 2, '.', '');
        }
    }
}