<?php
require_once __DIR__.'/../includes/init.php';require_once __DIR__.'/../includes/checkout_service.php';
header('Content-Type: application/json; charset=utf-8');
function voucher_response(array $data,int $code=200): void{http_response_code($code);exit(json_encode($data,JSON_UNESCAPED_UNICODE));}
if($_SERVER['REQUEST_METHOD']!=='POST'){voucher_response(['success'=>false],405);}
if(!isLoggedIn()){voucher_response(['success'=>false,'message'=>'Vui lòng đăng nhập.'],401);}
if(!is_string($_POST['csrf']??null)||!hash_equals($_SESSION['csrf'],$_POST['csrf'])){voucher_response(['success'=>false,'message'=>'Phiên đã hết hạn.'],403);}
try{
    $code=is_string($_POST['code']??null)?strtoupper(trim($_POST['code'])):'';
    $cart=checkout_items($pdo,$_SESSION['checkout_cart_override']??$_SESSION['cart']??[]);$subtotal=checkout_subtotal($cart);
    $q=$pdo->prepare('SELECT * FROM vouchers WHERE code=? AND is_active=1 AND (expires_at IS NULL OR expires_at>NOW()) AND used_count<max_usage');$q->execute([$code]);$v=$q->fetch();
    if(!$v){throw new RuntimeException('Mã không hợp lệ hoặc đã hết lượt.');}
    $v=voucher_assert_account($pdo,$v,(int)$_SESSION['user_id']);
    voucher_response(['success'=>true,'discount'=>checkout_discount($v,$subtotal),'remaining'=>$v['remaining'],'user_remaining'=>$v['user_remaining'],'per_user_limit'=>(int)$v['per_user_limit']]);
}catch(RuntimeException $e){voucher_response(['success'=>false,'message'=>$e instanceof PDOException?'Chưa thể kiểm tra mã.':$e->getMessage()],422);}
