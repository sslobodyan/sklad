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

$dbGroup = $_GET['db_group'] ?? '';

if (empty($dbGroup)) {
    header('Location: ' . BASE_PATH . '/login.php');
    exit;
}

$databases = require ROOT_PATH . '/config/databases.php';
if (!isset($databases[$dbGroup])) {
    header('Location: ' . BASE_PATH . '/login.php');
    exit;
}

$config = $databases[$dbGroup];
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Підтвердження перезапису</title>
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
        .container { max-width: 500px; width: 100%; }
        .card {
            background: white;
            border-radius: 20px;
            padding: 40px 32px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .title { font-size: 24px; font-weight: 600; text-align: center; margin-bottom: 16px; }
        .warning {
            background: #fff3e0;
            color: #e65100;
            padding: 16px;
            border-radius: 10px;
            margin-bottom: 24px;
            text-align: center;
        }
        .db-name {
            font-family: monospace;
            font-weight: bold;
            background: #f0f0f0;
            padding: 8px 12px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 24px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #1a237e;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            margin-right: 10px;
        }
        .btn-danger { background: #c62828; }
        .btn-danger:hover { background: #b71c1c; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #5a6268; }
        .buttons { margin-top: 20px; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="title">Підтвердження перезапису</div>
            <div class="warning">
                ⚠️ Увага! ⚠️<br>
                База даних <strong><?= htmlspecialchars($dbGroup) ?></strong> вже містить таблиці.<br>
                Встановлення ПОВНІСТЮ ВИДАЛИТЬ всі наявні дані в цій базі.
            </div>
            <div class="db-name">
                База: <?= htmlspecialchars($config['name']) ?><br>
                Хост: <?= htmlspecialchars($config['host']) ?>
            </div>
            <div class="buttons">
                <form method="post" action="<?= BASE_PATH ?>/install_db.php" style="display: inline;">
                    <input type="hidden" name="db_group" value="<?= htmlspecialchars($dbGroup) ?>">
                    <input type="hidden" name="confirm_overwrite" value="1">
                    <button type="submit" class="btn btn-danger">Так, перезаписати базу</button>
                </form>
                <a href="<?= BASE_PATH ?>/login.php" class="btn btn-secondary">Скасувати</a>
            </div>
        </div>
    </div>
</body>
</html>