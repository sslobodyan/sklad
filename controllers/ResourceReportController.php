<?php
/**
 * Контролер звіту по використанню ресурсів
 */
class ResourceReportController extends Controller
{
    private ResourceTypesModel $typesModel;
    private ResourceRatesModel $ratesModel;
    private ResourceUsageReportModel $reportModel;

    public function __construct(Database $db)
    {
        parent::__construct($db);
        $this->typesModel = new ResourceTypesModel($db);
        $this->ratesModel = new ResourceRatesModel($db);
        $this->reportModel = new ResourceUsageReportModel($db);
    }

    public function index(): void
    {

    $resourceTypeId = (int)$this->get('resource_type_id', 0);
    
    // Якщо date_from/date_to не передані в URL, беремо з глобального діапазону
    $dateFrom = $this->get('date_from');
    $dateTo = $this->get('date_to');
    
    if (empty($dateFrom)) {
        $dateFrom = SettingsController::getDateFrom();
    }
    if (empty($dateTo)) {
        $dateTo = SettingsController::getDateTo();
    }
        
        $resourceTypeId = (int)$this->get('resource_type_id', 0);

        $types = $this->typesModel->getTypes();
        
        $reportData = null;
        $totalDelta = 0;
        $totalOpening = 0;
        $totalIncoming = 0;
        $totalConsumed = 0;
        $totalClosing = 0;

        if ($resourceTypeId > 0) {
            $warehouses = $this->ratesModel->getWarehousesByResourceType($resourceTypeId);
            $warehouseIds = array_column($warehouses, 'id');
            
            if (!empty($warehouseIds)) {
                $reportData = $this->reportModel->getDetailedReport(
                    $resourceTypeId,
                    $warehouseIds,
                    $dateFrom,
                    $dateTo
                );
                
                foreach ($reportData as $material) {
                    $totalDelta += $material['total_delta'];
                    $totalOpening += $material['total_opening'];
                    $totalIncoming += $material['total_incoming'];
                    $totalConsumed += $material['total_consumed'];
                    $totalClosing += $material['total_closing'];
                }
            }
        }

        $this->render('reports/resource_usage', [
            'title' => 'Звіт по використанню ресурсів',
            'types' => $types,
            'resourceTypeId' => $resourceTypeId,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'reportData' => $reportData,
            'totalDelta' => $totalDelta,
            'totalOpening' => $totalOpening,
            'totalIncoming' => $totalIncoming,
            'totalConsumed' => $totalConsumed,
            'totalClosing' => $totalClosing,
            'activePage' => 'reports',
        ]);
        
    }
}