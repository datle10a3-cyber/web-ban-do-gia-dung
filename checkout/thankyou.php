<?php
require_once __DIR__.'/../includes/init.php';
requireLogin();
$q=$pdo->prepare('SELECT * FROM orders WHERE id=? AND order_code=? AND user_id=?');
$q->execute([(int)($_GET['order_id']??0),$_GET['code']??'',$_SESSION['user_id']]);$order=$q->fetch();
if(!$order){http_response_code(404);}
$page_title=$order?'Đã tiếp nhận đơn hàng':'Không tìm thấy đơn hàng';
require __DIR__.'/../includes/header.php';
?>
<main class="container py-5"><section class="shop-panel mx-auto" style="max-width:800px">
<?php if(!$order): ?><h1 class="h3">Không tìm thấy đơn hàng</h1><p>Vui lòng kiểm tra lại trong tài khoản của bạn.</p>
<?php else:
$q=$pdo->prepare('SELECT i.*,p.name,COALESCE(v.image,p.image) AS image,v.color,v.capacity FROM order_items i LEFT JOIN products p ON p.id=i.product_id LEFT JOIN product_variants v ON v.id=i.variant_id WHERE i.order_id=?');$q->execute([$order['id']]);$items=$q->fetchAll(); ?>
<span class="eyebrow">CẢM ƠN BẠN ĐÃ MUA SẮM</span><h1 class="h2 mt-2">Đơn hàng đã được tiếp nhận.</h1><p class="text-muted">Mã đơn hàng <strong class="text-dark"><?= sanitize($order['order_code']) ?></strong></p>
<div class="alert <?= $order['payment_status']==='paid'?'alert-success':'alert-info' ?>"><?= $order['payment_status']==='paid'?'Đã xác nhận thanh toán.':($order['payment_method']==='cod'?'Bạn sẽ thanh toán khi nhận hàng.':'Đơn hàng đang chờ xác nhận thanh toán.') ?></div>
<?php foreach($items as $i): ?><div class="summary-item"><img src="<?= UPLOAD_PATH ?>/<?= sanitize($i['image']??'no-image.jpg') ?>" alt="<?= sanitize($i['name']) ?>"><div><strong><?= sanitize($i['name']) ?></strong><small><?= sanitize(trim(($i['color']??'').' '.($i['capacity']??''))) ?> · SL: <?= $i['quantity'] ?></small><span><?= number_format($i['price']*$i['quantity'],0,',','.') ?>đ</span></div></div><?php endforeach; ?>
<div class="row g-3 mt-3"><div class="col-sm-6"><strong>Người nhận</strong><p><?= sanitize($order['fullname']) ?><br><?= sanitize($order['phone']) ?></p></div><div class="col-sm-6"><strong>Địa chỉ giao hàng</strong><p><?= nl2br(sanitize($order['shipping_address'])) ?></p></div></div>
<p class="fs-4">Tổng cộng <strong class="text-primary"><?= number_format($order['final_amount'],0,',','.') ?>đ</strong></p>
<?php if($order['payment_method']==='bank_transfer' && app_env('BANK_BIN') && app_env('BANK_ACCOUNT') && app_env('BANK_ACCOUNT_NAME')):
$url='https://img.vietqr.io/image/'.rawurlencode(app_env('BANK_BIN')).'-'.rawurlencode(app_env('BANK_ACCOUNT')).'-compact2.png?'.http_build_query(['amount'=>(int)$order['final_amount'],'addInfo'=>$order['order_code'],'accountName'=>app_env('BANK_ACCOUNT_NAME')]); ?>
<aside class="p-3 bg-light rounded mb-3"><h2 class="h5">Chuyển khoản cho đơn hàng</h2><img src="<?= sanitize($url) ?>" alt="Mã QR chuyển khoản" style="max-width:260px;width:100%"><p><?= sanitize(app_env('BANK_ACCOUNT_NAME')) ?><br>Số tài khoản: <?= sanitize(app_env('BANK_ACCOUNT')) ?><br>Nội dung: <?= sanitize($order['order_code']) ?></p><small>Shop sẽ xác nhận sau khi nhận được tiền.</small></aside>
<?php endif; ?>
<?php if($order['payment_method']==='vnpay' && $order['payment_status']!=='paid' && $order['status']==='pending'): ?>
<button class="btn btn-primary mb-3" id="retryPayment">Tiếp tục thanh toán VNPAY</button><p id="retryError" role="alert"></p>
<script>document.getElementById('retryPayment').onclick=async function(){this.disabled=true;try{const f=new FormData();f.append('csrf',<?= json_encode($_SESSION['csrf']) ?>);f.append('order_id',<?= (int)$order['id'] ?>);const r=await fetch(<?= json_encode(BASE_URL.'/cart/create_payment.php') ?>,{method:'POST',body:f});const d=await r.json();if(!d.success)throw Error(d.message);location.assign(d.payment_url);}catch(e){document.getElementById('retryError').textContent=e.message;this.disabled=false;}};</script>
<?php endif; endif; ?>
<div class="d-flex flex-wrap gap-2"><a class="btn btn-primary" href="<?= BASE_URL ?>/user/orders.php<?= $order?'?id='.(int)$order['id']:'' ?>">Xem đơn hàng</a><a class="btn btn-outline-primary" href="<?= BASE_URL ?>/products/">Tiếp tục mua sắm</a></div>
</section></main><?php require __DIR__.'/../includes/footer.php'; ?>
