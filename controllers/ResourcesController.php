<?php
/**
 * Контролер ресурсів (пробіг, мотогодини, норми, журнал)
 */

class ResourcesController extends Controller
{
    private ResourceModel $model;
    private WarehouseModel $warehouseModel;
    private MaterialModel $materialModel;
    private MovementModel $movementModel;

    public function __construct(Database $db)
    {
        parent::__construct($db);
        $this->model = new ResourceModel($db);
        $this->warehouseModel = new WarehouseModel($db);
        $this->materialModel = new MaterialModel($db);
        $this->movementModel = new MovementModel($db);
    }

    // =============================================
    // Типи ресурсів
    // =============================================

    public function types(): void
    {
        $types = $this->model->getTypes();

        $this->render('resources/types', [
            'title' => 'Типи ресурсів',
            'types' => $types,
            'activePage' => 'resource-types',
        ]);
    }

    public function savetype($id = null): void
    {
        if (!$this->isPost()) { $this->redirect('resources/types'); return; }

        $name = trim($this->post('name', ''));
        $unit = trim($this->post('unit', ''));
        $format = $this->post('format', 'int');
        if (!in_array($format, ['int', 'dec2', 'hm'])) $format = 'int';

        if (!$name || !$unit) {
            $this->respondAjax(false, 'Заповніть назву та одиницю');
            return;
        }

        if ($id) {
            $this->model->updateType((int)$id, $name, $unit, $format);
        } else {
            $this->model->createType($name, $unit, $format);
        }

        $this->respondAjax(true, $id ? 'Тип оновлено' : 'Тип додано');
    }

    public function deletetype($id): void
    {
        if ($this->model->isTypeUsed((int)$id)) {
            $this->flash('error', 'Неможливо видалити: тип використовується');
        } else {
            $this->model->deleteType((int)$id);
            $this->flash('success', 'Тип видалено');
        }
        $this->redirect('resources/types');
    }

    // =============================================
    // Норми списання (налаштування для складу)
    // =============================================

    public function rates(): void
    {
        $warehouseId = (int)$this->get('warehouse_id', 0);
        $resourceTypeId = (int)$this->get('resource_type_id', 0);

        $whWithRes = $this->model->getWarehousesWithResources();
        $types = $this->model->getTypes();
        $warehouses = $this->warehouseModel->getAll('name ASC');
        $materials = $this->materialModel->getAll('name ASC');

        $warehouseResources = [];
        $rates = [];
        $selectedWarehouse = null;

        if ($warehouseId) {
            $selectedWarehouse = $this->warehouseModel->getById($warehouseId);
            $warehouseResources = $this->model->getWarehouseResources($warehouseId);

            if ($resourceTypeId) {
                $rates = $this->model->getRates($warehouseId, $resourceTypeId);
            }
        }

        $this->render('resources/rates', [
            'title' => 'Норми списання',
            'warehousesWithResources' => $whWithRes,
            'types' => $types,
            'warehouses' => $warehouses,
            'materials' => $materials,
            'warehouseId' => $warehouseId,
            'resourceTypeId' => $resourceTypeId,
            'selectedWarehouse' => $selectedWarehouse,
            'warehouseResources' => $warehouseResources,
            'rates' => $rates,
            'activePage' => 'resource-rates',
        ]);
    }

    public function addresource(): void
    {
        if (!$this->isPost()) { $this->redirect('resources/rates'); return; }
        $whId = (int)$this->post('warehouse_id');
        $rtId = (int)$this->post('resource_type_id');
        if ($whId && $rtId) {
            $this->model->addWarehouseResource($whId, $rtId);
            $this->flash('success', 'Ресурс прив\'язано до складу');
        }
        $this->redirect('resources/rates?warehouse_id=' . $whId);
    }

    public function removeresource(): void
    {
        if (!$this->isPost()) { $this->redirect('resources/rates'); return; }
        $whId = (int)$this->post('warehouse_id');
        $rtId = (int)$this->post('resource_type_id');
        if ($whId && $rtId) {
            $this->model->removeWarehouseResource($whId, $rtId);
            $this->flash('success', 'Ресурс видалено зі складу');
        }
        $this->redirect('resources/rates?warehouse_id=' . $whId);
    }

    public function saverate(): void
    {
        if (!$this->isPost()) { $this->redirect('resources/rates'); return; }
        $whId = (int)$this->post('warehouse_id');
        $rtId = (int)$this->post('resource_type_id');
        $matId = (int)$this->post('material_id');
        $rate = (float)$this->post('rate');
        $srcWhId = (int)$this->post('source_warehouse_id') ?: null;
        $spreadByDay = !empty($this->post('spread_by_day'));

        if ($whId && $rtId && $matId && $rate > 0) {
            $this->model->saveRate($whId, $rtId, $matId, $rate, $srcWhId, $spreadByDay);
            $this->respondAjax(true, 'Норму збережено');
        } else {
            $this->respondAjax(false, 'Заповніть усі поля');
        }
    }

    public function deleterate($id): void
    {
        $this->model->deleteRate((int)$id);
        $this->flash('success', 'Норму видалено');
        $referer = $_SERVER['HTTP_REFERER'] ?? BASE_PATH . '/resources/rates';
        header('Location: ' . $referer);
        exit;
    }

    // =============================================
    // Журнал показників (введення одометра тощо)
    // =============================================

    public function index(): void
    {
        $highlightId = $this->get('highlight');
        
        $filters = [
            'warehouse_id' => $this->get('warehouse_id'),
            'resource_type_id' => $this->get('resource_type_id'),
            'date_from' => $this->get('date_from'),
            'date_to' => $this->get('date_to'),
        ];

        // Якщо є highlight — скидаємо фільтри, щоб показати запис
        if ($highlightId) {
            $filters = ['warehouse_id' => '', 'resource_type_id' => '', 'date_from' => '', 'date_to' => ''];
        }

        $logs = $this->model->getLogs($filters);
        $whWithRes = $this->model->getWarehousesWithResources();
        $types = $this->model->getTypes();

        // Знайти prevDate для кожного логу (для URL переходу на рухи)
        $prevDates = [];
        foreach ($logs as $l) {
            $prev = $this->db->query(
                "SELECT log_date FROM resource_logs 
                 WHERE warehouse_id = ? AND resource_type_id = ? AND id < ? 
                 ORDER BY log_date DESC, id DESC LIMIT 1",
                [$l['warehouse_id'], $l['resource_type_id'], $l['id']]
            )->fetch();
            if ($prev) {
                // Дата наступного дня після попереднього запису
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

    public function export(): void
    {
        $filters = [
            'warehouse_id' => $this->get('warehouse_id'),
            'resource_type_id' => $this->get('resource_type_id'),
            'date_from' => $this->get('date_from'),
            'date_to' => $this->get('date_to'),
        ];

        $logs = $this->model->getLogs($filters);

        $rows = [];
        $rows[] = ['Дата', 'Склад', 'Ресурс', 'Показник', 'Попередній', 'Витрата', 'Поправка', 'Примітка'];

        foreach ($logs as $l) {
            $fmt = $l['format'] ?? 'dec2';
            $rows[] = [
                date('d.m.Y', strtotime($l['log_date'])),
                $l['warehouse_name'],
                $l['type_name'] . ' (' . $l['unit'] . ')',
                $this->formatVal($l['reading'], $fmt),
                $l['prev_reading'] !== null ? $this->formatVal($l['prev_reading'], $fmt) : '',
                $l['delta'] !== null ? $this->formatVal($l['delta'], $fmt) : '',
                ($l['correction_pct'] > 0) || ($l['correction_pct'] < 0 ) ? $l['correction_pct'] : '',
                $l['note'] ?? '',
            ];
        }

        $filename = 'Витрата_ресурсів';
        if (!empty($filters['date_from'])) $filename .= '_від_' . $filters['date_from'];
        if (!empty($filters['date_to'])) $filename .= '_до_' . $filters['date_to'];
        $filename .= '.xlsx';

        $xlsx = $this->generateXlsx($rows, 'Витрата ресурсів');

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($xlsx));
        echo $xlsx;
        exit;
    }

    private function formatVal($v, string $fmt): string
    {
        if ($v === null || $v === '') return '';
        $v = (float)$v;
        switch ($fmt) {
            case 'int': return (string)(int)$v;
            case 'hm':
                $h = (int)floor($v);
                $m = (int)round(($v - $h) * 60);
                return $h . ':' . str_pad($m, 2, '0', STR_PAD_LEFT);
            default: return number_format($v, 2, '.', '');
        }
    }

    private function generateXlsx(array $rows, string $sheetName): string
    {
        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
        foreach ($rows as $ri => $row) {
            $r = $ri + 1;
            $sheetXml .= '<row r="' . $r . '">';
            foreach ($row as $ci => $cell) {
                $ref = chr(65 + $ci) . $r;
                $sheetXml .= '<c r="' . $ref . '" t="inlineStr"><is><t>' . htmlspecialchars((string)$cell, ENT_XML1) . '</t></is></c>';
            }
            $sheetXml .= '</row>';
        }
        $sheetXml .= '</sheetData></worksheet>';

        $ct = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>';
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
        $wb = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . htmlspecialchars($sheetName, ENT_XML1) . '" sheetId="1" r:id="rId1"/></sheets></workbook>';
        $wbr = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>';

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new \ZipArchive();
        $zip->open($tmp, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', $ct);
        $zip->addFromString('_rels/.rels', $rels);
        $zip->addFromString('xl/workbook.xml', $wb);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbr);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->close();
        $content = file_get_contents($tmp);
        unlink($tmp);
        return $content;
    }

    public function add(): void
    {
        if (!$this->isPost()) { $this->redirect('resources'); return; }

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
        if (($type['format'] ?? 'dec2') === 'int') {
            if (abs($reading - round($reading)) > 0.000001) {
                $this->respondAjax(false, 'Для цього ресурсу дозволені тільки цілі числа');
                return;
            }
            $reading = round($reading);
        } elseif (($type['format'] ?? 'dec2') === 'dec2') {
            $reading = round($reading, 2);
        }

        if (!$whId || !$rtId || !$date || $reading < 0) {
            $this->respondAjax(false, 'Заповніть усі поля');
            return;
        }

        // Перевірка закритого періоду
        $config = new ConfigModel($this->db);
        if ($config->isDateClosed($date)) {
            $this->respondAjax(false, 'Дата потрапляє в закритий період');
            return;
        }

        // Перевірка: показник не менше попереднього і не більше наступного
        $prev = $this->db->query(
            "SELECT reading FROM resource_logs WHERE warehouse_id = ? AND resource_type_id = ? AND log_date <= ? ORDER BY log_date DESC, id DESC LIMIT 1",
            [$whId, $rtId, $date]
        )->fetch();
        if ($prev && $reading < (float)$prev['reading']) {
            $this->respondAjax(false, 'Показник не може бути меншим за попередній (' . $prev['reading'] . ')');
            return;
        }

        $next = $this->db->query(
            "SELECT reading, log_date FROM resource_logs WHERE warehouse_id = ? AND resource_type_id = ? AND log_date > ? ORDER BY log_date ASC, id ASC LIMIT 1",
            [$whId, $rtId, $date]
        )->fetch();
        if ($next && $reading > (float)$next['reading']) {
            $this->respondAjax(false, 'Показник не може бути більшим за наступний (' . $next['reading'] . ' від ' . $next['log_date'] . ')');
            return;
        }

        $result = $this->model->addReading($whId, $rtId, $date, $reading, $note, $correctionPct, $this->movementModel);

        if ($result['success']) {
            if (!empty($result['is_first'])) {
                $msg = "Початковий показник зафіксовано: " . $reading . " " . ($type['unit'] ?? '');
            } else {
                $msg = "Записано: Δ " . $result['delta'] . " " . ($type['unit'] ?? '') . ". Рухів: " . $result['movements_created'];
            }
            if (!empty($result['recalculated'])) {
                $msg .= ". Перепроведено ще: " . $result['recalculated'];
            }
            $this->respondAjax(true, $msg);
        } else {
            $this->respondAjax(false, $result['error']);
        }
    }

    public function editlog($id = null): void
    {

to_log('editlog start');

        if (!$this->isPost() || !$id) { $this->redirect('resources'); return; }

        $date = $this->post('log_date');

        $reading = (float)$this->post('reading');
        $note = $this->post('note', '');
        $correctionPct = (float)$this->post('correction_pct', 0);
        $log = $this->model->getLogById((int)$id);

        if (!$log) {
            $this->respondAjax(false, 'Запис не знайдено');
            return;
        }
        if (($log['format'] ?? 'dec2') === 'int') {
            if (abs($reading - round($reading)) > 0.000001) {
                $this->respondAjax(false, 'Для цього ресурсу дозволені тільки цілі числа');
                return;
            }
            $reading = round($reading);
        } elseif (($log['format'] ?? 'dec2') === 'dec2') {
            $reading = round($reading, 2);
        }

        if (!$date || $reading < 0) {
            $this->respondAjax(false, 'Заповніть усі поля');
            return;
        }

        // Перевірка закритого періоду
        $config = new ConfigModel($this->db);

        if ($log && $config->isDateClosed($log['log_date'])) {
            $this->respondAjax(false, 'Запис у закритому періоді і не може бути змінений');
            return;
        }
        if ($config->isDateClosed($date)) {
            $this->respondAjax(false, 'Нова дата потрапляє в закритий період');
            return;
        }

        // Перевірка: показник не менше попереднього і не більше наступного (виключаючи цей запис)
        $whId = (int)$log['warehouse_id'];
        $rtId = (int)$log['resource_type_id'];

        $prev = $this->db->query(
            "SELECT reading FROM resource_logs WHERE warehouse_id = ? AND resource_type_id = ? AND log_date <= ? AND id <> ? ORDER BY log_date DESC, id DESC LIMIT 1",
            [$whId, $rtId, $date, (int)$id]
        )->fetch();
        if ($prev && $reading < (float)$prev['reading']) {
            $this->respondAjax(false, 'Показник не може бути меншим за попередній (' . $prev['reading'] . ')');
            return;
        }

        $next = $this->db->query(
            "SELECT reading, log_date FROM resource_logs WHERE warehouse_id = ? AND resource_type_id = ? AND log_date > ? AND id <> ? ORDER BY log_date ASC, id ASC LIMIT 1",
            [$whId, $rtId, $date, (int)$id]
        )->fetch();
        if ($next && $reading > (float)$next['reading']) {
            $this->respondAjax(false, 'Показник не може бути більшим за наступний (' . $next['reading'] . ' від ' . $next['log_date'] . ')');
            return;
        }

to_log('перевірки пройшли');

        $result = $this->model->updateReading((int)$id, $date, $reading, $note, $correctionPct, $this->movementModel);

to_log('updateReading', $result);

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
        // Перевірка закритого періоду
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

    /**
     * AJAX: отримати контекст для вводу показника
     * Параметри: warehouse_id, resource_type_id, date, [exclude_id]
     * Повертає: prev (показник і дата ДО вказаної дати), next (показник і дата ПІСЛЯ)
     */
    public function context(): void
    {
        $whId = (int)$this->get('warehouse_id');
        $rtId = (int)$this->get('resource_type_id');
        $date = $this->get('date', date('Y-m-d'));
        $excludeId = (int)$this->get('exclude_id', 0);

        $excludeClause = $excludeId ? " AND id <> {$excludeId}" : '';

        // Попередній: <= date
        $prev = $this->db->query(
            "SELECT id, log_date, reading FROM resource_logs
             WHERE warehouse_id = ? AND resource_type_id = ?
               AND log_date <= ? {$excludeClause}
             ORDER BY log_date DESC, id DESC LIMIT 1",
            [$whId, $rtId, $date]
        )->fetch();

        // Наступний: > date
        $next = $this->db->query(
            "SELECT id, log_date, reading FROM resource_logs
             WHERE warehouse_id = ? AND resource_type_id = ?
               AND log_date > ? {$excludeClause}
             ORDER BY log_date ASC, id ASC LIMIT 1",
            [$whId, $rtId, $date]
        )->fetch();

        // Формат ресурсу
        $type = $this->model->getTypeById($rtId);

        $this->json([
            'success' => true,
            'prev_reading' => $prev ? (float)$prev['reading'] : 0,
            'prev_date' => $prev ? $prev['log_date'] : null,
            'next_reading' => $next ? (float)$next['reading'] : null,
            'next_date' => $next ? $next['log_date'] : null,
            'format' => $type ? ($type['format'] ?? 'dec2') : 'dec2',
            'unit' => $type ? ($type['unit'] ?? '') : '',
        ]);
    }

    /**
     * Старий метод для сумісності
     */
    public function lastreading(): void
    {
        $whId = (int)$this->get('warehouse_id');
        $rtId = (int)$this->get('resource_type_id');
        $last = $this->model->getLastReading($whId, $rtId);
        $this->json([
            'success' => true,
            'reading' => $last ? (float)$last['reading'] : 0,
            'date' => $last ? $last['log_date'] : null,
        ]);
    }

    // =============================================
    // Helpers
    // =============================================

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
}
