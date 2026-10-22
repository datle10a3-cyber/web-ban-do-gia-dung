<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/app.php';
$pdo = new PDO('mysql:host=' . app_env('DB_HOST', '127.0.0.1') . ';port=' . app_env('DB_PORT', 3306) . ';charset=utf8mb4', app_env('DB_USER', 'root'), app_env('DB_PASSWORD'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$name = app_env('DB_NAME', 'web_gia_dung');
if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) { throw new RuntimeException('Invalid database name'); }
$pdo->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE `$name`");
if (!$pdo->query('SHOW TABLES')->fetchColumn()) {
    $sql = file_get_contents(__DIR__ . '/../database.sql/web_gia_dung.sql');
    $sql = preg_replace('/CREATE DATABASE[^;]+;|USE web_gia_dung;/', '', $sql);
    $pdo->exec($sql);
    echo "Imported starter database.\n";
}
$pdo->exec(file_get_contents(__DIR__ . '/../database.sql/20261022_integrations.sql'));
$pdo->exec(file_get_contents(__DIR__ . '/../database.sql/20261022_newsletter.sql'));
$pdo->exec(file_get_contents(__DIR__ . '/../database.sql/20261022_voucher_limits.sql'));
$pdo->exec(file_get_contents(__DIR__ . '/../database.sql/20261022_accounts.sql'));
$pdo->exec(file_get_contents(__DIR__ . '/../database.sql/20261022_voucher_accounts.sql'));
echo "Database ready. Existing records preserved.\n";
