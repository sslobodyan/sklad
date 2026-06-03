<?php

class UserMenuPermissionModel extends Model
{
    protected string $table = 'user_menu_permissions';

    /**
     * Отримати всі права користувача
     * Повертає масив [menu_item_id => access_level]
     */
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

    /**
     * Встановити права для користувача
     * $permissions - масив [menu_item_id => access_level]
     */
    public function setUserPermissions(string $ncUser, array $permissions): bool
    {
        try {
            $this->db->query("START TRANSACTION");
            
            // Видаляємо старі права
            $this->db->query(
                "DELETE FROM user_menu_permissions WHERE nc_user = ?",
                [$ncUser]
            );
            
            // Вставляємо нові
            foreach ($permissions as $menuItemId => $accessLevel) {
                if ($accessLevel !== 'none') {
                    $this->db->query(
                        "INSERT INTO user_menu_permissions (nc_user, menu_item_id, access_level, author) 
                         VALUES (?, ?, ?, ?)",
                        [$ncUser, $menuItemId, $accessLevel, $this->authorStamp()]
                    );
                }
            }
            
            $this->db->query("COMMIT");
            return true;
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            return false;
        }
    }

    /**
     * Отримати рівень доступу до конкретного пункту меню
     */
    public function getAccessLevel(string $ncUser, int $menuItemId): string
    {
        $result = $this->db->query(
            "SELECT access_level FROM user_menu_permissions 
             WHERE nc_user = ? AND menu_item_id = ?",
            [$ncUser, $menuItemId]
        )->fetch();
        
        return $result ? $result['access_level'] : 'none';
    }

    /**
     * Скопіювати типові права для ролі
     */
    public function copyFromRole(string $ncUser, string $role): bool
    {
        $defaultPermissions = $this->getDefaultForRole($role);
        return $this->setUserPermissions($ncUser, $defaultPermissions);
    }

    /**
     * Отримати типові права для ролі
     */
    public function getDefaultForRole(string $role): array
    {
        // Отримуємо всі пункти меню
        $menuModel = new MenuModel($this->db);
        $allItems = $menuModel->getAllItems();
        
        $permissions = [];
        
        foreach ($allItems as $item) {
            $controller = $item['controller'];
            
            // Пропускаємо групи (controller = NULL)
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
                    if (in_array($controller, ['movements', 'resources', 'reports'])) {
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

    /**
     * Перевірити чи має користувач доступ до контролера
     */
    public function canAccessController(string $ncUser, string $controller, string $action): bool
    {
        // Адмін має повний доступ
        $groups = $_SESSION['nc_groups'] ?? [];
        if (in_array('admin', $groups)) {
            return true;
        }
        
        // Отримуємо пункт меню за контролером
        $menuModel = new MenuModel($this->db);
        $menuItem = $menuModel->getByController($controller);
        
        if (!$menuItem) {
            return false;
        }
        
        $accessLevel = $this->getAccessLevel($ncUser, $menuItem['id']);
        
        if ($accessLevel === 'none') {
            return false;
        }
        
        if ($accessLevel === 'view') {
            // Дозволені тільки GET методи
            $viewMethods = ['index', 'getone', 'history', 'export', 'get', 'view', 'show'];
            return in_array($action, $viewMethods);
        }
        
        if ($accessLevel === 'edit') {
            // Дозволені всі методи
            return true;
        }
        
        return false;
    }

    /**
     * Отримати всіх користувачів з правами для конкретного пункту меню
     */
    public function getUsersByMenuItem(int $menuItemId): array
    {
        return $this->db->query(
            "SELECT nc_user, access_level FROM user_menu_permissions 
             WHERE menu_item_id = ? AND access_level != 'none'
             ORDER BY nc_user ASC",
            [$menuItemId]
        )->fetchAll();
    }

    /**
     * Видалити всі права користувача
     */
    public function deleteUserPermissions(string $ncUser): bool
    {
        $this->db->query(
            "DELETE FROM user_menu_permissions WHERE nc_user = ?",
            [$ncUser]
        );
        return true;
    }

    /**
     * Отримати кількість користувачів з певним рівнем доступу до пункту
     */
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