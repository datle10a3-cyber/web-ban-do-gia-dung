<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/oauth.php';
$source = $argv[1] ?? '';
$data = is_file($source) ? json_decode(ltrim(file_get_contents($source), "\xEF\xBB\xBF"), true) : [];
$origin = $data['public_origin'] ?? '';
if (!preg_match('~^https://[a-z0-9-]+\.ngrok-free\.(?:app|dev)$~D', $origin)) { exit(1); }
$directory = oauth_runtime_dir();
if (!is_dir($directory)) { mkdir($directory, 0700, true); }
$target = $directory . '/runtime.json';
$temp = tempnam($directory, 'runtime-');
file_put_contents($temp, json_encode(['public_origin'=>$origin], JSON_THROW_ON_ERROR), LOCK_EX);
if (!rename($temp, $target)) { @unlink($temp); exit(1); }
echo 'Facebook HTTPS callback: ' . $origin . BASE_URL . '/auth/oauth_callback.php' . PHP_EOL;
