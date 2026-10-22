<?php
// Quotas belong to the authenticated account; previewing never consumes a use.
function voucher_has_orders(PDO $pdo, int $uid): bool {
    $q=$pdo->prepare('SELECT 1 FROM orders WHERE user_id=? LIMIT 1');
    $q->execute([$uid]);
    return (bool)$q->fetchColumn();
}
function voucher_account_state(PDO $pdo, array $voucher, int $uid): array {
    $q=$pdo->prepare('SELECT COUNT(*) FROM voucher_usage WHERE voucher_id=? AND user_id=?');
    $q->execute([$voucher['id'],$uid]);
    $voucher['user_used']=(int)$q->fetchColumn();
    $voucher['user_remaining']=max(0,(int)$voucher['per_user_limit']-$voucher['user_used']);
    $voucher['remaining']=max(0,(int)$voucher['max_usage']-(int)$voucher['used_count']);
    return $voucher;
}
function voucher_assert_account(PDO $pdo, array $voucher, int $uid): array {
    $voucher=voucher_account_state($pdo,$voucher,$uid);
    if (!$voucher['user_remaining']) { throw new RuntimeException('Bạn đã dùng hết '.$voucher['per_user_limit'].' lượt của mã này.'); }
    if ($voucher['new_customer_only'] && voucher_has_orders($pdo,$uid)) { throw new RuntimeException('Mã chào mừng chỉ áp dụng cho đơn hàng đầu tiên của tài khoản.'); }
    return $voucher;
}
function voucher_available(PDO $pdo, ?int $uid): array {
    $q=$pdo->prepare('SELECT v.*,COALESCE(u.user_used,0) AS user_used,GREATEST(0,v.max_usage-v.used_count) AS remaining FROM vouchers v LEFT JOIN (SELECT voucher_id,COUNT(*) AS user_used FROM voucher_usage WHERE user_id=? GROUP BY voucher_id) u ON u.voucher_id=v.id WHERE v.is_active=1 AND (v.expires_at IS NULL OR v.expires_at>NOW()) AND v.used_count<v.max_usage ORDER BY v.discount_value DESC,v.id');
    $q->execute([$uid??0]);
    $hasOrders=$uid?voucher_has_orders($pdo,$uid):false;
    $result=[];
    foreach($q->fetchAll() as $v) {
        $v['user_remaining']=max(0,(int)$v['per_user_limit']-(int)$v['user_used']);
        if($uid && (!$v['user_remaining'] || ($v['new_customer_only'] && $hasOrders))) { continue; }
        $result[]=$v;
    }
    return $result;
}
