<?php
session_start();
$cart = $_SESSION['cart'] ?? [];
echo array_sum($cart); 
?>