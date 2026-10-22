<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/init.php';
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit(json_encode(['success'=>false]));}


$base = '/web-ban-do-gia-dung';   

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

$user_id   = $_SESSION['user_id'];
$review_id = (int)($_POST['id'] ?? 0);

if ($review_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
    exit;
}

if (empty($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['csrf']) {
    echo json_encode(['success' => false, 'message' => 'CSRF sai']);
    exit;
}

$stmt = $pdo->prepare("SELECT id, order_id FROM product_reviews WHERE id = ? AND user_id = ?");
$stmt->execute([$review_id, $user_id]);
$review = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$review) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy đánh giá']);
    exit;
}

$redirect_url = $base . '/user/review.php?order_id=' . $review['order_id'] . '&edit_review_id=' . $review_id;

echo json_encode(['success' => true, 'redirect' => $redirect_url]);
exit;
?>