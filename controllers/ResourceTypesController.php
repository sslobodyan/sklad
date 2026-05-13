<?php
/**
 * Контролер типів ресурсів
 */
class ResourceTypesController extends Controller
{
    private ResourceModel $model;

    public function __construct(Database $db)
    {
        parent::__construct($db);
        $this->model = new ResourceModel($db);
    }

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
        if (!$this->isPost()) {
            $this->redirect('resources/types');
            return;
        }

        $name = trim($this->post('name', ''));
        $unit = trim($this->post('unit', ''));
        $format = $this->post('format', 'int');
        
        if (!in_array($format, ['int', 'dec2', 'hm'])) {
            $format = 'int';
        }

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

    private function respondAjax(bool $success, string $message): void
    {
        if ($this->isAjax()) {
            $this->json(['success' => $success, $success ? 'message' : 'error' => $message]);
        } else {
            $this->flash($success ? 'success' : 'error', $message);
            $this->redirect('resources/types');
        }
    }

    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}