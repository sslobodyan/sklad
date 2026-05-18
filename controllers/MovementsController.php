<?php
/**
 * Контролер руху матеріалів — основний CRUD
 */

require_once __DIR__ . '/traits/MovementRedirectTrait.php';
require_once __DIR__ . '/traits/MovementValidationTrait.php';

class MovementsController extends Controller
{
    use MovementRedirectTrait;
    use MovementValidationTrait;

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

        $_SESSION['movements_filters'] = $filters;

        if (!$highlightId) {
            if (empty($filters['date_from'])) {
                $filters['date_from'] = SettingsController::getDateFrom();
            }
            if (empty($filters['date_to'])) {
                $filters['date_to'] = SettingsController::getDateTo();
            }
        }

        $sorting = $this->getSorting();
        $movements = $this->model->getAllWithNames($filters, $sorting['orderBy']);

        $this->render('movements/index', [
            'title' => 'Рух матеріалів',
            'movements' => $movements,
            'warehouses' => $this->warehouseModel->getAll('name ASC'),
            'materials' => $this->materialModel->getAll('name ASC'),
            'filters' => $filters,
            'sortKey' => $sorting['key'],
            'sortDir' => $sorting['dir'],
            'highlightId' => $highlightId,
            'activePage' => 'movements',
        ]);
    }

    public function save($id = null): void
    {
        if (!$this->isPost()) {
            $this->redirect('movements');
            return;
        }

        $config = new ConfigModel($this->db);
        $data = $this->getFormData();

        if (!empty($data['movement_date']) && $config->isDateClosed($data['movement_date'])) {
            $this->respondWith(false, 'Дата потрапляє в закритий період (по ' . date('d.m.Y', strtotime($config->getClosedDate())) . ')');
            return;
        }

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
            $this->redirectBack();
            return;
        }

        $config = new ConfigModel($this->db);
        if ($existing && $config->isDateClosed($existing['movement_date'])) {
            $this->flash('error', 'Цей запис знаходиться в закритому періоді і не може бути видалений');
            $this->redirectBack();
            return;
        }

        $this->model->delete((int)$id);
        $this->flash('success', 'Запис видалено');
        $this->redirectBack();
    }

    public function getone($id = null): void
    {
        if (!$id) {
            $this->json(['success' => false, 'error' => 'ID не вказано']);
            return;
        }
        $movement = $this->model->getById((int)$id);
        if (!$movement) {
            $this->json(['success' => false, 'error' => 'Не знайдено']);
            return;
        }
        $this->json(['success' => true, 'data' => $movement]);
    }

    private function getSorting(): array
    {
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
        
        return ['key' => $sortKey, 'dir' => $sortDir, 'orderBy' => $orderBy];
    }

/**
 * Отримати історію змін для AJAX
 */
public function history($id = null): void
{
    if (!$id) {
        $this->json(['success' => false, 'error' => 'ID не вказано']);
        return;
    }
    
    $history = $this->model->getHistory((int)$id);
    
    // Форматуємо дати
    foreach ($history as &$item) {
        $item['changed_at_formatted'] = date('d.m.Y H:i:s', strtotime($item['changed_at']));
        if ($item['action'] === 'UPDATE') {
            $item['action_text'] = 'Редагування';
        } elseif ($item['action'] === 'DELETE') {
            $item['action_text'] = 'Видалення';
        } else {
            $item['action_text'] = $item['action'];
        }
    }
    
    $this->json(['success' => true, 'history' => $history]);
}

}