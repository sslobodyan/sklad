<?php
/**
 * Контролер руху матеріалів
 */

class MovementsController extends Controller
{
    private MovementModel $model;
    private WarehouseModel $warehouseModel;
    private MaterialModel $materialModel;

    public function __construct(Database $db)
    {
        parent::__construct($db);
        $this->model = new MovementModel($db);
        $this->warehouseModel = new WarehouseModel($db);
        $this->materialModel = new MaterialModel($db);
    }

    public function index(): void
    {
        $highlightId = $this->get('highlight');
        $filters = [
            'date_from' => $this->get('date_from'),
            'date_to' => $this->get('date_to'),
            'warehouse_id' => $this->get('warehouse_id'),
            'material_id' => $this->get('material_id'),
        ];
        // При першому відкритті таблиці Рух — беремо дати з головного діапазону.
        // Якщо є highlight, дати не нав'язуємо, щоб точно показати потрібний запис.
        if (!$highlightId) {
            if (empty($filters['date_from'])) {
                $filters['date_from'] = SettingsController::getDateFrom();
            }
            if (empty($filters['date_to'])) {
                $filters['date_to'] = SettingsController::getDateTo();
            }
        }

        // Сортування

        $allowedSort = [
            'date' => 'm.movement_date',
            'from' => 'wf.name',
            'to' => 'wt.name',
            'material' => 'mat.name',
            'quantity' => 'm.quantity',
            'note' => 'm.note',
        ];
        $sortKey = $this->get('sort', 'date');
        $sortDir = strtolower($this->get('order', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (!isset($allowedSort[$sortKey])) {
            $sortKey = 'date';
        }
        $orderBy = $allowedSort[$sortKey] . ' ' . $sortDir . ', m.id ' . $sortDir;

        $movements = $this->model->getAllWithNames($filters, $orderBy);
        //$highlightId = $this->get('highlight');

        $this->render('movements/index', [
            'title' => 'Рух матеріалів',
            'movements' => $movements,
            'warehouses' => $this->warehouseModel->getAll('name ASC'),
            'materials' => $this->materialModel->getAll('name ASC'),
            'filters' => $filters,
            'sortKey' => $sortKey,
            'sortDir' => $sortDir,
            'highlightId' => $highlightId,
            'activePage' => 'movements',
        ]);
    }

    /**
     * Збереження (AJAX + звичайний POST)
     */
    public function save($id = null): void
    {
        if (!$this->isPost()) {
            $this->redirect('movements');
            return;
        }

        $config = new ConfigModel($this->db);
        $data = $this->getFormData();

        // Перевірка закритого періоду для нового запису
        if (!empty($data['movement_date']) && $config->isDateClosed($data['movement_date'])) {
            $this->respondWith(false, 'Дата потрапляє в закритий період (по ' . date('d.m.Y', strtotime($config->getClosedDate())) . ')');
            return;
        }

        // Перевірка при редагуванні
        if ($id) {
            $existing = $this->model->getById((int)$id);
            if ($existing && !empty($existing['resource_log_id'])) {
                $this->respondWith(false, 'Автоматичний запис — редагуйте через Витрату ресурсів');
                return;
            }
            if ($existing && $config->isDateClosed($existing['movement_date'])) {
                $this->respondWith(false, 'Цей запис знаходиться в закритому періоді і не може бути змінений');
                return;
            }
        }

        $error = $this->validateData($data);
        if ($error) {
            $this->respondWith(false, $error);
            return;
        }

        try {
            if ($id) {
                $this->model->update((int)$id, $data);
                $message = 'Рух оновлено';
            } else {
                $id = $this->model->create($data);
                $message = 'Рух додано';
            }
            
            $this->respondWith(true, $message, ['id' => $id]);
        } catch (Exception $e) {
            $this->respondWith(false, 'Помилка збереження: ' . $e->getMessage());
        }
    }

    public function delete($id): void
    {
        $existing = $this->model->getById((int)$id);

        if ($existing && !empty($existing['resource_log_id'])) {
            $this->flash('error', 'Автоматичний запис — видаліть через Витрату ресурсів');
            $this->redirect('movements');
            return;
        }

        $config = new ConfigModel($this->db);
        if ($existing && $config->isDateClosed($existing['movement_date'])) {
            $this->flash('error', 'Цей запис знаходиться в закритому періоді і не може бути видалений');
            $this->redirect('movements');
            return;
        }

        $this->model->delete((int)$id);
        $this->flash('success', 'Запис видалено');
        $this->redirect('movements');
    }

    /**
     * Експорт у Excel (.xlsx)
     */
    public function export(): void
    {
        $filters = [
            'date_from' => $this->get('date_from'),
            'date_to' => $this->get('date_to'),
            'warehouse_id' => $this->get('warehouse_id'),
            'material_id' => $this->get('material_id'),
        ];

        $movements = $this->model->getAllWithNames($filters);

        // Формуємо ім'я файлу
        $parts = ['Рух_матеріалів'];
        if (!empty($filters['date_from'])) $parts[] = 'від_' . $filters['date_from'];
        if (!empty($filters['date_to'])) $parts[] = 'до_' . $filters['date_to'];
        $filename = implode('_', $parts) . '.xlsx';

        // Генеруємо XLSX
        $xlsx = $this->generateXlsx($movements);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($xlsx));
        header('Cache-Control: max-age=0');
        echo $xlsx;
        exit;
    }

    /**
     * Імпорт з Excel (.xlsx)
     */
    public function import(): void
    {
        if (!$this->isPost() || empty($_FILES['file'])) {
            $this->redirect('movements');
            return;
        }

        $file = $_FILES['file'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->flash('error', 'Помилка завантаження файлу');
            $this->redirect('movements');
            return;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'xlsx') {
            $this->flash('error', 'Підтримується тільки формат .xlsx');
            $this->redirect('movements');
            return;
        }

        try {
            $rows = $this->parseXlsx($file['tmp_name']);
        } catch (Exception $e) {
            $this->flash('error', 'Не вдалося прочитати файл: ' . $e->getMessage());
            $this->redirect('movements');
            return;
        }

        if (count($rows) < 2) {
            $this->flash('error', 'Файл порожній або містить тільки заголовок');
            $this->redirect('movements');
            return;
        }

        // Закритий період
        $config = new ConfigModel($this->db);
        $closedDate = $config->getClosedDate();

        // Галочка "Видалити поточні дані"
        // Видаляємо ТІЛЬКИ ручні рухи, автоматичні (resource_log_id IS NOT NULL) залишаємо
        $clearExisting = !empty($this->post('clear_existing'));
        if ($clearExisting) {
            if ($closedDate) {
                $this->db->query(
                    "DELETE FROM movements WHERE resource_log_id IS NULL AND movement_date > ?",
                    [$closedDate]
                );
            } else {
                $this->db->query("DELETE FROM movements WHERE resource_log_id IS NULL");
            }
        }

        $imported = 0;
        $skippedClosed = 0;
        $created = ['warehouses' => [], 'materials' => []];
        $errors = [];

        // Пропускаємо рядок 0 (заголовок)
        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $rowNum = $i + 1;

            if (count($row) < 5) {
                $errors[] = "Рядок {$rowNum}: недостатньо колонок";
                continue;
            }

            $dateRaw = trim($row[0] ?? '');
            $fromRaw = trim($row[1] ?? '');
            $toRaw   = trim($row[2] ?? '');
            $matRaw  = trim($row[3] ?? '');
            $qtyRaw  = trim($row[4] ?? '');
            $noteRaw = trim($row[5] ?? '');

            // Дата
            $date = $this->parseDate($dateRaw);
            if (!$date) {
                $errors[] = "Рядок {$rowNum}: невірний формат дати «{$dateRaw}»";
                continue;
            }

            // Перевірка закритого періоду
            if ($closedDate && $date <= $closedDate) {
                $skippedClosed++;
                continue;
            }

            // Матеріал — знайти або створити
            if ($matRaw === '') {
                $errors[] = "Рядок {$rowNum}: не вказано матеріал";
                continue;
            }
            $materialId = $this->materialModel->findOrCreate($matRaw);
            if (!isset($created['materials'][$matRaw]) && !$this->materialExistedBefore($matRaw)) {
                $created['materials'][$matRaw] = true;
            }

            // Кількість
            $quantity = (float)str_replace([',', ' '], ['.', ''], $qtyRaw);
            if ($quantity <= 0) {
                $errors[] = "Рядок {$rowNum}: невірна кількість «{$qtyRaw}»";
                continue;
            }

            // Склад-звідки — знайти або створити
            $fromId = null;
            if ($fromRaw !== '') {
                $fromId = $this->warehouseModel->findOrCreate($fromRaw);
                if (!isset($created['warehouses'][$fromRaw]) && !$this->warehouseExistedBefore($fromRaw)) {
                    $created['warehouses'][$fromRaw] = true;
                }
            }

            // Склад-куди — знайти або створити
            $toId = null;
            if ($toRaw !== '') {
                $toId = $this->warehouseModel->findOrCreate($toRaw);
                if (!isset($created['warehouses'][$toRaw]) && !$this->warehouseExistedBefore($toRaw)) {
                    $created['warehouses'][$toRaw] = true;
                }
            }

            // Хоча б один склад
            if (!$fromId && !$toId) {
                $errors[] = "Рядок {$rowNum}: не вказано жодного складу";
                continue;
            }

            // Однакові склади
            if ($fromId && $toId && $fromId === $toId) {
                $errors[] = "Рядок {$rowNum}: склад-звідки та склад-куди однакові";
                continue;
            }

            try {
                $this->model->create([
                    'movement_date' => $date,
                    'warehouse_from_id' => $fromId,
                    'warehouse_to_id' => $toId,
                    'material_id' => $materialId,
                    'quantity' => $quantity,
                    'note' => $noteRaw,
                ]);
                $imported++;
            } catch (Exception $e) {
                $errors[] = "Рядок {$rowNum}: помилка збереження — " . $e->getMessage();
            }
        }

        // Результат
        $msg = [];
        if ($clearExisting) {
            $msg[] = $closedDate
                ? "Видалено ручні рухи після " . date('d.m.Y', strtotime($closedDate))
                : "Видалено всі ручні рухи";
        }
        $msg[] = "Імпортовано {$imported} записів";
        if ($skippedClosed > 0) $msg[] = "пропущено {$skippedClosed} (закритий період)";
        $newWh = count($created['warehouses']);
        $newMat = count($created['materials']);
        if ($newWh > 0) $msg[] = "створено {$newWh} нових складів";
        if ($newMat > 0) $msg[] = "створено {$newMat} нових матеріалів";
        if (!empty($errors)) $msg[] = "помилок: " . count($errors);

        if ($imported > 0) {
            $this->flash('success', implode('. ', $msg));
        } else {
            $this->flash('error', "Жодного запису не імпортовано. " . implode('; ', array_slice($errors, 0, 5)));
        }

        $this->redirect('movements');
    }

    /**
     * Перевірка чи склад вже існував (для повідомлення про створення нових)
     */
    private $_existingWarehouses = null;
    private function warehouseExistedBefore(string $name): bool
    {
        if ($this->_existingWarehouses === null) {
            $all = $this->warehouseModel->getAll();
            $this->_existingWarehouses = [];
            foreach ($all as $w) {
                $this->_existingWarehouses[mb_strtolower(trim($w['name']))] = true;
            }
        }
        return isset($this->_existingWarehouses[mb_strtolower(trim($name))]);
    }

    private $_existingMaterials = null;
    private function materialExistedBefore(string $name): bool
    {
        if ($this->_existingMaterials === null) {
            $all = $this->materialModel->getAll();
            $this->_existingMaterials = [];
            foreach ($all as $m) {
                $this->_existingMaterials[mb_strtolower(trim($m['name']))] = true;
            }
        }
        return isset($this->_existingMaterials[mb_strtolower(trim($name))]);
    }

    /**
     * Парсинг дати з різних форматів
     */
    private function parseDate(string $raw): ?string
    {
        $raw = trim($raw);
        if (!$raw) return null;

        // YYYY-MM-DD
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $raw, $m)) {
            return sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
        }
        // DD.MM.YYYY
        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $raw, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }
        // DD/MM/YYYY
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $raw, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }
        // Excel serial date number (days since 1900-01-01)
        if (is_numeric($raw) && (int)$raw > 40000 && (int)$raw < 55000) {
            $unix = ((int)$raw - 25569) * 86400;
            return date('Y-m-d', $unix);
        }

        return null;
    }

    /**
     * Парсинг xlsx файлу — повертає масив рядків
     */
    private function parseXlsx(string $filepath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($filepath) !== true) {
            throw new Exception('Не вдалося відкрити файл як ZIP');
        }

        // Читаємо shared strings (якщо є)
        $sharedStrings = [];
        $ssXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($ssXml) {
            $ss = new SimpleXMLElement($ssXml);
            foreach ($ss->si as $si) {
                // Текст може бути в <t> або в кількох <r><t>
                $text = '';
                if (isset($si->t)) {
                    $text = (string)$si->t;
                } elseif (isset($si->r)) {
                    foreach ($si->r as $run) {
                        $text .= (string)$run->t;
                    }
                }
                $sharedStrings[] = $text;
            }
        }

        // Читаємо sheet1
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if (!$sheetXml) {
            $zip->close();
            throw new Exception('Не знайдено лист sheet1 у файлі');
        }

        $sheet = new SimpleXMLElement($sheetXml);
        $zip->close();

        $rows = [];
        foreach ($sheet->sheetData->row as $rowEl) {
            $rowData = [];
            $maxCol = 0;

            foreach ($rowEl->c as $cell) {
                $ref = (string)$cell['r']; // Наприклад "B3"
                $colIndex = $this->colToIndex($ref);
                $maxCol = max($maxCol, $colIndex);

                $value = '';
                $type = (string)$cell['t'];

                if ($type === 's') {
                    // Shared string
                    $idx = (int)$cell->v;
                    $value = $sharedStrings[$idx] ?? '';
                } elseif ($type === 'inlineStr') {
                    // Inline string
                    $value = (string)$cell->is->t;
                } else {
                    // Number або інше
                    $value = (string)$cell->v;
                }

                // Заповнюємо пропущені колонки
                while (count($rowData) < $colIndex) {
                    $rowData[] = '';
                }
                $rowData[$colIndex] = $value;
            }

            $rows[] = $rowData;
        }

        return $rows;
    }

    /**
     * Конвертація посилання на клітинку ("B3") у індекс колонки (1)
     */
    private function colToIndex(string $cellRef): int
    {
        preg_match('/^([A-Z]+)/', $cellRef, $m);
        $letters = $m[1];
        $index = 0;
        $len = strlen($letters);
        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($letters[$i]) - 64);
        }
        return $index - 1; // 0-based
    }

    /**
     * API: отримання даних одного руху
     */
    public function load($id): void
    {
        $movement = $this->model->getById((int)$id);
        if (!$movement) {
            $this->json(['success' => false, 'error' => 'Не знайдено']);
            return;
        }
        $this->json(['success' => true, 'data' => $movement]);
    }

    // -------------------------------------------------------
    // Private
    // -------------------------------------------------------

    private function getFormData(): array
    {
        return [
            'movement_date' => $this->post('movement_date'),
            'warehouse_from_id' => $this->post('warehouse_from_id') ?: null,
            'warehouse_to_id' => $this->post('warehouse_to_id') ?: null,
            'material_id' => $this->post('material_id'),
            'quantity' => (float)$this->post('quantity'),
            'note' => $this->post('note', ''),
        ];
    }

    private function validateData(array $data): ?string
    {
        if (empty($data['movement_date'])) return 'Вкажіть дату';
        if (empty($data['material_id'])) return 'Оберіть матеріал';
        if ($data['quantity'] <= 0) return 'Вкажіть кількість';
        if (empty($data['warehouse_from_id']) && empty($data['warehouse_to_id'])) return 'Вкажіть хоча б один склад';
        if ($data['warehouse_from_id'] && $data['warehouse_from_id'] === $data['warehouse_to_id']) return 'Склади не можуть бути однаковими';
        return null;
    }

    private function respondWith(bool $success, string $message, array $extra = []): void
    {
        if ($this->isAjax()) {
            $data = array_merge(['success' => $success], $extra);
            $data[$success ? 'message' : 'error'] = $message;
            $this->json($data);
        } else {
            $this->flash($success ? 'success' : 'error', $message);
            $this->redirect('movements');
        }
    }

    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Форматування ресурсного значення для експорту
     */
    private function formatResourceValue($value, string $format = 'dec2'): string
    {
        if ($value === null || $value === '') return '';
        $v = (float)$value;
        switch ($format) {
            case 'int':
                return (string)(int)round($v);
            case 'hm':
                $h = (int)floor($v);
                $m = (int)round(($v - $h) * 60);
                return $h . ':' . str_pad($m, 2, '0', STR_PAD_LEFT);
            case 'dec2':
            default:
                return number_format($v, 2, '.', '');
        }
    }

    /**
     * Генерація XLSX (Office Open XML) без зовнішніх бібліотек
     */
    private function generateXlsx(array $movements): string
    {
        // --- Дані для sheet ---
        $rows = [];

        // Додаткові колонки для автоматичних рухів: показник, дельта, норма
        $rows[] = ['Дата', 'Звідки', 'Куди', 'Матеріал', 'Кількість', 'Показник', 'Дельта', 'Норма', 'Поправка', 'Примітка'];

        foreach ($movements as $m) {
            $isAuto = !empty($m['resource_log_id']);
            $fmt = $m['resource_format'] ?? 'dec2';

            $rows[] = [
                date('d.m.Y', strtotime($m['movement_date'])),
                $m['warehouse_from_name'] ?? '',
                $m['warehouse_to_name'] ?? '',
                $m['material_name'],
                (float)$m['quantity'],
                $isAuto ? $this->formatResourceValue($m['resource_value'] ?? null, $fmt) : '',
                $isAuto ? $this->formatResourceValue($m['resource_delta'] ?? null, $fmt) : '',
                $isAuto && isset($m['resource_rate']) ? rtrim(rtrim(number_format((float)$m['resource_rate'], 6, '.', ''), '0'), '.') : '',
                ($m['resource_correction']>0 || $m['resource_correction']<0) ? $m['resource_correction'] : '', 
                $m['note'] ?? '',
            ];
        }

        // --- XML для sheet1 ---
        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $sheetXml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $sheetXml .= '<cols>';
        $sheetXml .= '<col min="1" max="1" width="12" bestFit="1"/>';   // Дата
        $sheetXml .= '<col min="2" max="2" width="24"/>';               // Звідки
        $sheetXml .= '<col min="3" max="3" width="24"/>';               // Куди
        $sheetXml .= '<col min="4" max="4" width="24"/>';               // Матеріал
        $sheetXml .= '<col min="5" max="5" width="12"/>';               // Кількість
        $sheetXml .= '<col min="6" max="6" width="14"/>';               // Показник
        $sheetXml .= '<col min="7" max="7" width="14"/>';               // Дельта
        $sheetXml .= '<col min="8" max="8" width="10"/>';               // Норма
        $sheetXml .= '<col min="9" max="9" width="10"/>';               // Поправка
        $sheetXml .= '<col min="10" max="10" width="30"/>';             // Примітка
        $sheetXml .= '</cols>';
        $sheetXml .= '<sheetData>';

        foreach ($rows as $rowIdx => $row) {
            $r = $rowIdx + 1;
            $sheetXml .= '<row r="' . $r . '">';
            foreach ($row as $colIdx => $cell) {
                $colLetter = chr(65 + $colIdx); // A, B, C...
                $ref = $colLetter . $r;

                if (is_numeric($cell) && !is_string($cell)) {
                    // Число
                    $sheetXml .= '<c r="' . $ref . '"><v>' . $cell . '</v></c>';
                } else {
                    // Текст (inline string)
                    $sheetXml .= '<c r="' . $ref . '" t="inlineStr"><is><t>' . htmlspecialchars((string)$cell, ENT_XML1) . '</t></is></c>';
                }
            }
            $sheetXml .= '</row>';
        }

        $sheetXml .= '</sheetData>';

        // Автофільтр на заголовок
        $lastCol = chr(65 + count($rows[0]) - 1);
        $lastRow = count($rows);
        $sheetXml .= '<autoFilter ref="A1:' . $lastCol . $lastRow . '"/>';

        $sheetXml .= '</worksheet>';

        // --- Решта XML-файлів для ZIP (xlsx = zip) ---
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
            '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' .
            '<Default Extension="xml" ContentType="application/xml"/>' .
            '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' .
            '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' .
            '</Types>';

        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' .
            '</Relationships>';

        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' .
            '<sheets><sheet name="Рух матеріалів" sheetId="1" r:id="rId1"/></sheets>' .
            '</workbook>';

        $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' .
            '</Relationships>';

        // --- Збираємо ZIP ---
        $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        $zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $rels);
        $zip->addFromString('xl/workbook.xml', $workbook);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);

        $zip->close();

        $content = file_get_contents($tmpFile);
        unlink($tmpFile);

        return $content;
    }
}
