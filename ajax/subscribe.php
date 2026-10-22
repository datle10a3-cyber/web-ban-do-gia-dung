<?php
require_once __DIR__.'/../includes/init.php';
header('Content-Type: application/json; charset=utf-8');
function subscribe_response(bool $ok,string $message,int $status=200):void{http_response_code($status);echo json_encode(['success'=>$ok,'message'=>$message],JSON_UNESCAPED_UNICODE);exit;}
if($_SERVER['REQUEST_METHOD']!=='POST'){subscribe_response(false,'Chỉ chấp nhận POST.',405);}
if(!is_string($_POST['csrf']??null)||!hash_equals($_SESSION['csrf'],$_POST['csrf'])){subscribe_response(false,'Phiên hết hạn. Vui lòng tải lại trang.',403);}
$email=is_string($_POST['email']??null)?strtolower(trim($_POST['email'])):'';
if(strlen($email)>100||!filter_var($email,FILTER_VALIDATE_EMAIL)){subscribe_response(false,'Vui lòng nhập email hợp lệ.',422);}
if(($_SESSION['newsletter_last']??0)>time()-10){subscribe_response(false,'Vui lòng chờ một chút rồi thử lại.',429);}
try{
 $q=$pdo->prepare('INSERT INTO newsletter_subscribers(email) VALUES (?) ON DUPLICATE KEY UPDATE email=VALUES(email)');$q->execute([$email]);$_SESSION['newsletter_last']=time();
 subscribe_response(true,'Đã ghi nhận email đăng ký nhận ưu đãi.');
}catch(PDOException $e){error_log('Newsletter save failed: '.$e->getCode());subscribe_response(false,'Chưa thể đăng ký. Vui lòng thử lại sau.',503);}
