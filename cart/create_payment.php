<?php
require_once __DIR__.'/../includes/init.php';
require_once __DIR__.'/../includes/vnpay.php';
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); exit(json_encode(['success'=>false])); }
if (empty($_SESSION['user_id']) || !is_string($_POST['csrf']??null) || !hash_equals($_SESSION['csrf'],$_POST['csrf'])) { http_response_code(403); exit(json_encode(['success'=>false,'message'=>'Vui lòng đăng nhập và tải lại trang.'])); }
try {
    $pdo->beginTransaction();
    $q=$pdo->prepare("SELECT * FROM orders WHERE id=? AND user_id=? AND payment_method='vnpay' AND payment_status<>'paid' AND status='pending' FOR UPDATE");
    $q->execute([(int)($_POST['order_id']??0),$_SESSION['user_id']]); $order=$q->fetch();
    if (!$order) { throw new RuntimeException('Không thể thanh toán đơn hàng này.'); }
    $url=vnpay_payment_url($pdo,$order); $pdo->commit();
    echo json_encode(['success'=>true,'payment_url'=>$url]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    http_response_code(422); echo json_encode(['success'=>false,'message'=>'Chưa thể tạo thanh toán. Vui lòng kiểm tra cấu hình VNPAY.']);
}
