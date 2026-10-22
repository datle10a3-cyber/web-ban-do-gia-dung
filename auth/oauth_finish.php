<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/oauth_handoff.php';
oauth_no_store();
try {
    if (!oauth_is_local_origin()) { throw new RuntimeException('Invalid origin'); }
    $flow = $_SESSION['facebook_flow'] ?? [];
    $id = (string)($_GET['flow'] ?? ''); $ticket = (string)($_GET['ticket'] ?? '');
    if (!$id || !hash_equals($flow['id'] ?? '', $id) || !preg_match('/^[a-f0-9]{64}$/D', $ticket)) { throw new RuntimeException('Invalid browser binding'); }
    $uid = oauth_flow($id, function (&$data) use ($flow, $ticket) {
        if (!oauth_flow_valid($data, 'ready') || !hash_equals($data['binding'], hash('sha256', $flow['nonce'] ?? '')) || !hash_equals($data['ticket'], hash('sha256', $ticket))) { throw new RuntimeException('Invalid login grant'); }
        $data['status'] = 'consumed'; unset($data['ticket']); return $data['user_id'];
    });
    unset($_SESSION['facebook_flow']);
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id=?'); $stmt->execute([$uid]); $user = $stmt->fetch();
    if (!$user) { throw new RuntimeException('Account unavailable'); }
    account_sign_in($user);
    $redirect = $_SESSION['redirect_after_login'] ?? BASE_URL . '/index.php'; unset($_SESSION['redirect_after_login']);
    if (!str_starts_with($redirect, BASE_URL . '/') || preg_match('/[\r\n]/', $redirect)) { $redirect = BASE_URL . '/index.php'; }
    header('Location: ' . $redirect); exit;
} catch (Throwable $e) { oauth_error('Phiên đăng nhập Facebook đã hết hạn hoặc không đúng trình duyệt. Vui lòng thử lại.'); }
