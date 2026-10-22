<?php

require_once __DIR__ . '/../config/config.php';    
require_once __DIR__ . '/functions.php';           

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
account_user();
?>
