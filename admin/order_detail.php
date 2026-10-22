<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__.'/../includes/order_service.php';

if (!isAdmin()) {
    header('Location: ../index.php');
    exit;
}

$order_id = (int)($_GET['id'] ?? 0);
if (!$order_id) {
    header('Location: manage_orders.php');
    exit;
}

$page_title = 'Chi tiết đơn hàng #' . $order_id;

// Xử lý cập nhật status
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf'] ?? '')) {
    $status = sanitize($_POST['status'] ?? '');
    $allowed_statuses = ['pending', 'processing', 'shipping', 'delivered', 'cancelled', 'completed'];
    if (in_array($status, $allowed_statuses)) {
        try {
            order_change_status($pdo,$order_id,$status,null,'Quản trị viên hủy đơn',isset($_POST['confirm_cod']));
            $success = 'Cập nhật trạng thái thành công!';
        } catch (Throwable $e) {
            $error = $e instanceof RuntimeException&&!($e instanceof PDOException)?$e->getMessage():'Chưa thể cập nhật đơn hàng.';
        }
    } else {
        $error = 'Trạng thái không hợp lệ!';
    }
}

// Lấy thông tin đơn hàng + user
$stmt = $pdo->prepare("
    SELECT o.*, COALESCE(u.display_name,u.username) AS username, u.email 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    WHERE o.id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) { 
    header('Location: manage_orders.php');
    exit; 
}

// Lấy sản phẩm + variant (màu, dung tích, ảnh)
$items_stmt = $pdo->prepare("
    SELECT oi.*, 
           p.name as product_name, 
           p.image AS product_image,
           pv.color, pv.capacity, pv.image AS variant_image
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id
    LEFT JOIN product_variants pv ON oi.variant_id = pv.id
    WHERE oi.order_id = ?
");
$items_stmt->execute([$order_id]);
$items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
$total_items = count($items);
?>

<?php include 'includes/admin_header.php'; ?>

<div class="top-bar">
    <h2 class="mb-0"><i class="fas fa-receipt me-2 text-primary"></i> Chi tiết Đơn hàng #<?= $order_id ?></h2>
    <a href="manage_orders.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>Quay lại</a>
</div>

<style>
    :root {
        --primary-light-blue: #E3F2FD;
        --primary-blue: #1976D2;
        --primary-dark-blue: #0D47A1;
        --secondary-blue: #BBDEFB;
        --success: #4CAF50;
        --warning: #FFC107;
        --danger: #DC3545;
        --info: #17A2B8;
        --shadow: 0 4px 20px rgba(25, 118, 210, 0.1);
        --border-radius: 12px;
        --transition: all 0.3s ease;
    }

    body {
        background-color: var(--primary-light-blue);
    }

    .admin-container {
        padding: 2rem 0;
    }

    .page-header {
        background: linear-gradient(135deg, var(--primary-blue), var(--primary-dark-blue));
        color: white;
        padding: 2rem;
        border-radius: var(--border-radius);
        margin-bottom: 2rem;
        box-shadow: var(--shadow);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
    }

    .page-title {
        font-weight: 700;
        font-size: 2rem;
        margin: 0;
    }

    .back-btn {
        background: rgba(255, 255, 255, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: var(--border-radius);
        text-decoration: none;
        font-weight: 600;
        transition: var(--transition);
    }

    .back-btn:hover {
        background: rgba(255, 255, 255, 0.3);
        color: white;
        transform: translateY(-1px);
    }

    .alert {
        border-radius: var(--border-radius);
        border: none;
        margin-bottom: 1.5rem;
    }

    .order-card {
        background: white;
        border-radius: var(--border-radius);
        overflow: hidden;
        box-shadow: var(--shadow);
        margin-bottom: 2rem;
    }

    .card-header {
        background: var(--secondary-blue);
        padding: 1.5rem;
        color: var(--primary-dark-blue);
        font-weight: 600;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .card-body {
        padding: 1.5rem;
    }

    .table thead th {
        background: var(--secondary-blue);
        color: var(--primary-dark-blue);
        font-weight: 600;
        border: none;
        padding: 1rem;
    }

    .table tbody td {
        padding: 1rem;
        vertical-align: middle;
        border-color: #e9ecef;
    }

    .product-img {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid #e9ecef;
    }

    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .status-pending { background-color: #fff3cd; color: #856404; }
    .status-processing { background-color: #d1ecf1; color: #0c5460; }
    .status-shipping { background-color: #cce5ff; color: #004085; }
    .status-delivered { background-color: #d4edda; color: #155724; }
    .status-cancelled { background-color: #f8d7da; color: #721c24; }
    .status-completed { background-color: #d1ecf1; color: #0c5460; }

    .status-select {
        border: 1px solid var(--secondary-blue);
        border-radius: var(--border-radius);
        padding: 0.5rem;
        font-size: 0.9rem;
    }

    .status-select:focus {
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 0.2rem rgba(25, 118, 210, 0.25);
    }

    .info-sidebar {
        position: sticky;
        top: 100px;
    }

    .info-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #e9ecef;
    }

    .info-label {
        font-weight: 600;
        color: var(--primary-dark-blue);
    }

    .info-value {
        text-align: right;
        font-weight: 500;
    }

    .total-amount {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--success);
    }

    .no-items {
        text-align: center;
        padding: 3rem;
        color: var(--primary-dark-blue);
    }

    .no-items i {
        font-size: 4rem;
        opacity: 0.5;
        margin-bottom: 1rem;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
            text-align: center;
            gap: 1rem;
        }
        .info-sidebar {
            position: static;
        }
        .row {
            flex-direction: column-reverse;
        }
        .table-responsive {
            font-size: 0.9rem;
        }
    }
</style>

<div class="admin-container">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb bg-white p-3 rounded shadow-sm mb-3">
                <li class="breadcrumb-item"><a href="index.php" class="text-primary">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="manage_orders.php" class="text-primary">Đơn hàng</a></li>
                <li class="breadcrumb-item active">Chi tiết #<?php echo $order_id; ?></li>
            </ol>
        </nav>

        <div class="page-header">
            <div>
                <h1 class="page-title"><i class="fas fa-file-invoice me-2"></i>Chi tiết đơn hàng #<?php echo $order_id; ?></h1>
                <p class="mb-0">Thông tin chi tiết và sản phẩm trong đơn hàng</p>
            </div>
            <a href="manage_orders.php" class="back-btn">
                <i class="fas fa-arrow-left me-2"></i>Quay lại danh sách
            </a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success d-flex align-items-center">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <div class="order-card">
                    <div class="card-header">
                        <h5><i class="fas fa-shopping-cart me-2"></i>Sản phẩm trong đơn hàng (<?php echo $total_items; ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($items)): ?>
                            <div class="no-items">
                                <i class="fas fa-shopping-cart"></i>
                                <h4>Không có sản phẩm</h4>
                                <p>Đơn hàng này chưa có sản phẩm nào.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Sản phẩm</th>
                                            <th>Số lượng</th>
                                            <th>Đơn giá</th>
                                            <th>Tổng phụ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($items as $item): 
                                            $variant_text = '';
                                            if (!empty($item['color'])) $variant_text .= $item['color'];
                                            if (!empty($item['capacity'])) $variant_text .= ($variant_text ? ' - ' : '') . $item['capacity'];
                                            $thumb = $item['variant_image'] ?: $item['product_image'] ?: 'no-image.jpg';
                                        ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <img src="../assets/images/products/<?php echo htmlspecialchars($thumb); ?>" 
                                                             alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                                             class="product-img me-3" 
                                                             onerror="this.src='../assets/images/products/no-image.jpg';">
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                                            <?php if ($variant_text): ?>
                                                                <br><small class="text-primary fw-bold">Phân loại: <?= htmlspecialchars($variant_text) ?></small>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><span class="badge bg-info"><?php echo $item['quantity']; ?></span></td>
                                                <td><?php echo number_format($item['price']); ?>đ</td>
                                                <td><strong class="text-success"><?php echo number_format($item['quantity'] * $item['price']); ?>đ</strong></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="info-sidebar">
                    <div class="order-card">
                        <div class="card-header">
                            <h5><i class="fas fa-info-circle me-2"></i>Thông tin đơn hàng</h5>
                        </div>
                        <div class="card-body">
                            <div class="info-item">
                                <span class="info-label">Mã đơn:</span>
                                <span class="info-value fw-bold text-primary">
                                    <?= htmlspecialchars($order['order_code'] ?? 'GD' . str_pad($order_id, 8, '0', STR_PAD_LEFT)) ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Khách hàng:</span>
                                <span class="info-value fw-bold">
                                    <?= htmlspecialchars($order['fullname'] ?: $order['username'] ?: 'Khách lẻ') ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Số điện thoại:</span>
                                <span class="info-value"><?= htmlspecialchars($order['phone'] ?: '—') ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Email:</span>
                                <span class="info-value"><?= htmlspecialchars($order['email'] ?: '—') ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Ngày đặt:</span>
                                <span class="info-value"><?= date('d/m/Y H:i', strtotime($order['created_at'])); ?></span>
                            </div>

                            <?php if (!empty($order['note'])): ?>
                            <div class="info-item border-top pt-3 bg-light rounded p-3">
                                <span class="info-label text-warning"><i class="fas fa-sticky-note me-2"></i>Ghi chú từ khách:</span>
                                <div class="info-value text-dark text-end">
                                    <?= nl2br(htmlspecialchars($order['note'])) ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php if ($order['status'] === 'cancelled' && !empty($order['cancel_reason'])): ?>
<div class="info-item border-top pt-3 bg-danger text-white rounded p-3 mt-3">
    <span class="info-label"><i class="fas fa-exclamation-triangle me-2"></i>Lý do hủy đơn (từ khách):</span>
    <div class="info-value text-end fw-bold">
        <?= nl2br(htmlspecialchars($order['cancel_reason'])) ?>
    </div>
</div>
<?php endif; ?>

                            <div class="info-item">
                                <span class="info-label">Phương thức thanh toán:</span>
                                <span class="info-value">
                                    <?php
                                    $pm = $order['payment_method'] ?? 'cod';
                                    $pm_labels = [
                                        'cod' => 'Thanh toán khi nhận hàng (COD)',
                                        'bank_transfer' => 'Chuyển khoản ngân hàng (QR)',
                                        'momo' => 'Ví MoMo',
                                        'zalopay' => 'Ví ZaloPay',
                                        'transfer' => 'Chuyển khoản ngân hàng',
                                        'card' => 'Thanh toán thẻ'
                                    ];
                                    echo $pm_labels[$pm] ?? 'COD';
                                    if (in_array($pm, ['bank_transfer','momo','zalopay','transfer','card']) && $order['payment_status'] === 'paid') {
                                        echo ' <span class="text-success"><i class="fas fa-check-circle"></i> Đã thanh toán</span>';
                                    }
                                    ?>
                                </span>
                            </div>

                            <?php if ($order['discount_amount'] > 0): ?>
                            <div class="info-item text-success fw-bold">
                                <span class="info-label"><i class="fas fa-tag me-2"></i>Mã giảm giá:</span>
                                <span class="info-value">
                                    <?= htmlspecialchars($order['voucher_code']) ?>
                                    (-<?= number_format($order['discount_amount']) ?>đ)
                                </span>
                            </div>
                            <?php endif; ?>

                            <div class="info-item border-top pt-3">
                                <span class="info-label h4 text-success">Tổng thanh toán:</span>
                                <span class="info-value total-amount">
                                    <?= number_format($order['final_amount'] ?? $order['total'] ?? 0) ?>đ
                                </span>
                            </div>

                            <div class="info-item mt-4">
                                <span class="info-label">Trạng thái đơn hàng:</span>
                                <span class="info-value">
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="csrf" value="<?php echo getCsrfToken(); ?>">
                                        <select name="status" class="status-select mb-2 w-100">
                                            <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>Chờ xử lý</option>
                                            <option value="processing" <?= $order['status'] == 'processing' ? 'selected' : '' ?>>Đang xử lý</option>
                                            <option value="shipping" <?= $order['status'] == 'shipping' ? 'selected' : '' ?>>Đang giao hàng</option>
                                            <option value="delivered" <?= $order['status'] == 'delivered' ? 'selected' : '' ?>>Đã giao</option>
                                            <option value="completed" <?= $order['status'] == 'completed' ? 'selected' : '' ?>>Hoàn thành</option>
                                            <option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
                                        </select>
                                        <?php if($order['payment_method']==='cod'&&$order['payment_status']!=='paid'): ?><label class="small d-block mb-2"><input type="checkbox" name="confirm_cod"> Xác nhận đã thu đủ tiền COD</label><?php endif; ?>
                                        <button class="btn btn-primary btn-sm">Lưu trạng thái</button>
                                        <?php if($order['status']==='cancelled'&&$order['payment_status']==='paid'): ?><p class="small text-warning mt-2">Đơn đã hủy có tiền đã thanh toán. Cần xử lý hoàn tiền với khách; hệ thống chưa tự hoàn tiền.</p><?php endif; ?>
                                    </form>
                                    <div class="mt-2">
                                        <span class="status-badge status-<?= $order['status'] ?>">
                                            <?= ucfirst(str_replace('_', ' ', $order['status'])) ?>
                                        </span>
                                    </div>
                                </span>
                            </div>

                            <div class="info-item">
                                <span class="info-label">Địa chỉ giao hàng:</span>
                                <span class="info-value text-end">
                                    <?= nl2br(htmlspecialchars($order['shipping_address'])) ?>
                                </span>
                            </div>

                            <a href="manage_orders.php" class="btn btn-secondary w-100 mt-4">
                                <i class="fas fa-arrow-left me-2"></i>Quay lại danh sách
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



<?php include 'includes/admin_footer.php'; ?>
