<?php
require_once __DIR__.'/includes/init.php';
require_once __DIR__.'/includes/checkout_service.php';
require_once __DIR__.'/includes/vnpay.php';
requireLogin();
$error=''; $cart=[];
try {
    if (!empty($_SESSION['direct_buy'])) {
        $i=$_SESSION['direct_buy'];
        $cart=['direct_'.$i['product_id'].'_'.($i['variant_id']??0)=>$i];
        $_SESSION['checkout_mode']='direct'; unset($_SESSION['direct_buy']);
    } elseif (($_SESSION['checkout_mode']??'')==='direct' && !empty($_SESSION['checkout_cart_override']) && !isset($_GET['items'])) {
        $cart=$_SESSION['checkout_cart_override'];
    } else {
        $full=$_SESSION['cart']??[];
        $keys=isset($_GET['items']) ? explode(',',$_GET['items']) : ($_SESSION['selected_cart_items']??array_keys($full));
        $cart=array_intersect_key($full,array_flip($keys)); $_SESSION['checkout_mode']='cart';
    }
    $cart=checkout_items($pdo,$cart);
    $old=$_SESSION['checkout_cart_override']??[];
    if ($old!==$cart || empty($_SESSION['checkout_request_key'])) { $_SESSION['checkout_request_key']=bin2hex(random_bytes(16)); }
    $_SESSION['checkout_cart_override']=$cart;
} catch (RuntimeException $e) { $error=$e->getMessage(); }
$user=account_user();
$q=$pdo->prepare('SELECT * FROM user_addresses WHERE user_id=? ORDER BY is_default DESC,id DESC');$q->execute([$_SESSION['user_id']]);$addresses=$q->fetchAll();
$vouchers=voucher_available($pdo,(int)$_SESSION['user_id']);
$subtotal=checkout_subtotal($cart);
$bankReady=app_env('BANK_BIN') && app_env('BANK_ACCOUNT') && app_env('BANK_ACCOUNT_NAME');
$page_title='Thanh toán'; require __DIR__.'/includes/header.php';
?>
<main class="container checkout-page py-5">
<nav class="shop-breadcrumb" aria-label="Điều hướng"><a href="<?= BASE_URL ?>/cart/">Giỏ hàng</a><span>/</span><strong>Thanh toán</strong><span>/</span><span>Hoàn tất</span></nav>
<div class="page-heading"><span class="eyebrow">HOÀN TẤT MUA SẮM</span><h1>Thêm tiện nghi, thêm an tâm.</h1><p>Kiểm tra thông tin nhận hàng và chọn cách thanh toán phù hợp.</p></div>
<?php if ($error): ?><div class="alert alert-warning"><?= sanitize($error) ?> <a href="<?= BASE_URL ?>/cart/">Quay lại giỏ hàng</a></div>
<?php else: ?>
<form id="checkoutForm"><input type="hidden" name="csrf" value="<?= sanitize($_SESSION['csrf']) ?>"><input type="hidden" name="request_key" value="<?= sanitize($_SESSION['checkout_request_key']) ?>">
<div class="row g-4"><div class="col-lg-7">
<section class="shop-panel"><h2 class="h5 mb-4">01. Thông tin giao hàng</h2><div class="row g-3">
<div class="col-sm-6"><label for="fullname" class="form-label">Họ và tên</label><input id="fullname" name="fullname" class="form-control" maxlength="100" minlength="2" autocomplete="name" value="<?= sanitize(account_display_name($user)) ?>" required></div>
<div class="col-sm-6"><label for="phone" class="form-label">Số điện thoại</label><input id="phone" name="phone" class="form-control" type="tel" pattern="0[0-9]{9}" maxlength="10" autocomplete="tel" value="<?= sanitize($user['phone']??'') ?>" placeholder="09xxxxxxxx" required></div>
<div class="col-12"><label for="email" class="form-label">Email (không bắt buộc)</label><input id="email" name="email" class="form-control" type="email" maxlength="100" autocomplete="email" value="<?= sanitize(str_ends_with($user['email']??'','@social.invalid')?'':($user['email']??'')) ?>"></div>
<?php if ($addresses): ?><div class="col-12"><label for="savedAddress" class="form-label">Địa chỉ đã lưu</label><select id="savedAddress" class="form-select"><option value="">Nhập địa chỉ mới</option><?php foreach($addresses as $a): ?><option value="<?= sanitize($a['address']) ?>"><?= sanitize($a['address']) ?></option><?php endforeach; ?></select></div><?php endif; ?>
<div class="col-12"><label for="address" class="form-label">Địa chỉ nhận hàng</label><textarea id="address" name="address" class="form-control" minlength="10" maxlength="2000" autocomplete="street-address" rows="2" placeholder="Số nhà, đường, phường/xã, tỉnh/thành phố" required><?= sanitize($addresses[0]['address']??'') ?></textarea></div>
<div class="col-12"><label for="order_note" class="form-label">Ghi chú (không bắt buộc)</label><textarea id="order_note" name="order_note" class="form-control" maxlength="2000" rows="2" placeholder="Lưu ý khi giao hàng..."></textarea></div></div></section>
<section class="shop-panel mt-4"><h2 class="h5 mb-4">02. Phương thức thanh toán</h2>
<label class="checkout-method"><input type="radio" name="payment_method" value="cod" checked><span><strong>Thanh toán khi nhận hàng</strong><small>Thanh toán cho đơn vị giao hàng khi nhận sản phẩm.</small></span><span class="method-tag">COD</span></label>
<label class="checkout-method <?= vnpay_ready()?'':'unavailable' ?>"><input type="radio" name="payment_method" value="vnpay" <?= vnpay_ready()?'':'disabled' ?>><span><strong>Thanh toán qua VNPAY</strong><small><?= vnpay_ready()?(str_contains(app_env('VNPAY_URL'),'sandbox.vnpayment.vn')?'Sandbox: giao dịch thử, không dùng để thanh toán thật.':'Chọn ngân hàng, thẻ hoặc quét QR.'):'Chưa kết nối thanh toán trực tuyến.' ?></small></span><span class="method-tag"><?= str_contains(app_env('VNPAY_URL'),'sandbox.vnpayment.vn')?'SANDBOX':'VNPAY' ?></span></label>
<label class="checkout-method unavailable"><input type="radio" name="payment_method" value="momo" disabled><img class="payment-brand" src="<?= BASE_URL ?>/assets/images/payment/momo.png" alt=""><span><strong>Ví MoMo</strong><small>Chưa kết nối thanh toán trực tuyến.</small></span><span class="method-tag">MoMo</span></label>
<label class="checkout-method unavailable"><input type="radio" name="payment_method" value="zalopay" disabled><img class="payment-brand" src="<?= BASE_URL ?>/assets/images/payment/zalopay.png" alt=""><span><strong>Ví ZaloPay</strong><small>Chưa kết nối thanh toán trực tuyến.</small></span><span class="method-tag">ZaloPay</span></label>
<?php if ($bankReady): ?><label class="checkout-method"><input type="radio" name="payment_method" value="bank_transfer"><span><strong>Chuyển khoản ngân hàng</strong><small>Thông tin chuyển khoản hiển thị sau khi tạo đơn. Shop xác nhận khi nhận được tiền.</small></span><span class="method-tag">QR</span></label><?php endif; ?>
</section></div><div class="col-lg-5"><section class="shop-panel order-summary"><h2 class="h5 mb-4">Đơn hàng của bạn</h2>
<?php foreach($cart as $i): ?><div class="summary-item"><img src="<?= UPLOAD_PATH ?>/<?= sanitize($i['image']) ?>" alt="<?= sanitize($i['name']) ?>"><div><strong><?= sanitize($i['name']) ?></strong><small><?= sanitize($i['variant_name']) ?> · SL: <?= $i['qty'] ?></small><span><?= number_format($i['price']*$i['qty'],0,',','.') ?>đ</span></div></div><?php endforeach; ?>
<label class="form-label mt-3" for="voucher">Ưu đãi của bạn</label><select id="voucher" name="voucher_code" class="form-select" aria-describedby="voucherAvailability"><option value="" data-discount="0">Không sử dụng mã</option><?php foreach($vouchers as $v): if($subtotal<$v['min_order'])continue; ?><option value="<?= sanitize($v['code']) ?>" data-discount="<?= checkout_discount($v,$subtotal) ?>" data-remaining="<?= (int)$v['remaining'] ?>" data-user-remaining="<?= (int)$v['user_remaining'] ?>" data-user-limit="<?= (int)$v['per_user_limit'] ?>" data-new-customer="<?= (int)$v['new_customer_only'] ?>"><?= sanitize($v['code']) ?> — giảm <?= $v['discount_type']==='percent'?(float)$v['discount_value'].'%':number_format($v['discount_value'],0,',','.').'đ' ?> · bạn còn <?= (int)$v['user_remaining'] ?> lượt<?= $v['new_customer_only']?' · đơn đầu tiên':'' ?></option><?php endforeach; ?></select>
<p id="voucherAvailability" class="voucher-availability" aria-live="polite">Mỗi mã có 1–2 lượt cho tài khoản của bạn. Chỉ hiện mã đủ điều kiện cho đơn này.</p>
<dl class="checkout-totals"><div><dt>Tạm tính</dt><dd><?= number_format($subtotal,0,',','.') ?>đ</dd></div><div><dt>Vận chuyển</dt><dd>Miễn phí</dd></div><div><dt>Giảm giá</dt><dd id="discountDisplay">0đ</dd></div><div class="grand-total"><dt>Tổng cộng</dt><dd id="totalDisplay"><?= number_format($subtotal,0,',','.') ?>đ</dd></div></dl>
<div id="checkoutError" class="alert alert-danger" role="alert" hidden></div><button id="placeOrderBtn" type="submit" class="btn btn-primary w-100 py-3">Đặt hàng <i class="fas fa-arrow-right ms-2" aria-hidden="true"></i></button><p class="text-muted small mt-3 mb-0">Kiểm tra kỹ thông tin trước khi đặt hàng. Bạn có thể theo dõi trạng thái trong tài khoản.</p>
</section></div></div></form>
<script>
(()=>{const form=document.getElementById('checkoutForm'),btn=document.getElementById('placeOrderBtn'),err=document.getElementById('checkoutError'),voucher=document.getElementById('voucher');
voucher.addEventListener('change',()=>{const selected=voucher.selectedOptions[0],discount=Number(selected.dataset.discount);document.getElementById('discountDisplay').textContent='−'+discount.toLocaleString('vi-VN')+'đ';document.getElementById('totalDisplay').textContent=(<?= $subtotal ?>-discount).toLocaleString('vi-VN')+'đ';document.getElementById('voucherAvailability').textContent=selected.value?'Bạn còn '+selected.dataset.userRemaining+'/'+selected.dataset.userLimit+' lượt dùng mã này'+(selected.dataset.newCustomer==='1'?' · Chỉ đơn đầu tiên':'')+'. Toàn shop còn '+Number(selected.dataset.remaining).toLocaleString('vi-VN')+' lượt.':'Mỗi mã có 1–2 lượt cho tài khoản của bạn. Chỉ hiện mã đủ điều kiện cho đơn này.';});
document.getElementById('savedAddress')?.addEventListener('change',e=>{document.getElementById('address').value=e.target.value;});
form.addEventListener('submit',async e=>{e.preventDefault();if(btn.disabled)return;btn.disabled=true;btn.textContent='Đang tạo đơn hàng...';err.hidden=true;
try{const r=await fetch(<?= json_encode(BASE_URL.'/cart/place_order.php') ?>,{method:'POST',body:new FormData(form)});const data=await r.json();if(!data.success)throw Error(data.message||'Không thể tạo đơn hàng.');window.location.assign(data.redirect_url);}
catch(e){err.textContent=e.message||'Kết nối bị gián đoạn. Vui lòng thử lại.';err.hidden=false;btn.disabled=false;btn.textContent='Đặt hàng';}});})();
</script>
<?php endif; ?></main>
<?php require __DIR__.'/includes/footer.php'; ?>
