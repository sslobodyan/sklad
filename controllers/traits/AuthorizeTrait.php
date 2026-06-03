<?php

trait AuthorizeTrait
{
    private ?PermissionManager $permManager = null;

    protected function getPermManager(): PermissionManager
    {
        if ($this->permManager === null) {
            $this->permManager = PermissionManager::getInstance($this->db);
        }
        return $this->permManager;
    }

    protected function checkAccess(string $action): void
    {
        $user = PermissionManager::getCurrentUser();
        $controller = $this->getControllerName();

        if (!$this->getPermManager()->canAccess($user, $controller, $action)) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'error' => 'Доступ заборонено']);
            } else {
                $this->flash('error', 'Доступ заборонено');
                $this->redirect('dashboard');
            }
            exit;
        }
    }

    protected function getAccessLevel(string $controller): string
    {
        $user = PermissionManager::getCurrentUser();
        return $this->getPermManager()->getAccessLevel($user, $controller);
    }

    protected function filterWarehouses(array $warehouses): array
    {
        $user = PermissionManager::getCurrentUser();
        return $this->getPermManager()->filterWarehouses($user, $warehouses);
    }

    protected function filterMaterials(array $materials): array
    {
        $user = PermissionManager::getCurrentUser();
        return $this->getPermManager()->filterMaterials($user, $materials);
    }

    protected function filterResourceTypes(array $types): array
    {
        $user = PermissionManager::getCurrentUser();
        return $this->getPermManager()->filterResourceTypes($user, $types);
    }

    protected function canExport(): bool
    {
        $user = PermissionManager::getCurrentUser();
        return $this->getPermManager()->canExport($user);
    }

    protected function canImport(): bool
    {
        $user = PermissionManager::getCurrentUser();
        return $this->getPermManager()->canImport($user);
    }

    protected function canEditRates(): bool
    {
        $user = PermissionManager::getCurrentUser();
        return $this->getPermManager()->canEditRates($user);
    }

    protected function isAdmin(): bool
    {
        $user = PermissionManager::getCurrentUser();
        return $this->getPermManager()->isAdmin($user);
    }

    protected function logActivity(string $action, ?int $itemId = null, ?array $details = null): void
    {
        $user = PermissionManager::getCurrentUser();
        $controller = $this->getControllerName();
        $this->getPermManager()->logActivity($user, $action, $controller, $itemId, $details);
    }

    private function getControllerName(): string
    {
        $className = get_class($this);
        $className = str_replace('Controller', '', $className);
        $className = str_replace('controllers\\', '', $className);
        return strtolower($className);
    }

}