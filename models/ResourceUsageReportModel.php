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
        // Отримуємо ВСІ movements для складу та матеріалу за період
        // Беремо дані безпосередньо з movements: resource_delta, resource_rate, resource_correction
        $movements = $this->db->query(
            "SELECT m.*, 
                    m.resource_delta, 
                    m.resource_rate, 
                    m.resource_correction,
                    m.resource_value
             FROM movements m
             WHERE (m.warehouse_to_id = ? OR m.warehouse_from_id = ?)
               AND m.material_id = ?
               AND m.movement_date >= ?
               AND m.movement_date <= ?
             ORDER BY m.movement_date ASC, m.id ASC",
            [$warehouseId, $warehouseId, $materialId, $dateFrom, $dateTo]
        )->fetchAll();
        
        // Розраховуємо баланс накопичувально
        $balance = $this->getBalanceBeforeDate($warehouseId, $materialId, $dateFrom);
        $openingBalance = $balance;
        
        $rows = [];
        $totalDelta = 0;
        $totalIncoming = 0;
        $totalConsumed = 0;
        
        foreach ($movements as $movement) {
            $date = $movement['movement_date'];
            $openingBalanceDay = $balance;
            
            // Визначаємо тип руху
            if ($movement['warehouse_to_id'] == $warehouseId) {
                // НАДХОДЖЕННЯ на склад
                $quantity = (float)$movement['quantity'];
                $balance += $quantity;
                $totalIncoming += $quantity;
                
                $rows[] = [
                    'date' => $date,
                    'reading' => '',
                    'delta' => '',
                    'rate' => '',
                    'correction_pct' => '',
                    'opening_balance' => $openingBalanceDay,
                    'incoming' => number_format($quantity, 2),
                    'consumed' => '',
                    'closing_balance' => $balance,
                    'note' => $movement['note'] ?? 'Надходження',
                    'has_manual' => false
                ];
            }
            elseif ($movement['warehouse_from_id'] == $warehouseId) {
                // СПИСАННЯ зі складу
                $quantity = (float)$movement['quantity'];
                $isManual = ($movement['resource_log_id'] === null);
                
                $balance -= $quantity;
                $totalConsumed += $quantity;
                
                // Беремо дані безпосередньо з руху
                $delta = 0;
                $reading = '';
                $resourceRate = '';
                $correctionDisplay = '';
                $note = $movement['note'] ?? '';
                
                if (!$isManual) {
                    // Дані з полів руху (заповнюються при створенні автоматичних записів)
                    $delta = (float)($movement['resource_delta'] ?? 0);
                    $totalDelta += $delta;
                    
                    $reading = $this->formatValue($movement['resource_value'] ?? null, 'dec2');
                    $resourceRate = number_format((float)($movement['resource_rate'] ?? 0), 4);
                    
                    $correction = (float)($movement['resource_correction'] ?? 0);
                    $correctionDisplay = $correction != 0 ? ($correction > 0 ? '+' : '') . $correction . '%' : '';
                }
                
                $rows[] = [
                    'date' => $date,
                    'reading' => $reading,
                    'delta' => !$isManual ? $this->formatDelta($delta, 'dec2') : '',
                    'rate' => $resourceRate,
                    'correction_pct' => $correctionDisplay,
                    'opening_balance' => $openingBalanceDay,
                    'incoming' => '',
                    'consumed' => number_format($quantity, 2),
                    'closing_balance' => $balance,
                    'note' => $note,
                    'has_manual' => $isManual
                ];
            }
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