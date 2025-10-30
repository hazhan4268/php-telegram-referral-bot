<?php
/**
 * Database Connection Class
 * مدیریت اتصال به دیتابیس با PDO
 */

class Database {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            // Don't use die() - throw exception instead to allow proper error handling
            throw new RuntimeException('Database connection failed: ' . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->pdo;
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query failed: " . $e->getMessage() . " | SQL: " . $sql);
            throw $e;
        }
    }
    
    public function fetchOne($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }
    
    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }
    
    public function execute($sql, $params = []) {
        return $this->query($sql, $params)->rowCount();
    }
    
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
    
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    public function commit() {
        return $this->pdo->commit();
    }
    
    public function rollback() {
        return $this->pdo->rollback();
    }
    
    /**
     * ایجاد schema دیتابیس
     */
    public function createSchema() {
        $sql = file_get_contents(__DIR__ . '/schema.sql');
        
        // تبدیل schema از SQLite به MySQL
        $sql = $this->convertSchemaToMySQL($sql);
        
        // اجرای هر کوئری به صورت جداگانه
        $queries = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($queries as $query) {
            if (!empty($query)) {
                try {
                    $this->pdo->exec($query);
                } catch (PDOException $e) {
                    error_log("Schema creation error: " . $e->getMessage());
                }
            }
        }
    }
    
    /**
     * تبدیل schema از SQLite به MySQL
     */
    private function convertSchemaToMySQL($sql) {
        // حذف IF NOT EXISTS از CREATE TABLE (MySQL قدیمی ممکن است پشتیبانی نکند)
        // تبدیل INTEGER به INT
        $sql = str_replace('INTEGER PRIMARY KEY AUTOINCREMENT', 'INT PRIMARY KEY AUTO_INCREMENT', $sql);
        $sql = str_replace('INTEGER PRIMARY KEY', 'BIGINT PRIMARY KEY', $sql);
        $sql = str_replace('INTEGER NOT NULL', 'INT NOT NULL', $sql);
        $sql = str_replace('INTEGER DEFAULT', 'INT DEFAULT', $sql);
        $sql = str_replace('INTEGER', 'INT', $sql);
        
        // اضافه کردن ENGINE و CHARSET
        $sql = preg_replace('/CREATE TABLE IF NOT EXISTS (\w+) \(/i', 
            'CREATE TABLE IF NOT EXISTS $1 (', $sql);
        $sql = preg_replace('/\);/i', 
            ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;', $sql);
        
        return $sql;
    }
}
