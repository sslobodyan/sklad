<?php

class PermissionManager
{
    private static ?PermissionManager $instance = null;
    private Database $db;
    private ?UserRoleModel $userRoleModel = null;
    private ?UserMenuPermissionModel $permissionModel = null;
    private ?MenuModel $menuModel = null;

    const VIEW_METHODS = ['index', 'getone', 'history', 'export', 'get', 'view', 'show'];
    const EDIT_METHODS = [
        'save', 'update', 'delete', 'add', 'editlog', 'import',
        'doBackup', 'doRestore', 'saverate', 'addresource',
        'removeresource', 'deleterate', 'deletetype', 'savetype',
        'create', 'store', 'destroy', 'edit'
    ];

    private function __construct(Database $db)
    {
        $this->db = $db;
    }

    public static function getInstance(Database $db): self
    {
        if (self::$instance === null) {
            self::$instance = new self($db);
        }
        return self::$instance;
    }

    private function getUserRoleModel(): UserRoleModel
    {
        if ($this->userRoleModel === null) {
            $this->userRoleModel = new UserRoleModel($this->db);
        }
        return $this->userRoleModel;
    }

    private function getPermissionModel(): UserMenuPermissionModel
    {
        if ($this->permissionModel === null) {
            $this->permissionModel = new UserMenuPermissionModel($this->db);
        }
        return $this->permissionModel;
    }

    private function getMenuModel(): MenuModel
    {
        if ($this->menuModel === null) {
            $this->menuModel = new MenuModel($this->db);
        }
        return $this->menuModel;
    }

    public function canAccess(string $ncUser, string $controller, string $action): bool
    {
        error_log("canAccess called for controller: " . $controller . ", action: " . $action);

        if (empty($ncUser)) {
            return false;
        }

        if ($this->isAdminFromSession()) {
            return true;
        }

        $menuItem = $this->getMenuModel()->getByController($controller);
        if (!$menuItem) {
            return false;
        }

        $accessLevel = $this->getPermissionModel()->getAccessLevel($ncUser, $menuItem['id']);

        if ($accessLevel === 'none') {
            return false;
        }

        if ($accessLevel === 'view') {
            return in_array($action, self::VIEW_METHODS);
        }

        if ($accessLevel === 'edit') {
            return true;
        }

        return false;
    }

    public function getAccessLevel(string $ncUser, string $controller): string
    {
        if ($this->isAdminFromSession()) {
            return 'edit';
        }

        $menuItem = $this->getMenuModel()->getByController($controller);
        if (!$menuItem) {
            return 'none';
        }

        return $this->getPermissionModel()->getAccessLevel($ncUser, $menuItem['id']);
    }

    public function getAllowedWarehouses(string $ncUser): ?array
    {
        if ($this->isAdminFromSession()) {
            return null;
        }
        
        $user = $this->getUserRoleModel()->getUser($ncUser);
        if (!$user) {
            return [];
        }
        
        return $user['allowed_warehouses'];
    }

    public function canViewWarehouse(string $ncUser, int $warehouseId): bool
    {
        if ($this->isAdminFromSession()) {
            return true;
        }

        return $this->getUserRoleModel()->hasWarehouseAccess($ncUser, $warehouseId);
    }

    public function canViewMaterial(string $ncUser, int $materialId): bool
    {
        if ($this->isAdminFromSession()) {
            return true;
        }

        return $this->getUserRoleModel()->hasMaterialAccess($ncUser, $materialId);
    }

    public function canViewResourceType(string $ncUser, int $typeId): bool
    {
        if ($this->isAdminFromSession()) {
            return true;
        }

        return $this->getUserRoleModel()->hasResourceTypeAccess($ncUser, $typeId);
    }

    public function filterWarehouses(string $ncUser, array $warehouses): array
    {
        if ($this->isAdminFromSession()) {
            return $warehouses;
        }

        return $this->getUserRoleModel()->filterWarehouses($ncUser, $warehouses);
    }

    public function filterMaterials(string $ncUser, array $materials): array
    {
        if ($this->isAdminFromSession()) {
            return $materials;
        }

        return $this->getUserRoleModel()->filterMaterials($ncUser, $materials);
    }

    public function filterResourceTypes(string $ncUser, array $types): array
    {
        if ($this->isAdminFromSession()) {
            return $types;
        }

        $user = $this->getUserRoleModel()->getUser($ncUser);
        if (!$user) {
            return [];
        }

        $allowed = $user['allowed_resource_types'];
        if ($allowed === null) {
            return $types;
        }

        return array_filter($types, function($type) use ($allowed) {
            $id = is_array($type) ? $type['id'] : $type;
            return in_array($id, $allowed);
        });
    }

    public function canEditRates(string $ncUser): bool
    {
        if ($this->isAdminFromSession()) {
            return true;
        }

        $user = $this->getUserRoleModel()->getUser($ncUser);
        return $user ? (bool)$user['can_edit_rates'] : false;
    }

    public function canExport(string $ncUser): bool
    {
        if ($this->isAdminFromSession()) {
            return true;
        }

        $user = $this->getUserRoleModel()->getUser($ncUser);
        return $user ? (bool)$user['can_export'] : false;
    }

    public function canImport(string $ncUser): bool
    {
        if ($this->isAdminFromSession()) {
            return true;
        }

        $user = $this->getUserRoleModel()->getUser($ncUser);
        return $user ? (bool)$user['can_import'] : false;
    }

    public function isAdmin(string $ncUser): bool
    {
        return $this->isAdminFromSession();
    }

    public function logActivity(string $ncUser, string $action, string $controller, ?int $itemId = null, ?array $details = null): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $detailsJson = $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null;

        $this->db->query(
            "INSERT INTO user_activity_log (nc_user, action, controller, item_id, details, ip_address, user_agent) 
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$ncUser, $action, $controller, $itemId, $detailsJson, $ip, $userAgent]
        );
    }

    public static function getCurrentUser(): string
    {
        return $_SESSION['nc_user'] ?? '';
    }

    public static function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    private function isAdminFromSession(): bool
    {
        $groups = $_SESSION['nc_groups'] ?? [];
        return in_array('admin', $groups);
    }

    public function syncCurrentUser(): void
    {
        $ncUser = self::getCurrentUser();
        if (!empty($ncUser)) {
            $this->getUserRoleModel()->syncFromSession();
        }
    }
}