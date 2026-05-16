<?php
/**
 * Контролер ресурсів — основний (журнал показників)
 */

require_once __DIR__ . '/traits/ResourceValidationTrait.php';
require_once __DIR__ . '/traits/ResourceContextTrait.php';

class ResourcesController extends Controller
{
    use ResourceValidationTrait;
    use ResourceContextTrait;

    private ResourceModel $model;
    private MovementModel $movementModel;

    public function __construct(Database $db)
    {
        parent::__construct($db);
        $this->model = new ResourceModel($db);
        $this->movementModel = new MovementModel($db);
    }

    public function index(): void
    {
        $highlightId = $this->get('highlight');
        
        $filters = [
            'warehouse_id' => $this->get('warehouse_id'),
            'resource_type_id' => $this->get('resource_type_id'),
            'date_from' => $this->get('date_from'),
            'date_to' => $this->get('date_to'),
        ];

        if ($highlightId) {
            $filters = ['warehouse_id' => '', 'resource_type_id' => '', 'date_from' => '', 'date_to' => ''];
        }

        $logs = $this->model->getLogs($filters);
        $whWithRes = $this->model->getWarehousesWithResources();
        $types = $this->model->getTypes();

        $prevDates = [];
        foreach ($logs as $l) {
            $prev = $this->db->query(
                "SELECT log_date FROM resource_logs 
                 WHERE warehouse_id = ? AND resource_type_id = ? AND id < ? 
                 ORDER BY log_date DESC, id DESC LIMIT 1",
                [$l['warehouse_id'], $l['resource_type_id'], $l['id']]
            )->fetch();
            if ($prev) {
                $d = new \DateTime($prev['log_date']);
                $d->modify('+1 day');
                $prevDates[$l['id']] = $d->format('Y-m-d');
            }
        }

        $this->render('resources/index', [
            'title' => 'Витрата ресурсів',
            'logs' => $logs,
            'warehousesWithResources' => $whWithRes,
            'types' => $types,
            'filters' => $filters,
            'highlightId' => $highlightId,
            'prevDates' => $prevDates,
            'activePage' => 'resources',
        ]);
    }

    public function add(): void
    {
        if (!$this->isPost()) {
            $this->redirect('resources');
            return;
        }

        $whId = (int)$this->post('warehouse_id');
        $rtId = (int)$this->post('resource_type_id');
        $date = $this->post('log_date');
        $reading = (float)$this->post('reading');
        $note = $this->post('note', '');
        $correctionPct = (float)$this->post('correction_pct', 0);
        
        $type = $this->model->getTypeById($rtId);

        if (!$type) {
            $this->respondAjax(false, 'Невірний тип ресурсу');
            return;
        }

        $formatError = $this->validateReadingFormat($reading, $type['format'] ?? 'dec2');
        if ($formatError) {
            $this->respondAjax(false, $formatError);
            return;
        }
        
        $reading = $this->normalizeReading($reading, $type['format'] ?? 'dec2');

        if (!$whId || !$rtId || !$date || $reading < 0) {
            $this->respondAjax(false, 'Заповніть усі поля');
            return;
        }

        $config = new ConfigModel($this->db);
        if ($config->isDateClosed($date)) {
            $this->respondAjax(false, 'Дата потрапляє в закритий період');
            return;
        }

        $seqError = $this->validateReadingSequence($whId, $rtId, $date, $reading);
        if ($seqError) {
            $this->respondAjax(false, $seqError);
            return;
        }

        $result = $this->model->addReading($whId, $rtId, $date, $reading, $note, $correctionPct, $this->movementModel);

        if ($result['success']) {
            $msg = $this->buildSuccessMessage($result, $reading, $type['unit'] ?? '');
            $this->respondAjax(true, $msg);
        } else {
            $this->respondAjax(false, $result['error']);
        }
    }

    public function editlog($id = null): void
    {
        if (!$this->isPost() || !$id) {
            $this->redirect('resources');
            return;
        }

        $date = $this->post('log_date');
        $reading = (float)$this->post('reading');
        $note = $this->post('note', '');
        $correctionPct = (float)$this->post('correction_pct', 0);
        $log = $this->model->getLogById((int)$id);

        if (!$log) {
            $this->respondAjax(false, 'Запис не знайдено');
            return;
        }

        $formatError = $this->validateReadingFormat($reading, $log['format'] ?? 'dec2');
        if ($formatError) {
            $this->respondAjax(false, $formatError);
            return;
        }
        
        $reading = $this->normalizeReading($reading, $log['format'] ?? 'dec2');

        if (!$date || $reading < 0) {
            $this->respondAjax(false, 'Заповніть усі поля');
            return;
        }

        $config = new ConfigModel($this->db);

        if ($log && $config->isDateClosed($log['log_date'])) {
            $this->respondAjax(false, 'Запис у закритому періоді і не може бути змінений');
            return;
        }
        if ($config->isDateClosed($date)) {
            $this->respondAjax(false, 'Нова дата потрапляє в закритий період');
            return;
        }

        $whId = (int)$log['warehouse_id'];
        $rtId = (int)$log['resource_type_id'];

        $seqError = $this->validateReadingSequence($whId, $rtId, $date, $reading, (int)$id);
        if ($seqError) {
            $this->respondAjax(false, $seqError);
            return;
        }

        $result = $this->model->updateReading((int)$id, $date, $reading, $note, $correctionPct, $this->movementModel);

        if ($result['success']) {
            $msg = 'Оновлено: Δ ' . $result['delta'] . '. Рухів: ' . $result['movements_created'];
            if (!empty($result['recalculated'])) {
                $msg .= '. Перепроведено ще: ' . $result['recalculated'];
            }
            $this->respondAjax(true, $msg);
        } else {
            $this->respondAjax(false, $result['error']);
        }
    }

    public function deletelog($id): void
    {
        $config = new ConfigModel($this->db);
        $log = $this->db->query("SELECT * FROM resource_logs WHERE id = ?", [(int)$id])->fetch();

        if ($log && $config->isDateClosed($log['log_date'])) {
            $this->flash('error', 'Запис у закритому періоді');
        } else {
            $this->model->deleteLogAndRecalculate((int)$id, $this->movementModel);
            $this->flash('success', 'Запис та пов\'язані рухи видалено, наступні перепроведено');
        }
        $this->redirect('resources');
    }

    private function buildSuccessMessage(array $result, float $reading, string $unit): string
    {
        if (!empty($result['is_first'])) {
            return "Початковий показник зафіксовано: " . $reading . " " . $unit;
        }
        
        $msg = "Записано: Δ " . $result['delta'] . " " . $unit . ". Рухів: " . $result['movements_created'];
        if (!empty($result['recalculated'])) {
            $msg .= ". Перепроведено ще: " . $result['recalculated'];
        }
        return $msg;
    }

    private function respondAjax(bool $success, string $message): void
    {
        if ($this->isAjax()) {
            $this->json(['success' => $success, $success ? 'message' : 'error' => $message]);
        } else {
            $this->flash($success ? 'success' : 'error', $message);
            $referer = $_SERVER['HTTP_REFERER'] ?? BASE_PATH . '/resources';
            header('Location: ' . $referer);
            exit;
        }
    }

    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

public function getlog($id = null): void
{
    if (!$id) {
        $this->json(['success' => false, 'error' => 'ID не вказано']);
        return;
    }
    $log = $this->model->getLogById((int)$id);
    if (!$log) {
        $this->json(['success' => false, 'error' => 'Не знайдено']);
        return;
    }
    $this->json([
        'success' => true,
        'data' => [
            'id' => $log['id'],
            'author' => $log['author'] ?? '',
            'created_at' => $log['created_at'] ?? null,
            'updated_at' => $log['updated_at'] ?? null
        ]
    ]);
}

public function getrate($id = null): void
{
    if (!$id) {
        $this->json(['success' => false, 'error' => 'ID не вказано']);
        return;
    }
    $rate = $this->db->query(
        "SELECT rr.*, w.name as warehouse_name, rt.name as resource_name, m.name as material_name 
         FROM resource_rates rr
         JOIN warehouses w ON rr.warehouse_id = w.id
         JOIN resource_types rt ON rr.resource_type_id = rt.id
         JOIN materials m ON rr.material_id = m.id
         WHERE rr.id = ?",
        [(int)$id]
    )->fetch();
    if (!$rate) {
        $this->json(['success' => false, 'error' => 'Не знайдено']);
        return;
    }
    $this->json([
        'success' => true,
        'data' => [
            'id' => $rate['id'],
            'author' => $rate['author'] ?? '',
            'created_at' => $rate['created_at'] ?? null,
            'updated_at' => $rate['updated_at'] ?? null
        ]
    ]);
}

public function gettype($id = null): void
{
    if (!$id) {
        $this->json(['success' => false, 'error' => 'ID не вказано']);
        return;
    }
    $type = $this->db->query("SELECT * FROM resource_types WHERE id = ?", [(int)$id])->fetch();
    if (!$type) {
        $this->json(['success' => false, 'error' => 'Не знайдено']);
        return;
    }
    $this->json([
        'success' => true,
        'data' => [
            'id' => $type['id'],
            'author' => $type['author'] ?? '',
            'created_at' => $type['created_at'] ?? null,
            'updated_at' => $type['updated_at'] ?? null
        ]
    ]);
}

}