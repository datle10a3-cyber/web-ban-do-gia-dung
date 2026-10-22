<?php
require_once __DIR__.'/../includes/init.php';
require_once __DIR__.'/../includes/checkout_service.php';
require_once __DIR__.'/../includes/cart_service.php';
header('Content-Type: application/json; charset=utf-8');
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit(json_encode(['success'=>false,'message'=>'Chỉ chấp nhận POST.']));}
if(!verifyCsrfToken($_POST['csrf_token']??null)){http_response_code(403);exit(json_encode(['success'=>false,'message'=>'Phiên đã hết hạn.'],JSON_UNESCAPED_UNICODE));}
$key=$_POST['key']??null;$change=filter_var($_POST['change']??null,FILTER_VALIDATE_INT);
if(!is_string($key)||!isset($_SESSION['cart'][$key])||!in_array($change,[-1,1],true)){http_response_code(422);exit(json_encode(['success'=>false]));}
try{$item=$_SESSION['cart'][$key];$item['qty']=max(1,(int)$item['qty']+$change);$items=checkout_items($pdo,[$key=>$item]);$cart=$_SESSION['cart'];$cart[$key]=$items[$key];if(isLoggedIn()){cart_sync($pdo,(int)$_SESSION['user_id'],$cart);}$_SESSION['cart']=$cart;echo json_encode(['success'=>true]);}
catch(RuntimeException $e){http_response_code(422);echo json_encode(['success'=>false,'message'=>$e instanceof PDOException?'Chưa thể cập nhật giỏ hàng.':$e->getMessage()],JSON_UNESCAPED_UNICODE);}
