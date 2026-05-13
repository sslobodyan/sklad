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
        
        // Отримуємо всі рухи для цього складу та матеріалу
        // Але виключаємо ресурсні списання (вони будуть оброблені через resource_logs)
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
               AND movement_date <= ?
               AND (resource_log_id IS NULL OR warehouse_to_id = ?)
             ORDER BY movement_date ASC",
            [$warehouseId, $warehouseId, $warehouseId, $warehouseId, $materialId, $dateFrom, $dateTo, $warehouseId]
        )->fetchAll();
        
        to_log('movements count (without resource outgoing): ' . count($movements));
        
        // Збираємо всі події
        $events = [];
        
        // Додаємо resource_logs як події списання
        foreach ($logs as $log) {
            $delta = (float)$log['delta'];
            $correctionPct = (float)$log['correction_pct'];
            $correctionMul = 1 + $correctionPct / 100;
            $consumed = round($delta * $rate * $correctionMul, 2);
            
            $events[] = [
                'date' => $log['log_date'],
                'type' => 'resource_log',
                'delta' => $delta,
                'reading' => $log['reading'],
                'format' => $log['format'] ?? 'dec2',
                'correction_pct' => $correctionPct,
                'consumed' => $consumed,
                'note' => $log['note'] ?? '',
                'log_id' => $log['id']
            ];
        }
        
        // Додаємо рухи (тільки надходження та ручні списання)
        foreach ($movements as $movement) {
            if ($movement['type'] == 'incoming') {
                $events[] = [
                    'date' => $movement['movement_date'],
                    'type' => 'incoming',
                    'quantity' => (float)$movement['quantity'],
                    'note' => ''
                ];
            } elseif ($movement['type'] == 'outgoing' && $movement['resource_log_id'] === null) {
                // Тільки ручні списання (не пов'язані з ресурсом)
                $events[] = [
                    'date' => $movement['movement_date'],
                    'type' => 'manual_outgoing',
                    'quantity' => (float)$movement['quantity'],
                    'note' => 'Ручне списання'
                ];
            }
        }
        
        // Сортуємо за датою
        usort($events, fn($a, $b) => strcmp($a['date'], $b['date']));
        
        to_log('total events count: ' . count($events));
        
        // Розраховуємо баланс накопичувально
        $balance = $this->getBalanceBeforeDate($warehouseId, $materialId, $dateFrom);
        $openingBalance = $balance;
        
        $rows = [];
        $totalDelta = 0;
        $totalIncoming = 0;
        $totalConsumed = 0;
        
        foreach ($events as $event) {
            $date = $event['date'];
            $openingBalanceDay = $balance;
            
            if ($event['type'] == 'incoming') {
                $quantity = $event['quantity'];
                $balance += $quantity;
                $totalIncoming += $quantity;
                
                $rows[] = [
                    'date' => $date,
                    'type' => 'incoming',
                    'reading' => '',
                    'delta' => '',
                    'rate' => $rate,
                    'correction_pct' => '',
                    'opening_balance' => $openingBalanceDay,
                    'incoming' => $quantity,
                    'consumed' => 0,
                    'closing_balance' => $balance,
                    'note' => 'Надходження на склад',
                    'has_manual' => false,
                    'manual_note' => ''
                ];
                to_log("INCOMING: date={$date}, qty={$quantity}, balance={$balance}");
            }
            elseif ($event['type'] == 'manual_outgoing') {
                $quantity = $event['quantity'];
                $balance -= $quantity;
                $totalConsumed += $quantity;
                
                $rows[] = [
                    'date' => $date,
                    'type' => 'manual_outgoing',
                    'reading' => '',
                    'delta' => '',
                    'rate' => $rate,
                    'correction_pct' => '',
                    'opening_balance' => $openingBalanceDay,
                    'incoming' => 0,
                    'consumed' => $quantity,
                    'closing_balance' => $balance,
                    'note' => $event['note'],
                    'has_manual' => true,
                    'manual_note' => "Ручне списання: {$quantity} од."
                ];
                to_log("MANUAL OUTGOING: date={$date}, qty={$quantity}, balance={$balance}");
            }
            elseif ($event['type'] == 'resource_log') {
                $consumed = $event['consumed'];
                $delta = $event['delta'];
                $balance -= $consumed;
                $totalDelta += $delta;
                $totalConsumed += $consumed;
                
                $rows[] = [
                    'date' => $date,
                    'type' => 'resource_log',
                    'reading' => $this->formatValue($event['reading'], $event['format']),
                    'delta' => $this->formatDelta($delta, $event['format']),
                    'rate' => $rate,
                    'correction_pct' => $event['correction_pct'],
                    'opening_balance' => $openingBalanceDay,
                    'incoming' => 0,
                    'consumed' => $consumed,
                    'closing_balance' => $balance,
                    'note' => $event['note'],
                    'has_manual' => false,
                    'manual_note' => ''
                ];
                to_log("RESOURCE_LOG: date={$date}, delta={$delta}, consumed={$consumed}, balance={$balance}");
            }
        }
        
        $closingBalance = $balance;
        
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