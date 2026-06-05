<?php

class AdminMenuController extends Controller
{
    use AuthorizeTrait;
    
    public function __construct(Database $db)
    {
        parent::__construct($db);
    }

    public function index(): void
    {
        $this->checkAccess('index');
        
        $menuModel = new MenuModel($this->db);
        $menuItems = $menuModel->getAllItems();
        $groups = $menuModel->getGroups();
        
        $this->render('admin/menu', [
            'title' => 'Управління меню',
            'menuItems' => $menuItems,
            'groups' => $groups,
            'activePage' => 'adminMenu',
        ]);
    }

public function save(): void
{
    $this->checkAccess('save');
    
    if (!$this->isPost()) {
        $this->redirect('adminMenu');
        return;
    }
    
    $items = $this->post('items', []);
    $menuModel = new MenuModel($this->db);
    
    foreach ($items as $id => $data) {
        $updateData = [];
        
        if (isset($data['label'])) {
            $updateData['label'] = $data['label'];
        }
        if (isset($data['parent_id'])) {
            $updateData['parent_id'] = $data['parent_id'] ?: null;
        }
        if (isset($data['sort_order'])) {
            $updateData['sort_order'] = (int)$data['sort_order'];
        }
        if (isset($data['is_enabled'])) {
            $updateData['is_enabled'] = 1;
        } else {
            $updateData['is_enabled'] = 0;
        }
        if (isset($data['requires_date_range'])) {
            $updateData['requires_date_range'] = 1;
        } else {
            $updateData['requires_date_range'] = 0;
        }
        
        if (!empty($updateData)) {
            $menuModel->updateItem((int)$id, $updateData);
        }
    }
    
    $this->flash('success', 'Меню збережено');
    $this->redirect('adminMenu');
}

    public function save_old(): void
    {
        $this->checkAccess('save');
        
        if (!$this->isPost()) {
            $this->redirect('adminMenu');
            return;
        }
        
        $items = $this->post('items', []);
        $menuModel = new MenuModel($this->db);
        
        foreach ($items as $id => $data) {
            $updateData = [];
            if (isset($data['label'])) {
                $updateData['label'] = $data['label'];
            }
            if (isset($data['is_enabled'])) {
                $updateData['is_enabled'] = 1;
            } else {
                $updateData['is_enabled'] = 0;
            }
            if (isset($data['requires_date_range'])) {
                $updateData['requires_date_range'] = 1;
            } else {
                $updateData['requires_date_range'] = 0;
            }
            
            if (!empty($updateData)) {
                $menuModel->updateItem((int)$id, $updateData);
            }
        }
        
        $this->flash('success', 'Меню збережено');
        $this->redirect('adminMenu');
    }

    public function reorder(): void
    {
        $this->checkAccess('reorder');
        
        if (!$this->isPost()) {
            $this->json(['success' => false, 'error' => 'Невірний метод']);
            return;
        }
        
        $order = $this->post('order', []);
        $menuModel = new MenuModel($this->db);
        
        if ($menuModel->updateOrder($order)) {
            $this->json(['success' => true]);
        } else {
            $this->json(['success' => false, 'error' => 'Помилка збереження порядку']);
        }
    }

    public function sync(): void
    {
        $this->checkAdmin();
        $this->checkAccess('sync');
    
        if (!$this->isPost()) {
            $this->json(['success' => false, 'error' => 'Невірний метод']);
            return;
        }
    
        $menuModel = new MenuModel($this->db);
        $newControllers = $menuModel->syncFromFiles();
    
        $this->json([
            'success' => true,
            'added' => $newControllers
        ]);
    }

public function addGroup(): void
{
    $this->checkAccess('addGroup');
    
    if (!$this->isPost()) {
        $this->redirect('adminMenu');
        return;
    }
    
    $groupName = trim($this->post('group_name', ''));
    $sortOrder = (int)$this->post('sort_order', 999);
    
    if (empty($groupName)) {
        $this->flash('error', 'Введіть назву групи');
        $this->redirect('adminMenu');
        return;
    }
    
    $menuModel = new MenuModel($this->db);
    $menuModel->createItem([
        'controller' => null,
        'label' => $groupName,
        'parent_id' => null,
        'sort_order' => $sortOrder,
        'is_enabled' => 1,
        'requires_date_range' => 0,
    ]);
    
    $this->flash('success', 'Групу додано');
    $this->redirect('adminMenu');
}

}