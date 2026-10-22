<?php
// PHP's built-in server router. Never serve the project as an unrestricted document root.
if (PHP_SAPI !== 'cli-server') { http_response_code(404); exit; }
$root=dirname(__DIR__);$prefix='/web-ban-do-gia-dung';
$path=rawurldecode(parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH)?:'/');
header('X-Robots-Tag: noindex, nofollow');header('X-Content-Type-Options: nosniff');
function preview_deny(int $code=404): never {http_response_code($code);header('Content-Type: text/plain; charset=utf-8');exit('Không tìm thấy tài nguyên.');}
if(!in_array($_SERVER['REQUEST_METHOD'],['GET','HEAD'],true)){preview_deny(405);}
if(str_contains($path,"\0")||str_contains($path,'\\')||preg_match('~(?:^|/)\.{1,2}(?:/|$)|%[a-f0-9]{2}~i',$path)){preview_deny();}
if($path==='/'){header('Location: '.$prefix.'/');exit;}
if(!str_starts_with($path,$prefix.'/')){preview_deny();}
$relative=substr($path,strlen($prefix)+1);
// Only these two authentication endpoints use a persistent, isolated HTTPS session.
if(in_array($relative,['auth/oauth_start.php','auth/oauth_callback.php'],true)){
    $runtimePath=getenv('LOCALAPPDATA').'/GiaDungSandbox/runtime.json';
    $runtime=is_file($runtimePath)?json_decode(ltrim(file_get_contents($runtimePath),"\xEF\xBB\xBF"),true):[];
    $expected=parse_url($runtime['public_origin']??'',PHP_URL_HOST);
    if(!$expected||!hash_equals($expected,strtolower($_SERVER['HTTP_HOST']??''))||($_SERVER['HTTP_X_FORWARDED_PROTO']??'')!=='https'){preview_deny(400);}
    $_SERVER['HTTPS']='on';$_SERVER['GD_HTTPS_OAUTH']=true;
    ini_set('session.use_strict_mode','1');session_name('GD_OAUTH_HTTPS');
    session_set_cookie_params(['path'=>$prefix.'/auth/','secure'=>true,'httponly'=>true,'samesite'=>'Lax']);
    require $root.'/'.$relative;exit;
}
// Preview traffic cannot reuse a logged-in localhost session or create a persistent account.
ini_set('session.use_cookies','0');session_id(bin2hex(random_bytes(20)));
register_shutdown_function(static function(){if(session_status()===PHP_SESSION_ACTIVE){session_destroy();}});
if($relative==='cart/vnpay_ipn.php'){
    // External end-to-end tests use their own disposable database, never live shop orders.
    $runtimePath=getenv('LOCALAPPDATA').'/GiaDungSandbox/runtime.json';
    $runtime=is_file($runtimePath)?json_decode(ltrim(file_get_contents($runtimePath),"\xEF\xBB\xBF"),true):[];
    $testDb=$runtime['test_db']??'';$testPrefix=$runtime['test_txn_prefix']??'';
    if(preg_match('/^giadung_e2e_[a-f0-9]{10}$/',$testDb)&&preg_match('/^SIT[a-f0-9]{10}$/',$testPrefix)&&is_string($_GET['vnp_TxnRef']??null)&&str_starts_with($_GET['vnp_TxnRef'],$testPrefix)){putenv('DB_NAME='.$testDb);}
    $_SERVER['HTTPS']='on';require $root.'/cart/vnpay_ipn.php';exit;
}
// Shopping and social sign-in remain on the original host, preserving OAuth callbacks.
$localPages=['cart/','cart/index.php','checkout.php','user/orders.php','user/wishlist.php','auth/login.php'];
if(in_array($relative,$localPages,true)){
    require_once $root.'/config/app.php';header('Location: '.APP_URL.'/'.($relative==='cart/'?'cart/index.php':$relative));exit;
}
if($relative==='auth/oauth.php'){
    require_once $root.'/config/app.php';$provider=$_GET['provider']??'';
    if(!in_array($provider,['google','facebook'],true)){preview_deny();}
    header('Location: '.APP_URL.'/auth/oauth.php?provider='.$provider);exit;
}
if(str_starts_with($relative,'assets/')&&preg_match('~\.(css|js|png|jpe?g|gif|webp|svg|ico|woff2?|ttf|eot)$~i',$relative,$match)){
    $file=realpath($root.'/'.$relative);$assetRoot=realpath($root.'/assets').DIRECTORY_SEPARATOR;
    if(!$file||!str_starts_with($file,$assetRoot)||!is_file($file)){preview_deny();}
    $types=['css'=>'text/css','js'=>'application/javascript','svg'=>'image/svg+xml','png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','gif'=>'image/gif','webp'=>'image/webp','ico'=>'image/x-icon','woff'=>'font/woff','woff2'=>'font/woff2','ttf'=>'font/ttf','eot'=>'application/vnd.ms-fontobject'];
    header('Content-Type: '.$types[strtolower($match[1])]);header('Cache-Control: public, max-age=1800');if($_SERVER['REQUEST_METHOD']!=='HEAD'){readfile($file);}exit;
}
$pages=[''=>'index.php','index.php'=>'index.php','products/'=>'products/index.php','products/index.php'=>'products/index.php','products/detail.php'=>'products/detail.php','products/category.php'=>'products/category.php','pages/about.php'=>'pages/about.php','pages/tintuc.php'=>'pages/tintuc.php','pages/tintuc-detail.php'=>'pages/tintuc-detail.php','pages/stores.php'=>'pages/stores.php','pages/support.php'=>'pages/support.php'];
if(preg_match('~^(?:pages/tintuc-detail|tin-tuc)/([a-z0-9-]+)$~',$relative,$match)){$_GET['slug']=$match[1];$relative='pages/tintuc-detail.php';}
if(!isset($pages[$relative])){preview_deny();}
putenv('CHAT_API_KEY=');putenv('CHAT_FALLBACK_API_KEY=');
$target=$root.'/'.$pages[$relative];$_SERVER['SCRIPT_NAME']=$prefix.'/'.$pages[$relative];$_SERVER['SCRIPT_FILENAME']=$target;$_SERVER['HTTPS']='on';
chdir(dirname($target));ob_start();require $target;$html=ob_get_clean();
$note='<div style="padding:8px 16px;background:#edf3e6;color:#34543b;text-align:center;font:12px/1.5 system-ui">Bản xem trước phục vụ kiểm thử VNPAY sandbox · Mua sắm và đăng nhập tiếp tục tại website localhost.</div>';
$html=preg_replace('~(<body\b[^>]*>)~i','$1'.$note,$html,1);
if($_SERVER['REQUEST_METHOD']!=='HEAD'){echo $html;}
