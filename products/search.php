<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/../includes/init.php';

$term = is_string($_GET['term']??null)?trim($_GET['term']):'';
if (strlen($term) < 2) {
    http_response_code(400);
    exit(json_encode(['error' => 'Term phải ít nhất 2 ký tự'], JSON_UNESCAPED_UNICODE));
}

try {
    $sql = "SELECT id, name, description, price, discount, image FROM products 
            WHERE (name LIKE ? OR description LIKE ?) AND stock > 0
            ORDER BY created_at DESC LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(["%$term%", "%$term%"]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $ui_results = array_map(function($row) {
        $final_price = $row['price'] - ($row['price'] * ($row['discount'] ?? 0) / 100);
        return [
            'label' => htmlspecialchars($row['name']) . ' - ' . number_format($final_price) . ' VNĐ' . 
                      ($row['discount'] > 0 ? ' (-' . $row['discount'] . '%)' : ''),
            'value' => htmlspecialchars($row['name']),
            'id' => (int)$row['id'],
            'image' => BASE_URL . '/assets/images/products/' . htmlspecialchars($row['image'] ?? 'default.jpg'),  
            'description' => htmlspecialchars(substr($row['description'] ?? '', 0, 100)) . '...',
            'final_price' => number_format($final_price),
            'discount' => (int)($row['discount'] ?? 0)
        ];
    }, $results);

    echo json_encode([
        'results' => $ui_results,
        'count' => count($ui_results)
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    error_log('Search error: ' . $e->getMessage());
    exit(json_encode(['error' => 'Lỗi hệ thống, vui lòng thử lại'], JSON_UNESCAPED_UNICODE));
}
?>
