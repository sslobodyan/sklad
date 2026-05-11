<?php
/**
 * Контролер матеріалів
 */

class MaterialsController extends Controller
{
    private MaterialModel $model;

    public function __construct(Database $db)
    {
        parent::__construct($db);
        $this->model = new MaterialModel($db);
    }

    public function index(): void
    {
        $materials = $this->model->getAll('name ASC');
        $usedIds = $this->model->getUsedIds();
        
        $this->render('materials/index', [
            'title' => 'Матеріали',
            'materials' => $materials,
            'usedIds' => $usedIds,
            'activePage' => 'materials',
        ]);
    }

    /**
     * Збереження (AJAX)
     */
    public function save($id = null): void
    {
        if (!$this->isPost()) {
            $this->redirect('materials');
            return;
        }

        $name = trim($this->post('name', ''));
        
        if (empty($name)) {
            $this->jsonResponse(false, 'Введіть назву матеріалу');
            return;
        }

        try {
            if ($id) {
                $this->model->update((int)$id, $name);
                $message = 'Матеріал оновлено';
            } else {
                $id = $this->model->create($name);
                $message = 'Матеріал додано';
            }
            
            if ($this->isAjax()) {
                $this->json(['success' => true, 'message' => $message, 'id' => $id]);
            } else {
                $this->flash('success', $message);
                $this->redirect('materials');
            }
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Помилка збереження');
        }
    }

    public function delete($id): void
    {
        if ($this->model->isUsed((int)$id)) {
            $this->flash('error', 'Неможливо видалити: матеріал використовується в документах');
        } else {
            $this->model->delete((int)$id);
            $this->flash('success', 'Матеріал видалено');
        }
        $this->redirect('materials');
    }

    private function jsonResponse(bool $success, string $message): void
    {
        if ($this->isAjax()) {
            $this->json(['success' => $success, 'error' => $success ? null : $message]);
        } else {
            $this->flash($success ? 'success' : 'error', $message);
            $this->redirect('materials');
        }
    }

    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
