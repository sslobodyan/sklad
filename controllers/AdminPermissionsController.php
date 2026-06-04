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
    
    error_log("=== PERMISSIONS SAVE START ===");
    error_log("POST data: " . print_r($_POST, true));
    
    if (!$this->isPost()) {
        error_log("Not a POST request");
        $this->redirect('adminPermissions');
        return;
    }
    
    $ncUser = $this->post('nc_user');
    $permissions = $this->post('permissions', []);
    
    error_log("ncUser: " . $ncUser);
    error_log("permissions count: " . count($permissions));
    error_log("permissions: " . print_r($permissions, true));
    
    if (empty($ncUser)) {
        error_log("Empty ncUser");
        $this->flash('error', 'Користувача не вибрано');
        $this->redirect('adminPermissions');
        return;
    }
    
    error_log("Calling setUserPermissions...");
    $permissionModel = new UserMenuPermissionModel($this->db);
    $result = $permissionModel->setUserPermissions($ncUser, $permissions);
    
    error_log("setUserPermissions result: " . ($result ? "TRUE" : "FALSE"));
    
    if ($result) {
        $this->flash('success', 'Права збережено');
        error_log("Permissions saved successfully");
    } else {
        $this->flash('error', 'Помилка збереження прав');
        error_log("Permissions save FAILED");
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