<?php

class DashboardController extends Controller
{
    public function index(): void
    {
        $username = $_SESSION['nc_user'] ?? '';
        $displayName = $_SESSION['nc_display_name'] ?? '';
        $groups = $_SESSION['nc_groups'] ?? [];
        $isAdmin = in_array('admin', $groups);
        
        $sessionValid = !empty($username);
        
        if ($sessionValid && empty($_SESSION['last_login'])) {
            $_SESSION['last_login'] = date('Y-m-d H:i:s');
        }
        $lastLogin = $_SESSION['last_login'] ?? null;
        
        $stats = $this->getDashboardStats();
        $icons = $this->getMenuIcons();
        
        $this->render('dashboard/index', [
            'title' => 'Головна',
            'username' => $username,
            'displayName' => $displayName,
            'groups' => $groups,
            'isAdmin' => $isAdmin,
            'sessionValid' => $sessionValid,
            'lastLogin' => $lastLogin,
            'stats' => $stats,
            'warehouseIcon' => $icons['warehouses'] ?? null,
            'materialIcon' => $icons['materials'] ?? null,
            'resourceTypeIcon' => $icons['resourcetypes'] ?? null,
            'movementIcon' => $icons['movements'] ?? null,
            'resourceLogIcon' => $icons['resources'] ?? null,
            'activePage' => 'dashboard',
        ]);
    }

    public function changePassword(): void
    {
        //$this->checkAccess('changePassword');
        
        if (!$this->isPost()) {
            $this->json(['success' => false, 'error' => 'Невірний метод']);
            return;
        }
        
        $currentPassword = $this->post('current_password', '');
        $newPassword = $this->post('new_password', '');
        $confirmPassword = $this->post('confirm_password', '');
        
        if (strlen($newPassword) < 6) {
            $this->json(['success' => false, 'error' => 'Новий пароль повинен містити не менше 6 символів']);
            return;
        }
        
        if ($newPassword !== $confirmPassword) {
            $this->json(['success' => false, 'error' => 'Новий пароль і підтвердження не співпадають']);
            return;
        }
        
        $user = $this->db->query(
            "SELECT password_hash FROM user_roles WHERE nc_user = ?",
            [NC_USER]
        )->fetch();
        
        if (!$user || empty($user['password_hash']) || !password_verify($currentPassword, $user['password_hash'])) {
            $this->json(['success' => false, 'error' => 'Поточний пароль невірний']);
            return;
        }
        
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->db->query(
            "UPDATE user_roles SET password_hash = ? WHERE nc_user = ?",
            [$newHash, NC_USER]
        );
        
        $this->json(['success' => true, 'message' => 'Пароль успішно змінено']);
    }
    
    private function getDashboardStats(): array
    {
        $stats = [];
        
        $warehouseModel = new WarehouseModel($this->db);
        $stats['warehouses'] = $warehouseModel->count();
        
        $materialModel = new MaterialModel($this->db);
        $stats['materials'] = $materialModel->count();
        
        $today = date('Y-m-d');
        $movementModel = new MovementModel($this->db);
        $movementsToday = $this->db->query(
            "SELECT COUNT(*) as cnt FROM movements WHERE movement_date = ?",
            [$today]
        )->fetch();
        $stats['movements_today'] = (int)($movementsToday['cnt'] ?? 0);
        
        $resourceTypes = $this->db->query(
            "SELECT COUNT(*) as cnt FROM resource_types"
        )->fetch();
        $stats['resource_types'] = (int)($resourceTypes['cnt'] ?? 0);
        
        $resourceLogsToday = $this->db->query(
            "SELECT COUNT(*) as cnt FROM resource_logs WHERE log_date = ? AND delta IS NOT NULL",
            [$today]
        )->fetch();
        $stats['resource_logs_today'] = (int)($resourceLogsToday['cnt'] ?? 0);
        
        $lastMovements = $this->db->query(
            "SELECT m.*, 
                    wf.name AS warehouse_from_name,
                    wt.name AS warehouse_to_name,
                    mat.name AS material_name
             FROM movements m
             LEFT JOIN warehouses wf ON m.warehouse_from_id = wf.id
             LEFT JOIN warehouses wt ON m.warehouse_to_id = wt.id
             JOIN materials mat ON m.material_id = mat.id
             ORDER BY m.id DESC
             LIMIT 10"
        )->fetchAll();
        $stats['last_movements'] = $lastMovements;
        
        return $stats;
    }
    
    private function getMenuIcons(): array
    {
        $icons = [];
        $controllers = ['warehouses', 'materials', 'resourcetypes', 'movements', 'resources'];
        
        foreach ($controllers as $ctrl) {
            $item = $this->db->query(
                "SELECT icon_svg FROM menu_items WHERE controller = ?",
                [$ctrl]
            )->fetch();
            if ($item && !empty($item['icon_svg'])) {
                $icons[$ctrl] = $item['icon_svg'];
            }
        }
        
        return $icons;
    }
}