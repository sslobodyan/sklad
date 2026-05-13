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
        $report = [];
        
        foreach ($warehouseIds as $warehouseId) {
            $rates = $this->ratesModel->getRates($warehouseId, $resourceTypeId);
            if (empty($rates)) continue;
            
            foreach ($rates as $rate) {
                $materialId = $rate['material_id'];
                $materialName = $rate['material_name'];
                $rateValue = (float)$rate['rate'];
                
                if (!isset($report[$materialId])) {
                    $report[$materialId] = [
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
                
                $warehouseData = $this->getWarehouseData(
                    $warehouseId,
                    $materialId,
                    $resourceTypeId,
                    $dateFrom,
                    $dateTo,
                    $rateValue
                );
                
                if (!empty($warehouseData['rows']) || $warehouseData['opening_balance'] != 0) {
                    $report[$materialId]['warehouses'][] = $warehouseData;
                    $report[$materialId]['total_delta'] += $warehouseData['total_delta'];
                    $report[$materialId]['total_opening'] += $warehouseData['opening_balance'];
                    $report[$materialId]['total_incoming'] += $warehouseData['total_incoming'];
                    $report[$materialId]['total_consumed'] += $warehouseData['total_consumed'];
                    $report[$materialId]['total_closing'] += $warehouseData['closing_balance'];
                }
            }
        }
        
        $report = array_filter($report, fn($m) => !empty($m['warehouses']));
        usort($report, fn($a, $b) => strcmp($a['material_name'], $b['material_name']));
        
        return array_values($report);
    }
    
    private function getWarehouseData(int $warehouseId, int $materialId, int $resourceTypeId, string $dateFrom, string $dateTo, float $rate): array
    {
        // Отримуємо всі resource_logs за період
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
        
        // Отримуємо всі надходження (рухи, де наш склад отримувач)
        $incomings = $this->db->query(
            "SELECT movement_date, SUM(quantity) as total_quantity
             FROM movements
             WHERE warehouse_to_id = ? 
               AND material_id = ?
               AND movement_date >= ?
               AND movement_date <= ?
             GROUP BY movement_date
             ORDER BY movement_date ASC",
            [$warehouseId, $materialId, $dateFrom, $dateTo]
        )->fetchAll();
        
        // Перетворюємо масив надходжень для швидкого доступу
        $incomingByDate = [];
        foreach ($incomings as $inc) {
            $incomingByDate[$inc['movement_date']] = (float)$inc['total_quantity'];
        }
        
        // Отримуємо ручні списання (не пов'язані з ресурсом)
        $manualOutgoings = $this->db->query(
            "SELECT movement_date, SUM(quantity) as total_quantity
             FROM movements
             WHERE warehouse_from_id = ? 
               AND material_id = ?
               AND movement_date >= ?
               AND movement_date <= ?
               AND resource_log_id IS NULL
             GROUP BY movement_date
             ORDER BY movement_date ASC",
            [$warehouseId, $materialId, $dateFrom, $dateTo]
        )->fetchAll();
        
        $manualByDate = [];
        foreach ($manualOutgoings as $manual) {
            $manualByDate[$manual['movement_date']] = (float)$manual['total_quantity'];
        }
        
        // Збираємо всі унікальні дати з resource_logs
        $dates = [];
        foreach ($logs as $log) {
            $dates[$log['log_date']] = [
                'log' => $log,
                'incoming' => $incomingByDate[$log['log_date']] ?? 0,
                'manual' => $manualByDate[$log['log_date']] ?? 0
            ];
        }
        
        // Також додаємо дати, де є тільки надходження або ручні списання, але немає resource_logs
        foreach ($incomingByDate as $date => $qty) {
            if (!isset($dates[$date])) {
                $dates[$date] = [
                    'log' => null,
                    'incoming' => $qty,
                    'manual' => $manualByDate[$date] ?? 0
                ];
            }
        }
        foreach ($manualByDate as $date => $qty) {
            if (!isset($dates[$date])) {
                $dates[$date] = [
                    'log' => null,
                    'incoming' => $incomingByDate[$date] ?? 0,
                    'manual' => $qty
                ];
            }
        }
        
        // Сортуємо за датою
        ksort($dates);
        
        // Розраховуємо баланс
        $balance = $this->getBalanceBeforeDate($warehouseId, $materialId, $dateFrom);
        $openingBalance = $balance;
        
        $rows = [];
        $totalDelta = 0;
        $totalIncoming = 0;
        $totalConsumed = 0;
        
        foreach ($dates as $date => $data) {
            $log = $data['log'];
            $incomingQty = $data['incoming'];
            $manualQty = $data['manual'];
            
            $delta = 0;
            $reading = '';
            $correctionPct = 0;
            $note = '';
            $format = 'dec2';
            $consumedByResource = 0;
            
            if ($log) {
                $delta = (float)$log['delta'];
                $correctionPct = (float)$log['correction_pct'];
                $correctionMul = 1 + $correctionPct / 100;
                $consumedByResource = round($delta * $rate * $correctionMul, 2);
                $reading = $this->formatValue($log['reading'], $log['format'] ?? 'dec2');
                $format = $log['format'] ?? 'dec2';
                $note = $log['note'] ?? '';
            }
            
            // Загальне списання = ресурсне + ручне
            $totalOutgoing = $consumedByResource + $manualQty;
            
            // Оновлюємо баланс
            $openingBalanceDay = $balance;
            $balance = $balance + $incomingQty - $totalOutgoing;
            
            // Додаємо рядок тільки якщо є хоч якась зміна
            if ($incomingQty != 0 || $totalOutgoing != 0 || $log) {
                $rows[] = [
                    'date' => $date,
                    'reading' => $reading,
                    'delta' => $delta > 0 ? $this->formatDelta($delta, $format) : '',
                    'rate' => $rate,
                    'correction_pct' => $correctionPct != 0 ? ($correctionPct > 0 ? '+' : '') . $correctionPct . '%' : '',
                    'opening_balance' => $openingBalanceDay,
                    'incoming' => $incomingQty != 0 ? number_format($incomingQty, 2) : '',
                    'consumed' => $totalOutgoing != 0 ? number_format($totalOutgoing, 2) : '',
                    'closing_balance' => $balance,
                    'note' => $note,
                    'has_manual' => $manualQty > 0
                ];
            }
            
            $totalDelta += $delta;
            $totalIncoming += $incomingQty;
            $totalConsumed += $totalOutgoing;
        }
        
        $closingBalance = $balance;
        
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
    
    private function getBalanceBeforeDate(int $warehouseId, int $materialId, string $date): float
    {
        $result = $this->db->query(
            "SELECT COALESCE(SUM(CASE WHEN warehouse_to_id = ? THEN quantity ELSE 0 END), 0) -
                    COALESCE(SUM(CASE WHEN warehouse_from_id = ? THEN quantity ELSE 0 END), 0) AS balance
             FROM movements
             WHERE material_id = ? AND movement_date < ?",
            [$warehouseId, $warehouseId, $materialId, $date]
        )->fetch();
        return (float)($result['balance'] ?? 0);
    }
    
    private function getWarehouseName(int $warehouseId): string
    {
        $result = $this->db->query("SELECT name FROM warehouses WHERE id = ?", [$warehouseId])->fetch();
        return $result['name'] ?? '';
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