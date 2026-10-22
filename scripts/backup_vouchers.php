<?php
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require_once __DIR__.'/../config/config.php';
$dir=dirname(__DIR__,3).'/var/gia-dung-audit';
if (!is_dir($dir) && !mkdir($dir,0700,true)) { throw new RuntimeException('Cannot create private backup directory.'); }
$data=['vouchers'=>$pdo->query('SELECT * FROM vouchers ORDER BY id')->fetchAll(PDO::FETCH_ASSOC),'voucher_usage'=>$pdo->query('SELECT * FROM voucher_usage ORDER BY id')->fetchAll(PDO::FETCH_ASSOC)];
$path=$dir.'/voucher-policy-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.json';
if(file_put_contents($path,json_encode($data,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT))===false){throw new RuntimeException('Backup failed.');}
echo 'Private voucher backup saved. Records: '.count($data['vouchers']).' vouchers, '.count($data['voucher_usage'])." uses.\n";
