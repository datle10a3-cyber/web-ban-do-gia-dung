<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/../includes/vnpay.php';
echo 'Merchant keys: '.(vnpay_ready()?'present; provider payment still needs verification':'not issued/configured yet').PHP_EOL;
echo 'Return URL: '.APP_URL.'/cart/vnpay_return.php'.PHP_EOL;
$runtime=getenv('LOCALAPPDATA')?getenv('LOCALAPPDATA').'/GiaDungSandbox/runtime.json':'';
if($runtime&&is_file($runtime)){
    $state=json_decode(ltrim(file_get_contents($runtime),"\xEF\xBB\xBF"),true);
    echo 'Public website: '.($state['website_url']??'unavailable').PHP_EOL;
    echo 'IPN URL: '.($state['ipn_url']??'unavailable').PHP_EOL;
}
echo 'Secret values are never displayed.'.PHP_EOL;
