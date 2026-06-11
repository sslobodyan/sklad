<?php

class AdminPermissionsController extends Controller
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
        $menuModel = new MenuModel($this->db);
        $permissionModel = new UserMenuPermissionModel($this->db);
        
        $users = $userRoleModel->getAllUsers();
        
        $selectedUser = null;
        $userPermissions = [];
        
        $userId = (int)($_GET['user_id'] ?? 0);
        
        if ($userId) {
            foreach ($users as $u) {
                if ($u['id'] == $userId) {
                    $selectedUser = $u;
                    break;
                }
            }
        }
        
        if (!$selectedUser && !empty($users)) {
            $selectedUser = $users[0];
        }
        
        if ($selectedUser) {
            $userPermissions = $permissionModel->getUserPermissions($selectedUser['nc_user']);
        }
        
        $menuItems = $menuModel->getAllItems();
        $groups = $menuModel->getGroups();
        
        $this->render('admin/permissions', [
            'title' => 'Права доступу',
            'users' => $users,
            'selectedUser' => $selectedUser,
            'userPermissions' => $userPermissions,
            'menuItems' => $menuItems,
            'groups' => $groups,
            'activePage' => 'adminPermissions',
        ]);
    }

public function save(): void
{
    $this->checkAccess('save');
    
    if (!$this->isPost()) {
        $this->redirect('adminPermissions');
        return;
    }
    
    $ncUser = $this->post('nc_user');
    $permissions = $this->post('permissions', []);
    
    if (empty($ncUser)) {
        $this->flash('error', 'Користувача не вибрано');
        $this->redirect('adminPermissions');
        return;
    }
    
    $permissionModel = new UserMenuPermissionModel($this->db);
    $result = $permissionModel->setUserPermissions($ncUser, $permissions);
    
    
    if ($result) {
        $this->flash('success', 'Права збережено');
    } else {
        $this->flash('error', 'Помилка збереження прав');
    }
    
    $userRoleModel = new UserRoleModel($this->db);
    $users = $userRoleModel->getAllUsers();
    $userId = null;
    foreach ($users as $u) {
        if ($u['nc_user'] === $ncUser) {
            $userId = $u['id'];
            break;
        }
    }
    
    $this->redirect('adminPermissions?user_id=' . $userId);
}

}