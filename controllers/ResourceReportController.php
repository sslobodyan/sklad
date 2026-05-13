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
        to_log('=== ResourceReportController::index() START ===');
        
        $resourceTypeId = (int)$this->get('resource_type_id', 0);
        $dateFrom = $this->get('date_from', SettingsController::getDateFrom());
        $dateTo = $this->get('date_to', SettingsController::getDateTo());

        to_log('resourceTypeId: ' . $resourceTypeId);
        to_log('dateFrom: ' . $dateFrom);
        to_log('dateTo: ' . $dateTo);

        $types = $this->typesModel->getTypes();
        to_log('types count: ' . count($types));
        
        $reportData = null;
        $totalDelta = 0;
        $totalOpening = 0;
        $totalIncoming = 0;
        $totalConsumed = 0;
        $totalClosing = 0;

        if ($resourceTypeId > 0) {
            $warehouses = $this->ratesModel->getWarehousesByResourceType($resourceTypeId);
            $warehouseIds = array_column($warehouses, 'id');
            to_log('warehouseIds: ' . json_encode($warehouseIds));
            
            if (!empty($warehouseIds)) {
                $reportData = $this->reportModel->getDetailedReport(
                    $resourceTypeId,
                    $warehouseIds,
                    $dateFrom,
                    $dateTo
                );
                to_log('reportData count: ' . count($reportData));
                
                foreach ($reportData as $material) {
                    $totalDelta += $material['total_delta'];
                    $totalOpening += $material['total_opening'];
                    $totalIncoming += $material['total_incoming'];
                    $totalConsumed += $material['total_consumed'];
                    $totalClosing += $material['total_closing'];
                }
                to_log("Totals - delta:{$totalDelta}, opening:{$totalOpening}, incoming:{$totalIncoming}, consumed:{$totalConsumed}, closing:{$totalClosing}");
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
        
        to_log('=== ResourceReportController::index() END ===');
    }
}