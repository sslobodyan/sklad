<?php

class MenuModel extends Model
{
    protected string $table = 'menu_items';

    /**
     * Отримати всі пункти меню
     */
    public function getAllItems(): array
    {
        return $this->db->query(
            "SELECT * FROM menu_items ORDER BY sort_order ASC, id ASC"
        )->fetchAll();
    }

    /**
     * Отримати дозволені пункти меню для користувача
     */
    public function getUserMenu(string $ncUser): array
    {
        // Перевіряємо чи користувач адмін (з nc_groups)
        $isAdmin = $this->isAdminFromSession();
        
        if ($isAdmin) {
            // Адмін бачить всі активні пункти
            $items = $this->db->query(
                "SELECT * FROM menu_items WHERE is_enabled = 1 ORDER BY sort_order ASC, id ASC"
            )->fetchAll();
        } else {
            // Звичайний користувач - тільки ті, де є права
            $items = $this->db->query(
                "SELECT m.* FROM menu_items m
                 INNER JOIN user_menu_permissions ump ON m.id = ump.menu_item_id
                 WHERE m.is_enabled = 1 
                   AND ump.nc_user = ? 
                   AND ump.access_level != 'none'
                 ORDER BY m.sort_order ASC, m.id ASC",
                [$ncUser]
            )->fetchAll();
        }
        
        // Будуємо дерево меню (групи та пункти)
        return $this->buildMenuTree($items);
    }

    /**
     * Отримати пункт меню за назвою контролера
     */
    public function getByController(string $controller): ?array
    {
        $result = $this->db->query(
            "SELECT * FROM menu_items WHERE controller = ? AND is_enabled = 1",
            [$controller]
        )->fetch();
        
        return $result ?: null;
    }

    /**
     * Отримати рівень доступу користувача до пункту меню
     */
    public function getAccessLevel(string $ncUser, int $menuItemId): string
    {
        // Адмін має edit
        if ($this->isAdminFromSession()) {
            return 'edit';
        }
        
        $result = $this->db->query(
            "SELECT access_level FROM user_menu_permissions 
             WHERE nc_user = ? AND menu_item_id = ?",
            [$ncUser, $menuItemId]
        )->fetch();
        
        return $result ? $result['access_level'] : 'none';
    }

    /**
     * Встановити права для користувача
     */
    public function setUserPermissions(string $ncUser, array $permissions): bool
    {
        try {
            // Починаємо транзакцію
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
     * Синхронізація контролерів з файлової системи
     */
    public function syncFromFiles(): array
    {
        $controllersDir = ROOT_PATH . '/controllers/';
        $files = glob($controllersDir . '*Controller.php');
        
        $excludePatterns = ['Export', 'Import', 'Helper', 'Report', 'Rates'];
        $newControllers = [];
        
        // Отримуємо існуючі контролери з БД
        $existing = $this->db->query(
            "SELECT controller FROM menu_items WHERE controller IS NOT NULL"
        )->fetchAll();
        $existingControllers = array_column($existing, 'controller');
        
        foreach ($files as $file) {
            $basename = basename($file, 'Controller.php');
            
            // Перевіряємо виключення
            $excluded = false;
            foreach ($excludePatterns as $pattern) {
                if (strpos($basename, $pattern) !== false) {
                    $excluded = true;
                    break;
                }
            }
            
            if ($excluded || in_array($basename, ['Model', 'Controller', 'Database'])) {
                continue;
            }
            
            $controller = strtolower($basename);
            
            // Якщо ще немає в БД - додаємо
            if (!in_array($controller, $existingControllers)) {
                $parentId = $this->getDefaultParentId($controller);
                
                $this->db->query(
                    "INSERT INTO menu_items (controller, label, parent_id, sort_order, is_enabled) 
                     VALUES (?, ?, ?, 999, 1)",
                    [$controller, $basename, $parentId]
                );
                
                $newControllers[] = $controller;
            }
        }
        
        return $newControllers;
    }

    /**
     * Оновити пункт меню
     */
    public function updateItem(int $id, array $data): bool
    {
        $fields = [];
        $params = [];
        
        $allowedFields = ['label', 'icon_svg', 'url', 'is_enabled', 'requires_date_range', 'sort_order', 'parent_id'];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $params[] = $id;
        $params[] = $this->authorStamp();
        
        $this->db->query(
            "UPDATE menu_items SET " . implode(', ', $fields) . ", updated_at = NOW(), author = ? WHERE id = ?",
            $params
        );
        
        return true;
    }

    /**
     * Оновити порядок сортування
     */
    public function updateOrder(array $order): bool
    {
        try {
            $this->db->query("START TRANSACTION");
            
            foreach ($order as $item) {
                $this->db->query(
                    "UPDATE menu_items SET sort_order = ? WHERE id = ?",
                    [$item['sort_order'], $item['id']]
                );
            }
            
            $this->db->query("COMMIT");
            return true;
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            return false;
        }
    }

    /**
     * Видалити пункт меню
     */
    public function deleteItem(int $id): bool
    {
        // Перевіряємо чи є діти
        $children = $this->db->query(
            "SELECT COUNT(*) as cnt FROM menu_items WHERE parent_id = ?",
            [$id]
        )->fetch();
        
        if ($children['cnt'] > 0) {
            return false;
        }
        
        $this->db->query("DELETE FROM menu_items WHERE id = ?", [$id]);
        return true;
    }

    /**
     * Створити новий пункт меню
     */
    public function createItem(array $data): int
    {
        $this->db->query(
            "INSERT INTO menu_items (controller, label, parent_id, sort_order, icon_svg, url, is_enabled, requires_date_range, author) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['controller'] ?? null,
                $data['label'],
                $data['parent_id'] ?? null,
                $data['sort_order'] ?? 999,
                $data['icon_svg'] ?? null,
                $data['url'] ?? null,
                $data['is_enabled'] ?? 1,
                $data['requires_date_range'] ?? 0,
                $this->authorStamp()
            ]
        );
        
        return $this->db->lastInsertId();
    }

    /**
     * Отримати всі групи (parent_id = NULL)
     */
    public function getGroups(): array
    {
        return $this->db->query(
            "SELECT * FROM menu_items WHERE parent_id IS NULL ORDER BY sort_order ASC"
        )->fetchAll();
    }

    /**
     * Отримати дітей групи
     */
    public function getChildren(int $parentId): array
    {
        return $this->db->query(
            "SELECT * FROM menu_items WHERE parent_id = ? ORDER BY sort_order ASC",
            [$parentId]
        )->fetchAll();
    }

    /**
     * Перевірка чи користувач адмін з сесії
     */
    private function isAdminFromSession(): bool
    {
        $groups = $_SESSION['nc_groups'] ?? [];
        return in_array('admin', $groups);
    }

    /**
     * Побудова дерева меню
     */
    private function buildMenuTree(array $items): array
    {
        $groups = [];
        $children = [];
        
        // Розділяємо групи та пункти
        foreach ($items as $item) {
            if ($item['parent_id'] === null) {
                $groups[] = $item;
            } else {
                if (!isset($children[$item['parent_id']])) {
                    $children[$item['parent_id']] = [];
                }
                $children[$item['parent_id']][] = $item;
            }
        }
        
        // Будуємо результат
        $result = [];
        foreach ($groups as $group) {
            $groupId = $group['id'];
            $result[] = [
                'id' => $groupId,
                'label' => $group['label'],
                'sort_order' => $group['sort_order'],
                'items' => $children[$groupId] ?? []
            ];
        }
        
        return $result;
    }

    /**
     * Визначення групи за замовчуванням для нового контролера
     */
    private function getDefaultParentId(string $controller): ?int
    {
        // Довідники
        $directories = ['warehouses', 'materials', 'resourcetypes', 'resourcerates'];
        if (in_array($controller, $directories)) {
            return 3; // ID групи Довідники
        }
        
        // Документи
        $documents = ['movements', 'resources', 'dashboard'];
        if (in_array($controller, $documents)) {
            return 1; // ID групи Документи
        }
        
        // Звіти
        if ($controller === 'reports') {
            return 2; // ID групи Звіти
        }
        
        // Система
        $system = ['simple', 'settings', 'admin'];
        if (in_array($controller, $system)) {
            return 4; // ID групи Система
        }
        
        return null;
    }
}