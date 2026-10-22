<?php
require_once __DIR__ . '/../includes/oauth_handoff.php';
oauth_no_store();
try {
    if (empty($_SERVER['GD_HTTPS_OAUTH'])) { throw new RuntimeException('HTTPS gateway required'); }
    $id = (string)($_GET['flow'] ?? '');
    oauth_flow($id, function (&$data) {
        if (!oauth_flow_valid($data, 'pending')) { throw new RuntimeException('Expired login flow'); }
        $data['status'] = 'started';
    });
    session_regenerate_id(true);
    $provider = oauth_provider('facebook');
    $url = $provider->getAuthorizationUrl(['scope'=>['public_profile']]);
    $_SESSION['facebook_flow_id'] = $id;
    $_SESSION['oauth_provider'] = 'facebook';
    $_SESSION['oauth2state'] = $provider->getState();
    $_SESSION['oauth_started'] = time();
    header('Location: ' . $url); exit;
} catch (Throwable $e) {
    http_response_code(400); exit('Phiên đăng nhập không hợp lệ. Vui lòng quay lại website localhost và thử lại.');
}
