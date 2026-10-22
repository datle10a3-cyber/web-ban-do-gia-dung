<?php
require_once __DIR__ . '/app.php';
require_once __DIR__ . '/../vendor/autoload.php';
function oauth_configured(string $name): bool {
    if (!in_array($name, ['google', 'facebook'], true)) { return false; }
    $prefix = $name === 'facebook' ? 'FB_APP_' : 'GOOGLE_CLIENT_';
    $id = (string)app_env($prefix . 'ID');
    $secret = (string)app_env($prefix . 'SECRET');
    return $id !== '' && $secret !== '' && stripos($id, 'xxxx') === false && stripos($secret, 'xxxx') === false
        && $id !== '123456789012345' && $secret !== 'abcdefghijklmnopqrstuvwxyz1234567890';
}
function oauth_provider(string $name) {
    $prefix = $name === 'facebook' ? 'FB_APP_' : 'GOOGLE_CLIENT_';
    $id = app_env($prefix . 'ID');
    $secret = app_env($prefix . 'SECRET');
    if (!oauth_configured($name)) {
        throw new RuntimeException('Đăng nhập ' . ucfirst($name) . ' chưa được cấu hình. Bạn có thể đăng nhập bằng email và mật khẩu.');
    }
    $options = ['clientId' => $id, 'clientSecret' => $secret, 'redirectUri' => oauth_redirect_uri($name)];
    if ($name === 'facebook') {
        $options['graphApiVersion'] = app_env('FB_GRAPH_VERSION', 'v25.0');
        $options['fields'] = ['id', 'name', 'picture.type(large){url,is_silhouette}'];
        return new League\OAuth2\Client\Provider\Facebook($options);
    }
    if ($name === 'google') { return new League\OAuth2\Client\Provider\Google($options); }
    throw new RuntimeException('Nhà cung cấp không hợp lệ.');
}
function oauth_local_url(): string {
    return rtrim((string)app_env('OAUTH_LOCAL_URL', APP_URL), '/');
}
function oauth_https_url(): string {
    $runtime = oauth_runtime_dir() . '/runtime.json';
    $data = is_file($runtime) ? json_decode(ltrim(file_get_contents($runtime), "\xEF\xBB\xBF"), true) : [];
    $origin = $data['public_origin'] ?? '';
    return preg_match('~^https://[a-z0-9-]+\.ngrok-free\.(?:app|dev)$~D', $origin) ? $origin . BASE_URL : '';
}
function oauth_runtime_dir(): string {
    return rtrim((string)app_env('OAUTH_RUNTIME_DIR', getenv('LOCALAPPDATA') . '/GiaDungSandbox'), '/\\');
}
function oauth_redirect_uri(string $name): string {
    if ($name === 'facebook') {
        $https = oauth_https_url();
        if (!$https) { throw new RuntimeException('Kết nối HTTPS Facebook chưa sẵn sàng.'); }
        return $https . '/auth/oauth_callback.php';
    }
    return oauth_local_url() . '/auth/oauth_callback.php';
}
function oauth_is_local_origin(): bool {
    $parts = parse_url(oauth_local_url());
    $expected = $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    return hash_equals($expected, strtolower($_SERVER['HTTP_HOST'] ?? '')) && $secure === (($parts['scheme'] ?? '') === 'https');
}
function oauth_error(string $message): void {
    $_SESSION['oauth_error'] = $message;
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}
