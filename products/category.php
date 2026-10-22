<?php
require_once __DIR__.'/../includes/init.php';
$filters=[];$id=(int)($_GET['cat']??0);$brand=(int)($_GET['brand']??0);
if($id){$q=$pdo->prepare('SELECT name FROM categories WHERE id=?');$q->execute([$id]);$filters['cat']=$q->fetchColumn()?:'';}
if($brand){$q=$pdo->prepare('SELECT name FROM brands WHERE id=?');$q->execute([$brand]);$filters['brand']=$q->fetchColumn()?:'';}
foreach(['search','sort'] as $key){if(isset($_GET[$key])){$filters[$key]=$_GET[$key];}}
foreach(['min_price','max_price'] as $key){if(isset($_GET[$key])&&is_numeric($_GET[$key])){$filters[$key]=(float)$_GET[$key]/1000000;}}
header('Location: '.BASE_URL.'/products/?'.http_build_query($filters));exit;
