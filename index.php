<?php
/**
 * Складський облік — Точка входу
 * MVC Router
 * 
 * Працює як standalone або вбудований у Nextcloud (iframe / NC App)
 * Підтримує мульти-базу: різні БД для різних груп Nextcloud
 */
header('X-Frame-Options: ALLOWALL');
header('Content-Security-Policy: frame-ancestors *');

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
// Авторизація через Nextcloud
// =============================================
$ncUser = $_GET['nc_user'] ?? null;
$ncGroups = $_GET['nc_groups'] ?? null;
$ncName = $_GET['nc_name'] ?? null;
$ncTs = $_GET['nc_ts'] ?? null;
$ncSig = $_GET['nc_sig'] ?? null;

if ($ncUser !== null && $ncSig !== null) {
    $authConfig = [];
    $authFile = ROOT_PATH . '/config/nc_auth.php';
    if (file_exists($authFile)) {
        $authConfig = require $authFile;
    }
    $secret = $authConfig['secret'] ?? '';
    $maxAge = $authConfig['max_age'] ?? 86400 * 30;
    
    $expectedSig = hash_hmac('sha256', $ncUser . '|' . $ncGroups . '|' . $ncTs, $secret);
    if ($secret && hash_equals($expectedSig, $ncSig) && (time() - (int)$ncTs) < $maxAge) {
        $_SESSION['nc_user'] = $ncUser;
        $_SESSION['nc_display_name'] = $ncName;
        $_SESSION['nc_groups'] = $ncGroups ? explode(',', $ncGroups) : [];
        
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

define('NC_USER', $_SESSION['nc_user'] ?? '');
define('NC_DISPLAY_NAME', $_SESSION['nc_display_name'] ?? '');
define('NC_GROUPS', $_SESSION['nc_groups'] ?? []);
define('NC_DB_GROUP', $_SESSION['nc_db_group'] ?? 'default');

// Автозавантаження класів
spl_autoload_register(function ($class) {
    $dirs = ['core', 'models', 'controllers', 'controllers/traits', 'helpers'];
    foreach ($dirs as $dir) {
        $file = ROOT_PATH . '/' . $dir . '/' . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

require_once ROOT_PATH . '/config/database.php';

try {
    $db = Database::getInstance();
} catch (Exception $e) {
    die('Помилка підключення до бази даних: ' . $e->getMessage());
}

$permManager = PermissionManager::getInstance($db);
$permManager->syncCurrentUser();

// =============================================
// Визначення маршруту
// =============================================
$requestUri = $_SERVER['REQUEST_URI'];
$route = parse_url($requestUri, PHP_URL_PATH);
$route = substr($route, strlen(BASE_PATH));
$route = trim($route, '/');

if (empty($route)) {
    header('Location: ' . BASE_PATH . '/dashboard');
    exit;
}

$parts = explode('/', $route);
$controllerName = ucfirst($parts[0]) . 'Controller';
$action = $parts[1] ?? 'index';
$id = $parts[2] ?? null;

// =============================================
// Спеціальні маршрути для імпорту/експорту
// =============================================

// Movements: import, export
if ($controllerName === 'MovementsController') {
    if ($action === 'import') {
        $controllerName = 'MovementsImportController';
        $action = 'import';
        $id = null;
    } elseif ($action === 'export') {
        $controllerName = 'MovementsExportController';
        $action = 'export';
        $id = null;
    }
}

// Resources: export
if ($controllerName === 'ResourcesController' && $action === 'export') {
    $controllerName = 'ResourceExportController';
    $action = 'export';
    $id = null;
}

// Reports: resource/export
if ($controllerName === 'ReportsController' && $action === 'resource' && $id === 'export') {
    $controllerName = 'ResourceUsageExportController';
    $action = 'export';
    $id = null;
}

error_log("=== ROUTING ===");
error_log("route: " . $route);
error_log("parts: " . print_r($parts, true));
error_log("controllerName: " . $controllerName);
error_log("action: " . $action);
error_log("id: " . $id);
error_log("controllerFile: " . $controllerFile);


// =============================================
// Перевірка існування контролера
// =============================================
$controllerFile = ROOT_PATH . '/controllers/' . $controllerName . '.php';
if (!file_exists($controllerFile)) {
    http_response_code(404);
    require ROOT_PATH . '/views/errors/404.php';
    exit;
}

// =============================================
// Перевірка доступу
// =============================================
// Використовуємо повний маршрут як назву контролера для перевірки
$controllerForAccess = $route;

if (!PermissionManager::getInstance($db)->canAccess(NC_USER, $controllerForAccess, $action)) {
    if (PermissionManager::isAjax()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Доступ заборонено']);
        exit;
    } else {
        $_SESSION['flash_messages'] = $_SESSION['flash_messages'] ?? [];
        $_SESSION['flash_messages'][] = ['type' => 'error', 'message' => 'Доступ заборонено'];
        header('Location: ' . BASE_PATH . '/dashboard');
        exit;
    }
}

// =============================================
// Виконання
// =============================================
try {
    $controller = new $controllerName($db);
    
    if (!method_exists($controller, $action)) {
        http_response_code(404);
        require ROOT_PATH . '/views/errors/404.php';
        exit;
    }
    
    if ($id !== null) {
        $controller->$action($id);
    } else {
        $controller->$action();
    }
} catch (Exception $e) {
    http_response_code(500);
    echo '<h1>Помилка сервера</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>';
    to_log('Помилка виконання', ['error' => $e->getMessage(), 'route' => $route]);
}

function to_log($message, $data = null) {
    $file = __DIR__ . '/debug.log';
    $time = date('Y-m-d H:i:s');
    $output = "[$time] $message  ";
    if ($data !== null) {
        $output .= print_r($data, true);
    }
    $output .= "\n";
    file_put_contents($file, $output, FILE_APPEND);
}