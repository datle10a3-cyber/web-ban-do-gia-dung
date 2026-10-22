<?php
require_once '../includes/init.php';
requireLogin();

$page_title = "Chi tiết đơn hàng - Gia Dụng Shop";
$base = "/web-ban-do-gia-dung";

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($order_id <= 0) {
    header('Location: orders.php');
    exit;
}

// 1. LẤY THÔNG TIN ĐƠN HÀNG
$stmt = $pdo->prepare("
    SELECT o.*, COALESCE(u.display_name,u.username) AS username 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: orders.php');
    exit;
}

// 2. LẤY SẢN PHẨM TRONG ĐƠN 
$stmt = $pdo->prepare("
    SELECT 
        oi.quantity,
        oi.price,
        p.name AS product_name,
        COALESCE(pv.image, p.image) AS display_image,  -- ƯU TIÊN ẢNH VARIANT TRƯỚC
        pv.color,
        pv.capacity
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    LEFT JOIN product_variants pv ON oi.variant_id = pv.id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>

<style>
    .status-pending    { background:#fff3cd; color:#856404; }
    .status-processing { background:#d1ecf1; color:#0c5460; }
    .status-shipping   { background:#cce5ff; color:#004085; }
    .status-delivered  { background:#d4edda; color:#155724; }
    .status-cancelled  { background:#f8d7da; color:#721c24; }
    .variant-badge {
        display: inline-block;
        padding: 0.35rem 0.8rem;
        border-radius: 50px;
        font-size: 0.85rem;
        font-weight: 600;
    }
</style>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
                <div class="card-header bg-primary text-white text-center py-4">
                    <h3 class="mb-0 fw-bold">ĐƠN HÀNG #<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?></h3>
                    <small><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></small>
                </div>

                <div class="card-body p-5">
                    <!-- Trạng thái đơn hàng -->
                    <div class="text-end mb-4">
                        <span class="badge fs-5 px-4 py-2 status-<?= htmlspecialchars($order['status']) ?>">
                            <?= ucfirst(str_replace('_', ' ', $order['status'])) ?>
                        </span>
                    </div>

                    <div class="row g-5">
                        <!-- DANH SÁCH SẢN PHẨM -->
                        <div class="col-lg-8">
                            <h5 class="fw-bold text-primary mb-4">
                                Sản phẩm trong đơn
                            </h5>

                            <?php foreach ($items as $item):
                                // Xử lý phân loại hiển thị
                                $variant_parts = [];
                                if (!empty($item['color']))    $variant_parts[] = $item['color'];
                                if (!empty($item['capacity'])) $variant_parts[] = $item['capacity'];
                                $variant_text = !empty($variant_parts) ? implode(' • ', $variant_parts) : 'Mặc định';
                            ?>
                                <div class="d-flex align-items-center py-4 border-bottom">
                                    <!-- Ảnh - DÙNG display_image từ COALESCE (đã ưu tiên variant rồi) -->
                                    <img src="<?= $base ?>/assets/images/products/<?= htmlspecialchars($item['display_image'] ?? 'no-image.jpg') ?>"
                                         class="rounded shadow-sm me-4"
                                         style="width:90px; height:90px; object-fit:cover; border-radius:12px;"
                                         onerror="this.onerror=null; this.src='<?= $base ?>/assets/images/products/no-image.jpg'"
                                         alt="<?= htmlspecialchars($item['product_name']) . ($variant_text !== 'Mặc định' ? ' - ' . htmlspecialchars($variant_text) : '') ?>">

                                    <div class="flex-grow-1">
                                        <h6 class="fw-bold mb-1"><?= htmlspecialchars($item['product_name']) ?></h6>

                                        <?php if ($variant_text !== 'Mặc định'): ?>
                                            <span class="badge bg-primary text-white mb-2">
                                                <?= htmlspecialchars($variant_text) ?>
                                            </span>
                                            <br>
                                        <?php endif; ?>

                                        <small class="text-muted">
                                            <?= (int)$item['quantity'] ?> × <?= number_format($item['price'], 0, '', '.') ?>đ
                                        </small>
                                    </div>

                                    <div class="text-end">
                                        <strong class="text-success fs-5">
                                            <?= number_format($item['price'] * $item['quantity'], 0, '', '.') ?>đ
                                        </strong>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- THÔNG TIN ĐƠN HÀNG -->
                        <div class="col-lg-4">
                            <div class="bg-light rounded-4 p-4 shadow-sm">
                                <h5 class="fw-bold mb-4 text-primary">
                                    Thông tin đơn hàng
                                </h5>

                                <p class="mb-2"><strong>Khách hàng:</strong> <?= htmlspecialchars($order['fullname']) ?></p>
                                <p class="mb-2"><strong>Số điện thoại:</strong> <?= htmlspecialchars($order['phone']) ?></p>
                                <p class="mb-2"><strong>Địa chỉ giao:</strong><br>
                                    <?= nl2br(htmlspecialchars($order['shipping_address'])) ?>
                                </p>
                                <p class="mb-3"><strong>Phương thức thanh toán:</strong>
                                    <?= sanitize(['cod'=>'Thanh toán khi nhận hàng (COD)','vnpay'=>'VNPAY','bank_transfer'=>'Chuyển khoản ngân hàng','momo'=>'MoMo','zalopay'=>'ZaloPay'][$order['payment_method']]??'Đang cập nhật') ?>
                                </p>

                                <hr>

                                <div class="d-flex justify-content-between fw-bold fs-5 mb-3">
                                    <span>Tổng thanh toán</span>
                                    <span class="text-success">
                                        <?= number_format($order['final_amount'] ?? $order['total'], 0, '', '.') ?>đ
                                    </span>
                                </div>

                                <?php if ($order['status'] === 'pending'): ?>
                                    <a href="orders.php?id=<?= (int)$order['id'] ?>" class="btn btn-danger w-100">Quản lý hoặc hủy đơn hàng</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-5">
                        <a href="orders.php" class="btn btn-outline-primary btn-lg px-5">
                            Quay lại danh sách đơn hàng
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>