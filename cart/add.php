<?php
require_once __DIR__.'/../includes/init.php';require_once __DIR__.'/../includes/cart_service.php';
function cart_reply(array $data,int $status=200): void{http_response_code($status);header('Content-Type: application/json; charset=utf-8');exit(json_encode($data,JSON_UNESCAPED_UNICODE));}
if($_SERVER['REQUEST_METHOD']!=='POST'){cart_reply(['success'=>false],405);}
if(!is_string($_POST['csrf']??null)||!hash_equals($_SESSION['csrf'],$_POST['csrf'])){cart_reply(['success'=>false,'message'=>'Phiên đã hết hạn.'],403);}
try{
    $pid=filter_var($_POST['product_id']??null,FILTER_VALIDATE_INT);$vid=filter_var($_POST['variant_id']??0,FILTER_VALIDATE_INT);$qty=filter_var($_POST['quantity']??1,FILTER_VALIDATE_INT);
    if(!$pid||$vid===false||$vid<0||!$qty||$qty<1||$qty>999){throw new RuntimeException('Sản phẩm hoặc số lượng không hợp lệ.');}
    $key=$pid.'_'.$vid;$cart=$_SESSION['cart']??[];
    $total=$qty+(!empty($_POST['buy_now'])?0:(int)($cart[$key]['qty']??0));
    $checked=checkout_items($pdo,[$key=>['product_id'=>$pid,'variant_id'=>$vid,'qty'=>$total]]);
    if(!empty($_POST['buy_now'])){$_SESSION['direct_buy']=$checked[$key];unset($_SESSION['checkout_request_key']);$redirect=BASE_URL.'/checkout.php';}
    else{$cart[$key]=$checked[$key];if(isLoggedIn()){cart_sync($pdo,(int)$_SESSION['user_id'],$cart);}$_SESSION['cart']=$cart;$_SESSION['selected_cart_items']=array_values(array_unique(array_merge($_SESSION['selected_cart_items']??[],[$key])));$redirect=BASE_URL.'/products/detail.php?id='.$pid;}
    if(!empty($_POST['ajax'])){cart_reply(['success'=>true,'message'=>'Đã thêm sản phẩm.','cart_count'=>count($_SESSION['cart']??[]),'total_qty'=>array_sum(array_column($_SESSION['cart']??[],'qty')),'redirect_url'=>$redirect]);}
    header('Location: '.$redirect,true,303);exit;
}catch(RuntimeException $e){cart_reply(['success'=>false,'message'=>$e instanceof PDOException?'Chưa thể cập nhật giỏ hàng.':$e->getMessage()],422);}
