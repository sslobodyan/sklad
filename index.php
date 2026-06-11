<?php
/**
 * Складський облік — Точка входу
 * MVC Router
 * 
 * Працює як standalone або вбудований у Nextcloud (iframe / NC App)
 * Підтримує мульти-базу: різні БД для різних груп Nextcloud
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

$basePath = dirname($_SERVER['SCRIPT_NAME']);
$basePath = str_replace('\\', '/', $basePath);
$basePath = rtrim($basePath, '/');
if ($basePath === '/' || $basePath === '\\' || $basePath === '') {
    $basePath = '';
}
define('BASE_PATH', $basePath);

// =============================================
// Авторизація (Nextcloud або локальна)
// =============================================

$ncUser = $_GET['nc_user'] ?? null;
$ncGroups = $_GET['nc_groups'] ?? null;
$ncName = $_GET['nc_name'] ?? null;
$ncTs = $_GET['nc_ts'] ?? null;
$ncSig = $_GET['nc_sig'] ?? null;

if ($ncUser !== null && $ncSig !== null) {
    // === Nextcloud авторизація ===
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
                // Якщо група не знайдена - видаляємо сесію і редирект на логін
                session_destroy();
                header('Location: ' . BASE_PATH . '/login.php?error=' . urlencode('Ваша група не має доступу до жодної бази даних'));
                exit;
            }
        } else {
            session_destroy();
            header('Location: ' . BASE_PATH . '/login.php?error=' . urlencode('Файл конфігурації баз даних не знайдено'));
            exit;
        }
    }
    
} elseif (!empty($_SESSION['nc_user'])) {
    // === Локальна авторизація (вже залогінений) ===
    // Нічого не робимо, сесія вже є
    
} else {
    // === Немає авторизації - редирект на логін ===
    header('Location: ' . BASE_PATH . '/login.php');
    exit;
}

// Зберігаємо NC-дані для view (для показу імені юзера тощо)
define('NC_USER', $_SESSION['nc_user'] ?? '');
define('NC_DISPLAY_NAME', $_SESSION['nc_display_name'] ?? '');
define('NC_GROUPS', $_SESSION['nc_groups'] ?? []);
define('NC_DB_GROUP', $_SESSION['nc_db_group'] ?? '');

// Перевірка чи встановлена поточна база
if (empty(NC_DB_GROUP) || !isDatabaseInstalledByGroup(NC_DB_GROUP)) {
    error_log("База ".NC_DB_GROUP." не встановлена");
    session_destroy();
    header('Location: ' . BASE_PATH . '/login.php?error=' . urlencode('База даних не встановлена або не існує'));
    exit;
}

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
    $db = Database::getInstance(NC_DB_GROUP);
} catch (Exception $e) {
    $error = 'Помилка підключення до бази даних: ' . $e->getMessage();
    session_destroy();
    header('Location: ' . BASE_PATH . '/login.php?error=' . urlencode($error));
    exit;
}

$permManager = PermissionManager::getInstance($db);
if (!empty(NC_USER)) {
    $permManager->syncCurrentUser();
}

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

// Вихід з системи
if ($route === 'logout') {
    session_destroy();
    header('Location: ' . BASE_PATH . '/login.php');
    exit;
}

$parts = explode('/', $route);
$controllerName = ucfirst($parts[0]) . 'Controller';
$action = $parts[1] ?? 'index';
$id = $parts[2] ?? null;

// =============================================
// Спеціальні маршрути для імпорту/експорту
// =============================================

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

if ($controllerName === 'ResourcesController' && $action === 'export') {
    $controllerName = 'ResourceExportController';
    $action = 'export';
    $id = null;
}

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
// Визначення назви контролера для перевірки доступу
// =============================================
$baseController = $parts[0] ?? '';
if (empty($baseController)) {
    $baseController = 'dashboard';
}

if ($baseController === 'settings' && in_array($action, ['dates', 'preset'])) {
    $skipAccessCheck = true;
} else {
    $menuItem = $db->query("SELECT id FROM menu_items WHERE controller = ?", [$baseController])->fetch();
    $skipAccessCheck = !$menuItem;
}

// =============================================
// Перевірка доступу
// =============================================
if (!empty(NC_USER) && !$skipAccessCheck && !PermissionManager::getInstance($db)->canAccess(NC_USER, $baseController, $action)) {
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
}

function isDatabaseInstalledByGroup($group) {
    $lockFile = ROOT_PATH . '/config/installed_' . $group . '.lock';
    if (!file_exists($lockFile)) {
        return false;
    }
    
    $databases = require ROOT_PATH . '/config/databases.php';
    if (!isset($databases[$group])) {
        return false;
    }
    
    $config = $databases[$group];
    try {
        $dsn = "mysql:host={$config['host']};dbname={$config['name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['user'], $config['pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->query("SELECT 1 FROM user_roles LIMIT 1");
        return true;
    } catch (Exception $e) {
        return false;
    }
}

