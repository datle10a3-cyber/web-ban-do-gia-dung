<?php

require_once __DIR__ . '/../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['user_id'])) {
    exit;
}

if (empty($_POST['csrf']) || $_POST['csrf'] !== ($_SESSION['csrf'] ?? '')) {
    exit;
}

$type = $_POST['type'] ?? '';
$desc = $_POST['desc'] ?? '';

if ($type && $desc) {
    logUserActivity($_SESSION['user_id'], $type, $desc);
}