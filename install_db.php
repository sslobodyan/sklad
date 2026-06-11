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

$databases = require ROOT_PATH . '/config/databases.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['db_group'])) {
    $dbGroup = $_POST['db_group'];
    
    if (!isset($databases[$dbGroup])) {
        $error = 'Невірна база даних';
    } else {
        $config = $databases[$dbGroup];
        
        try {
            $dsn = "mysql:host={$config['host']};dbname={$config['name']};charset=utf8mb4";
            $pdo = new PDO($dsn, $config['user'], $config['pass']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Перевіряємо чи є таблиці в базі
            $result = $pdo->query("SHOW TABLES LIKE 'user_roles'");
            if ($result->rowCount() > 0) {
                // Таблиці є - питаємо підтвердження
                if (!isset($_POST['confirm_overwrite'])) {
                    header('Location: ' . BASE_PATH . '/confirm_overwrite.php?db_group=' . urlencode($dbGroup));
                    exit;
                }
            }
            
            // Виконуємо SQL скрипт
            $sqlFile = ROOT_PATH . '/sql/install.sql';
            if (!file_exists($sqlFile)) {
                $error = 'Файл install.sql не знайдено';
            } else {
                // Очищаємо базу перед встановленням (якщо підтверджено)
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
                $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($tables as $table) {
                    $pdo->exec("DROP TABLE IF EXISTS `$table`");
                }
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
                
                // Виконуємо install.sql
                $sql = file_get_contents($sqlFile);
                $pdo->exec($sql);
                
                // Створюємо маркер інсталяції
                file_put_contents(ROOT_PATH . '/config/installed_' . $dbGroup . '.lock', date('Y-m-d H:i:s'));
                
                error_log("Встановили базу даних " . $dbGroup);
                $success = "База даних '{$dbGroup}' успішно інстальована!";
            }
        } catch (Exception $e) {
            $error = 'Помилка інсталяції: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Інсталяція бази даних</title>
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
        .title { font-size: 24px; font-weight: 600; text-align: center; margin-bottom: 32px; }
        .error-message { background: #fef2f2; color: #c62828; padding: 12px; border-radius: 10px; margin-bottom: 20px; }
        .success-message { background: #e8f5e9; color: #2e7d32; padding: 12px; border-radius: 10px; margin-bottom: 20px; }
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
        .btn:hover { background: #0d47a1; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #5a6268; }
        .buttons { margin-top: 20px; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="title">Інсталяція бази даних</div>
            
            <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <div class="buttons">
                <a href="<?= BASE_PATH ?>/login.php" class="btn btn-secondary">Назад</a>
            </div>
            <?php elseif ($success): ?>
            <div class="success-message"><?= htmlspecialchars($success) ?></div>
            <div class="buttons">
                <a href="<?= BASE_PATH ?>/login.php" class="btn">Перейти до входу</a>
            </div>
            <?php else: ?>
            <div class="error-message">Невідома помилка</div>
            <div class="buttons">
                <a href="<?= BASE_PATH ?>/login.php" class="btn btn-secondary">Назад</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>