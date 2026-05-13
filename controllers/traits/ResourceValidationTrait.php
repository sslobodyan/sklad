<?php
/**
 * Trait ResourceValidationTrait
 * Валідація показників ресурсів
 */
trait ResourceValidationTrait
{
    /**
     * Валідація та нормалізація показника згідно з форматом
     */
    protected function normalizeReading(float $reading, string $format): float
    {
        if ($format === 'int') {
            return round($reading);
        }
        if ($format === 'dec2') {
            return round($reading, 2);
        }
        return $reading;
    }

    /**
     * Перевірка показника на цілі числа (для формату int)
     */
    protected function validateReadingFormat(float $reading, string $format): ?string
    {
        if ($format === 'int') {
            if (abs($reading - round($reading)) > 0.000001) {
                return 'Для цього ресурсу дозволені тільки цілі числа';
            }
        }
        return null;
    }

    /**
     * Перевірка послідовності показників (не менше попереднього, не більше наступного)
     */
    protected function validateReadingSequence(int $whId, int $rtId, string $date, float $reading, ?int $excludeId = null): ?string
    {
        $excludeClause = $excludeId ? " AND id <> {$excludeId}" : '';

        $prev = $this->db->query(
            "SELECT reading FROM resource_logs 
             WHERE warehouse_id = ? AND resource_type_id = ? AND log_date <= ? {$excludeClause} 
             ORDER BY log_date DESC, id DESC LIMIT 1",
            [$whId, $rtId, $date]
        )->fetch();

        if ($prev && $reading < (float)$prev['reading']) {
            return 'Показник не може бути меншим за попередній (' . $prev['reading'] . ')';
        }

        $next = $this->db->query(
            "SELECT reading, log_date FROM resource_logs 
             WHERE warehouse_id = ? AND resource_type_id = ? AND log_date > ? {$excludeClause} 
             ORDER BY log_date ASC, id ASC LIMIT 1",
            [$whId, $rtId, $date]
        )->fetch();

        if ($next && $reading > (float)$next['reading']) {
            return 'Показник не може бути більшим за наступний (' . $next['reading'] . ' від ' . $next['log_date'] . ')';
        }

        return null;
    }
}