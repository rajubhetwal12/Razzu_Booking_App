<?php
/**
 * LuxStay — Core Configuration
 * Include this FIRST on every page. Starts session, defines constants, provides DB.
 */

// ① Start session immediately — before ANY output
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

// ② App constants (guard against re-define)
defined('BASE_URL') || define('BASE_URL', 'http://localhost/1');
defined('DB_HOST')  || define('DB_HOST',  '127.0.0.1');
defined('DB_USER')  || define('DB_USER',  'root');
defined('DB_PASS')  || define('DB_PASS',  '');
defined('DB_NAME')  || define('DB_NAME',  'luxstay');

// ③ PDO singleton
function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;
    try {
        $pdo = new PDO(
            'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
             PDO::ATTR_EMULATE_PREPARES   => false]
        );
    } catch (PDOException $e) {
        die('<!DOCTYPE html><html><head><title>DB Error</title><style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font:16px/1.6 system-ui,sans-serif;background:#0a0a1a;color:#fff;
             min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem}
        .b{background:#111130;border:1px solid #ef444455;border-radius:16px;padding:2rem;max-width:480px;text-align:center}
        h2{color:#f87171;margin-bottom:1rem}p{color:#aaa;font-size:.9rem;line-height:1.7}
        code{background:#ffffff11;padding:.2rem .5rem;border-radius:4px;font-family:monospace}
        a{color:#d4a017}</style></head><body><div class="b">
        <h2>⚠️ Database Error</h2>
        <p>Could not connect to MySQL.<br>
        Error: <code>'.htmlspecialchars($e->getMessage(),ENT_QUOTES).'</code><br><br>
        Steps to fix:<br>
        1. Start MySQL in XAMPP<br>
        2. Import <code>database.sql</code> in phpMyAdmin<br>
        3. Visit <a href="'.BASE_URL.'/setup.php">setup.php</a></p>
        </div></body></html>');
    }
    return $pdo;
}
