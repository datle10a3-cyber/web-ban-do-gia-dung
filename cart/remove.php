<?php
require_once __DIR__ . '/../includes/init.php';

require_once __DIR__.'/../includes/cart_service.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ']);
    exit;
}

if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'CSRF token không hợp lệ']);
    exit;
}

$key = $_POST['key'] ?? '';

if (isset($_SESSION['cart'][$key])) {
    $removed_item = $_SESSION['cart'][$key];
    $product_name = $removed_item['name'];
    if (!empty($removed_item['variant_name'])) {
        $product_name .= " ({$removed_item['variant_name']})";
    }
    $qty = $removed_item['qty'] ?? 1;

    if(isLoggedIn()){$pdo->prepare('DELETE FROM cart WHERE user_id=? AND product_id=? AND variant_id <=> ?')->execute([$_SESSION['user_id'],$removed_item['product_id'],$removed_item['variant_id']?:null]);}
    unset($_SESSION['cart'][$key]);

    $_SESSION['selected_cart_items'] = array_values(array_diff(
        ($_SESSION['selected_cart_items'] ?? []), [$key]
    ));

    if (isset($_SESSION['user_id'])) {
        logUserActivity(
            $_SESSION['user_id'],
            'cart_remove',
            $product_name . " ×{$qty}"
        );
    }

    echo json_encode(['success' => true, 'message' => 'Đã xóa sản phẩm']);
} else {
    echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
}