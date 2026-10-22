<?php
// Copy to local.php. Keep real secrets only in local.php or environment variables.
return [
    'DB_HOST' => '127.0.0.1', 'DB_PORT' => 3306,
    'DB_NAME' => 'web_gia_dung', 'DB_USER' => 'root', 'DB_PASSWORD' => '',
    'BASE_URL' => '/web-ban-do-gia-dung',
    'APP_URL' => 'http://localhost/web-ban-do-gia-dung',
    'OAUTH_LOCAL_URL' => 'http://localhost/web-ban-do-gia-dung',
    'OAUTH_RUNTIME_DIR' => str_replace('\\', '/', dirname(__DIR__, 3)) . '/var/gia-dung-oauth', // Private; outside htdocs
    'FB_APP_ID' => '', 'FB_APP_SECRET' => '', 'FB_GRAPH_VERSION' => 'v25.0',
    'GOOGLE_CLIENT_ID' => '', 'GOOGLE_CLIENT_SECRET' => '',
    'VNPAY_TMN_CODE' => '', 'VNPAY_HASH_SECRET' => '',
    'VNPAY_URL' => 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html',
    'BANK_BIN' => '', 'BANK_ACCOUNT' => '', 'BANK_ACCOUNT_NAME' => '',
    'CHAT_API_KEY' => '',
    'CHAT_API_URL' => '', // HTTPS endpoint compatible with chat/completions
    'CHAT_MODEL' => '',
    'CHAT_FALLBACK_API_KEY' => '',
    'CHAT_FALLBACK_API_URL' => '',
    'CHAT_FALLBACK_MODEL' => '',
];
