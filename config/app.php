<?php
// Private overrides live in config/local.php (never served by Apache).
$appSettings = is_file(__DIR__ . '/local.php') ? require __DIR__ . '/local.php' : [];
function app_env(string $key, $default = '') {
    global $appSettings;
    $value = getenv($key);
    return $value !== false ? $value : ($appSettings[$key] ?? $default);
}
define('BASE_URL', rtrim(app_env('BASE_URL', '/web-ban-do-gia-dung'), '/'));
define('APP_URL', rtrim(app_env('APP_URL', 'http://localhost' . BASE_URL), '/'));
define('SITE_NAME', 'Gia Dụng Shop');
define('UPLOAD_PATH', BASE_URL . '/assets/images/products');
$base = BASE_URL;
date_default_timezone_set('Asia/Ho_Chi_Minh');
if(PHP_SAPI!=='cli'){
    // Query parameters are scalar; only the two structured editor forms accept POST arrays.
    $arrayFields=[];$script=str_replace('\\','/',$_SERVER['SCRIPT_NAME']??'');
    if(str_ends_with($script,'/admin/manage_products.php')){$arrayFields=['v_id','v_sku','v_color','v_capacity','v_price_add','v_stock'];}
    elseif(str_ends_with($script,'/user/review.php')){$arrayFields=['rating','comment','anonymous','media'];}
    $invalid=false;
    foreach($_GET as $value){if(!is_string($value)){$invalid=true;break;}}
    foreach($_POST as $key=>$value){if(!is_string($value)&&!(is_array($value)&&in_array($key,$arrayFields,true))){$invalid=true;break;}}
    if($invalid){http_response_code(400);header('Content-Type: application/json; charset=utf-8');exit(json_encode(['success'=>false,'message'=>'Dữ liệu yêu cầu không hợp lệ.'],JSON_UNESCAPED_UNICODE));}
    ini_set('display_errors','0');
}
if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode','1');
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
    session_start();
}
