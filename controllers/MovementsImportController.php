<?php
/**
 * Контролер імпорту руху матеріалів
 */

require_once __DIR__ . '/../helpers/XlsxParserHelper.php';

class MovementsImportController extends Controller
{
    private MovementModel $model;
    private WarehouseModel $warehouseModel;
    private MaterialModel $materialModel;
    private ConfigModel $config;

    public function __construct(Database $db)
    {
        parent::__construct($db);
        $this->model = new MovementModel($db);
        $this->warehouseModel = new WarehouseModel($db);
        $this->materialModel = new MaterialModel($db);
        $this->config = new ConfigModel($db);
    }

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
            $rows = XlsxParserHelper::parse($file['tmp_name']);
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

        $closedDate = $this->config->getClosedDate();
        $clearExisting = !empty($this->post('clear_existing'));

        if ($clearExisting) {
            $this->clearExistingMovements($closedDate);
        }

        $result = $this->processImportRows($rows, $closedDate);

        $this->showImportResult($result, $clearExisting, $closedDate);
        $this->redirect('movements');
    }

    private function clearExistingMovements(?string $closedDate): void
    {
        if ($closedDate) {
            $this->db->query(
                "DELETE FROM movements WHERE resource_log_id IS NULL AND movement_date > ?",
                [$closedDate]
            );
        } else {
            $this->db->query("DELETE FROM movements WHERE resource_log_id IS NULL");
        }
    }

    private function processImportRows(array $rows, ?string $closedDate): array
    {
        $imported = 0;
        $skippedClosed = 0;
        $created = ['warehouses' => [], 'materials' => []];
        $errors = [];

        $existingWarehouses = $this->getExistingWarehouses();
        $existingMaterials = $this->getExistingMaterials();

        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $rowNum = $i + 1;

            if (count($row) < 5) {
                $errors[] = "Рядок {$rowNum}: мало колонок";
                continue;
            }

            $date = $this->parseDate(trim($row[0]));
            if (!$date) {
                $errors[] = "Рядок {$rowNum}: невірний формат дати «{$row[0]}»";
                continue;
            }

            if ($closedDate && $date <= $closedDate) {
                $skippedClosed++;
                continue;
            }

            $materialId = $this->processMaterial($row[3] ?? '', $rowNum, $existingMaterials, $created['materials'], $errors);
            if (!$materialId) continue;

            $quantity = $this->parseQuantity($row[4] ?? '', $rowNum, $errors);
            if ($quantity === null) continue;

            list($fromId, $toId) = $this->processWarehouses(
                $row[1] ?? '', $row[2] ?? '', $rowNum,
                $existingWarehouses, $created['warehouses'], $errors
            );
            if ($fromId === false || $toId === false) continue;

            try {
                $this->model->create([
                    'movement_date' => $date,
                    'warehouse_from_id' => $fromId,
                    'warehouse_to_id' => $toId,
                    'material_id' => $materialId,
                    'quantity' => $quantity,
                    'note' => trim($row[8] ?? $row[5] ?? ''),
                ]);
                $imported++;
            } catch (Exception $e) {
                $errors[] = "Рядок {$rowNum}: помилка збереження — " . $e->getMessage();
            }
        }

        return compact('imported', 'skippedClosed', 'created', 'errors');
    }

    private function getExistingWarehouses(): array
    {
        $result = [];
        foreach ($this->warehouseModel->getAll() as $w) {
            $result[mb_strtolower(trim($w['name']))] = true;
        }
        return $result;
    }

    private function getExistingMaterials(): array
    {
        $result = [];
        foreach ($this->materialModel->getAll() as $m) {
            $result[mb_strtolower(trim($m['name']))] = true;
        }
        return $result;
    }

    private function processMaterial(string $raw, int $rowNum, array &$existing, array &$created, array &$errors): ?int
    {
        $name = trim($raw);
        if ($name === '') {
            $errors[] = "Рядок {$rowNum}: порожній матеріал";
            return null;
        }

        $id = $this->materialModel->findOrCreate($name);
        $key = mb_strtolower($name);
        if (!isset($existing[$key]) && !isset($created[$key])) {
            $created[$key] = true;
        }
        return $id;
    }

    private function parseQuantity(string $raw, int $rowNum, array &$errors): ?float
    {
        $quantity = (float)str_replace([',', ' '], ['.', ''], trim($raw));
        if ($quantity <= 0) {
            $errors[] = "Рядок {$rowNum}: кількість <= 0";
            return null;
        }
        return $quantity;
    }

    private function processWarehouses(string $fromRaw, string $toRaw, int $rowNum, array &$existing, array &$created, array &$errors): array
    {
        $fromId = null;
        $toId = null;

        if (trim($fromRaw) !== '') {
            $fromId = $this->warehouseModel->findOrCreate(trim($fromRaw));
            $key = mb_strtolower(trim($fromRaw));
            if (!isset($existing[$key]) && !isset($created[$key])) {
                $created[$key] = true;
            }
        }

        if (trim($toRaw) !== '') {
            $toId = $this->warehouseModel->findOrCreate(trim($toRaw));
            $key = mb_strtolower(trim($toRaw));
            if (!isset($existing[$key]) && !isset($created[$key])) {
                $created[$key] = true;
            }
        }

        if (!$fromId && !$toId) {
            $errors[] = "Рядок {$rowNum}: не вказано жодного складу";
            return [false, false];
        }

        if ($fromId && $toId && $fromId === $toId) {
            $errors[] = "Рядок {$rowNum}: склад-звідки та склад-куди однакові";
            return [false, false];
        }

        return [$fromId, $toId];
    }

    private function parseDate(string $raw): ?string
    {
        if (!$raw) return null;

        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $raw, $m)) {
            return sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
        }
        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $raw, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $raw, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }
        if (is_numeric($raw) && (int)$raw > 40000 && (int)$raw < 60000) {
            $unix = ((int)$raw - 25569) * 86400;
            return date('Y-m-d', $unix);
        }
        return null;
    }

    private function showImportResult(array $result, bool $clearExisting, ?string $closedDate): void
    {
        $msg = [];
        if ($clearExisting) {
            $msg[] = $closedDate
                ? "Видалено ручні рухи після " . date('d.m.Y', strtotime($closedDate))
                : "Видалено всі ручні рухи";
        }
        $msg[] = "Імпортовано {$result['imported']} записів";
        if ($result['skippedClosed'] > 0) $msg[] = "пропущено {$result['skippedClosed']} (закритий період)";
        
        $newWh = count($result['created']['warehouses']);
        $newMat = count($result['created']['materials']);
        if ($newWh > 0) $msg[] = "створено {$newWh} нових складів";
        if ($newMat > 0) $msg[] = "створено {$newMat} нових матеріалів";
        if (!empty($result['errors'])) $msg[] = "помилок: " . count($result['errors']);

        if ($result['imported'] > 0) {
            $this->flash('success', implode('. ', $msg));
        } else {
            $this->flash('error', "Жодного запису не імпортовано. " . implode('; ', array_slice($result['errors'], 0, 5)));
        }
    }

    private function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    private function post(string $key, $default = null)
    {
        return $_POST[$key] ?? $default;
    }

    private function redirect(string $path): void
    {
        header('Location: ' . BASE_PATH . '/' . ltrim($path, '/'));
        exit;
    }

    private function flash(string $type, string $message): void
    {
        $_SESSION['flash_' . $type] = $message;
    }
}