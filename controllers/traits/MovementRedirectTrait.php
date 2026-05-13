<?php
/**
 * Trait MovementRedirectTrait
 * Редірект з відновленням фільтрів
 */
trait MovementRedirectTrait
{
    private function redirectBack(): void
    {
        $filters = $_SESSION['movements_filters'] ?? [];
        
        $redirectUrl = BASE_PATH . '/movements';
        
        if (!empty($filters)) {
            $params = [];
            if (!empty($filters['date_from'])) $params['date_from'] = $filters['date_from'];
            if (!empty($filters['date_to'])) $params['date_to'] = $filters['date_to'];
            if (!empty($filters['warehouse_id'])) $params['warehouse_id'] = $filters['warehouse_id'];
            if (!empty($filters['material_id'])) $params['material_id'] = $filters['material_id'];
            if (!empty($filters['sort'])) $params['sort'] = $filters['sort'];
            if (!empty($filters['order'])) $params['order'] = $filters['order'];
            
            if (!empty($params)) {
                $redirectUrl .= '?' . http_build_query($params);
            }
        }
        
        header('Location: ' . $redirectUrl);
        exit;
    }
}