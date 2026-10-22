<?php

require_once __DIR__.'/../includes/init.php';

require_once __DIR__.'/../includes/cart_service.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'CSRF token không hợp lệ']);
    exit;
}

if(isLoggedIn()){$pdo->prepare('DELETE FROM cart WHERE user_id=?')->execute([$_SESSION['user_id']]);}
unset($_SESSION['cart']);
unset($_SESSION['selected_cart_items']);
unset($_SESSION['cart_total_qty']); // nếu có lưu

echo json_encode(['success' => true, 'message' => 'Đã xóa toàn bộ giỏ hàng']);
exit;