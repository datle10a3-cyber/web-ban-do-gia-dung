<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/vnpay.php';
$valid = vnpay_verified($_GET);
$order = null;
if ($valid && !empty($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT o.* FROM orders o JOIN payment_transactions t ON t.order_id=o.id WHERE t.order_code=? AND o.user_id=? AND t.payment_method='vnpay'");
    $stmt->execute([$_GET['vnp_TxnRef'] ?? '', $_SESSION['user_id']]);
    $order = $stmt->fetch();
}
$page_title = 'Kết quả thanh toán';
require __DIR__ . '/../includes/header.php';
?>
<main class="container py-5"><div class="card p-4 mx-auto" style="max-width:650px">
<h1 class="h3">Kết quả thanh toán VNPAY</h1>
<?php if (!$valid): ?><p class="text-danger">Không xác minh được kết quả thanh toán. Vui lòng kiểm tra trong Đơn hàng của tôi.</p>
<?php elseif (!$order): ?><p>Vui lòng đăng nhập tài khoản đã đặt hàng để xem kết quả.</p>
<?php else: ?>
<p>Đơn hàng <strong><?= sanitize($order['order_code']) ?></strong></p>
<?php $responseCode=$_GET['vnp_ResponseCode']??'';
$resultMessage=$order['payment_status']==='paid'?'Thanh toán đã được xác nhận.':match($responseCode){
    '00'=>'Đã nhận kết quả từ VNPAY. Đang chờ hệ thống xác nhận thanh toán; bạn có thể kiểm tra lại trong đơn hàng.',
    '24'=>'Bạn đã hủy thanh toán VNPAY. Đơn hàng vẫn được giữ để bạn có thể thanh toán lại.',
    '11'=>'Phiên thanh toán đã hết hạn. Bạn có thể tạo lại phiên thanh toán cho đơn hàng này.',
    '51'=>'Thanh toán chưa thành công do tài khoản không đủ số dư. Bạn có thể thử lại bằng phương thức phù hợp.',
    default=>'Thanh toán chưa hoàn tất. Đơn hàng của bạn chưa được đánh dấu đã thanh toán.'}; ?>
<p role="status"><?= sanitize($resultMessage) ?></p>
<?php if($order['payment_status']!=='paid'&&$order['status']==='pending'): ?><a class="btn btn-outline-primary mb-3" href="<?= BASE_URL ?>/checkout/thankyou.php?order_id=<?= (int)$order['id'] ?>&amp;code=<?= rawurlencode($order['order_code']) ?>">Xem đơn và thanh toán lại</a><?php endif; ?>
<?php endif; ?>
<a class="btn btn-primary" href="<?= BASE_URL ?>/user/orders.php">Đơn hàng của tôi</a>
</div></main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
