<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$path=getenv('LOCALAPPDATA').'/GiaDungSandbox/e2e-payment.json';$state=json_decode(file_get_contents($path),true);
if(!preg_match('/^giadung_e2e_[a-f0-9]{10}$/',$state['database']??'')||!preg_match('/^SIT[a-f0-9]{10}$/',$state['prefix']??'')){throw new RuntimeException('Invalid isolated fixture');}
putenv('DB_NAME='.$state['database']);putenv('VNPAY_TXN_PREFIX='.$state['prefix']);putenv('APP_URL='.$state['local_url']);
require_once __DIR__.'/../includes/init.php';require_once __DIR__.'/../includes/vnpay.php';
$q=$pdo->prepare("SELECT * FROM orders WHERE id=? AND fullname='VNPAY SANDBOX TEST' AND payment_method='vnpay' AND status='pending' AND payment_status<>'paid'");$q->execute([$state['order_id']]);$order=$q->fetch();
if(!$order){throw new RuntimeException('Fixture is closed or already paid');}
$state['payment_url']=vnpay_payment_url($pdo,$order);file_put_contents($path,json_encode($state,JSON_PRETTY_PRINT));
echo 'Fresh sandbox payment reference generated privately.'.PHP_EOL;
