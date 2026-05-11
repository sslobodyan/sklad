<?php
/**
 * Контролер для спрощеної сторінки заправника
 */

class SimpleController extends Controller
{
    private MovementModel $movementModel;
    private WarehouseModel $warehouseModel;
    private MaterialModel $materialModel;
    private ConfigModel $configModel;

    public function __construct(Database $db)
    {
        parent::__construct($db);
        $this->movementModel = new MovementModel($db);
        $this->warehouseModel = new WarehouseModel($db);
        $this->materialModel = new MaterialModel($db);
        $this->configModel = new ConfigModel($db);
    }

    public function index(): void
    {
        $simpleWarehouseId = $this->getSimpleWarehouseId();
        
        if (!$simpleWarehouseId) {
            $this->renderSimple('simple/error', [
                'error' => 'Не налаштовано "тупий склад". Зверніться до адміністратора.'
            ]);
            return;
        }

        $warehouse = $this->warehouseModel->getById($simpleWarehouseId);
        if (!$warehouse) {
            $this->renderSimple('simple/error', [
                'error' => 'Склад не знайдено. Зверніться до адміністратора.'
            ]);
            return;
        }

        $allowedMaterialIds = $this->getSimpleMaterials();
        $materials = $this->getMaterialsByIds($allowedMaterialIds);

        $date = $this->get('date') ?: date('Y-m-d');

        // Всі склади крім тупого (для вибору куди видати)
        $otherWarehouses = $this->db->query(
            "SELECT id, name FROM warehouses WHERE id != ? ORDER BY name ASC",
            [$simpleWarehouseId]
        )->fetchAll();

        // Розрахунок залишків та рухів
        $data = $this->getWarehouseData($simpleWarehouseId, $date, $allowedMaterialIds);

        $this->renderSimple('simple/index', [
            'warehouse' => $warehouse,
            'materials' => $materials,
            'otherWarehouses' => $otherWarehouses,
            'date' => $date,
            'openingBalances' => $data['opening'],
            'movements' => $data['movements'],
            'closingBalances' => $data['closing'],
        ]);
    }

    /**
     * Отримати дані по складу: залишки та рухи
     */
    private function getWarehouseData(int $warehouseId, string $date, array $materialIds): array
    {
        $db = $this->db;
        
        // Вхідні залишки на початок дня
        $openingQuery = "
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
            $openingQuery .= " AND mv.material_id IN (" . implode(',', array_fill(0, count($materialIds), '?')) . ")";
            $params = array_merge($params, $materialIds);
        }
        
        $openingQuery .= " GROUP BY m.id, m.name ORDER BY m.name";
        
        $openingRows = $db->query($openingQuery, $params)->fetchAll();
        $opening = [];
        foreach ($openingRows as $row) {
            $opening[$row['material_id']] = [
                'material_id' => $row['material_id'],
                'material_name' => $row['material_name'],
                'balance' => (float)$row['opening_balance']
            ];
        }

        // Рухи за день (детально, кожен рух окремим рядком)
        $movementQuery = "
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
        $movementParams = [$warehouseId, $warehouseId, $date, $warehouseId, $warehouseId];
        
        if (!empty($materialIds)) {
            $movementQuery .= " AND mv.material_id IN (" . implode(',', array_fill(0, count($materialIds), '?')) . ")";
            $movementParams = array_merge($movementParams, $materialIds);
        }
        
        $movementQuery .= " ORDER BY m.name, mv.id";
        
        $movementRows = $db->query($movementQuery, $movementParams)->fetchAll();
        
        // Кожен рух окремим записом
        $movements = [];
        foreach ($movementRows as $row) {
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

        // Вихідні залишки (агрегуємо рухи по матеріалах для розрахунку)
        $movementsAggregated = [];
        foreach ($movements as $mv) {
            $matId = $mv['material_id'];
            if (!isset($movementsAggregated[$matId])) {
                $movementsAggregated[$matId] = ['incoming' => 0, 'outgoing' => 0];
            }
            $movementsAggregated[$matId]['incoming'] += $mv['incoming'];
            $movementsAggregated[$matId]['outgoing'] += $mv['outgoing'];
        }
        
        $closing = [];
        $allMaterialIds = array_unique(array_merge(
            array_keys($opening),
            array_keys($movementsAggregated)
        ));
        
        foreach ($allMaterialIds as $matId) {
            $openingBalance = $opening[$matId]['balance'] ?? 0;
            $incoming = $movementsAggregated[$matId]['incoming'] ?? 0;
            $outgoing = $movementsAggregated[$matId]['outgoing'] ?? 0;
            
            $closing[$matId] = [
                'material_id' => $matId,
                'material_name' => $opening[$matId]['material_name'] ?? ($movements[0]['material_name'] ?? ''),
                'balance' => $openingBalance + $incoming - $outgoing
            ];
        }

        // Сортування за назвою
        uasort($closing, fn($a, $b) => strcmp($a['material_name'], $b['material_name']));

        return [
            'opening' => $opening,
            'movements' => $movements,
            'closing' => $closing
        ];
    }

    public function incoming(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'error' => 'Невірний метод']);
            return;
        }

        $simpleWarehouseId = $this->getSimpleWarehouseId();
        if (!$simpleWarehouseId) {
            $this->jsonResponse(['success' => false, 'error' => 'Склад не налаштовано']);
            return;
        }

        $date = $this->post('date') ?: date('Y-m-d');
        $materialId = (int)$this->post('material_id');
        $quantity = (float)$this->post('quantity');

        if (!$materialId) {
            $this->jsonResponse(['success' => false, 'error' => 'Оберіть матеріал']);
            return;
        }
        if ($quantity <= 0) {
            $this->jsonResponse(['success' => false, 'error' => 'Вкажіть кількість більше 0']);
            return;
        }

        $allowedMaterials = $this->getSimpleMaterials();
        if (!empty($allowedMaterials) && !in_array($materialId, $allowedMaterials)) {
            $this->jsonResponse(['success' => false, 'error' => 'Цей матеріал не дозволено']);
            return;
        }

        if ($this->configModel->isDateClosed($date)) {
            $this->jsonResponse(['success' => false, 'error' => 'Дата в закритому періоді']);
            return;
        }

        try {
            $this->movementModel->create([
                'movement_date' => $date,
                'warehouse_from_id' => null,
                'warehouse_to_id' => $simpleWarehouseId,
                'material_id' => $materialId,
                'quantity' => $quantity,
                'note' => 'Надходження (заправка)',
            ]);

            $this->jsonResponse(['success' => true]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => 'Помилка збереження']);
        }
    }

    public function outgoing(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'error' => 'Невірний метод']);
            return;
        }

        $simpleWarehouseId = $this->getSimpleWarehouseId();
        if (!$simpleWarehouseId) {
            $this->jsonResponse(['success' => false, 'error' => 'Склад не налаштовано']);
            return;
        }

        $date = $this->post('date') ?: date('Y-m-d');
        $materialId = (int)$this->post('material_id');
        $targetWarehouseId = (int)$this->post('target_warehouse_id');
        $quantity = (float)$this->post('quantity');

        if (!$materialId) {
            $this->jsonResponse(['success' => false, 'error' => 'Оберіть матеріал']);
            return;
        }
        if (!$targetWarehouseId) {
            $this->jsonResponse(['success' => false, 'error' => 'Оберіть куди видати']);
            return;
        }
        if ($targetWarehouseId == $simpleWarehouseId) {
            $this->jsonResponse(['success' => false, 'error' => 'Не можна видати на свій склад']);
            return;
        }
        if ($quantity <= 0) {
            $this->jsonResponse(['success' => false, 'error' => 'Вкажіть кількість більше 0']);
            return;
        }

        $allowedMaterials = $this->getSimpleMaterials();
        if (!empty($allowedMaterials) && !in_array($materialId, $allowedMaterials)) {
            $this->jsonResponse(['success' => false, 'error' => 'Цей матеріал не дозволено']);
            return;
        }

        if ($this->configModel->isDateClosed($date)) {
            $this->jsonResponse(['success' => false, 'error' => 'Дата в закритому періоді']);
            return;
        }

        try {
            $this->movementModel->create([
                'movement_date' => $date,
                'warehouse_from_id' => $simpleWarehouseId,
                'warehouse_to_id' => $targetWarehouseId,
                'material_id' => $materialId,
                'quantity' => $quantity,
                'note' => 'Видача (заправка)',
            ]);

            $this->jsonResponse(['success' => true]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => 'Помилка збереження']);
        }
    }

    public function movements(): void
    {
        $simpleWarehouseId = $this->getSimpleWarehouseId();
        if (!$simpleWarehouseId) {
            $this->jsonResponse(['success' => false, 'error' => 'Склад не налаштовано']);
            return;
        }

        $date = $this->get('date') ?: date('Y-m-d');
        $materialId = $this->get('material_id') ? (int)$this->get('material_id') : null;
        $allowedMaterialIds = $this->getSimpleMaterials();
        
        // Фільтр по матеріалу якщо вказано
        if ($materialId) {
            $allowedMaterialIds = [$materialId];
        }
        
        $data = $this->getWarehouseData($simpleWarehouseId, $date, $allowedMaterialIds);

        $this->jsonResponse([
            'success' => true,
            'opening' => array_values($data['opening']),
            'movements' => array_values($data['movements']),
            'closing' => array_values($data['closing']),
        ]);
    }

    private function getSimpleWarehouseId(): ?int
    {
        $value = $this->configModel->getValue('simple_warehouse');
        return $value ? (int)$value : null;
    }

    private function getSimpleMaterials(): array
    {
        $value = $this->configModel->getValue('simple_materials');
        if (!$value) return [];
        
        $decoded = json_decode($value, true);
        return is_array($decoded) ? array_map('intval', $decoded) : [];
    }

    private function getMaterialsByIds(array $ids): array
    {
        if (empty($ids)) {
            return $this->materialModel->getAll('name ASC');
        }

        $all = $this->materialModel->getAll('name ASC');
        return array_filter($all, fn($m) => in_array($m['id'], $ids));
    }

    private function getMovementsForDate(int $warehouseId, string $date): array
    {
        return $this->db->query(
            "SELECT m.*,
                    wf.name AS warehouse_from_name,
                    wt.name AS warehouse_to_name,
                    mat.name AS material_name
             FROM movements m
             LEFT JOIN warehouses wf ON m.warehouse_from_id = wf.id
             LEFT JOIN warehouses wt ON m.warehouse_to_id = wt.id
             JOIN materials mat ON m.material_id = mat.id
             WHERE (m.warehouse_from_id = ? OR m.warehouse_to_id = ?)
               AND m.movement_date = ?
             ORDER BY m.id DESC",
            [$warehouseId, $warehouseId, $date]
        )->fetchAll();
    }

    private function renderSimple(string $view, array $data = []): void
    {
        extract($data);
        require ROOT_PATH . '/views/' . $view . '.php';
    }

    private function jsonResponse(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
