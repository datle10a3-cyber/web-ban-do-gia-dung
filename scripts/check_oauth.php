<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/../config/oauth.php';
foreach(['google','facebook'] as $name){try{echo ucfirst($name).' callback: '.oauth_redirect_uri($name).PHP_EOL;}catch(Throwable $e){echo ucfirst($name).' HTTPS endpoint unavailable'.PHP_EOL;}}
foreach(['google','facebook'] as $provider){echo ucfirst($provider).': '.(oauth_configured($provider)?'ID/secret present; real sign-in still needs verification':'Missing ID/secret or placeholder values').PHP_EOL;}
echo 'No secret values displayed. No external requests sent.'.PHP_EOL;
