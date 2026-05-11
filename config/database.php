<?php
/**
 * Конфігурація бази даних
 * Підтримує мульти-базу: різні БД для різних груп Nextcloud
 */
class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;
    private static string $currentDb = '';
    private function __construct(array $config)
    {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            $config['host'],
            $config['name']
        );
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $this->pdo = new PDO($dsn, $config['user'], $config['pass'], $options);
        self::$currentDb = $config['name'];
    }

    /**
     * Отримати єдиний екземпляр БД
     * Якщо передано dbName — підключитися до конкретної бази
     */
    public static function getInstance(?string $dbName = null): self
    {
        if ($dbName && self::$instance && self::$currentDb !== $dbName) {
            // Перепідключення до іншої бази
            self::$instance = null;
        }
        if (self::$instance === null) {
            $config = self::resolveConfig($dbName);
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    /**
     * Визначити конфігурацію БД
     */
    private static function resolveConfig(?string $dbName = null): array
    {
        $configFile = ROOT_PATH . '/config/databases.php';
        if (file_exists($configFile)) {
            $databases = require $configFile;
            // Якщо вказано конкретну БД
            if ($dbName && isset($databases[$dbName])) {
                return $databases[$dbName];
            }
            // Визначити за групою Nextcloud
            $ncGroup = $_SESSION['nc_db_group'] ?? null;
            if ($ncGroup && isset($databases[$ncGroup])) {
                return $databases[$ncGroup];
            }
            // Default
            if (isset($databases['default'])) {
                return $databases['default'];
            }
        }

        // Fallback — старий формат (для сумісності)
        return [
            'host' => 'localhost',
            'name' => $dbName ?? 'sklad',
            'user' => 'sklad',
            'pass' => '1qazxsw2#',
        ];
    }

    public static function getCurrentDb(): string
    {
        return self::$currentDb;
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function lastInsertId(): int
    {
        return (int) $this->pdo->lastInsertId();
    }
}