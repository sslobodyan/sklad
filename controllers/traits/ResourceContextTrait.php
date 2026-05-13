<?php
/**
 * Trait ResourceContextTrait
 * Отримання контексту для показників
 */
trait ResourceContextTrait
{
    /**
     * AJAX: отримати контекст для вводу показника
     */
    public function context(): void
    {
        $whId = (int)$this->get('warehouse_id');
        $rtId = (int)$this->get('resource_type_id');
        $date = $this->get('date', date('Y-m-d'));
        $excludeId = (int)$this->get('exclude_id', 0);

        $excludeClause = $excludeId ? " AND id <> {$excludeId}" : '';

        $prev = $this->db->query(
            "SELECT id, log_date, reading FROM resource_logs
             WHERE warehouse_id = ? AND resource_type_id = ?
               AND log_date <= ? {$excludeClause}
             ORDER BY log_date DESC, id DESC LIMIT 1",
            [$whId, $rtId, $date]
        )->fetch();

        $next = $this->db->query(
            "SELECT id, log_date, reading FROM resource_logs
             WHERE warehouse_id = ? AND resource_type_id = ?
               AND log_date > ? {$excludeClause}
             ORDER BY log_date ASC, id ASC LIMIT 1",
            [$whId, $rtId, $date]
        )->fetch();

        $type = $this->model->getTypeById($rtId);

        $this->json([
            'success' => true,
            'prev_reading' => $prev ? (float)$prev['reading'] : 0,
            'prev_date' => $prev ? $prev['log_date'] : null,
            'next_reading' => $next ? (float)$next['reading'] : null,
            'next_date' => $next ? $next['log_date'] : null,
            'format' => $type ? ($type['format'] ?? 'dec2') : 'dec2',
            'unit' => $type ? ($type['unit'] ?? '') : '',
        ]);
    }

    /**
     * Отримання останнього показника (для сумісності)
     */
    public function lastreading(): void
    {
        $whId = (int)$this->get('warehouse_id');
        $rtId = (int)$this->get('resource_type_id');
        $last = $this->model->getLastReading($whId, $rtId);
        $this->json([
            'success' => true,
            'reading' => $last ? (float)$last['reading'] : 0,
            'date' => $last ? $last['log_date'] : null,
        ]);
    }
}