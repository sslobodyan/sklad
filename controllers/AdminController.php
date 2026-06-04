<?php

class AdminController extends Controller
{
    use AuthorizeTrait;

    public function __construct(Database $db)
    {
        parent::__construct($db);
    }

    private function checkAdmin(): void
    {
        $groups = $_SESSION['nc_groups'] ?? [];
        if (!in_array('admin', $groups)) {
            $this->flash('error', 'Доступ заборонено');
            $this->redirect('dashboard');
            exit;
        }
    }

    public function backup(): void
    {
        $this->checkAdmin();
        $this->checkAccess('backup');
        
        $this->render('admin/backup', [
            'title' => 'Бекап бази даних',
            'activePage' => 'admin-backup',
        ]);
    }

    public function restore(): void
    {
        $this->checkAdmin();
        $this->checkAccess('restore');
        
        $this->render('admin/restore', [
            'title' => 'Відновлення бази даних',
            'activePage' => 'admin-restore',
        ]);
    }

    public function doBackup(): void
    {
        $this->checkAdmin();
        $this->checkAccess('doBackup');
        
        if (!$this->isPost()) {
            $this->redirect('admin/backup');
            return;
        }

        $config = Database::getCurrentConfig();
        $filename = 'sklad_backup_' . date('Y-m-d_H-i-s') . '_' . $config['name'] . '.sql';
        $zipFilename = str_replace('.sql', '.zip', $filename);
        
        $sqlFile = sys_get_temp_dir() . '/' . $filename;
        $zipFile = sys_get_temp_dir() . '/' . $zipFilename;
        
        $cmd = sprintf(
            'mysqldump --host=%s --user=%s --password=%s %s --routines --triggers --single-transaction --default-character-set=utf8mb4 2>&1',
            escapeshellarg($config['host']),
            escapeshellarg($config['user']),
            escapeshellarg($config['pass']),
            escapeshellarg($config['name'])
        );
        
        exec($cmd, $output, $returnCode);
        
        if ($returnCode !== 0) {
            $this->flash('error', 'Помилка створення бекапу: ' . implode("\n", $output));
            $this->redirect('admin/backup');
            return;
        }
        
        file_put_contents($sqlFile, implode("\n", $output));
        
        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->flash('error', 'Не вдалося створити ZIP архів');
            $this->redirect('admin/backup');
            return;
        }
        
        $zip->addFile($sqlFile, $filename);
        $zip->close();
        unlink($sqlFile);
        
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipFilename . '"');
        header('Content-Length: ' . filesize($zipFile));
        header('Cache-Control: max-age=0');
        readfile($zipFile);
        unlink($zipFile);
        exit;
    }

    public function doRestore(): void
    {
        $this->checkAdmin();
        $this->checkAccess('doRestore');
        
        if (!$this->isPost() || empty($_FILES['backup_file'])) {
            $this->flash('error', 'Файл не вибрано');
            $this->redirect('admin/restore');
            return;
        }
        
        $file = $_FILES['backup_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->flash('error', 'Помилка завантаження файлу');
            $this->redirect('admin/restore');
            return;
        }
        
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['sql', 'zip'])) {
            $this->flash('error', 'Підтримуються тільки .sql або .zip файли');
            $this->redirect('admin/restore');
            return;
        }
        
        if ($ext === 'zip') {
            $zip = new ZipArchive();
            if ($zip->open($file['tmp_name']) !== true) {
                $this->flash('error', 'Не вдалося відкрити ZIP архів');
                $this->redirect('admin/restore');
                return;
            }
            $sqlContent = '';
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                if (pathinfo($filename, PATHINFO_EXTENSION) === 'sql') {
                    $sqlContent = $zip->getFromName($filename);
                    break;
                }
            }
            $zip->close();
            if (empty($sqlContent)) {
                $this->flash('error', 'У ZIP архіві не знайдено .sql файлу');
                $this->redirect('admin/restore');
                return;
            }
        } else {
            $sqlContent = file_get_contents($file['tmp_name']);
        }
        
        $config = Database::getCurrentConfig();
        $sqlFile = sys_get_temp_dir() . '/restore_' . time() . '.sql';
        file_put_contents($sqlFile, $sqlContent);
        
        $cmd = sprintf(
            'mysql --host=%s --user=%s --password=%s %s < %s 2>&1',
            escapeshellarg($config['host']),
            escapeshellarg($config['user']),
            escapeshellarg($config['pass']),
            escapeshellarg($config['name']),
            escapeshellarg($sqlFile)
        );
        
        exec($cmd, $output, $returnCode);
        unlink($sqlFile);
        
        if ($returnCode !== 0) {
            $this->flash('error', 'Помилка відновлення: ' . implode("\n", $output));
        } else {
            $this->flash('success', 'Базу даних успішно відновлено');
        }
        
        $this->redirect('admin/restore');
    }

    public function users(): void
    {
        $this->checkAdmin();
        $this->checkAccess('users');
        
        $userRoleModel = new UserRoleModel($this->db);
        $users = $userRoleModel->getAllUsers();
        
        $this->render('admin/users', [
            'title' => 'Користувачі системи',
            'users' => $users,
            'activePage' => 'admin-users',
        ]);
    }

    public function userEdit($id = null): void
    {
        $this->checkAdmin();
        $this->checkAccess('userEdit');
        
        $userRoleModel = new UserRoleModel($this->db);
        $warehouseModel = new WarehouseModel($this->db);
        $materialModel = new MaterialModel($this->db);
        $resourceModel = new ResourceModel($this->db);
        
        $user = null;
        if ($id) {
            $allUsers = $userRoleModel->getAllUsers();
            foreach ($allUsers as $u) {
                if ($u['id'] == $id) {
                    $user = $u;
                    break;
                }
            }
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
            'activePage' => 'admin-users',
        ]);
    }

    public function userSave(): void
    {
        $this->checkAdmin();
        $this->checkAccess('userSave');
        
        if (!$this->isPost()) {
            $this->redirect('admin/users');
            return;
        }
        
        $ncUser = $this->post('nc_user');
        $role = $this->post('role', 'viewer');
        
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
            $this->redirect('admin/users');
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
        $this->redirect('admin/users');
    }

    public function userDelete($id): void
    {
        $this->checkAdmin();
        $this->checkAccess('userDelete');
        
        $userRoleModel = new UserRoleModel($this->db);
        $allUsers = $userRoleModel->getAllUsers();
        
        $user = null;
        foreach ($allUsers as $u) {
            if ($u['id'] == $id) {
                $user = $u;
                break;
            }
        }
        
        if ($user) {
            $userRoleModel->deleteUser($user['nc_user']);
            $this->flash('success', 'Користувача видалено');
        } else {
            $this->flash('error', 'Користувача не знайдено');
        }
        
        $this->redirect('admin/users');
    }

    public function menu(): void
    {
        $this->checkAdmin();
        $this->checkAccess('menu');
        
        $menuModel = new MenuModel($this->db);
        $menuItems = $menuModel->getAllItems();
        $groups = $menuModel->getGroups();
        
        $this->render('admin/menu', [
            'title' => 'Управління меню',
            'menuItems' => $menuItems,
            'groups' => $groups,
            'activePage' => 'admin-menu',
        ]);
    }

    public function menuSave(): void
    {
        $this->checkAdmin();
        $this->checkAccess('menuSave');
        
        if (!$this->isPost()) {
            $this->redirect('admin/menu');
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
        $this->redirect('admin/menu');
    }

    public function menuReorder(): void
    {
        $this->checkAdmin();
        $this->checkAccess('menuReorder');
        
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

    public function permissions($userId = null): void
    {
        $this->checkAdmin();
        $this->checkAccess('permissions');
        
        $userRoleModel = new UserRoleModel($this->db);
        $menuModel = new MenuModel($this->db);
        $permissionModel = new UserMenuPermissionModel($this->db);
        
        $users = $userRoleModel->getAllUsers();
        
        $selectedUser = null;
        $userPermissions = [];
        
        $requestedId = $_GET['user_id'] ?? $userId ?? null;
        
        if ($requestedId) {
            foreach ($users as $u) {
                if ($u['id'] == $requestedId) {
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
            'activePage' => 'admin-permissions',
        ]);
    }

    public function permissionsSave(): void
    {
        $this->checkAdmin();
        $this->checkAccess('permissionsSave');
        
        if (!$this->isPost()) {
            $this->redirect('admin/permissions');
            return;
        }
        
        $ncUser = $this->post('nc_user');
        $permissions = $this->post('permissions', []);
        
        if (empty($ncUser)) {
            $this->flash('error', 'Користувача не вибрано');
            $this->redirect('admin/permissions');
            return;
        }
        
        $menuModel = new MenuModel($this->db);
        $allItems = $menuModel->getAllItems();
        foreach ($allItems as $item) {
            if ($item['controller'] !== null && !isset($permissions[$item['id']])) {
                $permissions[$item['id']] = 'none';
            }
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
        
        $this->redirect('admin/permissions?user_id=' . $userId);
    }
}