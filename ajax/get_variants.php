<?php
require_once __DIR__ . '/../includes/init.php';

$id = (int)($_GET['id'] ?? 0);
if($id <= 0) exit(json_encode(['success'=>false]));

$stmt = $pdo->prepare("
    SELECT p.name, p.image, p.price, p.discount,p.stock,
           (p.price * (100 - p.discount)/100) AS final_price
    FROM products p WHERE p.id = ?
");
$stmt->execute([$id]);
$p = $stmt->fetch();
if(!$p){http_response_code(404);exit(json_encode(['success'=>false,'message'=>'Sản phẩm không tồn tại.']));}

$variants = $pdo->prepare("
    SELECT id, color, capacity, price_add, image, stock 
    FROM product_variants WHERE product_id = ? AND stock > 0
    ORDER BY color, capacity
");
$variants->execute([$id]);
$vars = $variants->fetchAll(PDO::FETCH_ASSOC);

$q=$pdo->prepare('SELECT COUNT(*) FROM product_variants WHERE product_id=?');$q->execute([$id]);
if(empty($vars)&&$q->fetchColumn()){http_response_code(409);exit(json_encode(['success'=>false,'message'=>'Phân loại sản phẩm đã hết hàng.'],JSON_UNESCAPED_UNICODE));}
if(empty($vars)){
    $vars = [[
        'id' => 0,
        'color' => 'Mặc định',
        'capacity' => '',
        'price_add' => 0,
        'image' => $p['image'],
        'stock' => (int)$p['stock']
    ]];
}

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'name' => $p['name'],
    'image' => $p['image'],
    'final_price' => $p['final_price'],
    'variants' => $vars
]);