<?php
/**
 * Базовий контролер
 */

abstract class Controller
{
    protected Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Рендеринг представлення
     */
    protected function render(string $view, array $data = []): void
    {
        $data['basePath'] = BASE_PATH;
        $data['activePage'] = $data['activePage'] ?? '';
        
        // Flash повідомлення (масив)
        $flashMessages = $_SESSION['flash_messages'] ?? [];
        unset($_SESSION['flash_messages']);
        $data['flashMessages'] = $flashMessages;
        
        // Глобальний діапазон дат (cookie → session → default)
        $data['globalDateFrom'] = $_COOKIE['sklad_date_from'] ?? $_SESSION['date_from'] ?? date('Y-m-01');
        $data['globalDateTo'] = $_COOKIE['sklad_date_to'] ?? $_SESSION['date_to'] ?? date('Y-m-d');

        // Дата закритого періоду
        $configModel = new ConfigModel($this->db);
        $data['closedDate'] = $configModel->getClosedDate();

        // Helper форматування дати для всіх view
        if (!function_exists('formatDateUa')) {
            function formatDateUa(?string $date): string
            {
                if (empty($date)) return '';
                $ts = strtotime($date);
                return $ts ? date('d.m.Y', $ts) : $date;
            }
        }

        // Helper форматування ресурсних значень
        // format: 'int' = цілі, 'dec2' = до сотих, 'hm' = год:хв
        if (!function_exists('formatReading')) {
            function formatReading($value, string $format = 'dec2'): string
            {
                if ($value === null || $value === '') return '—';
                $v = (float)$value;
                switch ($format) {
                    case 'int':
                        return number_format($v, 0, '.', ' ');
                    case 'hm':
                        $h = (int)floor($v);
                        $m = (int)round(($v - $h) * 60);
                        return $h . ':' . str_pad($m, 2, '0', STR_PAD_LEFT);
                    case 'dec2':
                    default:
                        return number_format($v, 2, '.', ' ');
                }
            }
        }

        // Helper: назва формату для UI
        if (!function_exists('formatLabel')) {
            function formatLabel(string $format): string
            {
                switch ($format) {
                    case 'int':  return 'Цілі числа';
                    case 'dec2': return 'До сотих';
                    case 'hm':   return 'Години:хвилини';
                    default:     return $format;
                }
            }
        }

        extract($data);

        $viewFile = ROOT_PATH . '/views/' . $view . '.php';
        if (!file_exists($viewFile)) {
            throw new RuntimeException("View not found: {$view}");
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        require ROOT_PATH . '/views/layout.php';
    }

    /**
     * Перенаправлення
     */
    protected function redirect(string $path): void
    {
        header('Location: ' . BASE_PATH . '/' . ltrim($path, '/'));
        exit;
    }

    /**
     * Flash повідомлення (додає в масив)
     */
    protected function flash(string $type, string $message): void
    {
        if (!isset($_SESSION['flash_messages'])) {
            $_SESSION['flash_messages'] = [];
        }
        $_SESSION['flash_messages'][] = [
            'type' => $type,
            'message' => $message
        ];
    }

    /**
     * POST дані
     */
    protected function post(string $key, $default = null)
    {
        return $_POST[$key] ?? $default;
    }

    /**
     * GET дані
     */
    protected function get(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Перевірка методу
     */
    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /**
     * JSON відповідь
     */
    protected function json(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
