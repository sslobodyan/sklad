<?php
/**
 * Контролер звітів
 */

class ReportsController extends Controller
{
    private MovementModel $movementModel;
    private WarehouseModel $warehouseModel;
    private MaterialModel $materialModel;
    private ResourceModel $resourceModel;

    public function __construct(Database $db)
    {
        parent::__construct($db);
        $this->movementModel = new MovementModel($db);
        $this->warehouseModel = new WarehouseModel($db);
        $this->materialModel = new MaterialModel($db);
        $this->resourceModel = new ResourceModel($db);
    }

    /**
     * Звіт по складу
     */
    public function warehouse(): void
    {
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

    /**
     * Звіт по матеріалу
     */
    public function material(): void
    {
        $materialId = (int)$this->get('material_id', 0);
        $dateFrom = $this->get('date_from') ?: SettingsController::getDateFrom();
        $dateTo = $this->get('date_to') ?: SettingsController::getDateTo();

        // Множинний вибір складів
        $warehouseIds = $this->get('wh');
        if (is_string($warehouseIds)) {
            $warehouseIds = array_filter(explode(',', $warehouseIds));
        }
        if (!is_array($warehouseIds)) {
            $warehouseIds = [];
        }
        $warehouseIds = array_map('intval', $warehouseIds);

        $report = [];
        $selectedMaterial = null;
        
        if ($materialId) {
            $report = $this->movementModel->reportByMaterial($materialId, $dateFrom, $dateTo);
            $selectedMaterial = $this->materialModel->getById($materialId);
            
            // Фільтр по складах (після побудови звіту)
            if (!empty($warehouseIds)) {
                $report = array_values(array_filter($report, function($row) use ($warehouseIds) {
                    return in_array((int)$row['warehouse_id'], $warehouseIds);
                }));
            }
        }

        $materials = $this->materialModel->getAll('name ASC');
        $warehouses = $this->warehouseModel->getAll('name ASC');

        $this->render('reports/material', [
            'title' => 'Звіт по матеріалу',
            'report' => $report,
            'materials' => $materials,
            'warehouses' => $warehouses,
            'materialId' => $materialId,
            'selectedMaterial' => $selectedMaterial,
            'selectedWarehouseIds' => $warehouseIds,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'activePage' => 'report-material',
        ]);
    }

    /**
     * Звіт про витрачання ресурсів (детальний)
     */
    public function resource(): void
    {
        $dateFrom = $this->get('date_from') ?: SettingsController::getDateFrom();
        $dateTo   = $this->get('date_to')   ?: SettingsController::getDateTo();

        $warehouseIds = $this->get('warehouse_ids', '');
        if (is_string($warehouseIds)) {
            $warehouseIds = array_values(array_filter(array_map('intval', explode(',', $warehouseIds))));
        }
        if (!is_array($warehouseIds)) $warehouseIds = [];

        $resourceTypeId = (int)$this->get('resource_type_id', 0);

        $report       = [];
        $selectedType = null;

        if ($resourceTypeId) {
            $report       = $this->resourceModel->getResourceUsageReportDetailed($dateFrom, $dateTo, $warehouseIds, $resourceTypeId);
            $selectedType = $this->resourceModel->getTypeById($resourceTypeId);
        }

        $warehouses = $this->resourceModel->getWarehousesWithResources();
        $types      = $this->resourceModel->getTypes();

        $this->render('reports/resource_usage', [
            'title'               => 'Звіт по ресурсу',
            'report'              => $report,
            'warehouses'          => $warehouses,
            'types'               => $types,
            'selectedWarehouseIds'=> $warehouseIds,
            'resourceTypeId'      => $resourceTypeId,
            'selectedType'        => $selectedType,
            'dateFrom'            => $dateFrom,
            'dateTo'              => $dateTo,
            'activePage'          => 'report-resource',
        ]);
    }
}
