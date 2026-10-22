<?php
require_once __DIR__.'/voucher_service.php';
function checkout_items(PDO $pdo, array $cart, bool $lock = false): array {
    if (!$cart || count($cart) > 100) { throw new RuntimeException('Giỏ hàng trống hoặc vượt quá số sản phẩm cho phép.'); }
    $items = [];
    foreach ($cart as $key => $item) {
        $pid = filter_var($item['product_id'] ?? null, FILTER_VALIDATE_INT);
        $qty = filter_var($item['qty'] ?? null, FILTER_VALIDATE_INT);
        $vid = (int)($item['variant_id'] ?? 0);
        if (!$pid || !$qty || $qty < 1 || $qty > 999 || $vid < 0) { throw new RuntimeException('Số lượng sản phẩm không hợp lệ.'); }
        $stmt = $pdo->prepare('SELECT * FROM products WHERE id=?' . ($lock ? ' FOR UPDATE' : ''));
        $stmt->execute([$pid]);
        $p = $stmt->fetch();
        if (!$p || $p['stock'] < $qty) { throw new RuntimeException('Sản phẩm không tồn tại hoặc không đủ tồn kho.'); }
        $v = null;
        if ($vid) {
            $stmt = $pdo->prepare('SELECT * FROM product_variants WHERE id=? AND product_id=?' . ($lock ? ' FOR UPDATE' : ''));
            $stmt->execute([$vid, $pid]);
            $v = $stmt->fetch();
            if (!$v || $v['stock'] < $qty) { throw new RuntimeException('Phân loại sản phẩm đã hết hàng hoặc không hợp lệ.'); }
        } else {
            $stmt=$pdo->prepare('SELECT id FROM product_variants WHERE product_id=? LIMIT 1');$stmt->execute([$pid]);
            if($stmt->fetchColumn()){throw new RuntimeException('Vui lòng chọn phân loại sản phẩm.');}
        }
        $items[$key] = ['product_id' => $pid, 'variant_id' => $vid ?: null, 'qty' => $qty,
            'name' => $p['name'], 'variant_name' => $v ? trim($v['color'] . ' ' . $v['capacity']) : '',
            'image' => $v['image'] ?? $p['image'],
            'price' => (int)round($p['price'] * (100 - $p['discount']) / 100 + ($v['price_add'] ?? 0))];
    }
    return $items;
}
function checkout_subtotal(array $items): int {
    return (int)array_sum(array_map(fn($i) => $i['qty'] * $i['price'], $items));
}
function checkout_discount(array $voucher, int $subtotal): int {
    if ($subtotal < $voucher['min_order']) { throw new RuntimeException('Đơn hàng chưa đạt giá trị tối thiểu để dùng mã giảm giá.'); }
    return min($subtotal, max(0, (int)round($voucher['discount_type'] === 'percent' ? $subtotal * $voucher['discount_value'] / 100 : $voucher['discount_value'])));
}
