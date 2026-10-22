<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../config/oauth.php';
require_once __DIR__ . '/../includes/oauth_handoff.php';
oauth_no_store();
$name = $_SESSION['oauth_provider'] ?? '';
$state = $_SESSION['oauth2state'] ?? '';
$started = $_SESSION['oauth_started'] ?? 0;
unset($_SESSION['oauth_provider'], $_SESSION['oauth2state'], $_SESSION['oauth_started']);
if (!$state || !is_string($_GET['state'] ?? null) || !hash_equals($state, $_GET['state']) || time() - $started > 600) {
    oauth_error('Phiên đăng nhập đã hết hạn hoặc không hợp lệ. Vui lòng thử lại.');
}
if (isset($_GET['error']) || empty($_GET['code'])) { oauth_error('Bạn đã hủy đăng nhập. Vui lòng thử lại hoặc chọn cách đăng nhập khác.'); }
try {
    $provider = oauth_provider($name);
    $token = $provider->getAccessToken('authorization_code', ['code' => $_GET['code']]);
    $owner = $provider->getResourceOwner($token);
    $providerId = (string)$owner->getId();
    $displayName=mb_substr(trim(preg_replace('/[\x00-\x1F\x7F]/u','',(string)$owner->getName())),0,100);
    if($displayName===''){$displayName='Khách hàng '.ucfirst($name);}
    if ($providerId === '') { throw new RuntimeException('Không lấy được tài khoản.'); }
    $stmt = $pdo->prepare('SELECT u.* FROM users u JOIN oauth_accounts a ON a.user_id=u.id WHERE a.provider=? AND a.provider_id=?');
    $stmt->execute([$name, $providerId]);
    $user = $stmt->fetch();
    if (!$user) {
        $email = $owner->getEmail();
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = $name . '_' . hash('sha256', $providerId) . '@social.invalid';
        }
        $exists = $pdo->prepare('SELECT id FROM users WHERE email=?');
        $exists->execute([$email]);
        if ($exists->fetchColumn()) {
            oauth_error('Email này đã có tài khoản. Vui lòng đăng nhập bằng mật khẩu để bảo vệ tài khoản hiện có.');
        }
        $username = $name . '_' . bin2hex(random_bytes(8));
        $pdo->beginTransaction();
        $pdo->prepare("INSERT INTO users(username,email,password,role,display_name,password_enabled) VALUES (?,?,?,'customer',?,0)")
            ->execute([$username, $email, password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT),$displayName]);
        $uid = $pdo->lastInsertId();
        $pdo->prepare('INSERT INTO oauth_accounts(user_id,provider,provider_id) VALUES (?,?,?)')->execute([$uid, $name, $providerId]);
        $pdo->commit();
        $user = ['id' => $uid, 'username' => $username, 'role' => 'customer'];
    }
    $pdo->prepare('UPDATE users SET display_name=? WHERE id=? AND display_name_custom=0')->execute([$displayName,$user['id']]);
    $q=$pdo->prepare('SELECT * FROM users WHERE id=?');$q->execute([$user['id']]);$user=$q->fetch();
    if (!empty($_SERVER['GD_HTTPS_OAUTH']) && $name === 'facebook') {
        $id = $_SESSION['facebook_flow_id'] ?? ''; $ticket = bin2hex(random_bytes(32));
        oauth_flow($id, function (&$data) use ($ticket, $user) {
            if (!oauth_flow_valid($data, 'started')) { throw new RuntimeException('Expired login flow'); }
            $data['status']='ready'; $data['ticket']=hash('sha256', $ticket); $data['user_id']=$user['id']; $data['expires']=time()+60;
        });
        $_SESSION = []; session_destroy();
        header('Location: ' . oauth_local_url() . '/auth/oauth_finish.php?flow=' . $id . '&ticket=' . $ticket); exit;
    }
    account_sign_in($user);
    $redirect = $_SESSION['redirect_after_login'] ?? BASE_URL . '/index.php';
    unset($_SESSION['redirect_after_login']);
    if (!str_starts_with($redirect, BASE_URL . '/') || str_contains($redirect, "\r") || str_contains($redirect, "\n")) { $redirect = BASE_URL . '/index.php'; }
    header('Location: ' . $redirect);
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    // Provider exception messages may contain URLs or tokens; never log those values.
    error_log('OAuth login failed for ' . $name . ': ' . get_class($e));
    oauth_error('Không thể đăng nhập qua mạng xã hội lúc này. Vui lòng thử lại hoặc đăng nhập bằng email.');
}
