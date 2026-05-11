<?php
/**
 * Модель налаштувань (key-value)
 */

class ConfigModel extends Model
{
    protected string $table = 'config';

    /**
     * Отримати значення
     */
    public function getValue(string $key, ?string $default = null): ?string
    {
        $row = $this->db->query(
            "SELECT `value` FROM config WHERE `key` = ?",
            [$key]
        )->fetch();
        return $row ? $row['value'] : $default;
    }

    /**
     * Зберегти значення
     */
    public function setValue(string $key, ?string $value): void
    {
        if ($value === null || $value === '') {
            $this->setCurrentUser();
            $this->db->query("DELETE FROM config WHERE `key` = ?", [$key]);
        } else {
            $this->setCurrentUser();
            $this->db->query(
                "INSERT INTO config (`key`, `value`, `author`) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `author` = VALUES(`author`)",
                [$key, $value, $this->authorStamp()]
            );
        }
    }

    /**
     * Отримати дату закритого періоду
     */
    public function getClosedDate(): ?string
    {
        return $this->getValue('closed_date');
    }

    /**
     * Встановити дату закритого періоду
     */
    public function setClosedDate(?string $date): void
    {
        $this->setValue('closed_date', $date);
    }

    /**
     * Перевірити чи дата потрапляє в закритий період
     */
    public function isDateClosed(string $date): bool
    {
        $closed = $this->getClosedDate();
        if (!$closed) return false;
        return $date <= $closed;
    }

    /**
     * Отримати ID "тупого складу" для спрощеної сторінки
     */
    public function getSimpleWarehouse(): ?int
    {
        $value = $this->getValue('simple_warehouse');
        return $value ? (int)$value : null;
    }

    /**
     * Встановити "тупий склад"
     */
    public function setSimpleWarehouse(?int $warehouseId): void
    {
        $this->setValue('simple_warehouse', $warehouseId ? (string)$warehouseId : null);
    }

    /**
     * Отримати масив дозволених матеріалів для "тупого складу"
     */
    public function getSimpleMaterials(): array
    {
        $value = $this->getValue('simple_materials');
        if (!$value) return [];
        
        $decoded = json_decode($value, true);
        return is_array($decoded) ? array_map('intval', $decoded) : [];
    }

    /**
     * Встановити дозволені матеріали для "тупого складу"
     */
    public function setSimpleMaterials(array $materialIds): void
    {
        if (empty($materialIds)) {
            $this->setValue('simple_materials', null);
        } else {
            $this->setValue('simple_materials', json_encode(array_values(array_map('intval', $materialIds))));
        }
    }
}
