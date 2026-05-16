<?php
/**
 * Контролер складів
 */

class WarehousesController extends Controller
{
    private WarehouseModel $model;

    public function __construct(Database $db)
    {
        parent::__construct($db);
        $this->model = new WarehouseModel($db);
    }

    public function index(): void
    {
        $warehouses = $this->model->getAll('name ASC');
        $usedIds = $this->model->getUsedIds();
        
        $this->render('warehouses/index', [
            'title' => 'Склади',
            'warehouses' => $warehouses,
            'usedIds' => $usedIds,
            'activePage' => 'warehouses',
        ]);
    }

    /**
     * Збереження (AJAX)
     */
    public function save($id = null): void
    {
        if (!$this->isPost()) {
            $this->redirect('warehouses');
            return;
        }

        $name = trim($this->post('name', ''));
        
        if (empty($name)) {
            $this->jsonResponse(false, 'Введіть назву складу');
            return;
        }

        try {
            if ($id) {
                $this->model->update((int)$id, $name);
                $message = 'Склад оновлено';
            } else {
                $id = $this->model->create($name);
                $message = 'Склад додано';
            }
            
            if ($this->isAjax()) {
                $this->json(['success' => true, 'message' => $message, 'id' => $id]);
            } else {
                $this->flash('success', $message);
                $this->redirect('warehouses');
            }
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Помилка збереження');
        }
    }

    public function delete($id): void
    {
        if ($this->model->isUsed((int)$id)) {
            $this->flash('error', 'Неможливо видалити: склад використовується в документах');
        } else {
            $this->model->delete((int)$id);
            $this->flash('success', 'Склад видалено');
        }
        $this->redirect('warehouses');
    }

    private function jsonResponse(bool $success, string $message): void
    {
        if ($this->isAjax()) {
            $this->json(['success' => $success, 'error' => $success ? null : $message]);
        } else {
            $this->flash($success ? 'success' : 'error', $message);
            $this->redirect('warehouses');
        }
    }

    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

public function getone($id = null): void
{
    if (!$id) {
        $this->json(['success' => false, 'error' => 'ID не вказано']);
        return;
    }
    $item = $this->model->getById((int)$id);
    if (!$item) {
        $this->json(['success' => false, 'error' => 'Не знайдено']);
        return;
    }
    $this->json([
        'success' => true, 
        'data' => [
            'id' => $item['id'],
            'name' => $item['name'],
            'author' => $item['author'] ?? '',
            'created_at' => $item['created_at'] ?? null,
            'updated_at' => $item['updated_at'] ?? null
        ]
    ]);
}

}
