<?php

class ResourceTypesController extends Controller
{
    use AuthorizeTrait;
    
    private ResourceModel $model;

    public function __construct(Database $db)
    {
        parent::__construct($db);
        $this->model = new ResourceModel($db);
    }

    public function index(): void
    {
        $this->checkAccess('index');
        
        $types = $this->model->getTypes();

        $this->render('resources/types', [
            'title' => 'Типи ресурсів',
            'types' => $types,
            'activePage' => 'resource-types',
        ]);
    }

    public function save($id = null): void
    {
        $this->checkAccess('save');
        
        if (!$this->isPost()) {
            $this->redirect('resource-types');
            return;
        }

        $name = trim($this->post('name', ''));
        $unit = trim($this->post('unit', ''));
        $format = $this->post('format', 'int');
        $showHours = $this->getCheckbox('show_hours');
        
        if (!in_array($format, ['int', 'dec2', 'hm'])) {
            $format = 'int';
        }

        if (!$name || !$unit) {
            $this->respondAjax(false, 'Заповніть назву та одиницю');
            return;
        }

        $typesModel = new ResourceTypesModel($this->db);
        
        if ($id) {
            $typesModel->updateType((int)$id, $name, $unit, $format, $showHours);
            $message = 'Тип оновлено';
        } else {
            $typesModel->createType($name, $unit, $format, $showHours);
            $message = 'Тип додано';
        }

        $this->respondAjax(true, $message);
    }

    public function delete($id): void
    {
        $this->checkAccess('delete');
        
        if ($this->model->isTypeUsed((int)$id)) {
            $this->flash('error', 'Неможливо видалити: тип використовується');
        } else {
            $this->model->deleteType((int)$id);
            $this->flash('success', 'Тип видалено');
        }
        $this->redirect('resource-types');
    }

    private function respondAjax(bool $success, string $message): void
    {
        if ($this->isAjax()) {
            $this->json(['success' => $success, $success ? 'message' : 'error' => $message]);
        } else {
            $this->flash($success ? 'success' : 'error', $message);
            $this->redirect('resource-types');
        }
    }

    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    protected function getCheckbox(string $key): int
    {
        return $this->post($key) ? 1 : 0;
    }
}
