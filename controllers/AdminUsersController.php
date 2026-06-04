<?php

class AdminUsersController extends Controller
{
    use AuthorizeTrait;
    
    public function __construct(Database $db)
    {
        parent::__construct($db);
    }

    public function index(): void
    {
        $this->checkAccess('index');
        
        $userRoleModel = new UserRoleModel($this->db);
        $users = $userRoleModel->getAllUsers();
        
        $this->render('admin/users', [
            'title' => 'Користувачі системи',
            'users' => $users,
            'activePage' => 'adminUsers',
        ]);
    }

    public function edit($id = null): void
    {
        $this->checkAccess('edit');
        
        $userRoleModel = new UserRoleModel($this->db);
        $warehouseModel = new WarehouseModel($this->db);
        $materialModel = new MaterialModel($this->db);
        $resourceModel = new ResourceModel($this->db);
        
        $user = null;
        if ($id) {
            $user = $userRoleModel->getUserById((int)$id);
        }
        
        $warehouses = $warehouseModel->getAll('name ASC');
        $materials = $materialModel->getAll('name ASC');
        $resourceTypes = $resourceModel->getTypes();
        
        $this->render('admin/user_edit', [
            'title' => 'Редагування користувача',
            'user' => $user,
            'warehouses' => $warehouses,
            'materials' => $materials,
            'resourceTypes' => $resourceTypes,
            'activePage' => 'adminUsers',
        ]);
    }

    public function save(): void
    {
        $this->checkAccess('save');
        
        if (!$this->isPost()) {
            $this->redirect('adminUsers');
            return;
        }
        
        $userId = (int)$this->post('user_id');
        $ncUser = $this->post('nc_user');
        $role = $this->post('role', 'viewer');
        
        // Якщо є userId, отримуємо існуючого користувача
        if ($userId) {
            $userRoleModel = new UserRoleModel($this->db);
            $existingUser = $userRoleModel->getUserById($userId);
            if ($existingUser) {
                $ncUser = $existingUser['nc_user'];
            }
        }
        
        $allowedWarehouses = $this->post('allowed_warehouses');
        $allowedWarehouses = !empty($allowedWarehouses) ? array_filter($allowedWarehouses) : null;
        
        $allowedMaterials = $this->post('allowed_materials');
        $allowedMaterials = !empty($allowedMaterials) ? array_filter($allowedMaterials) : null;
        
        $allowedResourceTypes = $this->post('allowed_resource_types');
        $allowedResourceTypes = !empty($allowedResourceTypes) ? array_filter($allowedResourceTypes) : null;
        
        $canEditRates = (bool)$this->post('can_edit_rates');
        $canExport = (bool)$this->post('can_export');
        $canImport = (bool)$this->post('can_import');
        
        if (empty($ncUser)) {
            $this->flash('error', 'Логін користувача обов\'язковий');
            $this->redirect('adminUsers');
            return;
        }
        
        $userRoleModel = new UserRoleModel($this->db);
        $userRoleModel->createOrUpdate($ncUser, [
            'role' => $role,
            'allowed_warehouses' => $allowedWarehouses,
            'allowed_materials' => $allowedMaterials,
            'allowed_resource_types' => $allowedResourceTypes,
            'can_edit_rates' => $canEditRates,
            'can_export' => $canExport,
            'can_import' => $canImport,
        ]);
        
        $this->flash('success', 'Користувача збережено');
        $this->redirect('adminUsers');
    }

    public function delete($id): void
    {
        $this->checkAccess('delete');
        
        $userRoleModel = new UserRoleModel($this->db);
        $user = $userRoleModel->getUserById((int)$id);
        
        if ($user) {
            $userRoleModel->deleteUser($user['nc_user']);
            $this->flash('success', 'Користувача видалено');
        } else {
            $this->flash('error', 'Користувача не знайдено');
        }
        
        $this->redirect('adminUsers');
    }
}