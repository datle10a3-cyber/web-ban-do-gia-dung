<?php
require_once __DIR__.'/../includes/init.php';
header('Content-Type: application/json; charset=utf-8');
function review_response(array $data,int $code=200): void{http_response_code($code);exit(json_encode($data,JSON_UNESCAPED_UNICODE));}
if($_SERVER['REQUEST_METHOD']!=='POST'){review_response(['success'=>false],405);}
if(!isLoggedIn()){review_response(['success'=>false,'message'=>'Vui lòng đăng nhập.'],401);}
if(!is_string($_POST['csrf']??null)||!hash_equals($_SESSION['csrf'],$_POST['csrf'])){review_response(['success'=>false,'message'=>'Phiên đã hết hạn.'],403);}
$pid=(int)($_POST['product_id']??0);$rating=filter_var($_POST['rating']??null,FILTER_VALIDATE_INT);$text=$_POST['review']??$_POST['comment']??'';
if($pid<1||!$rating||$rating<1||$rating>5||!is_string($text)||mb_strlen(trim($text))<1||mb_strlen($text)>5000){review_response(['success'=>false,'message'=>'Nội dung hoặc số sao chưa hợp lệ.'],422);}
try{
    $pdo->beginTransaction();$pdo->prepare('SELECT id FROM users WHERE id=? FOR UPDATE')->execute([$_SESSION['user_id']]);
    $q=$pdo->prepare("SELECT o.id FROM orders o JOIN order_items oi ON oi.order_id=o.id WHERE o.user_id=? AND oi.product_id=? AND o.status IN ('delivered','completed') AND NOT EXISTS (SELECT 1 FROM product_reviews r WHERE r.user_id=o.user_id AND r.order_id=o.id AND r.product_id=oi.product_id) ORDER BY o.id DESC LIMIT 1");$q->execute([$_SESSION['user_id'],$pid]);$oid=$q->fetchColumn();
    if(!$oid){$pdo->rollBack();review_response(['success'=>false,'message'=>'Bạn cần có đơn đã giao và chưa đánh giá sản phẩm này.'],422);}
    $pdo->prepare('INSERT INTO product_reviews(user_id,product_id,order_id,rating,review) VALUES (?,?,?,?,?)')->execute([$_SESSION['user_id'],$pid,$oid,$rating,trim($text)]);$pdo->commit();
    review_response(['success'=>true,'message'=>'Đã gửi đánh giá.']);
}catch(Throwable $e){if($pdo->inTransaction()){$pdo->rollBack();}review_response(['success'=>false,'message'=>'Chưa thể gửi đánh giá.'],422);}
