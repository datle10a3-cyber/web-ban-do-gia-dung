<?php
require_once __DIR__.'/../includes/init.php';
require_once __DIR__.'/../includes/checkout_service.php';
header('Content-Type: application/json; charset=utf-8');
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit(json_encode(['success'=>false]));}
$input=json_decode(file_get_contents('php://input'),true);
if(!is_array($input)||!is_string($input['csrf']??null)||!hash_equals($_SESSION['csrf'],$input['csrf'])){http_response_code(403);exit(json_encode(['success'=>false,'message'=>'Phiên đã hết hạn. Vui lòng tải lại trang.']));}
try{
    $items=checkout_items($pdo,[['product_id'=>$input['product_id']??0,'variant_id'=>$input['variant_id']??0,'qty'=>$input['qty']??1]]);
    $_SESSION['direct_buy']=reset($items);
    unset($_SESSION['checkout_request_key']);
    echo json_encode(['success'=>true]);
}catch(RuntimeException $e){http_response_code(422);echo json_encode(['success'=>false,'message'=>$e->getMessage()]);}
