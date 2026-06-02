<?php
// config/db.php
// Week 1 — database connection (unchanged from v1, still using PDO)

define('DB_HOST', 'localhost');
define('DB_NAME', 'ctisms_v2');
define('DB_USER', 'root');
define('DB_PASS', '');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER, DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            die("<div style='font-family:Arial;padding:20px;background:#fff3f3;border-left:4px solid red'>
                <b>Database Error:</b> " . htmlspecialchars($e->getMessage()) . "<br>
                Check config/db.php — DB_USER=root, DB_PASS='' for XAMPP default.
            </div>");
        }
    }
    return $pdo;
}
