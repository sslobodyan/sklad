<?php
/**
 * Скрипт встановлення системи аудиту
 * 
 * Додає $this->setCurrentUser(); перед UPDATE/DELETE операціями
 * у всіх моделях, не замінюючи файли повністю.
 * 
 * Використання:
 *   cd /var/www/sklad
 *   php install_audit.php
 */

define('SKLAD_PATH', '/var/www/sklad');

echo "=== Встановлення системи аудиту ===\n\n";

// ================================================
// 1. Патч core/Model.php — додати setCurrentUser()
// ================================================
echo "1. Патч core/Model.php...\n";

$modelFile = SKLAD_PATH . '/core/Model.php';
if (!file_exists($modelFile)) {
    die("   ПОМИЛКА: $modelFile не знайдено\n");
}

$content = file_get_contents($modelFile);

// Перевіряємо чи вже є setCurrentUser
if (strpos($content, 'function setCurrentUser()') !== false) {
    echo "   - setCurrentUser() вже існує\n";
} else {
    // Знаходимо метод authorStamp() і додаємо setCurrentUser() перед ним
    $authorStampPattern = '/(protected function authorStamp\(\))/';
    
    $setCurrentUserMethod = <<<'PHP'
/**
     * Встановлює поточного користувача для аудиту в тригерах
     */
    public function setCurrentUser(): void
    {
        $username = $this->authorStamp();
        $this->db->query("SET @current_user = ?", [$username]);
    }

    $1
PHP;

    if (preg_match($authorStampPattern, $content)) {
        $content = preg_replace($authorStampPattern, $setCurrentUserMethod, $content, 1);
        file_put_contents($modelFile, $content);
        echo "   ✓ Додано setCurrentUser()\n";
    } else {
        echo "   ! Не вдалося знайти authorStamp(), додайте вручну\n";
    }
}

// Патч delete() — додати setCurrentUser()
if (strpos($content, "setCurrentUser();\n        \$this->db->query(\n            \"DELETE") !== false ||
    strpos($content, "setCurrentUser();\n            \$this->db->query(\n                \"DELETE") !== false) {
    echo "   - delete() вже має setCurrentUser()\n";
} else {
    // Знаходимо delete() і додаємо setCurrentUser() після try {
    $content = file_get_contents($modelFile);
    $pattern = '/(public function delete\(int \$id\): bool\s*\{\s*try\s*\{)/s';
    if (preg_match($pattern, $content)) {
        $content = preg_replace($pattern, "$1\n            \$this->setCurrentUser();", $content, 1);
        file_put_contents($modelFile, $content);
        echo "   ✓ Додано setCurrentUser() в delete()\n";
    }
}

// Додати getHistory() якщо немає
$content = file_get_contents($modelFile);
if (strpos($content, 'function getHistory(') !== false) {
    echo "   - getHistory() вже існує\n";
} else {
    // Додаємо перед останньою закриваючою дужкою класу
    $getHistoryMethod = <<<'PHP'

    /**
     * Отримати історію змін для запису
     */
    public function getHistory(int $id): array
    {
        $historyTable = $this->table . '_history';
        try {
            return $this->db->query(
                "SELECT * FROM {$historyTable} WHERE id = ? ORDER BY changed_at DESC",
                [$id]
            )->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
}
PHP;
    // Замінюємо останню } на метод + }
    $content = preg_replace('/\}\s*$/', $getHistoryMethod, $content);
    file_put_contents($modelFile, $content);
    echo "   ✓ Додано getHistory()\n";
}

// ================================================
// 2. Патч моделей — додати setCurrentUser() перед UPDATE
// ================================================

$models = [
    'models/WarehouseModel.php' => ['update'],
    'models/MaterialModel.php' => ['update'],
    'models/MovementModel.php' => ['update'],
    'models/ConfigModel.php' => ['setValue'],
    'models/ResourceModel.php' => ['updateType', 'deleteType', 'removeWarehouseResource', 'saveRate', 'deleteRate', 'updateReading', 'deleteReading'],
];

foreach ($models as $file => $methods) {
    $filePath = SKLAD_PATH . '/' . $file;
    echo "\n2. Патч $file...\n";
    
    if (!file_exists($filePath)) {
        echo "   ! Файл не знайдено, пропускаю\n";
        continue;
    }
    
    $content = file_get_contents($filePath);
    $changed = false;
    
    foreach ($methods as $method) {
        // Перевіряємо чи вже є setCurrentUser в цьому методі
        $checkPattern = '/function ' . $method . '\s*\([^)]*\)[^{]*\{[^}]*setCurrentUser/s';
        if (preg_match($checkPattern, $content)) {
            echo "   - $method() вже має setCurrentUser()\n";
            continue;
        }
        
        // Шукаємо метод і додаємо setCurrentUser() після {
        // Для методів з try — після try {
        $patternTry = '/(public function ' . $method . '\s*\([^)]*\)[^{]*\{\s*try\s*\{)/s';
        $patternSimple = '/(public function ' . $method . '\s*\([^)]*\)[^{]*\{)/s';
        
        if (preg_match($patternTry, $content)) {
            $content = preg_replace($patternTry, "$1\n            \$this->setCurrentUser();", $content, 1);
            echo "   ✓ Додано setCurrentUser() в $method() (після try)\n";
            $changed = true;
        } elseif (preg_match($patternSimple, $content)) {
            $content = preg_replace($patternSimple, "$1\n        \$this->setCurrentUser();", $content, 1);
            echo "   ✓ Додано setCurrentUser() в $method()\n";
            $changed = true;
        } else {
            echo "   ? Метод $method() не знайдено\n";
        }
    }
    
    if ($changed) {
        // Бекап
        $backup = $filePath . '.backup_' . date('Ymd_His');
        copy($filePath, $backup);
        file_put_contents($filePath, $content);
        echo "   Бекап: $backup\n";
    }
}

// ================================================
// 3. Перевірка ConfigModel — методи для simple warehouse
// ================================================
echo "\n3. Патч ConfigModel.php — методи для заправки...\n";

$configFile = SKLAD_PATH . '/models/ConfigModel.php';
$content = file_get_contents($configFile);

if (strpos($content, 'function getSimpleWarehouse') !== false) {
    echo "   - Методи для заправки вже існують\n";
} else {
    $newMethods = <<<'PHP'

    /**
     * Отримати ID "тупого складу" для спрощеної сторінки
     */
    public function getSimpleWarehouse(): ?int
    {
        $value = $this->getValue('simple_warehouse');
        return $value ? (int)$value : null;
    }

    /**
     * Встановити "тупий склад"
     */
    public function setSimpleWarehouse(?int $warehouseId): void
    {
        $this->setValue('simple_warehouse', $warehouseId ? (string)$warehouseId : null);
    }

    /**
     * Отримати масив дозволених матеріалів для "тупого складу"
     */
    public function getSimpleMaterials(): array
    {
        $value = $this->getValue('simple_materials');
        if (!$value) return [];
        $decoded = json_decode($value, true);
        return is_array($decoded) ? array_map('intval', $decoded) : [];
    }

    /**
     * Встановити дозволені матеріали для "тупого складу"
     */
    public function setSimpleMaterials(array $materialIds): void
    {
        if (empty($materialIds)) {
            $this->setValue('simple_materials', null);
        } else {
            $this->setValue('simple_materials', json_encode(array_values(array_map('intval', $materialIds))));
        }
    }
}
PHP;
    
    $content = preg_replace('/\}\s*$/', $newMethods, $content);
    file_put_contents($configFile, $content);
    echo "   ✓ Додано методи getSimpleWarehouse(), setSimpleWarehouse(), getSimpleMaterials(), setSimpleMaterials()\n";
}

// ================================================
// Готово
// ================================================
echo "\n=== Патчі застосовано ===\n";
echo "\nТепер виконайте SQL міграцію:\n";
echo "  mysql -u sklad -p sklad < sql/migration_history.sql\n\n";
