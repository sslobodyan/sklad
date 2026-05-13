<?php
/**
 * Контролер для спрощеної сторінки заправника
 */

require_once __DIR__ . '/traits/SimpleConfigTrait.php';
require_once __DIR__ . '/traits/SimpleResponseTrait.php';
require_once __DIR__ . '/SimpleDataHelper.php';

class SimpleController extends Controller
{
    use SimpleConfigTrait;
    use SimpleResponseTrait;

    private MovementModel $movementModel;
    private WarehouseModel $warehouseModel;
    private MaterialModel $materialModel;
    private ConfigModel $configModel;
    private SimpleDataHelper $dataHelper;

    public function __construct(Database $db)
    {
        parent::__construct($db);
        $this->movementModel = new MovementModel($db);
        $this->warehouseModel = new WarehouseModel($db);
        $this->materialModel = new MaterialModel($db);
        $this->configModel = new ConfigModel($db);
        $this->dataHelper = new SimpleDataHelper($db);
    }

    public function index(): void
    {
        date_default_timezone_set('Europe/Kiev');
        
        $simpleWarehouseId = $this->getSimpleWarehouseId();
        
        if (!$simpleWarehouseId) {
            $this->renderSimpleError('Не налаштовано "тупий склад". Зверніться до адміністратора.');
            return;
        }

        $warehouse = $this->warehouseModel->getById($simpleWarehouseId);
        if (!$warehouse) {
            $this->renderSimpleError('Склад не знайдено. Зверніться до адміністратора.');
            return;
        }

        $allowedMaterialIds = $this->getSimpleMaterials();
        $materials = $this->getMaterialsByIds($allowedMaterialIds);
        $date = $this->get('date') ?: date('Y-m-d');
        $otherWarehouses = $this->getOtherWarehouses($simpleWarehouseId);

        $data = $this->dataHelper->getWarehouseData($simpleWarehouseId, $date, $allowedMaterialIds);

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

        $validationError = $this->validateMovement($materialId, $quantity, $date);
        if ($validationError) {
            $this->jsonResponse(['success' => false, 'error' => $validationError]);
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

        $validationError = $this->validateMovement($materialId, $quantity, $date);
        if ($validationError) {
            $this->jsonResponse(['success' => false, 'error' => $validationError]);
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
        
        if ($materialId) {
            $allowedMaterialIds = [$materialId];
        }
        
        $data = $this->dataHelper->getWarehouseData($simpleWarehouseId, $date, $allowedMaterialIds);

        $this->jsonResponse([
            'success' => true,
            'opening' => array_values($data['opening']),
            'movements' => array_values($data['movements']),
            'closing' => array_values($data['closing']),
        ]);
    }

    private function validateMovement(int $materialId, float $quantity, string $date): ?string
    {
        if (!$materialId) {
            return 'Оберіть матеріал';
        }
        if ($quantity <= 0) {
            return 'Вкажіть кількість більше 0';
        }
        return null;
    }


}