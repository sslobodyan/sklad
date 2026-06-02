<?php
/**
 * Контролер сторінки привітання (Dashboard)
 */

class DashboardController extends Controller
{
    public function index(): void
    {
        // Отримуємо дані користувача з сесії
        $username = $_SESSION['nc_user'] ?? '';
        $displayName = $_SESSION['nc_display_name'] ?? '';
        $groups = $_SESSION['nc_groups'] ?? [];
        $isAdmin = in_array('admin', $groups);
        
        // Перевіряємо чи сесія валідна (користувач авторизований)
        $sessionValid = !empty($username);
        
        // Останній вхід (зберігаємо в сесії)
        if ($sessionValid && empty($_SESSION['last_login'])) {
            $_SESSION['last_login'] = date('Y-m-d H:i:s');
        }
        $lastLogin = $_SESSION['last_login'] ?? null;
        
        // Статистика для дашборду
        $stats = $this->getDashboardStats();
        
        $this->render('dashboard/index', [
            'title' => 'Головна',
            'username' => $username,
            'displayName' => $displayName,
            'groups' => $groups,
            'isAdmin' => $isAdmin,
            'sessionValid' => $sessionValid,
            'lastLogin' => $lastLogin,
            'stats' => $stats,
            'activePage' => 'dashboard',
        ]);
    }
    
    /**
     * Отримати статистику для дашборду
     */
    private function getDashboardStats(): array
    {
        $stats = [];
        
        // Кількість складів
        $warehouseModel = new WarehouseModel($this->db);
        $stats['warehouses'] = $warehouseModel->count();
        
        // Кількість матеріалів
        $materialModel = new MaterialModel($this->db);
        $stats['materials'] = $materialModel->count();
        
        // Рухи за сьогодні
        $today = date('Y-m-d');
        $movementModel = new MovementModel($this->db);
        $movementsToday = $this->db->query(
            "SELECT COUNT(*) as cnt FROM movements WHERE movement_date = ?",
            [$today]
        )->fetch();
        $stats['movements_today'] = (int)($movementsToday['cnt'] ?? 0);
        
        // Рухи за поточний місяць
        $monthStart = date('Y-m-01');
        $monthEnd = date('Y-m-t');
        $movementsMonth = $this->db->query(
            "SELECT COUNT(*) as cnt FROM movements WHERE movement_date BETWEEN ? AND ?",
            [$monthStart, $monthEnd]
        )->fetch();
        $stats['movements_month'] = (int)($movementsMonth['cnt'] ?? 0);
        
        // Останні 5 рухів
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
             LIMIT 5"
        )->fetchAll();
        $stats['last_movements'] = $lastMovements;
        
        // Типи ресурсів
        $resourceTypes = $this->db->query(
            "SELECT COUNT(*) as cnt FROM resource_types"
        )->fetch();
        $stats['resource_types'] = (int)($resourceTypes['cnt'] ?? 0);

        $stats['resource_logs_today'] = (int)($this->db->query(
            "SELECT COUNT(*) as cnt FROM resource_logs WHERE log_date = ? AND delta IS NOT NULL",
            [$today]
        )->fetch()['cnt'] ?? 0);
        
        return $stats;
    }
}