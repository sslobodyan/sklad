<?php
/**
 * Контролер налаштувань (глобальний діапазон дат)
 * Зберігає дати в cookie (365 днів) — працює в iframe Nextcloud
 */

class SettingsController extends Controller
{
    public function dates(): void
    {
        if ($this->isPost()) {
            $dateFrom = $this->post('date_from');
            $dateTo = $this->post('date_to');
            
            if ($dateFrom && $dateTo) {
                self::saveDateRange($dateFrom, $dateTo);
                $this->flash('success', 'Період оновлено');
            }
            
            $referer = $_SERVER['HTTP_REFERER'] ?? BASE_PATH . '/';
            header('Location: ' . $referer);
            exit;
        }
        
        $this->redirect('/');
    }

    public function preset($type): void
    {
        $now = new DateTime();
        
        switch ($type) {
            case 'current-month':
                $dateFrom = $now->modify('first day of this month')->format('Y-m-d');
                $dateTo = $now->modify('last day of this month')->format('Y-m-d');
                break;
            case 'last-month':
                $dateFrom = $now->modify('first day of last month')->format('Y-m-d');
                $dateTo = (new DateTime())->modify('last day of last month')->format('Y-m-d');
                break;
            case 'current-year':
                $dateFrom = $now->format('Y') . '-01-01';
                $dateTo = (new DateTime())->format('Y-m-d');
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

    /**
     * Зберегти діапазон у cookie (365 днів)
     */
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
        
        // Також в сесію для поточного запиту
        $_SESSION['date_from'] = $from;
        $_SESSION['date_to'] = $to;
    }

    /**
     * Отримати поточний діапазон (cookie → session → default)
     */
    public static function getDateFrom(): string
    {
        return $_COOKIE['sklad_date_from'] ?? $_SESSION['date_from'] ?? date('Y-m-01');
    }

    public static function getDateTo(): string
    {
        return $_COOKIE['sklad_date_to'] ?? $_SESSION['date_to'] ?? date('Y-m-d');
    }

    /**
     * Зберегти дату закритого періоду
     */
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
}
