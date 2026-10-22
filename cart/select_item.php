<?php
require_once __DIR__ . '/../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit('invalid method');
}

if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    exit('csrf invalid');
}

$key = $_POST['key'] ?? '';
$selected = !empty($_POST['selected']);

if (!isset($_SESSION['cart'][$key])) {
    exit('invalid key');
}

if ($selected) {
    $_SESSION['selected_cart_items'] = array_unique(array_merge(
        ($_SESSION['selected_cart_items'] ?? []), [$key]
    ));
} else {
    $_SESSION['selected_cart_items'] = array_values(array_diff(
        ($_SESSION['selected_cart_items'] ?? []), [$key]
    ));
}

echo 'ok';