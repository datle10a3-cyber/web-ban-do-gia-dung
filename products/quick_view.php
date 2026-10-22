<?php require_once __DIR__.'/../config/app.php'; header('Location: '.BASE_URL.'/products/detail.php?id='.(int)($_GET['id']??0)); exit;
