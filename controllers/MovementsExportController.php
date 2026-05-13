<?php
/**
 * Контролер експорту та друку руху матеріалів
 */

require_once __DIR__ . '/../helpers/XlsxGeneratorHelper.php';

class MovementsExportController extends Controller
{
    private MovementModel $model;

    public function __construct(Database $db)
    {
        parent::__construct($db);
        $this->model = new MovementModel($db);
    }

    public function export(): void
    {
        $filters = [
            'date_from' => $this->get('date_from'),
            'date_to' => $this->get('date_to'),
            'warehouse_id' => $this->get('warehouse_id'),
            'material_id' => $this->get('material_id'),
        ];
        
        $movements = $this->model->getAllWithNames($filters);

        $parts = ['Рух_матеріалів'];
        if (!empty($filters['date_from'])) $parts[] = 'від_' . $filters['date_from'];
        if (!empty($filters['date_to'])) $parts[] = 'до_' . $filters['date_to'];
        $filename = implode('_', $parts) . '.xlsx';

        $xlsx = XlsxGeneratorHelper::generate($movements);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($xlsx));
        header('Cache-Control: max-age=0');
        echo $xlsx;
        exit;
    }

    private function get(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }
}