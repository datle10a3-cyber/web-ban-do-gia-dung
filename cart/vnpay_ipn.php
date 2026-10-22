<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/vnpay.php';
header('Content-Type: application/json; charset=utf-8');
if($_SERVER['REQUEST_METHOD']!=='GET'){http_response_code(405);header('Allow: GET');exit(json_encode(['RspCode'=>'99','Message'=>'GET required']));}
echo json_encode(vnpay_settle($pdo, $_GET));
