<?php

class ReportResourceController extends Controller
{
    use AuthorizeTrait;
    
    private ResourceModel $resourceModel;

    public function __construct(Database $db)
    {
        parent::__construct($db);
        $this->resourceModel = new ResourceModel($db);
    }

    public function index(): void
    {
        $this->checkAccess('index');
        
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
        $types = $this->resourceModel->getTypes();

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