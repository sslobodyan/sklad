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
        
        $report = [];
        
        foreach ($warehouseIds as $warehouseId) {
            to_log("Processing warehouse_id: {$warehouseId}");
            
            $rates = $this->ratesModel->getRates($warehouseId, $resourceTypeId);
            to_log('rates count for warehouse ' . $warehouseId . ': ' . count($rates));
            
            if (empty($rates)) continue;
            
            foreach ($rates as $rate) {
                $materialId = $rate['material_id'];
                $materialName = $rate['material_name'];
                $rateValue = (float)$rate['rate'];
                
                to_log("Processing material: {$materialName} (id:{$materialId}), rate:{$rateValue}");
                
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
                
                to_log("warehouseData rows count: " . count($warehouseData['rows']));
                
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
        
        to_log('Final report count: ' . count($report));
        
        return array_values($report);
    }
    
    private function getWarehouseData(int $warehouseId, int $materialId, int $resourceTypeId, string $dateFrom, string $dateTo, float $rate): array
    {
        to_log("=== getWarehouseData: warehouse={$warehouseId}, material={$materialId} ===");
        
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
        
        to_log('logs count: ' . count($logs));
        
        // Отримуємо всі рухи (надходження та списання) для цього складу та матеріалу
        $movements = $this->db->query(
            "SELECT movement_date, quantity, 
                    CASE WHEN warehouse_to_id = ? THEN 'incoming' 
                         WHEN warehouse_from_id = ? THEN 'outgoing' 
                    END AS type,
                    resource_log_id
             FROM movements
             WHERE (warehouse_to_id = ? OR warehouse_from_id = ?)
               AND material_id = ?
               AND movement_date >= ?
               AND movement_date <= ?",
            [$warehouseId, $warehouseId, $warehouseId, $warehouseId, $materialId, $dateFrom, $dateTo]
        )->fetchAll();
        
        to_log('movements count: ' . count($movements));
        
        // Збираємо всі події (ресурсні логи + рухи) в один масив
        $events = [];
        
        foreach ($logs as $log) {
            $events[] = [
                'date' => $log['log_date'],
                'type' => 'resource_log',
                'data' => $log
            ];
        }
        
        foreach ($movements as $movement) {
            $events[] = [
                'date' => $movement['movement_date'],
                'type' => $movement['type'],
                'data' => $movement
            ];
        }
        
        // Сортуємо за датою
        usort($events, fn($a, $b) => strcmp($a['date'], $b['date']));
        
        to_log('total events count: ' . count($events));
        
        // Розраховуємо баланс накопичувально
        $balance = 0;
        $rows = [];
        $totalDelta = 0;
        $totalIncoming = 0;
        $totalConsumed = 0;
        
        foreach ($events as $event) {
            $date = $event['date'];
            
            if ($event['type'] == 'incoming') {
                $quantity = (float)$event['data']['quantity'];
                $balance += $quantity;
                $totalIncoming += $quantity;
                to_log("INCOMING: date={$date}, qty={$quantity}, balance={$balance}");
                
                // Додаємо рядок надходження
                $rows[] = [
                    'date' => $date,
                    'type' => 'incoming',
                    'reading' => '',
                    'delta' => '',
                    'rate' => $rate,
                    'correction_pct' => '',
                    'opening_balance' => $balance - $quantity,
                    'incoming' => $quantity,
                    'consumed' => 0,
                    'closing_balance' => $balance,
                    'note' => 'Надходження на склад',
                    'has_manual' => false,
                    'manual_note' => ''
                ];
            }
            elseif ($event['type'] == 'outgoing') {
                $quantity = (float)$event['data']['quantity'];
                $balance -= $quantity;
                $totalConsumed += $quantity;
                to_log("OUTGOING: date={$date}, qty={$quantity}, balance={$balance}");
                
                $hasManual = $event['data']['resource_log_id'] === null;
                $rows[] = [
                    'date' => $date,
                    'type' => 'outgoing',
                    'reading' => '',
                    'delta' => '',
                    'rate' => $rate,
                    'correction_pct' => '',
                    'opening_balance' => $balance + $quantity,
                    'incoming' => 0,
                    'consumed' => $quantity,
                    'closing_balance' => $balance,
                    'note' => $hasManual ? '⚠️ РУЧНЕ СПИСАННЯ' : 'Списання за нормою',
                    'has_manual' => $hasManual,
                    'manual_note' => $hasManual ? "Ручне списання: {$quantity} од." : ''
                ];
            }
            elseif ($event['type'] == 'resource_log') {
                $log = $event['data'];
                $delta = (float)$log['delta'];
                $correctionPct = (float)$log['correction_pct'];
                $correctionMul = 1 + $correctionPct / 100;
                
                // Розраховуємо списання за нормою
                $consumed = round($delta * $rate * $correctionMul, 2);
                $balance -= $consumed;
                $totalDelta += $delta;
                $totalConsumed += $consumed;
                
                to_log("RESOURCE_LOG: date={$date}, delta={$delta}, correction={$correctionPct}%, consumed={$consumed}, balance={$balance}");
                
                $rows[] = [
                    'date' => $date,
                    'type' => 'resource_log',
                    'reading' => $this->formatValue($log['reading'], $log['format'] ?? 'dec2'),
                    'delta' => $this->formatDelta($delta, $log['format'] ?? 'dec2'),
                    'rate' => $rate,
                    'correction_pct' => $correctionPct,
                    'opening_balance' => $balance + $consumed,
                    'incoming' => 0,
                    'consumed' => $consumed,
                    'closing_balance' => $balance,
                    'note' => $log['note'] ?? '',
                    'has_manual' => false,
                    'manual_note' => ''
                ];
            }
        }
        
        // Вхідне сальдо на початок періоду
        $openingBalance = $this->getBalanceBeforeDate($warehouseId, $materialId, $dateFrom);
        
        // Вихідне сальдо на кінець періоду
        $closingBalance = $this->getBalanceBeforeDate($warehouseId, $materialId, date('Y-m-d', strtotime($dateTo . ' +1 day')));
        
        to_log("openingBalance: {$openingBalance}, closingBalance: {$closingBalance}");
        
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