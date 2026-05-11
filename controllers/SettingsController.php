<?php
/**
 * Контролер налаштувань (глобальний діапазон дат)
 */

class SettingsController extends Controller
{
    /**
     * Перевірка чи користувач є адміністратором
     */
    private function isAdmin(): bool
    {
        // Перевіряємо групи Nextcloud
        $ncGroups = $_SESSION['nc_groups'] ?? [];
        return in_array('admin', $ncGroups, true);
    }

    public function dates(): void
    {
        if ($this->isPost()) {
            $dateFrom = $this->post('date_from');
            $dateTo = $this->post('date_to');
            
            if ($dateFrom && $dateTo) {
                self::saveDateRange($dateFrom, $dateTo);
            }
            
            $referer = $_SERVER['HTTP_REFERER'] ?? BASE_PATH . '/';
            header('Location: ' . $referer);
            exit;
        }
        
        $this->redirect('/');
    }

    public function preset($type): void
    {
        $dateFrom = '';
        $dateTo = '';
        
        switch ($type) {
            case 'current-month':
                $dateFrom = date('Y-m-01');
                $dateTo = date('Y-m-t');
                break;
            case 'last-month':
                $dateFrom = date('Y-m-01', strtotime('-1 month'));
                $dateTo = date('Y-m-t', strtotime('-1 month'));
                break;
            case 'today':
                $dateFrom = date('Y-m-d');
                $dateTo = date('Y-m-d');
                break;
            case 'current-year':
                $year = date('Y');
                $dateFrom = $year . '-01-01';
                $dateTo = $year . '-12-31';
                break;
            default:
                $this->redirect('/');
                return;
        }
        
        self::saveDateRange($dateFrom, $dateTo);
        
        $referer = $_SERVER['HTTP_REFERER'] ?? BASE_PATH . '/';
        header('Location: ' . $referer);
        exit;
    }

    public static function saveDateRange(string $from, string $to): void
    {
        $expire = time() + 365 * 86400;
        $path = BASE_PATH . '/';
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        
        setcookie('sklad_date_from', $from, [
            'expires' => $expire,
            'path' => $path,
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'None',
        ]);
        setcookie('sklad_date_to', $to, [
            'expires' => $expire,
            'path' => $path,
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'None',
        ]);
        
        $_SESSION['date_from'] = $from;
        $_SESSION['date_to'] = $to;
    }

    public static function getDateFrom(): string
    {
        return $_COOKIE['sklad_date_from'] ?? $_SESSION['date_from'] ?? date('Y-m-01');
    }

    public static function getDateTo(): string
    {
        return $_COOKIE['sklad_date_to'] ?? $_SESSION['date_to'] ?? date('Y-m-d');
    }

    public function closeperiod(): void
    {
        if ($this->isPost()) {
            $config = new ConfigModel($this->db);
            $date = $this->post('closed_date');
            $config->setClosedDate($date ?: null);

            if ($date) {
                $this->flash('success', 'Період закрито по ' . date('d.m.Y', strtotime($date)));
            } else {
                $this->flash('success', 'Закритий період знято');
            }
        }

        $referer = $_SERVER['HTTP_REFERER'] ?? BASE_PATH . '/';
        header('Location: ' . $referer);
        exit;
    }

    /**
     * Сторінка налаштувань "тупого складу" (заправка)
     * Доступна тільки адміністраторам
     */
    public function simple(): void
    {
        // Перевірка прав доступу
        if (!$this->isAdmin()) {
            $this->flash('error', 'Доступ заборонено. Тільки для адміністраторів.');
            $this->redirect('/');
            return;
        }

        $config = new ConfigModel($this->db);
        $warehouseModel = new WarehouseModel($this->db);
        $materialModel = new MaterialModel($this->db);

        $warehouses = $warehouseModel->getAll('name ASC');
        $materials = $materialModel->getAll('name ASC');
        
        $currentWarehouse = $config->getSimpleWarehouse();
        $currentMaterials = $config->getSimpleMaterials();

        $this->render('settings/simple', [
            'title' => 'Налаштування заправки',
            'activePage' => 'settings-simple',
            'warehouses' => $warehouses,
            'materials' => $materials,
            'currentWarehouse' => $currentWarehouse,
            'currentMaterials' => $currentMaterials,
        ]);
    }

    /**
     * Зберегти налаштування "тупого складу"
     */
    public function simplesave(): void
    {
        // Перевірка прав доступу
        if (!$this->isAdmin()) {
            $this->jsonResponse(['success' => false, 'error' => 'Доступ заборонено']);
            return;
        }

        if (!$this->isPost()) {
            $this->redirect('/settings/simple');
            return;
        }

        $config = new ConfigModel($this->db);
        
        $warehouseId = (int)$this->post('simple_warehouse') ?: null;
        $materialIds = $this->post('simple_materials') ?: [];
        
        if (!is_array($materialIds)) {
            $materialIds = [];
        }
        
        $config->setSimpleWarehouse($warehouseId);
        $config->setSimpleMaterials($materialIds);

        $this->flash('success', 'Налаштування заправки збережено');
        $this->redirect('/settings/simple');
    }

    /**
     * Отримати список складів та матеріалів (AJAX)
     */
    public function simpledata(): void
    {
        if (!$this->isAdmin()) {
            $this->jsonResponse(['success' => false, 'error' => 'Доступ заборонено']);
            return;
        }

        $warehouseModel = new WarehouseModel($this->db);
        $materialModel = new MaterialModel($this->db);
        $config = new ConfigModel($this->db);

        $this->jsonResponse([
            'success' => true,
            'warehouses' => $warehouseModel->getAll('name ASC'),
            'materials' => $materialModel->getAll('name ASC'),
            'currentWarehouse' => $config->getSimpleWarehouse(),
            'currentMaterials' => $config->getSimpleMaterials(),
        ]);
    }

    private function jsonResponse(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
