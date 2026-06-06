<?php
/**
 * Скрипт інсталяції бази даних
 * Запускається автоматично при першому зверненні
 */

define('ROOT_PATH', __DIR__);

// Перевіряємо чи вже встановлено
if (file_exists(ROOT_PATH . '/config/installed.lock')) {
    die('Система вже встановлена. Видаліть config/installed.lock для перевстановлення.');
}

require_once ROOT_PATH . '/config/database.php';

try {
    $db = Database::getInstance();
    
    // Отримуємо SQL з файлу
    $sqlFile = ROOT_PATH . '/sql/install.sql';
    if (!file_exists($sqlFile)) {
        die('Файл /sql/install.sql не знайдено');
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Розбиваємо на окремі запити (за коментарями та крапкою з комою)
    $queries = explode(";\n", $sql);
    
    $db->query("START TRANSACTION");
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (empty($query) || strpos($query, '--') === 0) {
            continue;
        }
        try {
            $db->query($query);
        } catch (Exception $e) {
            // Ігноруємо помилки 'Table already exists'
            if (strpos($e->getMessage(), 'already exists') === false) {
                throw $e;
            }
        }
    }
    
    $db->query("COMMIT");
    
    // Створюємо маркер встановлення
    file_put_contents(ROOT_PATH . '/config/installed.lock', date('Y-m-d H:i:s'));
    
    echo "База даних успішно встановлена!";
    
} catch (Exception $e) {
    $db->query("ROLLBACK");
    die("Помилка інсталяції: " . $e->getMessage());
}