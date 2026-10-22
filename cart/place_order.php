<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/checkout_service.php';
require_once __DIR__ . '/../includes/vnpay.php';
header('Content-Type: application/json; charset=utf-8');
function order_response(array $data, int $status = 200): void {
    http_response_code($status); echo json_encode($data, JSON_UNESCAPED_UNICODE); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { order_response(['success'=>false,'message'=>'Chỉ chấp nhận POST.'],405); }
if (empty($_SESSION['user_id'])) { order_response(['success'=>false,'message'=>'Vui lòng đăng nhập để đặt hàng.'],401); }
if (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) { order_response(['success'=>false,'message'=>'Phiên đã hết hạn. Vui lòng tải lại trang.'],403); }
$uid = (int)$_SESSION['user_id'];
$key = $_POST['request_key'] ?? '';
if (!is_string($key) || !preg_match('/^[a-f0-9]{32}$/', $key)) { order_response(['success'=>false,'message'=>'Vui lòng tải lại trang thanh toán.'],422); }
function existing_order_response(PDO $pdo, int $uid, string $key): ?array {
    $q=$pdo->prepare('SELECT o.* FROM orders o JOIN checkout_requests r ON r.order_id=o.id WHERE r.user_id=? AND r.request_key=?');
    $q->execute([$uid,$key]); $o=$q->fetch();
    if (!$o) { return null; }
    return ['success'=>true,'order_id'=>$o['id'],'order_code'=>$o['order_code'],'redirect_url'=>BASE_URL.'/checkout/thankyou.php?order_id='.$o['id'].'&code='.rawurlencode($o['order_code'])];
}
if ($existing = existing_order_response($pdo,$uid,$key)) { order_response($existing); }
if (!hash_equals($_SESSION['checkout_request_key'] ?? '', $key)) { order_response(['success'=>false,'message'=>'Thông tin thanh toán đã thay đổi. Vui lòng tải lại trang.'],409); }
$fullname=trim($_POST['fullname'] ?? ''); $phone=trim($_POST['phone'] ?? '');
$email=trim($_POST['email'] ?? ''); $address=trim($_POST['address'] ?? ''); $note=trim($_POST['order_note'] ?? '');
$method=$_POST['payment_method'] ?? 'cod'; $code=strtoupper(trim($_POST['voucher_code'] ?? ''));
if (mb_strlen($fullname)<2 || mb_strlen($fullname)>100 || mb_strlen($address)<10 || mb_strlen($address)>2000 || !preg_match('/^0[0-9]{9}$/',$phone) || ($email && (!filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>100)) || mb_strlen($note)>2000) {
    order_response(['success'=>false,'message'=>'Kiểm tra họ tên, số điện thoại 10 số, email và địa chỉ giao hàng (ít nhất 10 ký tự).'],422);
}
if (!in_array($method,['cod','bank_transfer','vnpay'],true) || ($method==='vnpay' && !vnpay_ready()) || ($method==='bank_transfer' && (!app_env('BANK_ACCOUNT') || !app_env('BANK_BIN') || !app_env('BANK_ACCOUNT_NAME')))) {
    order_response(['success'=>false,'message'=>'Phương thức thanh toán chưa sẵn sàng. Vui lòng chọn COD.'],422);
}
try {
    $pdo->beginTransaction();
    // Serialize checkout for the account as well as using a unique request key.
    $pdo->prepare('SELECT id FROM users WHERE id=? FOR UPDATE')->execute([$uid]);
    if ($existing=existing_order_response($pdo,$uid,$key)) { $pdo->rollBack(); order_response($existing); }
    $cart=checkout_items($pdo,$_SESSION['checkout_cart_override'] ?? [],true);
    $subtotal=checkout_subtotal($cart); $discount=0; $voucher=null;
    if ($code!=='') {
        $q=$pdo->prepare('SELECT * FROM vouchers WHERE code=? AND is_active=1 AND (expires_at IS NULL OR expires_at>NOW()) AND used_count<max_usage FOR UPDATE');
        $q->execute([$code]); $voucher=$q->fetch();
        if (!$voucher) { throw new RuntimeException('Mã giảm giá không hợp lệ hoặc hết lượt.'); }
        voucher_assert_account($pdo,$voucher,$uid);
        $discount=checkout_discount($voucher,$subtotal);
    }
    $total=$subtotal-$discount;
    if ($method==='vnpay' && $total<5000) { throw new RuntimeException('VNPAY yêu cầu đơn hàng từ 5.000đ. Vui lòng chọn COD.'); }
    $q=$pdo->prepare("INSERT INTO orders(user_id,fullname,phone,email,shipping_address,note,payment_method,payment_status,voucher_code,discount_amount,shipping_fee,subtotal,final_amount,total,status) VALUES (?,?,?,?,?,?,?,'pending',?,?,0,?,?,?,'pending')");
    $q->execute([$uid,$fullname,$phone,$email?:null,$address,$note,$method,$code?:null,$discount,$subtotal,$total,$total]);
    $id=(int)$pdo->lastInsertId(); $orderCode='GD'.str_pad((string)$id,8,'0',STR_PAD_LEFT);
    $pdo->prepare('UPDATE orders SET order_code=? WHERE id=?')->execute([$orderCode,$id]);
    foreach ($cart as $item) {
        $q=$pdo->prepare('UPDATE products SET stock=stock-? WHERE id=? AND stock>=?');
        $q->execute([$item['qty'],$item['product_id'],$item['qty']]);
        if (!$q->rowCount()) { throw new RuntimeException('Sản phẩm không đủ tồn kho.'); }
        if ($item['variant_id']) {
            $q=$pdo->prepare('UPDATE product_variants SET stock=stock-? WHERE id=? AND product_id=? AND stock>=?');
            $q->execute([$item['qty'],$item['variant_id'],$item['product_id'],$item['qty']]);
            if (!$q->rowCount()) { throw new RuntimeException('Phân loại sản phẩm không đủ tồn kho.'); }
        }
        $pdo->prepare('INSERT INTO order_items(order_id,product_id,variant_id,quantity,price) VALUES (?,?,?,?,?)')->execute([$id,$item['product_id'],$item['variant_id'],$item['qty'],$item['price']]);
        if (($_SESSION['checkout_mode']??'')!=='direct') {
            $pdo->prepare('DELETE FROM cart WHERE user_id=? AND product_id=? AND variant_id <=> ?')->execute([$uid,$item['product_id'],$item['variant_id']]);
        }
    }
    if ($voucher) {
        $pdo->prepare('UPDATE vouchers SET used_count=used_count+1 WHERE id=?')->execute([$voucher['id']]);
        $pdo->prepare('INSERT INTO voucher_usage(voucher_id,user_id,order_id) VALUES (?,?,?)')->execute([$voucher['id'],$uid,$id]);
    }
    $pdo->prepare('INSERT INTO checkout_requests(user_id,request_key,order_id) VALUES (?,?,?)')->execute([$uid,$key,$id]);
    $redirect=BASE_URL.'/checkout/thankyou.php?order_id='.$id.'&code='.$orderCode;
    if ($method==='vnpay') { $redirect=vnpay_payment_url($pdo,['id'=>$id,'order_code'=>$orderCode,'final_amount'=>$total]); }
    logUserActivity($uid,'order_placed','Đơn hàng #'.$orderCode);
    $pdo->commit();
    if (($_SESSION['checkout_mode']??'')!=='direct') {
        foreach (array_keys($cart) as $cartKey) { unset($_SESSION['cart'][$cartKey]); }
    }
    unset($_SESSION['checkout_cart_override'],$_SESSION['checkout_mode'],$_SESSION['selected_cart_items'],$_SESSION['direct_buy'],$_SESSION['checkout_request_key']);
    order_response(['success'=>true,'order_id'=>$id,'order_code'=>$orderCode,'redirect_url'=>$redirect]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    error_log('Checkout: '.$e->getMessage());
    order_response(['success'=>false,'message'=>$e instanceof RuntimeException && !($e instanceof PDOException) ? $e->getMessage() : 'Không thể tạo đơn hàng lúc này. Vui lòng thử lại.'],422);
}
