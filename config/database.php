<?php
/**
 * 硬盘空间 - 数据库配置
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'repo_888422_xyz');
define('DB_USER', 'repo_888422_xyz');
define('DB_PASS', 'repo_888422_xyz');
define('DB_CHARSET', 'utf8mb4');

// PDO连接
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException $e) {
            die("数据库连接失败: " . $e->getMessage());
        }
    }
    return $pdo;
}
