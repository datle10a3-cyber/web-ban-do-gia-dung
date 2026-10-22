<?php require_once __DIR__.'/../config/app.php';header('Location: '.BASE_URL.'/user/orders.php?id='.(int)($_GET['order']??$_GET['order_id']??0));exit;
