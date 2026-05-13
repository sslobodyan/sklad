<?php
/**
 * Модель руху матеріалів
 */

class MovementModel extends Model
{
    protected string $table = 'movements';

    public function create(array $data): int
    {
        $this->db->query(
            "INSERT INTO movements (movement_date, warehouse_from_id, warehouse_to_id, material_id, quantity, note, author)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $data['movement_date'],
                $data['warehouse_from_id'] ?: null,
                $data['warehouse_to_id'] ?: null,
                $data['material_id'],
                $data['quantity'],
                trim($data['note'] ?? ''),
                $this->authorStamp(),
            ]
        );
        return $this->db->lastInsertId();
    }

    /**
     * Створити рух з прив'язкою до журналу ресурсу
     */
    public function createFromResource(array $data): int
    {

        $data['author'] = $this->authorStamp();

        $this->db->query(
            "INSERT INTO movements (
                movement_date, warehouse_from_id, warehouse_to_id, material_id, quantity, note, author,
                resource_log_id, resource_value, resource_delta, resource_rate, resource_correction
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['movement_date'],
                $data['warehouse_from_id'] ?: null,
                $data['warehouse_to_id'] ?: null,
                $data['material_id'],
                $data['quantity'],
                trim($data['note'] ?? ''),
                $data['author'],
                $data['resource_log_id'],
                $data['resource_value'] ?? null,
                $data['resource_delta'] ?? null,
                $data['resource_rate'] ?? null,
                $data['resource_correction'] ?? null,
            ]
        );

	$lastId = $this->db->lastInsertId();

        return $lastId;
    }

    public function update(int $id, array $data): void
    {
        $this->setCurrentUser();
        $this->db->query(
            "UPDATE movements SET 
                movement_date = ?, 
                warehouse_from_id = ?, 
                warehouse_to_id = ?,
                material_id = ?, 
                quantity = ?, 
                note = ?,
                author = ?
             WHERE id = ?",
            [
                $data['movement_date'],
                $data['warehouse_from_id'] ?: null,
                $data['warehouse_to_id'] ?: null,
                $data['material_id'],
                $data['quantity'],
                trim($data['note'] ?? ''),
                $this->authorStamp(),
                $id,
            ]
        );
    }

    /**
     * Отримати рухи з назвами
     */
    public function getAllWithNames(array $filters = [], string $orderBy = 'm.movement_date DESC, m.id DESC'): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['date_from'])) {
            $where[] = "m.movement_date >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[] = "m.movement_date <= ?";
            $params[] = $filters['date_to'];
        }
        if (!empty($filters['warehouse_id'])) {
            if ($filters['warehouse_id'] === '__incoming') {
                // Прихід ззовні: warehouse_from_id IS NULL
                $where[] = "m.warehouse_from_id IS NULL";
            } elseif ($filters['warehouse_id'] === '__writeoff') {
                // Списання: warehouse_to_id IS NULL
                $where[] = "m.warehouse_to_id IS NULL";
            } else {
                $where[] = "(m.warehouse_from_id = ? OR m.warehouse_to_id = ?)";
                $params[] = $filters['warehouse_id'];
                $params[] = $filters['warehouse_id'];
            }
        }
        if (!empty($filters['material_id'])) {
            $where[] = "m.material_id = ?";
            $params[] = $filters['material_id'];
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        return $this->db->query(
            "SELECT m.*,
                    wf.name AS warehouse_from_name,
                    wt.name AS warehouse_to_name,
                    mat.name AS material_name,
                    rt.unit AS resource_unit,
                    rt.format AS resource_format
             FROM movements m
             LEFT JOIN warehouses wf ON m.warehouse_from_id = wf.id
             LEFT JOIN warehouses wt ON m.warehouse_to_id = wt.id
             JOIN materials mat ON m.material_id = mat.id
             LEFT JOIN resource_logs rl ON m.resource_log_id = rl.id
             LEFT JOIN resource_types rt ON rl.resource_type_id = rt.id
             {$whereClause}
             ORDER BY {$orderBy}",
            $params
        )->fetchAll();
    }

    /**
     * Звіт по складу
     */
    public function reportByWarehouse(int $warehouseId, string $dateFrom, string $dateTo): array
    {
        // Вхідне сальдо
        $openingData = $this->db->query(
            "SELECT material_id,
                    SUM(CASE WHEN warehouse_to_id = ? THEN quantity ELSE 0 END) AS total_in,
                    SUM(CASE WHEN warehouse_from_id = ? THEN quantity ELSE 0 END) AS total_out
             FROM movements
             WHERE (warehouse_from_id = ? OR warehouse_to_id = ?)
               AND movement_date < ?
             GROUP BY material_id",
            [$warehouseId, $warehouseId, $warehouseId, $warehouseId, $dateFrom]
        )->fetchAll();

        $opening = [];
        foreach ($openingData as $row) {
            $opening[$row['material_id']] = (float)$row['total_in'] - (float)$row['total_out'];
        }

        // Рухи за період
        $periodData = $this->db->query(
            "SELECT m.*, mat.name AS material_name,
                    wf.name AS warehouse_from_name,
                    wt.name AS warehouse_to_name
             FROM movements m
             JOIN materials mat ON m.material_id = mat.id
             LEFT JOIN warehouses wf ON m.warehouse_from_id = wf.id
             LEFT JOIN warehouses wt ON m.warehouse_to_id = wt.id
             WHERE (m.warehouse_from_id = ? OR m.warehouse_to_id = ?)
               AND m.movement_date >= ? AND m.movement_date <= ?
             ORDER BY m.movement_date, m.id",
            [$warehouseId, $warehouseId, $dateFrom, $dateTo]
        )->fetchAll();

        // Матеріали
        $materials = $this->db->query("SELECT id, name FROM materials ORDER BY name")->fetchAll();
        $matMap = array_column($materials, 'name', 'id');

        // Збір звіту
        $allMaterialIds = array_unique(array_merge(
            array_keys($opening),
            array_column($periodData, 'material_id')
        ));

        $report = [];
        foreach ($allMaterialIds as $matId) {
            $row = [
                'material_id' => $matId,
                'material_name' => $matMap[$matId] ?? '—',
                'opening_balance' => $opening[$matId] ?? 0,
                'incoming' => 0,
                'outgoing' => 0,
                'details' => [],
            ];

            foreach ($periodData as $pd) {
                if ($pd['material_id'] != $matId) continue;

                $isIn = $pd['warehouse_to_id'] == $warehouseId;
                $isOut = $pd['warehouse_from_id'] == $warehouseId;

                if ($isIn) {
                    $row['incoming'] += (float)$pd['quantity'];
                    $row['details'][] = [
                        'id' => $pd['id'],
                        'date' => $pd['movement_date'],
                        'type' => $pd['warehouse_from_id'] ? 'Від складу' : 'Прихід',
                        'counterpart' => $pd['warehouse_from_name'] ?? 'Ззовні',
                        'incoming' => (float)$pd['quantity'],
                        'outgoing' => 0,
                        'note' => $pd['note'],
                    ];
                }
                if ($isOut) {
                    $row['outgoing'] += (float)$pd['quantity'];
                    $row['details'][] = [
                        'id' => $pd['id'],
                        'date' => $pd['movement_date'],
                        'type' => $pd['warehouse_to_id'] ? 'На склад' : 'Списання',
                        'counterpart' => $pd['warehouse_to_name'] ?? 'Списання',
                        'incoming' => 0,
                        'outgoing' => (float)$pd['quantity'],
                        'note' => $pd['note'],
                    ];
                }
            }

            $row['closing_balance'] = $row['opening_balance'] + $row['incoming'] - $row['outgoing'];

            if ($row['opening_balance'] != 0 || $row['incoming'] != 0 || $row['outgoing'] != 0) {
                $report[] = $row;
            }
        }

        usort($report, fn($a, $b) => strcmp($a['material_name'], $b['material_name']));
        return $report;
    }

    /**
     * Звіт по матеріалу
     */
    public function reportByMaterial(int $materialId, string $dateFrom, string $dateTo): array
    {
        // Вхідне сальдо
        $openingData = $this->db->query(
            "SELECT warehouse_to_id, warehouse_from_id, quantity
             FROM movements
             WHERE material_id = ? AND movement_date < ?",
            [$materialId, $dateFrom]
        )->fetchAll();

        $opening = [];
        foreach ($openingData as $row) {
            if ($row['warehouse_to_id']) {
                $opening[$row['warehouse_to_id']] = ($opening[$row['warehouse_to_id']] ?? 0) + (float)$row['quantity'];
            }
            if ($row['warehouse_from_id']) {
                $opening[$row['warehouse_from_id']] = ($opening[$row['warehouse_from_id']] ?? 0) - (float)$row['quantity'];
            }
        }

        // Рухи за період
        $periodData = $this->db->query(
            "SELECT m.*,
                    wf.name AS warehouse_from_name,
                    wt.name AS warehouse_to_name
             FROM movements m
             LEFT JOIN warehouses wf ON m.warehouse_from_id = wf.id
             LEFT JOIN warehouses wt ON m.warehouse_to_id = wt.id
             WHERE m.material_id = ?
               AND m.movement_date >= ? AND m.movement_date <= ?
             ORDER BY m.movement_date, m.id",
            [$materialId, $dateFrom, $dateTo]
        )->fetchAll();

        // Склади
        $warehouses = $this->db->query("SELECT id, name FROM warehouses ORDER BY name")->fetchAll();
        $whMap = array_column($warehouses, 'name', 'id');

        // Усі склади
        $allWhIds = array_unique(array_merge(
            array_keys($opening),
            array_filter(array_column($periodData, 'warehouse_from_id')),
            array_filter(array_column($periodData, 'warehouse_to_id'))
        ));

        $report = [];
        foreach ($allWhIds as $whId) {
            $row = [
                'warehouse_id' => $whId,
                'warehouse_name' => $whMap[$whId] ?? '—',
                'opening_balance' => $opening[$whId] ?? 0,
                'incoming' => 0,
                'outgoing' => 0,
                'details' => [],
            ];

            foreach ($periodData as $pd) {
                $isIn = $pd['warehouse_to_id'] == $whId;
                $isOut = $pd['warehouse_from_id'] == $whId;

                if ($isIn) {
                    $row['incoming'] += (float)$pd['quantity'];
                    $row['details'][] = [
                        'id' => $pd['id'],
                        'date' => $pd['movement_date'],
                        'type' => $pd['warehouse_from_id'] ? 'Від складу' : 'Прихід',
                        'counterpart' => $pd['warehouse_from_name'] ?? 'Ззовні',
                        'incoming' => (float)$pd['quantity'],
                        'outgoing' => 0,
                        'note' => $pd['note'],
                    ];
                }
                if ($isOut) {
                    $row['outgoing'] += (float)$pd['quantity'];
                    $row['details'][] = [
                        'id' => $pd['id'],
                        'date' => $pd['movement_date'],
                        'type' => $pd['warehouse_to_id'] ? 'На склад' : 'Списання',
                        'counterpart' => $pd['warehouse_to_name'] ?? 'Списання',
                        'incoming' => 0,
                        'outgoing' => (float)$pd['quantity'],
                        'note' => $pd['note'],
                    ];
                }
            }

            $row['closing_balance'] = $row['opening_balance'] + $row['incoming'] - $row['outgoing'];

            if ($row['opening_balance'] != 0 || $row['incoming'] != 0 || $row['outgoing'] != 0) {
                $report[] = $row;
            }
        }

        usort($report, fn($a, $b) => strcmp($a['warehouse_name'], $b['warehouse_name']));
        return $report;
    }
}
