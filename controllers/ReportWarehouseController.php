<?php

class ReportWarehouseController extends Controller
{
    use AuthorizeTrait;
    
    private MovementModel $movementModel;
    private WarehouseModel $warehouseModel;

    public function __construct(Database $db)
    {
        parent::__construct($db);
        $this->movementModel = new MovementModel($db);
        $this->warehouseModel = new WarehouseModel($db);
    }

    public function index(): void
    {
        $this->checkAccess('index');
        
        $warehouseId = (int)$this->get('warehouse_id', 0);
        $dateFrom = $this->get('date_from') ?: SettingsController::getDateFrom();
        $dateTo = $this->get('date_to') ?: SettingsController::getDateTo();

        $report = [];
        $selectedWarehouse = null;
        
        if ($warehouseId) {
            $report = $this->movementModel->reportByWarehouse($warehouseId, $dateFrom, $dateTo);
            $selectedWarehouse = $this->warehouseModel->getById($warehouseId);
        }

        $warehouses = $this->warehouseModel->getAll('name ASC');
        $warehouses = $this->filterWarehouses($warehouses);

        $this->render('reports/warehouse', [
            'title' => 'Звіт по складу',
            'report' => $report,
            'warehouses' => $warehouses,
            'warehouseId' => $warehouseId,
            'selectedWarehouse' => $selectedWarehouse,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'activePage' => 'report-warehouse',
        ]);
    }
}