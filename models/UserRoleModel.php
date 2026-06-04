<?php

class UserRoleModel extends Model
{
    protected string $table = 'user_roles';

    public function getUser(string $ncUser): ?array
    {
        $result = $this->db->query(
            "SELECT * FROM user_roles WHERE nc_user = ?",
            [$ncUser]
        )->fetch();
        
        if (!$result) {
            return null;
        }
        
        $result['allowed_warehouses'] = $this->decodeJson($result['allowed_warehouses']);
        $result['allowed_materials'] = $this->decodeJson($result['allowed_materials']);
        $result['allowed_resource_types'] = $this->decodeJson($result['allowed_resource_types']);
        
        return $result;
    }

    public function getUserById(int $id): ?array
    {
        $result = $this->db->query(
            "SELECT * FROM user_roles WHERE id = ?",
            [$id]
        )->fetch();
        
        if (!$result) {
            return null;
        }
        
        $result['allowed_warehouses'] = $this->decodeJson($result['allowed_warehouses']);
        $result['allowed_materials'] = $this->decodeJson($result['allowed_materials']);
        $result['allowed_resource_types'] = $this->decodeJson($result['allowed_resource_types']);
        
        return $result;
    }

    public function getAllUsers(): array
    {
        $users = $this->db->query(
            "SELECT * FROM user_roles ORDER BY nc_user ASC"
        )->fetchAll();
        
        foreach ($users as &$user) {
            $user['allowed_warehouses'] = $this->decodeJson($user['allowed_warehouses']);
            $user['allowed_materials'] = $this->decodeJson($user['allowed_materials']);
            $user['allowed_resource_types'] = $this->decodeJson($user['allowed_resource_types']);
        }
        
        return $users;
    }

    public function createOrUpdate(string $ncUser, array $data): bool
    {
        $allowedWarehouses = $this->encodeJson($data['allowed_warehouses'] ?? null);
        $allowedMaterials = $this->encodeJson($data['allowed_materials'] ?? null);
        $allowedResourceTypes = $this->encodeJson($data['allowed_resource_types'] ?? null);
        
        $role = $data['role'] ?? 'viewer';
        $canEditRates = isset($data['can_edit_rates']) ? (int)$data['can_edit_rates'] : 0;
        $canExport = isset($data['can_export']) ? (int)$data['can_export'] : 1;
        $canImport = isset($data['can_import']) ? (int)$data['can_import'] : 0;
        
        $existing = $this->getUser($ncUser);
        
        if ($existing) {
            $this->db->query(
                "UPDATE user_roles 
                 SET role = ?, 
                     allowed_warehouses = ?, 
                     allowed_materials = ?, 
                     allowed_resource_types = ?,
                     can_edit_rates = ?,
                     can_export = ?,
                     can_import = ?,
                     author = ?,
                     updated_at = NOW()
                 WHERE nc_user = ?",
                [
                    $role,
                    $allowedWarehouses,
                    $allowedMaterials,
                    $allowedResourceTypes,
                    $canEditRates,
                    $canExport,
                    $canImport,
                    $this->authorStamp(),
                    $ncUser
                ]
            );
        } else {
            $this->db->query(
                "INSERT INTO user_roles (nc_user, role, allowed_warehouses, allowed_materials, allowed_resource_types, can_edit_rates, can_export, can_import, author) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $ncUser,
                    $role,
                    $allowedWarehouses,
                    $allowedMaterials,
                    $allowedResourceTypes,
                    $canEditRates,
                    $canExport,
                    $canImport,
                    $this->authorStamp()
                ]
            );
        }
        
        return true;
    }

    public function deleteUser(string $ncUser): bool
    {
        $this->db->query("DELETE FROM user_roles WHERE nc_user = ?", [$ncUser]);
        $this->db->query("DELETE FROM user_menu_permissions WHERE nc_user = ?", [$ncUser]);
        return true;
    }

    public function syncFromSession(): void
    {
        $ncUser = $_SESSION['nc_user'] ?? null;
        if (!$ncUser) {
            return;
        }
        
        $existing = $this->getUser($ncUser);
        if (!$existing) {
            $this->createOrUpdate($ncUser, [
                'role' => 'viewer',
                'allowed_warehouses' => null,
                'allowed_materials' => null,
                'allowed_resource_types' => null,
                'can_edit_rates' => false,
                'can_export' => true,
                'can_import' => false
            ]);
        }
    }

    public function getUsersByRole(string $role): array
    {
        return $this->db->query(
            "SELECT * FROM user_roles WHERE role = ? ORDER BY nc_user ASC",
            [$role]
        )->fetchAll();
    }

    public function getUsersWithWarehouseAccess(int $warehouseId): array
    {
        $allUsers = $this->getAllUsers();
        $result = [];
        
        foreach ($allUsers as $user) {
            $allowed = $user['allowed_warehouses'];
            if ($allowed === null || in_array($warehouseId, $allowed)) {
                $result[] = $user;
            }
        }
        
        return $result;
    }

    public function hasWarehouseAccess(string $ncUser, int $warehouseId): bool
    {
        $user = $this->getUser($ncUser);
        if (!$user) {
            return false;
        }
        
        $allowed = $user['allowed_warehouses'];
        return ($allowed === null || in_array($warehouseId, $allowed));
    }

    public function hasMaterialAccess(string $ncUser, int $materialId): bool
    {
        $user = $this->getUser($ncUser);
        if (!$user) {
            return false;
        }
        
        $allowed = $user['allowed_materials'];
        return ($allowed === null || in_array($materialId, $allowed));
    }

    public function hasResourceTypeAccess(string $ncUser, int $typeId): bool
    {
        $user = $this->getUser($ncUser);
        if (!$user) {
            return false;
        }
        
        $allowed = $user['allowed_resource_types'];
        return ($allowed === null || in_array($typeId, $allowed));
    }

    public function filterWarehouses(string $ncUser, array $warehouses): array
    {
        $user = $this->getUser($ncUser);
        if (!$user) {
            return [];
        }
        
        $allowed = $user['allowed_warehouses'];
        if ($allowed === null) {
            return $warehouses;
        }
        
        return array_filter($warehouses, function($warehouse) use ($allowed) {
            $id = is_array($warehouse) ? $warehouse['id'] : $warehouse;
            return in_array($id, $allowed);
        });
    }

    public function filterMaterials(string $ncUser, array $materials): array
    {
        $user = $this->getUser($ncUser);
        if (!$user) {
            return [];
        }
        
        $allowed = $user['allowed_materials'];
        if ($allowed === null) {
            return $materials;
        }
        
        return array_filter($materials, function($material) use ($allowed) {
            $id = is_array($material) ? $material['id'] : $material;
            return in_array($id, $allowed);
        });
    }

    private function decodeJson($value): ?array
    {
        if (empty($value)) {
            return null;
        }
        
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function encodeJson($value): ?string
    {
        if (empty($value)) {
            return null;
        }
        
        if (is_array($value)) {
            return json_encode(array_values($value));
        }
        
        return null;
    }
}