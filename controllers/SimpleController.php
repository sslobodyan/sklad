<?php
/**
 * Контролер для спрощеної сторінки заправника
 * Без меню, мінімальний функціонал, заточено під телефон
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

    /**
     * Головна сторінка заправника
     */
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

        // Дозволені матеріали
        $allowedMaterialIds = $this->getSimpleMaterials();
        $materials = $this->getMaterialsByIds($allowedMaterialIds);

        // Всі склади крім тупого (для вибору куди видати)
        $allWarehouses = $this->warehouseModel->getAll('name ASC');
        $otherWarehouses = array_filter($allWarehouses, fn($w) => $w['id'] != $simpleWarehouseId);

        // Дата
        $date = $this->get('date') ?: date('Y-m-d');

        // Рухи за дату
        $movements = $this->getMovementsForDate($simpleWarehouseId, $date);

        $this->renderSimple('simple/index', [
            'warehouse' => $warehouse,
            'materials' => $materials,
            'otherWarehouses' => $otherWarehouses,
            'date' => $date,
            'movements' => $movements,
        ]);
    }

    /**
     * Зберегти надходження (ззовні -> тупий склад)
     */
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

        // Валідація
        if (!$materialId) {
            $this->jsonResponse(['success' => false, 'error' => 'Оберіть матеріал']);
            return;
        }
        if ($quantity <= 0) {
            $this->jsonResponse(['success' => false, 'error' => 'Вкажіть кількість більше 0']);
            return;
        }

        // Перевірка що матеріал дозволений
        $allowedMaterials = $this->getSimpleMaterials();
        if (!empty($allowedMaterials) && !in_array($materialId, $allowedMaterials)) {
            $this->jsonResponse(['success' => false, 'error' => 'Цей матеріал не дозволено']);
            return;
        }

        // Перевірка закритого періоду
        if ($this->configModel->isDateClosed($date)) {
            $this->jsonResponse(['success' => false, 'error' => 'Дата в закритому періоді']);
            return;
        }

        try {
            $this->movementModel->create([
                'movement_date' => $date,
                'warehouse_from_id' => null,  // Ззовні
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

    /**
     * Зберегти видачу (тупий склад -> інший склад)
     */
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

        // Валідація
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

        // Перевірка що матеріал дозволений
        $allowedMaterials = $this->getSimpleMaterials();
        if (!empty($allowedMaterials) && !in_array($materialId, $allowedMaterials)) {
            $this->jsonResponse(['success' => false, 'error' => 'Цей матеріал не дозволено']);
            return;
        }

        // Перевірка закритого періоду
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

    /**
     * Отримати рухи за дату (AJAX)
     */
    public function movements(): void
    {
        $simpleWarehouseId = $this->getSimpleWarehouseId();
        if (!$simpleWarehouseId) {
            $this->jsonResponse(['success' => false, 'error' => 'Склад не налаштовано']);
            return;
        }

        $date = $this->get('date') ?: date('Y-m-d');
        $movements = $this->getMovementsForDate($simpleWarehouseId, $date);

        $this->jsonResponse([
            'success' => true,
            'movements' => $movements,
            'date' => $date,
        ]);
    }

    /**
     * Отримати ID тупого складу з конфігурації
     */
    private function getSimpleWarehouseId(): ?int
    {
        $value = $this->configModel->getValue('simple_warehouse');
        return $value ? (int)$value : null;
    }

    /**
     * Отримати масив дозволених матеріалів
     */
    private function getSimpleMaterials(): array
    {
        $value = $this->configModel->getValue('simple_materials');
        if (!$value) return [];
        
        $decoded = json_decode($value, true);
        return is_array($decoded) ? array_map('intval', $decoded) : [];
    }

    /**
     * Отримати матеріали за ID
     */
    private function getMaterialsByIds(array $ids): array
    {
        if (empty($ids)) {
            // Якщо не вказані — повернути всі
            return $this->materialModel->getAll('name ASC');
        }

        $all = $this->materialModel->getAll('name ASC');
        return array_filter($all, fn($m) => in_array($m['id'], $ids));
    }

    /**
     * Отримати рухи по складу за дату
     */
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

    /**
     * Рендер без стандартного layout (спрощений)
     */
    private function renderSimple(string $view, array $data = []): void
    {
        extract($data);
        require ROOT_PATH . '/views/' . $view . '.php';
    }

    /**
     * JSON відповідь
     */
    private function jsonResponse(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
