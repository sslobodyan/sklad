<?php
/**
 * Модель ресурсів — фасад, що об'єднує всі підмоделі
 */

require_once __DIR__ . '/traits/ResourceFormatTrait.php';
require_once __DIR__ . '/traits/ResourceChainTrait.php';
require_once __DIR__ . '/ResourceTypesModel.php';
require_once __DIR__ . '/ResourceRatesModel.php';
require_once __DIR__ . '/ResourceLogsModel.php';
require_once __DIR__ . '/ResourceReportModel.php';

class ResourceModel extends Model
{
    use ResourceFormatTrait;

    private ResourceTypesModel $typesModel;
    private ResourceRatesModel $ratesModel;
    private ResourceLogsModel $logsModel;
    private ResourceReportModel $reportModel;

    public function __construct(Database $db)
    {
        parent::__construct($db);
        $this->typesModel = new ResourceTypesModel($db);
        $this->ratesModel = new ResourceRatesModel($db);
        $this->logsModel = new ResourceLogsModel($db);
        $this->reportModel = new ResourceReportModel($db);
    }

    // Делегування методів типів
    public function getTypes(): array { return $this->typesModel->getTypes(); }
    public function getTypeById(int $id): ?array { return $this->typesModel->getTypeById($id); }
    public function createType(string $name, string $unit, string $format = 'int'): int { return $this->typesModel->createType($name, $unit, $format); }
    public function updateType(int $id, string $name, string $unit, string $format = 'int'): void { $this->typesModel->updateType($id, $name, $unit, $format); }
    public function deleteType(int $id): bool { return $this->typesModel->deleteType($id); }
    public function isTypeUsed(int $id): bool { return $this->typesModel->isTypeUsed($id); }

    // Делегування методів норм
    public function getRates(int $warehouseId, int $resourceTypeId): array { return $this->ratesModel->getRates($warehouseId, $resourceTypeId); }
    public function saveRate(int $warehouseId, int $resourceTypeId, int $materialId, float $rate, ?int $sourceWarehouseId, bool $spreadByDay = false): void { $this->ratesModel->saveRate($warehouseId, $resourceTypeId, $materialId, $rate, $sourceWarehouseId, $spreadByDay); }
    public function deleteRate(int $id): void { $this->ratesModel->deleteRate($id); }
    public function getWarehouseResources(int $warehouseId): array { return $this->ratesModel->getWarehouseResources($warehouseId); }
    public function addWarehouseResource(int $warehouseId, int $resourceTypeId): void { $this->ratesModel->addWarehouseResource($warehouseId, $resourceTypeId); }
    public function removeWarehouseResource(int $warehouseId, int $resourceTypeId): void { $this->ratesModel->removeWarehouseResource($warehouseId, $resourceTypeId); }
    public function getWarehousesWithResources(): array { return $this->ratesModel->getWarehousesWithResources(); }

    // Делегування методів журналу
    public function getLastReading(int $warehouseId, int $resourceTypeId): ?array { return $this->logsModel->getLastReading($warehouseId, $resourceTypeId); }
    public function getLogs(array $filters = []): array { return $this->logsModel->getLogs($filters); }
    public function getLogById(int $id): ?array { return $this->logsModel->getLogById($id); }
    public function addReading(int $warehouseId, int $resourceTypeId, string $date, float $reading, string $note, float $correctionPct, MovementModel $movementModel): array { return $this->logsModel->addReading($warehouseId, $resourceTypeId, $date, $reading, $note, $correctionPct, $movementModel); }
    public function updateReading(int $logId, string $date, float $reading, string $note, float $correctionPct, MovementModel $movementModel): array { return $this->logsModel->updateReading($logId, $date, $reading, $note, $correctionPct, $movementModel); }
    public function deleteLogAndRecalculate(int $id, MovementModel $movementModel): void { $this->logsModel->deleteLogAndRecalculate($id, $movementModel); }

    // Делегування методів звітів
    public function getResourceUsageReportDetailed(string $dateFrom, string $dateTo, array $warehouseIds, int $resourceTypeId): array { return $this->reportModel->getResourceUsageReportDetailed($dateFrom, $dateTo, $warehouseIds, $resourceTypeId); }
    public function getResourceUsageReport(string $dateFrom, string $dateTo, array $warehouseIds, int $resourceTypeId): array { return $this->getResourceUsageReportDetailed($dateFrom, $dateTo, $warehouseIds, $resourceTypeId); }
}