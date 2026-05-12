<?php
/**
 * Складський облік — Точка входу
 * MVC Router
 * 
 * Працює як standalone або вбудований у Nextcloud (iframe / NC App)
 * Підтримує мульти-базу: різні БД для різних груп Nextcloud
 */
// Дозвіл вбудовування у iframe (Nextcloud External Sites або NC App)
header('X-Frame-Options: ALLOWALL');
header('Content-Security-Policy: frame-ancestors *');
// Налаштування сесії для роботи в iframe (Nextcloud)
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_set_cookie_params([
    'lifetime' => 86400 * 30,
    'path' => rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => $secure ? 'None' : 'Lax',
]);
date_default_timezone_set('Europe/Kyiv');
session_start();
define('ROOT_PATH', __DIR__);
define('BASE_PATH', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'));

// =============================================
// Авторизація через Nextcloud (якщо є параметри)
// =============================================
$ncUser = $_GET['nc_user'] ?? null;
$ncGroups = $_GET['nc_groups'] ?? null;
$ncName = $_GET['nc_name'] ?? null;
$ncTs = $_GET['nc_ts'] ?? null;
$ncSig = $_GET['nc_sig'] ?? null;
if ($ncUser !== null && $ncSig !== null) {
    // Завантажити секрет
    $authConfig = [];
    $authFile = ROOT_PATH . '/config/nc_auth.php';
    if (file_exists($authFile)) {
        $authConfig = require $authFile;
    }
    $secret = $authConfig['secret'] ?? '';
    $maxAge = $authConfig['max_age'] ?? 3600;
    // Перевірити підпис
    $expectedSig = hash_hmac('sha256', $ncUser . '|' . $ncGroups . '|' . $ncTs, $secret);
    if ($secret && hash_equals($expectedSig, $ncSig) && (time() - (int)$ncTs) < $maxAge) {
        // Підпис валідний — зберегти в сесію
        $_SESSION['nc_user'] = $ncUser;
        $_SESSION['nc_display_name'] = $ncName;
        $_SESSION['nc_groups'] = $ncGroups ? explode(',', $ncGroups) : [];
        // Визначити БД за групою
        $dbConfigFile = ROOT_PATH . '/config/databases.php';
        if (file_exists($dbConfigFile)) {
            $databases = require $dbConfigFile;
            $matched = false;
            foreach ($_SESSION['nc_groups'] as $group) {
                if (isset($databases[$group])) {
                    $_SESSION['nc_db_group'] = $group;
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                $_SESSION['nc_db_group'] = 'default';
            }
        }
    }
}

// Зберігаємо NC-дані для view (для показу імені юзера тощо)
define('NC_USER', $_SESSION['nc_user'] ?? '');
define('NC_DISPLAY_NAME', $_SESSION['nc_display_name'] ?? '');
define('NC_GROUPS', $_SESSION['nc_groups'] ?? []);
define('NC_DB_GROUP', $_SESSION['nc_db_group'] ?? 'default');

// Автозавантаження класів
spl_autoload_register(function ($class) {
    $dirs = ['core', 'models', 'controllers'];
    foreach ($dirs as $dir) {
        $file = ROOT_PATH . '/' . $dir . '/' . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Конфігурація
require_once ROOT_PATH . '/config/database.php';

// Ініціалізація бази даних (автоматично підбере базу за групою)
try {
    $db = Database::getInstance();
} catch (Exception $e) {
    die('Помилка підключення до бази даних: ' . $e->getMessage());
}

// Визначення маршруту
$requestUri = $_SERVER['REQUEST_URI'];
$route = parse_url($requestUri, PHP_URL_PATH);
$route = substr($route, strlen(BASE_PATH));
$route = trim($route, '/');
if (empty($route)) {
    $route = 'movements';
}

// Розбір маршруту
$parts = explode('/', $route);
$controllerName = ucfirst($parts[0]) . 'Controller';
$action = $parts[1] ?? 'index';
$id = $parts[2] ?? null;

// Перевірка контролера
$controllerFile = ROOT_PATH . '/controllers/' . $controllerName . '.php';
if (!file_exists($controllerFile)) {
    http_response_code(404);
    require ROOT_PATH . '/views/errors/404.php';
    exit;
}

// Виконання
try {
    $controller = new $controllerName($db);
    if (!method_exists($controller, $action)) {
        http_response_code(404);
        require ROOT_PATH . '/views/errors/404.php';
        exit;
    }
    $controller->$action($id);
} catch (Exception $e) {
    http_response_code(500);
    echo '<h1>Помилка сервера</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>';
}