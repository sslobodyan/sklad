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
            $this->db->query("DELETE FROM config WHERE `key` = ?", [$key]);
        } else {
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
}
