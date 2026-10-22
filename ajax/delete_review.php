<?php
                    
ini_set('display_errors', 0); 
error_reporting(0);


require_once __DIR__ . '/../includes/init.php';
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit(json_encode(['success'=>false]));}
  

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

$user_id = $_SESSION['user_id'];
$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
    exit;
}

if (!empty($_SESSION['csrf']) && ($_POST['csrf'] ?? '') !== $_SESSION['csrf']) {
    echo json_encode(['success' => false, 'message' => 'CSRF invalid']);
    exit;
}

$stmt = $pdo->prepare("DELETE FROM product_reviews WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $user_id]);

if ($stmt->rowCount() > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy hoặc không có quyền']);
}
exit;
?>