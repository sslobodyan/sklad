<?php

class UserMenuPermissionModel extends Model
{
    protected string $table = 'user_menu_permissions';

    public function getUserPermissions(string $ncUser): array
    {
        $rows = $this->db->query(
            "SELECT menu_item_id, access_level FROM user_menu_permissions WHERE nc_user = ?",
            [$ncUser]
        )->fetchAll();
        
        $result = [];
        foreach ($rows as $row) {
            $result[$row['menu_item_id']] = $row['access_level'];
        }
        
        return $result;
    }

public function setUserPermissions(string $ncUser, array $permissions): bool
{
    try {
        $this->db->query("START TRANSACTION");
        
        // Видаляємо старі права
        $deleteSql = "DELETE FROM user_menu_permissions WHERE nc_user = ?";
        $this->db->query($deleteSql, [$ncUser]);
        
        // Вставляємо нові
        $inserted = 0;
        foreach ($permissions as $menuItemId => $accessLevel) {
            if ($accessLevel !== 'none' && !empty($accessLevel)) {
                $insertSql = "INSERT INTO user_menu_permissions (nc_user, menu_item_id, access_level) VALUES (?, ?, ?)";
                $this->db->query($insertSql, [$ncUser, $menuItemId, $accessLevel]);
                $inserted++;
            } else {
            }
        }
        
        $this->db->query("COMMIT");
        return true;
    } catch (Exception $e) {
        $this->db->query("ROLLBACK");
        return false;
    }
}

    public function getAccessLevel(string $ncUser, int $menuItemId): string
    {
        $result = $this->db->query(
            "SELECT access_level FROM user_menu_permissions 
             WHERE nc_user = ? AND menu_item_id = ?",
            [$ncUser, $menuItemId]
        )->fetch();
        
        return $result ? $result['access_level'] : 'none';
    }

    public function copyFromRole(string $ncUser, string $role): bool
    {
        $defaultPermissions = $this->getDefaultForRole($role);
        return $this->setUserPermissions($ncUser, $defaultPermissions);
    }

public function getDefaultForRole(string $role): array
{
    $menuModel = new MenuModel($this->db);
    $allItems = $menuModel->getAllItems();
    
    $permissions = [];
    
    foreach ($allItems as $item) {
        $controller = $item['controller'];
        
        if ($controller === null) {
            continue;
        }
        
        switch ($role) {
            case 'manager':
                if ($controller === 'adminUsers' || $controller === 'adminMenu' || $controller === 'adminPermissions' || $controller === 'adminBackup' || $controller === 'adminRestore') {
                    $permissions[$item['id']] = 'none';
                } else {
                    $permissions[$item['id']] = 'edit';
                }
                break;
                
            case 'viewer':
                if (in_array($controller, ['dashboard', 'movements', 'resources', 'reportWarehouse', 'reportMaterial', 'reportResource'])) {
                    $permissions[$item['id']] = 'view';
                } else {
                    $permissions[$item['id']] = 'none';
                }
                break;
                
            case 'fuel':
                if ($controller === 'simple') {
                    $permissions[$item['id']] = 'edit';
                } else {
                    $permissions[$item['id']] = 'none';
                }
                break;
                
            default:
                $permissions[$item['id']] = 'none';
        }
    }
    
    return $permissions;
}

    public function getDefaultForRole_old(string $role): array
    {
        $menuModel = new MenuModel($this->db);
        $allItems = $menuModel->getAllItems();
        
        $permissions = [];
        
        foreach ($allItems as $item) {
            $controller = $item['controller'];
            
            if ($controller === null) {
                continue;
            }
            
            switch ($role) {
                case 'manager':
                    if ($controller === 'admin') {
                        $permissions[$item['id']] = 'none';
                    } else {
                        $permissions[$item['id']] = 'edit';
                    }
                    break;
                    
                case 'viewer':
                    if (in_array($controller, ['movements', 'resources', 'reports', 'dashboard'])) {
                        $permissions[$item['id']] = 'view';
                    } else {
                        $permissions[$item['id']] = 'none';
                    }
                    break;
                    
                case 'fuel':
                    if ($controller === 'simple') {
                        $permissions[$item['id']] = 'edit';
                    } else {
                        $permissions[$item['id']] = 'none';
                    }
                    break;
                    
                default:
                    $permissions[$item['id']] = 'none';
            }
        }
        
        return $permissions;
    }

    public function getUsersByMenuItem(int $menuItemId): array
    {
        return $this->db->query(
            "SELECT nc_user, access_level FROM user_menu_permissions 
             WHERE menu_item_id = ? AND access_level != 'none'
             ORDER BY nc_user ASC",
            [$menuItemId]
        )->fetchAll();
    }

    public function deleteUserPermissions(string $ncUser): bool
    {
        $this->db->query(
            "DELETE FROM user_menu_permissions WHERE nc_user = ?",
            [$ncUser]
        );
        return true;
    }

    public function countUsersByLevel(int $menuItemId, string $level): int
    {
        $result = $this->db->query(
            "SELECT COUNT(*) as cnt FROM user_menu_permissions 
             WHERE menu_item_id = ? AND access_level = ?",
            [$menuItemId, $level]
        )->fetch();
        
        return (int)$result['cnt'];
    }
}