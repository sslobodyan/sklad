<?php

class ReportMaterialController extends Controller
{
    use AuthorizeTrait;
    
    private MovementModel $movementModel;
    private MaterialModel $materialModel;
    private WarehouseModel $warehouseModel;

    public function __construct(Database $db)
    {
        parent::__construct($db);
        $this->movementModel = new MovementModel($db);
        $this->materialModel = new MaterialModel($db);
        $this->warehouseModel = new WarehouseModel($db);
    }

    public function index(): void
    {
        $this->checkAccess('index');
        
        $materialId = (int)$this->get('material_id', 0);
        $dateFrom = $this->get('date_from') ?: SettingsController::getDateFrom();
        $dateTo = $this->get('date_to') ?: SettingsController::getDateTo();

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
            
            if (!empty($warehouseIds)) {
                $report = array_values(array_filter($report, function($row) use ($warehouseIds) {
                    return in_array((int)$row['warehouse_id'], $warehouseIds);
                }));
            }
        }

        $materials = $this->materialModel->getAll('name ASC');
        $materials = $this->filterMaterials($materials);
        
        $warehouses = $this->warehouseModel->getAll('name ASC');
        $warehouses = $this->filterWarehouses($warehouses);

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
}