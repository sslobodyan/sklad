<?php

class MenuModel extends Model
{
    protected string $table = 'menu_items';

    public function getAllItems(): array
    {
        return $this->db->query(
            "SELECT * FROM menu_items ORDER BY sort_order ASC, id ASC"
        )->fetchAll();
    }

    public function getUserMenu(string $ncUser): array
    {
        $groups = $_SESSION['nc_groups'] ?? [];
        $isAdmin = in_array('admin', $groups);
        
        if ($isAdmin) {
            $items = $this->db->query(
                "SELECT * FROM menu_items WHERE is_enabled = 1 ORDER BY sort_order ASC, id ASC"
            )->fetchAll();
        } else {
            $groups = $this->db->query(
                "SELECT * FROM menu_items WHERE parent_id IS NULL AND is_enabled = 1 ORDER BY sort_order ASC, id ASC"
            )->fetchAll();
            
            $allowedItems = $this->db->query(
                "SELECT m.* FROM menu_items m
                 INNER JOIN user_menu_permissions ump ON m.id = ump.menu_item_id
                 WHERE m.is_enabled = 1 
                   AND ump.nc_user = ? 
                   AND ump.access_level != 'none'
                   AND m.parent_id IS NOT NULL
                 ORDER BY m.sort_order ASC, m.id ASC",
                [$ncUser]
            )->fetchAll();
            
            $items = array_merge($groups, $allowedItems);
        }
        
        return $this->buildMenuTree($items);
    }

    public function getByController(string $controller): ?array
    {
        $result = $this->db->query(
            "SELECT * FROM menu_items WHERE controller = ? AND is_enabled = 1",
            [$controller]
        )->fetch();
        
        return $result ?: null;
    }

public function syncFromFiles(): array
{
    error_log("=== syncFromFiles START ===");
    
    $controllersDir = ROOT_PATH . '/controllers/';
    $files = glob($controllersDir . '*Controller.php');
    error_log("Files found: " . count($files));
    
    $excludePatterns = ['Export', 'Import', 'Helper', 'Report', 'Rates'];
    $newControllers = [];
    
    $existing = $this->db->query(
        "SELECT controller FROM menu_items WHERE controller IS NOT NULL"
    )->fetchAll();
    $existingControllers = array_map('strtolower', array_column($existing, 'controller'));
    error_log("Existing controllers (lowercase): " . print_r($existingControllers, true));
    
    foreach ($files as $file) {
        $basename = basename($file, 'Controller.php');
        error_log("Processing: " . $basename);
        
        $excluded = false;
        foreach ($excludePatterns as $pattern) {
            if (strpos($basename, $pattern) !== false) {
                $excluded = true;
                error_log("Excluded by pattern: " . $pattern);
                break;
            }
        }
        
        if ($excluded || in_array($basename, ['Model', 'Controller', 'Database'])) {
            continue;
        }
        
        $lowerBasename = strtolower($basename);
        error_log("Checking if exists: " . $basename . " (lowercase: " . $lowerBasename . ")");
        
        if (!in_array($lowerBasename, $existingControllers)) {
            error_log("Inserting new controller: " . $basename);
            $this->db->query(
                "INSERT INTO menu_items (controller, label, parent_id, sort_order, is_enabled) 
                 VALUES (?, ?, NULL, 0, 1)",
                [$basename, $basename]
            );
            $newControllers[] = $basename;
            error_log("Inserted: " . $basename);
        } else {
            error_log("Already exists, skipping");
        }
    }
    
    error_log("=== syncFromFiles END, new controllers: " . print_r($newControllers, true));
    return $newControllers;
}


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
    
    // Додаємо updated_at
    $fields[] = "updated_at = NOW()";
    
    // Додаємо author
    $fields[] = "author = ?";
    $params[] = $this->authorStamp();
    
    // Додаємо id в кінець
    $params[] = $id;
    
    $sql = "UPDATE menu_items SET " . implode(', ', $fields) . " WHERE id = ?";
    
    $this->db->query($sql, $params);
    
    return true;
}

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

    public function deleteItem(int $id): bool
    {
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

    public function getGroups(): array
    {
        return $this->db->query(
            "SELECT * FROM menu_items WHERE parent_id IS NULL ORDER BY sort_order ASC"
        )->fetchAll();
    }

    public function getChildren(int $parentId): array
    {
        return $this->db->query(
            "SELECT * FROM menu_items WHERE parent_id = ? ORDER BY sort_order ASC",
            [$parentId]
        )->fetchAll();
    }

    private function buildMenuTree(array $items): array
    {
        $groups = [];
        $children = [];
        
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

    private function getDefaultParentId(string $controller): ?int
    {
        $directories = ['warehouses', 'materials', 'resourcetypes', 'resourcerates'];
        if (in_array($controller, $directories)) {
            return 3;
        }
        
        $documents = ['movements', 'resources', 'dashboard'];
        if (in_array($controller, $documents)) {
            return 1;
        }
        
        if ($controller === 'reports') {
            return 2;
        }
        
        $system = ['simple', 'settings', 'admin'];
        if (in_array($controller, $system)) {
            return 4;
        }
        
        return null;
    }
}