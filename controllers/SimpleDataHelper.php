<?php
/**
 * SimpleDataHelper
 * Отримання даних по складу для спрощеного режиму
 */
class SimpleDataHelper
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Отримати дані по складу: залишки та рухи
     */
    public function getWarehouseData(int $warehouseId, string $date, array $materialIds): array
    {
        $opening = $this->getOpeningBalances($warehouseId, $date, $materialIds);
        $movements = $this->getMovementsWithDetails($warehouseId, $date, $materialIds);
        $closing = $this->calculateClosingBalances($opening, $movements, $materialIds);

        return [
            'opening' => $opening,
            'movements' => $movements,
            'closing' => $closing
        ];
    }

    private function getOpeningBalances(int $warehouseId, string $date, array $materialIds): array
    {
        $query = "
            SELECT 
                m.id AS material_id,
                m.name AS material_name,
                COALESCE(SUM(CASE WHEN warehouse_to_id = ? THEN quantity ELSE 0 END), 0) -
                COALESCE(SUM(CASE WHEN warehouse_from_id = ? THEN quantity ELSE 0 END), 0) AS opening_balance
            FROM movements mv
            JOIN materials m ON mv.material_id = m.id
            WHERE mv.movement_date < ?
        ";
        $params = [$warehouseId, $warehouseId, $date];
        
        if (!empty($materialIds)) {
            $query .= " AND mv.material_id IN (" . implode(',', array_fill(0, count($materialIds), '?')) . ")";
            $params = array_merge($params, $materialIds);
        }
        
        $query .= " GROUP BY m.id, m.name ORDER BY m.name";
        
        $rows = $this->db->query($query, $params)->fetchAll();
        $opening = [];
        
        foreach ($rows as $row) {
            $opening[$row['material_id']] = [
                'material_id' => $row['material_id'],
                'material_name' => $row['material_name'],
                'balance' => (float)$row['opening_balance']
            ];
        }
        
        return $opening;
    }

    private function getMovementsWithDetails(int $warehouseId, string $date, array $materialIds): array
    {
        $query = "
            SELECT 
                mv.id,
                mv.material_id,
                m.name AS material_name,
                mv.quantity,
                mv.warehouse_from_id,
                mv.warehouse_to_id,
                wf.name AS warehouse_from_name,
                wt.name AS warehouse_to_name,
                CASE 
                    WHEN mv.warehouse_to_id = ? THEN 'in'
                    WHEN mv.warehouse_from_id = ? THEN 'out'
                    ELSE 'other'
                END AS type
            FROM movements mv
            JOIN materials m ON mv.material_id = m.id
            LEFT JOIN warehouses wf ON mv.warehouse_from_id = wf.id
            LEFT JOIN warehouses wt ON mv.warehouse_to_id = wt.id
            WHERE mv.movement_date = ?
              AND (mv.warehouse_from_id = ? OR mv.warehouse_to_id = ?)
        ";
        $params = [$warehouseId, $warehouseId, $date, $warehouseId, $warehouseId];
        
        if (!empty($materialIds)) {
            $query .= " AND mv.material_id IN (" . implode(',', array_fill(0, count($materialIds), '?')) . ")";
            $params = array_merge($params, $materialIds);
        }
        
        $query .= " ORDER BY m.name, mv.id";
        
        $rows = $this->db->query($query, $params)->fetchAll();
        $movements = [];
        
        foreach ($rows as $row) {
            $correspondent = '';
            $incoming = 0;
            $outgoing = 0;
            
            if ($row['type'] === 'in') {
                $correspondent = $row['warehouse_from_name'] ?? 'Ззовні';
                $incoming = (float)$row['quantity'];
            } elseif ($row['type'] === 'out') {
                $correspondent = $row['warehouse_to_name'] ?? 'Списано';
                $outgoing = (float)$row['quantity'];
            }
            
            $movements[] = [
                'id' => $row['id'],
                'material_id' => $row['material_id'],
                'material_name' => $row['material_name'],
                'correspondent' => $correspondent,
                'incoming' => $incoming,
                'outgoing' => $outgoing
            ];
        }
        
        return $movements;
    }

    private function calculateClosingBalances(array $opening, array $movements, array $materialIds): array
    {
        $movementsAggregated = [];
        foreach ($movements as $mv) {
            $matId = $mv['material_id'];
            if (!isset($movementsAggregated[$matId])) {
                $movementsAggregated[$matId] = ['incoming' => 0, 'outgoing' => 0];
            }
            $movementsAggregated[$matId]['incoming'] += $mv['incoming'];
            $movementsAggregated[$matId]['outgoing'] += $mv['outgoing'];
        }
        
        $allMaterialIds = array_unique(array_merge(
            array_keys($opening),
            array_keys($movementsAggregated),
            $materialIds
        ));
        
        $closing = [];
        foreach ($allMaterialIds as $matId) {
            $openingBalance = $opening[$matId]['balance'] ?? 0;
            $incoming = $movementsAggregated[$matId]['incoming'] ?? 0;
            $outgoing = $movementsAggregated[$matId]['outgoing'] ?? 0;
            $materialName = $opening[$matId]['material_name'] ?? $movements[0]['material_name'] ?? '';
            
            $closing[$matId] = [
                'material_id' => $matId,
                'material_name' => $materialName,
                'balance' => $openingBalance + $incoming - $outgoing
            ];
        }

        uasort($closing, fn($a, $b) => strcmp($a['material_name'], $b['material_name']));
        
        return $closing;
    }
}