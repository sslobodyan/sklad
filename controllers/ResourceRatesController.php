<?php
/**
 * Контролер норм списання ресурсів
 */
class ResourceRatesController extends Controller
{
    private ResourceModel $model;
    private WarehouseModel $warehouseModel;
    private MaterialModel $materialModel;

    public function __construct(Database $db)
    {
        parent::__construct($db);
        $this->model = new ResourceModel($db);
        $this->warehouseModel = new WarehouseModel($db);
        $this->materialModel = new MaterialModel($db);
    }

    public function rates(): void
    {
        $warehouseId = (int)$this->get('warehouse_id', 0);
        $resourceTypeId = (int)$this->get('resource_type_id', 0);

        $whWithRes = $this->model->getWarehousesWithResources();
        $types = $this->model->getTypes();
        $warehouses = $this->warehouseModel->getAll('name ASC');
        $materials = $this->materialModel->getAll('name ASC');

        $warehouseResources = [];
        $rates = [];
        $selectedWarehouse = null;

        if ($warehouseId) {
            $selectedWarehouse = $this->warehouseModel->getById($warehouseId);
            $warehouseResources = $this->model->getWarehouseResources($warehouseId);

            if ($resourceTypeId) {
                $rates = $this->model->getRates($warehouseId, $resourceTypeId);
            }
        }

        $this->render('resources/rates', [
            'title' => 'Норми списання',
            'warehousesWithResources' => $whWithRes,
            'types' => $types,
            'warehouses' => $warehouses,
            'materials' => $materials,
            'warehouseId' => $warehouseId,
            'resourceTypeId' => $resourceTypeId,
            'selectedWarehouse' => $selectedWarehouse,
            'warehouseResources' => $warehouseResources,
            'rates' => $rates,
            'activePage' => 'resource-rates',
        ]);
    }

    public function addresource(): void
    {
        if (!$this->isPost()) {
            $this->redirect('resources/rates');
            return;
        }
        
        $whId = (int)$this->post('warehouse_id');
        $rtId = (int)$this->post('resource_type_id');
        
        if ($whId && $rtId) {
            $this->model->addWarehouseResource($whId, $rtId);
            $this->flash('success', 'Ресурс прив\'язано до складу');
        }
        $this->redirect('resources/rates?warehouse_id=' . $whId);
    }

    public function removeresource(): void
    {
        if (!$this->isPost()) {
            $this->redirect('resources/rates');
            return;
        }
        
        $whId = (int)$this->post('warehouse_id');
        $rtId = (int)$this->post('resource_type_id');
        
        if ($whId && $rtId) {
            $this->model->removeWarehouseResource($whId, $rtId);
            $this->flash('success', 'Ресурс видалено зі складу');
        }
        $this->redirect('resources/rates?warehouse_id=' . $whId);
    }

    public function saverate(): void
    {
        if (!$this->isPost()) {
            $this->redirect('resources/rates');
            return;
        }
        
        $whId = (int)$this->post('warehouse_id');
        $rtId = (int)$this->post('resource_type_id');
        $matId = (int)$this->post('material_id');
        $rate = (float)$this->post('rate');
        $srcWhId = (int)$this->post('source_warehouse_id') ?: null;
        $spreadByDay = !empty($this->post('spread_by_day'));

        if ($whId && $rtId && $matId && $rate > 0) {
            $this->model->saveRate($whId, $rtId, $matId, $rate, $srcWhId, $spreadByDay);
            $this->respondAjax(true, 'Норму збережено');
        } else {
            $this->respondAjax(false, 'Заповніть усі поля');
        }
    }

    public function deleterate($id): void
    {
        $this->model->deleteRate((int)$id);
        $this->flash('success', 'Норму видалено');
        $referer = $_SERVER['HTTP_REFERER'] ?? BASE_PATH . '/resources/rates';
        header('Location: ' . $referer);
        exit;
    }

    private function respondAjax(bool $success, string $message): void
    {
        if ($this->isAjax()) {
            $this->json(['success' => $success, $success ? 'message' : 'error' => $message]);
        } else {
            $this->flash($success ? 'success' : 'error', $message);
        }
    }

    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}