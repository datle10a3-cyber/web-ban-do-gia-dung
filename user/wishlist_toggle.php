<?php

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    
}

require_once __DIR__ . '/../includes/init.php';

// CSRF check
if (
    !isset($_POST['csrf']) ||
    !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])
) {
    echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ']);
    exit;
}

if (empty($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'login'   => true,
        'message' => 'Vui lòng đăng nhập để sử dụng chức năng này'
    ]);
    exit;
}

$product_id = (int)($_POST['product_id'] ?? 0);
if ($product_id < 1) {
    echo json_encode(['success' => false, 'message' => 'ID sản phẩm không hợp lệ']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

try {
    // Kiểm tra sản phẩm có tồn tại không
    $stmt = $pdo->prepare("SELECT 1 FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    if (!$stmt->fetchColumn()) {
        echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
        exit;
    }

    // LẤY TÊN SẢN PHẨM ĐỂ GHI LOG (rất quan trọng!)
    $nameStmt = $pdo->prepare("SELECT name FROM products WHERE id = ?");
    $nameStmt->execute([$product_id]);
    $product_name = $nameStmt->fetchColumn() ?: 'Sản phẩm #' . $product_id;

    // Kiểm tra đã có trong wishlist chưa
    $check = $pdo->prepare("SELECT 1 FROM wishlist WHERE user_id = ? AND product_id = ?");
    $check->execute([$user_id, $product_id]);
    $exists = $check->fetchColumn();

    if ($exists) {
        // XÓA KHỎI YÊU THÍCH
        $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?")
            ->execute([$user_id, $product_id]);
        
        // GHI HOẠT ĐỘNG: BỎ YÊU THÍCH
        logUserActivity($user_id, 'wishlist_remove', $product_name);

        $added   = false;
        $action  = 'removed';
        $message = 'Đã bỏ khỏi danh sách yêu thích';
    } else {
        // THÊM VÀO YÊU THÍCH
        $pdo->prepare("
            INSERT INTO wishlist (user_id, product_id, created_at) 
            VALUES (?, ?, NOW())
        ")->execute([$user_id, $product_id]);
        
        // GHI HOẠT ĐỘNG: THÊM YÊU THÍCH
        logUserActivity($user_id, 'wishlist_add', $product_name);

        $added   = true;
        $action  = 'added';
        $message = 'Đã thêm vào danh sách yêu thích';
    }

    // Đếm lại số lượng wishlist
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
    $countStmt->execute([$user_id]);
    $wishlist_count = (int)$countStmt->fetchColumn();

    // Trả về JSON
    echo json_encode([
        'success'        => true,
        'added'          => $added,
        'action'         => $action,
        'message'        => $message,
        'wishlist_count' => $wishlist_count
    ]);

} catch (Exception $e) {
    error_log("Wishlist toggle error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống, vui lòng thử lại sau']);
}

exit;