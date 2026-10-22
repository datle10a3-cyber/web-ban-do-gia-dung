<?php
require_once __DIR__ . '/app.php';
try {
    $pdo = new PDO('mysql:host=' . app_env('DB_HOST', '127.0.0.1') . ';port=' . app_env('DB_PORT', 3306) . ';dbname=' . app_env('DB_NAME', 'web_gia_dung') . ';charset=utf8mb4', app_env('DB_USER', 'root'), app_env('DB_PASSWORD'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
} catch (PDOException $e) {
    error_log('Database connection: ' . $e->getMessage());
    http_response_code(503);
    if (str_contains($_SERVER['REQUEST_URI'] ?? '', '/ajax/') || ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        header('Content-Type: application/json; charset=utf-8');
        exit(json_encode(['success' => false, 'message' => 'Chưa kết nối được dữ liệu. Vui lòng kiểm tra MySQL trong XAMPP.']));
    }
    exit('<!doctype html><html lang="vi"><meta charset="utf-8"><title>Thiết lập Gia Dụng Shop</title><body style="font-family:system-ui;max-width:640px;margin:80px auto;padding:24px"><h1>Cần kết nối cơ sở dữ liệu</h1><p>Bật MySQL trong XAMPP. Chạy <code>E:\\xampp\\php\\php.exe scripts/setup.php</code> tại thư mục dự án để nhập dữ liệu lần đầu, rồi tải lại trang.</p><p>Cấu hình kết nối tại <code>config/local.php</code>, tham khảo <code>config/local.example.php</code>.</p></body></html>');
}
