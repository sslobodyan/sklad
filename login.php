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

// Якщо вже залогінений - на головну
if (!empty($_SESSION['nc_user'])) {
    header('Location: ' . BASE_PATH . '/');
    exit;
}

// Отримуємо список баз
$databases = [];
if (file_exists(ROOT_PATH . '/config/databases.php')) {
    $databases = require ROOT_PATH . '/config/databases.php';
}

// Функція перевірки чи база встановлена
function isDatabaseInstalled($group, $config) {
    $lockFile = ROOT_PATH . '/config/installed_' . $group . '.lock';
    if (!file_exists($lockFile)) {
        return false;
    }
    
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

// Розділяємо бази на встановлені та невстановлені
$installedGroups = [];
$notInstalledGroups = [];

foreach ($databases as $group => $config) {
    if (isDatabaseInstalled($group, $config)) {
        $installedGroups[] = $group;
    } else {
        $notInstalledGroups[] = $group;
    }
}

$error = '';
if (isset($_GET['error'])) {
    $error = urldecode($_GET['error']);
}
$installNeeded = isset($_GET['install_needed']);
$selectedDbGroup = $_GET['db_group'] ?? '';
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
        .login-container { max-width: 450px; width: 100%; }
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
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover { background: #5a6268; }
        .error-message {
            background: #fef2f2;
            color: #c62828;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        .info-message {
            background: #e3f2fd;
            color: #1565c0;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 24px 0;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #ddd;
        }
        .divider span {
            padding: 0 10px;
            color: #999;
            font-size: 12px;
        }
        .install-list {
            margin-top: 16px;
        }
        .install-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 12px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 8px;
        }
        .install-item span {
            font-family: monospace;
            font-size: 14px;
        }
        .btn-sm {
            padding: 6px 12px;
            width: auto;
            font-size: 12px;
        }
        .form-select {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 14px;
            font-family: inherit;
            background: white;
        }
        .required-field {
            color: #c62828;
            margin-left: 4px;
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
            
            <?php if ($installNeeded): ?>
            <div class="info-message">Обрана база даних не встановлена. Будь ласка, встановіть її нижче.</div>
            <?php endif; ?>
            
            <?php if (!empty($installedGroups)): ?>
            <form method="post" action="<?= BASE_PATH ?>/login_process.php">
                <div class="form-group">
                    <label class="form-label">Логін <span class="required-field">*</span></label>
                    <input type="text" name="username" class="form-input" required autofocus>
                </div>
                <div class="form-group">
                    <label class="form-label">Пароль <span class="required-field">*</span></label>
                    <input type="password" name="password" class="form-input" required>
                </div>
                
                <?php if (count($installedGroups) == 1): ?>
                    <input type="hidden" name="db_group" value="<?= htmlspecialchars($installedGroups[0]) ?>">
                <?php else: ?>
                <div class="form-group">
                    <label class="form-label">База даних <span class="required-field">*</span></label>
                    <select name="db_group" class="form-select" required>
                        <option value="">-- Виберіть базу даних --</option>
                        <?php foreach ($installedGroups as $group): ?>
                        <option value="<?= htmlspecialchars($group) ?>" <?= ($selectedDbGroup === $group) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($group) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <button type="submit" class="btn">Увійти</button>
            </form>
            <?php else: ?>
            <div class="info-message" style="margin-bottom: 20px;">
                Немає встановлених баз даних. Будь ласка, встановіть базу нижче.
            </div>
            <?php endif; ?>
            
            <?php if (!empty($notInstalledGroups)): ?>
            <div class="divider"><span>АБО</span></div>
            
            <div class="install-list">
                <?php foreach ($notInstalledGroups as $group): ?>
                <div class="install-item">
                    <span><?= htmlspecialchars($group) ?></span>
                    <form method="post" action="<?= BASE_PATH ?>/install_db.php" style="margin: 0;" onsubmit="return confirm('Встановити базу даних \'<?= htmlspecialchars($group) ?>\'? Всі дані в цій базі будуть перезаписані.');">
                        <input type="hidden" name="db_group" value="<?= htmlspecialchars($group) ?>">
                        <button type="submit" class="btn-sm btn-secondary">Встановити</button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>