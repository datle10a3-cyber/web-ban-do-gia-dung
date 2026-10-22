<?php
require_once __DIR__.'/../includes/init.php';
require_once __DIR__.'/../includes/checkout_service.php';
require_once __DIR__.'/../includes/cart_service.php';
requireLogin();
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit('Chỉ chấp nhận POST.');}
if(!verifyCsrfToken($_POST['csrf']??null)){http_response_code(403);exit('Phiên đã hết hạn.');}
$id=(int)($_POST['id']??0);$q=$pdo->prepare('SELECT oi.product_id,oi.variant_id,oi.quantity AS qty FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE o.id=? AND o.user_id=?');$q->execute([$id,$_SESSION['user_id']]);$rows=$q->fetchAll();
if(!$rows){http_response_code(404);exit('Không tìm thấy đơn hàng của bạn.');}
try{
    $items=checkout_items($pdo,$rows);$cart=$_SESSION['cart']??[];
    foreach($items as $item){$key=$item['product_id'].'_'.($item['variant_id']??0);$item['qty']+=(int)($cart[$key]['qty']??0);$cart[$key]=$item;}
    $cart=checkout_items($pdo,$cart);cart_sync($pdo,(int)$_SESSION['user_id'],$cart);$_SESSION['cart']=$cart;$_SESSION['selected_cart_items']=array_keys($cart);
    unset($_SESSION['checkout_mode'],$_SESSION['checkout_cart_override'],$_SESSION['direct_buy']);
    header('Location: '.BASE_URL.'/cart/',true,303);exit;
}catch(RuntimeException $e){http_response_code(422);exit(sanitize($e instanceof PDOException?'Chưa thể mua lại đơn hàng.':$e->getMessage()));}
