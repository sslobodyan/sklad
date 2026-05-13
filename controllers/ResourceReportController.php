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

to_log('function __construct in ResourceReportController.php');

    }

    public function index(): void
    {

to_log('function index() in ResourceUsageReportController.php');

        $resourceTypeId = (int)$this->get('resource_type_id', 0);
        $dateFrom = $this->get('date_from', SettingsController::getDateFrom());
        $dateTo = $this->get('date_to', SettingsController::getDateTo());

        $types = $this->typesModel->getTypes();
        
        $reportData = null;
        $totalDelta = 0;
        $totalOpening = 0;
        $totalIncoming = 0;
        $totalConsumed = 0;
        $totalClosing = 0;

        if ($resourceTypeId > 0) {
            // Отримуємо всі склади, що мають норми для цього типу ресурсу
            $warehouses = $this->ratesModel->getWarehousesByResourceType($resourceTypeId);
            $warehouseIds = array_column($warehouses, 'id');



// Після отримання warehouseIds, перед викликом getDetailedReport, додайте:

to_log('=== ResourceUsageReport Debug ===');
to_log('resource_type_id: ' . $resourceTypeId);
to_log('date_from: ' . $dateFrom);
to_log('date_to: ' . $dateTo);
to_log('warehouseIds: ' . json_encode($warehouseIds));

// Перевіряємо чи є resource_types
$typesCheck = $this->db->query("SELECT * FROM resource_types")->fetchAll();
to_log('resource_types count: ' . count($typesCheck));

// Перевіряємо чи є resource_logs
$logsCheck = $this->db->query(
    "SELECT COUNT(*) as cnt FROM resource_logs WHERE resource_type_id = ? AND log_date BETWEEN ? AND ?",
    [$resourceTypeId, $dateFrom, $dateTo]
)->fetch();
to_log('resource_logs count for period: ' . ($logsCheck['cnt'] ?? 0));

// Перевіряємо чи є warehouse_resources
$whResCheck = $this->db->query(
    "SELECT COUNT(*) as cnt FROM warehouse_resources WHERE resource_type_id = ?",
    [$resourceTypeId]
)->fetch();
to_log('warehouse_resources count: ' . ($whResCheck['cnt'] ?? 0));

// Перевіряємо чи є resource_rates
$ratesCheck = $this->db->query(
    "SELECT COUNT(*) as cnt FROM resource_rates WHERE resource_type_id = ?",
    [$resourceTypeId]
)->fetch();
to_log('resource_rates count: ' . ($ratesCheck['cnt'] ?? 0));



            
            if (!empty($warehouseIds)) {
                $reportData = $this->reportModel->getDetailedReport(
                    $resourceTypeId,
                    $warehouseIds,
                    $dateFrom,
                    $dateTo
                );
                
                // Підрахунок загальних підсумків
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