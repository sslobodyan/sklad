<?php
session_start();

define('ROOT_PATH', __DIR__);

$basePath = dirname($_SERVER['SCRIPT_NAME']);
$basePath = str_replace('\\', '/', $basePath);
$basePath = rtrim($basePath, '/');
if ($basePath === '/' || $basePath === '\\' || $basePath === '') {
    $basePath = '';
}
define('BASE_PATH', $basePath);

spl_autoload_register(function ($class) {
    $dirs = ['core', 'models', 'controllers', 'helpers'];
    foreach ($dirs as $dir) {
        $file = ROOT_PATH . '/' . $dir . '/' . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

require_once ROOT_PATH . '/config/database.php';

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$dbGroup = $_POST['db_group'] ?? null;

$error = '';

if (empty($username) || empty($password)) {
    $error = 'Заповніть всі поля';
} elseif (empty($dbGroup)) {
    $error = 'Виберіть базу даних';
} else {
    try {
        // Перевіряємо чи база встановлена
        $lockFile = ROOT_PATH . '/config/installed_' . $dbGroup . '.lock';
        if (!file_exists($lockFile)) {
            header('Location: ' . BASE_PATH . '/login.php?install_needed=1&db_group=' . urlencode($dbGroup));
            exit;
        }
        
        $db = Database::getInstance($dbGroup);
        
        $user = $db->query(
            "SELECT * FROM user_roles WHERE nc_user = ?",
            [$username]
        )->fetch();
        
        if ($user && !empty($user['password_hash']) && password_verify($password, $user['password_hash'])) {
            $_SESSION['nc_user'] = $user['nc_user'];
            $_SESSION['nc_display_name'] = $user['display_name'] ?? $user['nc_user'];
            $_SESSION['nc_groups'] = [];
            $_SESSION['nc_db_group'] = $dbGroup;
            $_SESSION['last_login'] = date('Y-m-d H:i:s');
            
            header('Location: ' . BASE_PATH . '/');
            exit;
        } else {
            $error = 'Невірний логін або пароль';
        }
    } catch (Exception $e) {
        $error = 'Помилка підключення до бази даних: ' . $e->getMessage();
    }
}

header('Location: ' . BASE_PATH . '/login.php?error=' . urlencode($error));
exit;