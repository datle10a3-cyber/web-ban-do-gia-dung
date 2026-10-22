<?php
// Short-lived, browser-bound grants, outside the web root. No provider tokens are persisted.
require_once __DIR__ . '/../config/oauth.php';
function oauth_flow(string $id, callable $change, bool $create = false) {
    if (!preg_match('/^[a-f0-9]{64}$/D', $id)) { throw new RuntimeException('Invalid login flow'); }
    $dir = oauth_runtime_dir() . '/oauth-flows';
    if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) { throw new RuntimeException('Login storage unavailable'); }
    $file = @fopen($dir . '/' . $id . '.json', $create ? 'x+' : 'r+');
    if (!$file || !flock($file, LOCK_EX)) { throw new RuntimeException('Login storage unavailable'); }
    try {
        $raw = stream_get_contents($file);
        $data = $raw === '' ? null : json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
        $result = $change($data);
        rewind($file); ftruncate($file, 0); fwrite($file, json_encode($data, JSON_THROW_ON_ERROR)); fflush($file);
        return $result;
    } finally { flock($file, LOCK_UN); fclose($file); }
}
function oauth_flow_valid(?array $data, string $status): bool {
    return $data && ($data['status'] ?? '') === $status && ($data['expires'] ?? 0) >= time();
}
function oauth_no_store(): void {
    header('Cache-Control: no-store'); header('Referrer-Policy: no-referrer');
    header('X-Frame-Options: DENY'); header("Content-Security-Policy: frame-ancestors 'none'; base-uri 'none'");
}
