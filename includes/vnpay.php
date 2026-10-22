<?php
require_once __DIR__ . '/../config/app.php';
function vnpay_audit(string $event, array $query = [], ?int $orderId = null): void {
    $ref=is_scalar($query['vnp_TxnRef']??null)?(string)$query['vnp_TxnRef']:'';
    $entry=['time'=>date(DATE_ATOM),'event'=>$event,'reference'=>substr(preg_replace('/[^a-zA-Z0-9_-]/','',$ref),0,64)];
    if($orderId!==null){$entry['order_id']=$orderId;}
    // No secrets, signatures, payer details or full callback URLs are logged.
    $path=app_env('VNPAY_AUDIT_LOG',sys_get_temp_dir().DIRECTORY_SEPARATOR.'giadung-vnpay-audit.log');
    @file_put_contents($path,json_encode($entry,JSON_UNESCAPED_UNICODE).PHP_EOL,FILE_APPEND|LOCK_EX);
}
function vnpay_reply(string $code, string $message, array $query): array {
    vnpay_audit('ipn_'.$code,$query);
    return ['RspCode'=>$code,'Message'=>$message];
}
function vnpay_ready(): bool { return app_env('VNPAY_TMN_CODE') !== '' && app_env('VNPAY_HASH_SECRET') !== ''; }
function vnpay_sign(array $data): string {
    ksort($data);
    return hash_hmac('sha512', http_build_query($data, '', '&', PHP_QUERY_RFC1738), app_env('VNPAY_HASH_SECRET'));
}
function vnpay_verified(array $query): bool {
    $signature = $query['vnp_SecureHash'] ?? '';
    if (!vnpay_ready() || !is_string($signature) || strlen($signature) !== 128) { return false; }
    $data = [];
    foreach ($query as $key => $value) {
        if (str_starts_with($key, 'vnp_') && !in_array($key, ['vnp_SecureHash', 'vnp_SecureHashType'], true)) {
            if (!is_scalar($value)) { return false; }
            $data[$key] = (string)$value;
        }
    }
    return ($data['vnp_TmnCode'] ?? '') === app_env('VNPAY_TMN_CODE') && hash_equals(vnpay_sign($data), strtolower($signature));
}
function vnpay_payment_url(PDO $pdo, array $order): string {
    if (!vnpay_ready()) { throw new RuntimeException('VNPAY chưa được cấu hình. Vui lòng chọn COD.'); }
    $ref = app_env('VNPAY_TXN_PREFIX','') . $order['order_code'] . bin2hex(random_bytes(5));
    $data = ['vnp_Version' => '2.1.0', 'vnp_Command' => 'pay', 'vnp_TmnCode' => app_env('VNPAY_TMN_CODE'),
        'vnp_Amount' => (int)round($order['final_amount'] * 100), 'vnp_CurrCode' => 'VND', 'vnp_TxnRef' => $ref,
        'vnp_OrderInfo' => 'Thanh toan don hang ' . $order['order_code'], 'vnp_OrderType' => 'other',
        'vnp_Locale' => 'vn', 'vnp_ReturnUrl' => APP_URL . '/cart/vnpay_return.php',
        'vnp_IpAddr' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', 'vnp_CreateDate' => date('YmdHis'),
        'vnp_ExpireDate' => date('YmdHis', time() + 900)];
    $pdo->prepare("INSERT INTO payment_transactions(order_id,order_code,payment_method,amount,status) VALUES (?,?,'vnpay',?,'pending')")
        ->execute([$order['id'], $ref, $order['final_amount']]);
    ksort($data);
    vnpay_audit('payment_request_created',$data,(int)$order['id']);
    return app_env('VNPAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html') . '?' . http_build_query($data, '', '&', PHP_QUERY_RFC1738) . '&vnp_SecureHash=' . vnpay_sign($data);
}
function vnpay_settle(PDO $pdo, array $query): array {
    if (!vnpay_verified($query)) { return vnpay_reply('97','Invalid signature',$query); }
    if(!preg_match('/^[0-9]{2}$/',(string)($query['vnp_ResponseCode']??''))||!preg_match('/^[0-9]{2}$/',(string)($query['vnp_TransactionStatus']??''))){return vnpay_reply('99','Incomplete result',$query);}
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT t.*,o.payment_status,o.status AS order_status,o.final_amount FROM payment_transactions t JOIN orders o ON o.id=t.order_id WHERE t.order_code=? AND t.payment_method='vnpay' FOR UPDATE");
        $stmt->execute([$query['vnp_TxnRef'] ?? '']);
        $tx = $stmt->fetch();
        if (!$tx) { $pdo->rollBack(); return vnpay_reply('01','Order not found',$query); }
        if (!ctype_digit((string)($query['vnp_Amount'] ?? '')) || (int)$query['vnp_Amount'] !== (int)round($tx['amount'] * 100) || $tx['amount'] != $tx['final_amount']) {
            $pdo->rollBack(); return vnpay_reply('04','Invalid amount',$query);
        }
        if ($tx['status'] !== 'pending' || $tx['payment_status'] === 'paid') {
            $pdo->rollBack(); return vnpay_reply('02','Order already confirmed or closed',$query);
        }
        $paid = ($query['vnp_ResponseCode'] ?? '') === '00' && ($query['vnp_TransactionStatus'] ?? '') === '00';
        $pdo->prepare('UPDATE payment_transactions SET status=?,transaction_id=? WHERE id=?')->execute([$paid ? 'success' : 'failed', $query['vnp_TransactionNo'] ?? null, $tx['id']]);
        $pdo->prepare('UPDATE orders SET payment_status=? WHERE id=?')->execute([$paid ? 'paid' : 'failed', $tx['order_id']]);
        $pdo->commit();
        if($paid&&$tx['order_status']==='cancelled'){vnpay_audit('paid_after_cancellation',$query,(int)$tx['order_id']);}
        return vnpay_reply('00','Confirm success',$query);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        error_log('VNPAY IPN: ' . $e->getMessage());
        return vnpay_reply('99','Temporary error',$query);
    }
}
