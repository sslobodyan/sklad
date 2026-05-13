<?php
/**
 * Trait SimpleConfigTrait
 * Отримання налаштувань для спрощеного режиму
 */
trait SimpleConfigTrait
{
    private ?int $cachedWarehouseId = null;
    private ?array $cachedMaterials = null;

    protected function getSimpleWarehouseId(): ?int
    {
        if ($this->cachedWarehouseId !== null) {
            return $this->cachedWarehouseId;
        }
        
        $value = $this->configModel->getValue('simple_warehouse');
        $this->cachedWarehouseId = $value ? (int)$value : null;
        return $this->cachedWarehouseId;
    }

    protected function getSimpleMaterials(): array
    {
        if ($this->cachedMaterials !== null) {
            return $this->cachedMaterials;
        }
        
        $value = $this->configModel->getValue('simple_materials');
        if (!$value) {
            $this->cachedMaterials = [];
            return [];
        }
        
        $decoded = json_decode($value, true);
        $this->cachedMaterials = is_array($decoded) ? array_map('intval', $decoded) : [];
        return $this->cachedMaterials;
    }

    protected function getMaterialsByIds(array $ids): array
    {
        if (empty($ids)) {
            return $this->materialModel->getAll('name ASC');
        }

        $all = $this->materialModel->getAll('name ASC');
        return array_filter($all, fn($m) => in_array($m['id'], $ids));
    }

    protected function getOtherWarehouses(int $simpleWarehouseId): array
    {
        $allowedWarehouseIds = $this->configModel->getSimpleWarehouses();
        
        if (!empty($allowedWarehouseIds)) {
            $placeholders = implode(',', array_fill(0, count($allowedWarehouseIds), '?'));
            return $this->db->query(
                "SELECT id, name FROM warehouses WHERE id != ? AND id IN ($placeholders) ORDER BY name ASC",
                array_merge([$simpleWarehouseId], $allowedWarehouseIds)
            )->fetchAll();
        }
        
        return $this->db->query(
            "SELECT id, name FROM warehouses WHERE id != ? ORDER BY name ASC",
            [$simpleWarehouseId]
        )->fetchAll();
    }
}