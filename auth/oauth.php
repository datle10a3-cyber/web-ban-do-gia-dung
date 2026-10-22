<?php
require_once __DIR__ . '/../config/oauth.php';
require_once __DIR__ . '/../includes/oauth_handoff.php';
oauth_no_store();
$name = $_GET['provider'] ?? '';
if (!in_array($name, ['facebook', 'google'], true)) { oauth_error('Nhà cung cấp đăng nhập không hợp lệ.'); }
if (!oauth_is_local_origin()) {
    header('Location: ' . oauth_local_url() . '/auth/oauth.php?provider=' . $name); exit;
}
try {
    if ($name === 'facebook') {
        if (!oauth_configured($name)) { throw new RuntimeException('Facebook chưa được cấu hình.'); }
        $https = oauth_https_url();
        if (!$https) { throw new RuntimeException('Kết nối HTTPS Facebook chưa sẵn sàng.'); }
        $id = bin2hex(random_bytes(32)); $nonce = bin2hex(random_bytes(32));
        oauth_flow($id, function (&$data) use ($nonce) {
            $data = ['status'=>'pending','binding'=>hash('sha256', $nonce),'expires'=>time()+600];
        }, true);
        $_SESSION['facebook_flow'] = ['id'=>$id, 'nonce'=>$nonce];
        header('Location: ' . $https . '/auth/oauth_start.php?flow=' . $id); exit;
    }
    $provider = oauth_provider($name);
    $url = $provider->getAuthorizationUrl(['scope' => $name === 'facebook' ? ['email', 'public_profile'] : ['openid', 'email', 'profile']]);
    $_SESSION['oauth_provider'] = $name;
    $_SESSION['oauth2state'] = $provider->getState();
    $_SESSION['oauth_started'] = time();
    header('Location: ' . $url);
    exit;
} catch (Throwable $e) { oauth_error($e->getMessage()); }
