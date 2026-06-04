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
    error_log("=== UserMenuPermissionModel::setUserPermissions ===");
    error_log("ncUser: " . $ncUser);
    error_log("permissions array size: " . count($permissions));
    
    try {
        $this->db->query("START TRANSACTION");
        error_log("Transaction started");
        
        // Видаляємо старі права
        $deleteSql = "DELETE FROM user_menu_permissions WHERE nc_user = ?";
        error_log("Deleting old permissions: " . $deleteSql . " with user=" . $ncUser);
        $this->db->query($deleteSql, [$ncUser]);
        error_log("Old permissions deleted");
        
        // Вставляємо нові
        $inserted = 0;
        foreach ($permissions as $menuItemId => $accessLevel) {
            error_log("Processing: menuItemId=$menuItemId, accessLevel=$accessLevel");
            if ($accessLevel !== 'none' && !empty($accessLevel)) {
                $insertSql = "INSERT INTO user_menu_permissions (nc_user, menu_item_id, access_level) VALUES (?, ?, ?)";
                error_log("Inserting: $insertSql, values: [$ncUser, $menuItemId, $accessLevel]");
                $this->db->query($insertSql, [$ncUser, $menuItemId, $accessLevel]);
                $inserted++;
                error_log("Inserted successfully");
            } else {
                error_log("Skipping because accessLevel is none or empty");
            }
        }
        
        $this->db->query("COMMIT");
        error_log("Transaction committed. Inserted $inserted records");
        return true;
    } catch (Exception $e) {
        $this->db->query("ROLLBACK");
        error_log("ERROR: " . $e->getMessage());
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