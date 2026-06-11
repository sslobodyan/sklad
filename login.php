<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

define('ROOT_PATH', __DIR__);
$basePath = dirname($_SERVER['SCRIPT_NAME']);
// Замінюємо зворотні слеші на прямі
$basePath = str_replace('\\', '/', $basePath);
// Обрізаємо зайвий слеш в кінці
$basePath = rtrim($basePath, '/');
// Якщо залишилось порожньо або '/', робимо порожнім
if ($basePath === '/' || $basePath === '\\') {
    $basePath = '';
}
define('BASE_PATH', $basePath);

if (!empty($_SESSION['nc_user'])) {
    header('Location: ' . BASE_PATH . '/');
    exit;
}

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

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Заповніть всі поля';
    } else {
        try {
            $db = Database::getInstance();
            
            // Шукаємо користувача в user_roles
            $user = $db->query(
                "SELECT * FROM user_roles WHERE nc_user = ?",
                [$username]
            )->fetch();
            
            if ($user && !empty($user['password_hash']) && password_verify($password, $user['password_hash'])) {
                $_SESSION['nc_user'] = $user['nc_user'];
                $_SESSION['nc_display_name'] = $user['display_name'] ?? $user['nc_user'];
                $_SESSION['nc_groups'] = []; // локальні не мають груп
                $_SESSION['nc_db_group'] = 'default';
                $_SESSION['last_login'] = date('Y-m-d H:i:s');
                
                header('Location: ' . BASE_PATH . '/');
                exit;
            } else {
                $error = 'Невірний логін або пароль';
            }
        } catch (Exception $e) {
            $error = 'Помилка підключення до бази даних';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вхід в систему</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #1a237e 0%, #0d47a1 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-container { max-width: 400px; width: 100%; }
        .login-card {
            background: white;
            border-radius: 20px;
            padding: 40px 32px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .login-logo svg { width: 64px; height: 64px; stroke: #1a237e; display: block; margin: 0 auto 32px; }
        .login-title { font-size: 24px; font-weight: 600; text-align: center; margin-bottom: 8px; }
        .login-subtitle { font-size: 14px; color: #666; text-align: center; margin-bottom: 32px; }
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-size: 13px; font-weight: 500; margin-bottom: 6px; }
        .form-input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 14px;
            font-family: inherit;
        }
        .form-input:focus {
            outline: none;
            border-color: #1a237e;
            box-shadow: 0 0 0 3px rgba(26,35,126,0.1);
        }
        .btn {
            width: 100%;
            padding: 12px;
            background: #1a237e;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn:hover { background: #0d47a1; }
        .error-message {
            background: #fef2f2;
            color: #c62828;
            padding: 12px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-logo">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="3" y="3" width="7" height="7" rx="1"/>
                    <rect x="14" y="3" width="7" height="7" rx="1"/>
                    <rect x="3" y="14" width="7" height="7" rx="1"/>
                    <rect x="14" y="14" width="7" height="7" rx="1"/>
                </svg>
            </div>
            <div class="login-title">Складський облік</div>
            <div class="login-subtitle">Вхід в систему</div>
            
            <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="post">
                <div class="form-group">
                    <label class="form-label">Логін</label>
                    <input type="text" name="username" class="form-input" required autofocus>
                </div>
                <div class="form-group">
                    <label class="form-label">Пароль</label>
                    <input type="password" name="password" class="form-input" required>
                </div>
                <button type="submit" class="btn">Увійти</button>
            </form>
        </div>
    </div>
</body>
</html>