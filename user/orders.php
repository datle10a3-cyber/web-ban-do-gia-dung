<?php
include '../config/database.php';
include '../includes/functions.php';
requireLogin();
require_once __DIR__.'/../includes/order_service.php';

$page_title = 'Đơn hàng của tôi';

$user_id = $_SESSION['user_id'];
$order_id = (int)($_GET['id'] ?? 0);

// Xử lý hủy đơn hàng + lý do
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order']) && $order_id > 0) {
    $reason = $_POST['cancel_reason'] ?? '';
    $other_reason = trim($_POST['other_reason'] ?? '');

    if (!verifyCsrfToken($_POST['csrf']??null)) {
        http_response_code(403);$error='Phiên đã hết hạn. Vui lòng tải lại trang.';
    } elseif (!is_string($reason)||!is_string($_POST['other_reason']??'')||mb_strlen($reason)>100||mb_strlen($other_reason)>500) {
        $error='Lý do hủy đơn không hợp lệ.';
    } elseif (empty($reason)) {
        $error = 'Vui lòng chọn lý do hủy đơn hàng.';
    } else {
        if ($reason === 'Lý do khác' && empty($other_reason)) {
            $error = 'Vui lòng nhập lý do khác.';
        } else {
            $final_reason = $reason === 'Lý do khác' ? $other_reason : $reason;
            try {
                order_change_status($pdo,$order_id,'cancelled',(int)$user_id,$final_reason);
                $success = 'Hủy đơn hàng thành công! Cảm ơn bạn đã cho chúng tôi biết lý do.';
            } catch (RuntimeException $e) {
                $error=$e instanceof PDOException?'Chưa thể hủy đơn. Vui lòng thử lại.':$e->getMessage();
            }
        }
    }
}

// Biến chung
$orders = [];
$order = null;
$items = [];
$total_amount = 0;
$is_detail = ($order_id > 0);

if ($is_detail) {
    $stmt = $pdo->prepare("SELECT o.*, COALESCE(u.display_name,u.username) AS username FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ? AND o.user_id = ?");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        $error = 'Đơn hàng không tồn tại hoặc không thuộc về bạn.';
    } else {
        $stmt = $pdo->prepare("
            SELECT 
                oi.quantity AS qty, 
                oi.price, 
                p.name AS product_name,
                COALESCE(pv.image, p.image) AS display_image,
                pv.color, 
                pv.capacity
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.id
            LEFT JOIN product_variants pv ON oi.variant_id = pv.id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$order_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} else {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Đếm đơn hàng cho badge
$stmt_count = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
$stmt_count->execute([$user_id]);
$total_orders = (int)$stmt_count->fetchColumn();

// Đường dẫn chính xác tới reorder.php
$reorder_url = '../cart/reorder.php?id=' . $order_id;

include '../includes/header.php';
?>

<!-- Font Awesome chính thức -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
    :root {
        --primary: #1976D2;
        --success: #28a745;
        --danger: #dc3545;
        --warning: #ffc107;
        --light: #f8f9fa;
        --gray: #6c757d;
        --shadow: 0 6px 25px rgba(0,0,0,0.1);
        --radius: 16px;
        --transition: all 0.3s ease;
    }
    body {
        background: linear-gradient(135deg, #E3F2FD 0%, #BBDEFB 100%);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        min-height: 100vh;
    }
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    .page-header {
        background: linear-gradient(135deg, #1976D2, #42A5F5);
        color: white;
        padding: 3.5rem 2rem;
        border-radius: var(--radius);
        text-align: center;
        box-shadow: var(--shadow);
        margin-bottom: 2rem;
    }
    .page-title {
        font-size: 2.8rem;
        font-weight: 800;
        margin: 0;
        text-shadow: 0 2px 10px rgba(0,0,0,0.2);
    }
    .alert {
        border-radius: var(--radius);
        padding: 1.2rem 1.8rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--shadow);
        font-weight: 600;
        border: none;
    }
    .alert-success { background: #d4edda; color: #155724; border-left: 5px solid #28a745; }
    .alert-danger { background: #f8d7da; color: #721c24; border-left: 5px solid #dc3545; }

    .order-card, .order-list {
        background: white;
        border-radius: var(--radius);
        overflow: hidden;
        box-shadow: var(--shadow);
        margin-bottom: 2rem;
        transition: var(--transition);
    }
    .order-card:hover, .order-list:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(0,0,0,0.15); }

    .order-item {
        padding: 1.8rem;
        border-bottom: 1px solid #eee;
        display: flex;
        align-items: center;
        gap: 1.2rem;
        flex-wrap: wrap;
        transition: var(--transition);
    }
    .order-item:hover { background: #f0f8ff; }
    .order-item:last-child { border-bottom: none; }

    .order-thumb {
        width: 70px;
        height: 70px;
        object-fit: cover;
        border-radius: 12px;
        border: 2px solid #e0e0e0;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    .order-info { flex: 1; min-width: 220px; }
    .order-id {
        font-weight: bold;
        color: var(--primary);
        font-size: 1.3rem;
    }
    .status-badge {
        padding: 0.6rem 1.3rem;
        border-radius: 50px;
        font-size: 0.95rem;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .status-pending { background: #fff3cd; color: #856404; }
    .status-processing { background: #d1ecf1; color: #0c5460; }
    .status-shipping { background: #cce5ff; color: #004085; }
    .status-delivered { background: #d4edda; color: #155724; }
    .status-cancelled { background: #f8d7da; color: #721c24; }

    .btn {
        border-radius: var(--radius);
        padding: 0.8rem 1.8rem;
        font-weight: 600;
        transition: var(--transition);
        text-decoration: none;
        display: inline-block;
        border: none;
        cursor: pointer;
    }
    .btn-primary { background: var(--primary); color: white; }
    .btn-danger { background: var(--danger); color: white; }
    .btn-success { background: var(--success); color: white; }
    .btn-sm { padding: 0.6rem 1.2rem; font-size: 0.9rem; }
    .btn:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.2); }

    .modal {
        display: none;
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.6);
        z-index: 9999;
        justify-content: center;
        align-items: center;
        backdrop-filter: blur(5px);
    }
    .modal.active { display: flex; }
    .modal-content {
        background: white;
        padding: 2.5rem;
        border-radius: var(--radius);
        width: 90%;
        max-width: 520px;
        box-shadow: 0 20px 50px rgba(0,0,0,0.3);
        animation: modalShow 0.4s ease;
    }
    @keyframes modalShow { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }

    .product-preview {
        display: flex;
        align-items: center;
        gap: 15px;
        margin: 12px 0;
        padding: 15px;
        background: #f8fdff;
        border-radius: 12px;
        border-left: 4px solid var(--primary);
        transition: var(--transition);
    }
    .product-preview:hover { background: #e3f2fd; transform: translateX(5px); }
    .product-preview img {
        width: 70px;
        height: 70px;
        object-fit: cover;
        border-radius: 10px;
        border: 2px solid #ddd;
    }

    /* Scrollbar đẹp */
    ::-webkit-scrollbar { width: 10px; }
    ::-webkit-scrollbar-track { background: #f1f8ff; border-radius: 10px; }
    ::-webkit-scrollbar-thumb { background: linear-gradient(135deg, #1976D2, #0D47A1); border-radius: 10px; }
    ::-webkit-scrollbar-thumb:hover { background: #0D47A1; }
</style>

<div class="container py-4">
    <div class="page-header">
        <h1 class="page-title">Đơn hàng của tôi</h1>
        <p style="font-size:1.2rem; opacity:0.9;">Theo dõi và quản lý tất cả đơn hàng của bạn</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger">✗ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($is_detail): ?>
        <?php if (!$order): ?>
            <div class="text-center py-5 bg-white rounded" style="box-shadow: var(--shadow);">
                <h3>Đơn hàng không tồn tại</h3>
                <a href="orders.php" class="btn btn-primary">Quay lại danh sách</a>
            </div>
        <?php else: ?>
            <div class="order-card">
                <div class="p-4 border-bottom" style="background: linear-gradient(135deg, #f8fdff, #e3f2fd);">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div>
                            <h4>Đơn hàng #<?= htmlspecialchars($order['order_code'] ?? $order_id) ?></h4>                       
                            <small class="text-muted">Ngày đặt: <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></small>
                            <?php if (!empty($order['cancel_reason'])): ?>
                                <br><small class="text-danger"><strong>Lý do hủy:</strong> <?= htmlspecialchars($order['cancel_reason']) ?></small>
                            <?php endif; ?>
                        </div>
                        <span class="status-badge status-<?= $order['status'] ?>">
                            <?= $order['status'] === 'pending' ? 'Chờ xử lý' : 
                               ($order['status'] === 'processing' ? 'Đang xử lý' : 
                               ($order['status'] === 'shipping' ? 'Đang giao' : 
                               ($order['status'] === 'delivered' ? 'Đã giao' : 'Đã hủy'))) ?>
                        </span>
                    </div>
                </div>

                <div class="p-4">
                    <h6 class="mb-4 text-primary fw-bold">Sản phẩm trong đơn hàng:</h6>
                    <?php foreach ($items as $item): 
                        $variant = array_filter([$item['color'] ?? '', $item['capacity'] ?? '']);
                        $variant_text = !empty($variant) ? implode(' - ', $variant) : '';
                    ?>
                        <div class="product-preview">
                            <img src="../assets/images/products/<?= htmlspecialchars($item['display_image'] ?? 'no-image.jpg') ?>" 
                                 onerror="this.src='../assets/images/products/no-image.jpg'" alt="<?= htmlspecialchars($item['product_name']) ?>">
                            <div style="flex:1;">
                                <strong class="d-block mb-1"><?= htmlspecialchars($item['product_name']) ?></strong>
                                <?php if ($variant_text): ?>
                                    <small class="text-primary fw-bold">Phân loại: <?= htmlspecialchars($variant_text) ?></small><br>
                                <?php endif; ?>
                                <small class="text-muted">
                                    Số lượng: <?= $item['qty'] ?> × <?= number_format($item['price']) ?>đ
                                </small>
                            </div>
                            <div class="text-end">
                                <strong class="text-success fs-5"><?= number_format($item['price'] * $item['qty']) ?>đ</strong>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <hr style="border-top: 2px dashed #ddd; margin: 2rem 0;">

                    <div class="row">
                        <div class="col-md-8">
                           <?php
                            $name    = $order['fullname'] ?? 'Khách lẻ';
                            $phone   = $order['phone'] ?? 'Không có số điện thoại';
                            $address = $order['shipping_address'] ?? 'Chưa có địa chỉ chi tiết';
                            ?>
                            <p><strong>Người nhận:</strong> <?= htmlspecialchars($name) ?> - <?= htmlspecialchars($phone) ?></p>
                            <p><strong>Địa chỉ giao:</strong> <?= nl2br(htmlspecialchars($address)) ?></p>
                            <p><strong>Hình thức thanh toán:</strong> 
                                <?= sanitize(['cod'=>'Thanh toán khi nhận hàng (COD)','bank_transfer'=>'Chuyển khoản ngân hàng','vnpay'=>'VNPAY','transfer'=>'Chuyển khoản','card'=>'Thẻ','momo'=>'MoMo','zalopay'=>'ZaloPay'][$order['payment_method']] ?? $order['payment_method']) ?>
                            </p>
                            <p><strong>Trạng thái thanh toán:</strong> <?= sanitize(['pending'=>'Chờ thanh toán','paid'=>'Đã thanh toán','failed'=>'Chưa hoàn tất','refunded'=>'Đã hoàn tiền'][$order['payment_status']] ?? 'Đang cập nhật') ?></p>
                            <?php if ($order['payment_method']==='vnpay' && $order['payment_status']!=='paid' && $order['status']==='pending'): ?>
                            <a class="btn btn-outline-primary mb-3" href="<?= BASE_URL ?>/checkout/thankyou.php?order_id=<?= (int)$order['id'] ?>&amp;code=<?= rawurlencode($order['order_code']) ?>">Tiếp tục thanh toán VNPAY</a>
                            <?php endif; ?>
                            <?php if (!empty($order['note'])): ?>
                                <div class="mt-3 p-3 bg-light border-start border-warning border-5 rounded">
                                    <p class="mb-0 fw-bold text-primary">
                                        <i class="fas fa-sticky-note me-2"></i>Ghi chú từ khách hàng:
                                    </p>
                                    <p class="mb-0 ms-4 text-dark"><?= nl2br(htmlspecialchars($order['note'])) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="border-start border-5 border-primary ps-4">
                                <?php if (($order['discount_amount'] ?? 0) > 0): ?>
                                    <div class="mb-2">
                                        <small class="text-muted">Tạm tính</small><br>
                                        <span class="text-dark"><?= number_format($order['subtotal'] ?? 0) ?>đ</span>
                                    </div>
                                    <div class="mb-3 text-success fw-bold">
                                        <i class="fas fa-tag me-1"></i> Giảm giá voucher
                                        <div class="fs-5">-<?= number_format($order['discount_amount']) ?>đ</div>
                                    </div>
                                <?php endif; ?>

                                <h5 class="mb-1 fw-bold">Tổng thanh toán:</h5>
                                <h3 class="text-success fw-bold">
                                    <?= number_format($order['final_amount'] ?? $order['total'] ?? 0) ?>đ
                                </h3>
                                <small class="text-success d-block fw-bold">Miễn phí vận chuyển toàn quốc</small>

                                <?php if (($order['discount_amount'] ?? 0) > 0): ?>
                                    <small class="text-primary d-block mt-3">
                                        Đã áp dụng mã: <strong><?= htmlspecialchars($order['voucher_code'] ?? 'N/A') ?></strong>
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 text-end">
                        <?php if ($order['status'] === 'pending'): ?>
                            <button type="button" class="btn btn-danger btn-lg px-5" onclick="openCancelModal()">
                                Hủy đơn hàng
                            </button>
                        <?php elseif ($order['status'] === 'cancelled'): ?>
                            <form method="post" action="../cart/reorder.php" class="d-inline"><input type="hidden" name="csrf" value="<?= getCsrfToken() ?>"><input type="hidden" name="id" value="<?= $order_id ?>"><button class="btn btn-success btn-lg px-5">
                                Mua lại đơn hàng
                            </button></form>
                        <?php elseif ($order['status'] === 'delivered'): ?>
                            <a href="review.php?order_id=<?= $order_id ?>" class="btn btn-success btn-lg px-5">
                                Đánh giá sản phẩm
                            </a>
                        <?php endif; ?>

                        <a href="orders.php" class="btn btn-primary btn-lg ms-3 px-5">Quay lại danh sách</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <?php if (empty($orders)): ?>
            <div class="text-center py-5 bg-white rounded" style="box-shadow: var(--shadow);">
                <h3>Chưa có đơn hàng nào</h3>
                <p class="text-muted">Hãy mua sắm để có đơn hàng đầu tiên nhé!</p>
                <a href="../index.php" class="btn btn-primary btn-lg px-5">Mua sắm ngay</a>
            </div>
        <?php else: ?>
            <div class="order-list">
                <?php foreach ($orders as $o): 
                    $stmt_thumb = $pdo->prepare("
                        SELECT COALESCE(pv.image, p.image) 
                        FROM order_items oi 
                        LEFT JOIN products p ON oi.product_id = p.id 
                        LEFT JOIN product_variants pv ON oi.variant_id = pv.id 
                        WHERE oi.order_id = ? 
                        LIMIT 1
                    ");
                    $stmt_thumb->execute([$o['id']]);
                    $thumb = $stmt_thumb->fetchColumn() ?: 'no-image.jpg';
                ?>
                    <div class="order-item">
                        <?php if ($thumb): ?>
                            <img src="../assets/images/products/<?= htmlspecialchars($thumb) ?>" class="order-thumb" onerror="this.style.display='none'">
                        <?php endif; ?>
                        <div class="order-info">
                            <div class="order-id">Đơn hàng #<?= sanitize($o['order_code'] ?: str_pad($o['id'], 6, '0', STR_PAD_LEFT)) ?></div>
                            <small class="text-muted">
                                <?= date('d/m/Y H:i', strtotime($o['created_at'])) ?> • 
                                <?= htmlspecialchars($o['fullname'] ?? 'Khách lẻ') ?>
                            </small>
                            <small class="d-block <?= $o['payment_status']==='paid'?'text-success':'text-muted' ?>"><?= sanitize(['cod'=>'COD','vnpay'=>'VNPAY','bank_transfer'=>'Chuyển khoản','momo'=>'MoMo','zalopay'=>'ZaloPay','transfer'=>'Chuyển khoản','card'=>'Thẻ'][$o['payment_method']]??'Thanh toán') ?> · <?= sanitize(['pending'=>'Chờ thanh toán','paid'=>'Đã thanh toán','failed'=>'Chưa hoàn tất','refunded'=>'Đã hoàn tiền'][$o['payment_status']]??'Đang cập nhật') ?></small>
                        </div>
                        <span class="status-badge status-<?= $o['status'] ?>">
                            <?= $o['status'] === 'pending' ? 'Chờ xử lý' : 
                               ($o['status'] === 'processing' ? 'Đang xử lý' : 
                               ($o['status'] === 'shipping' ? 'Đang giao' : 
                               ($o['status'] === 'delivered' ? 'Đã giao' : 'Đã hủy'))) ?>
                        </span>
                        <div class="text-end">
                            <strong class="text-success fs-4"><?= number_format($o['final_amount'] ?? 0) ?>đ</strong><br>
                            <small class="text-success fw-bold">Miễn phí ship</small>
                        </div>
                        <a href="orders.php?id=<?= $o['id'] ?>" class="btn btn-primary btn-sm">Xem chi tiết</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Modal Hủy đơn hàng -->
<div class="modal" id="cancelModal">
    <div class="modal-content">
        <h5 class="text-danger fw-bold mb-3">Hủy đơn hàng #<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?></h5>
        <p>Vui lòng cho chúng tôi biết lý do bạn hủy đơn hàng:</p>
        <form method="POST">
            <div class="reason-option">
                <label><input type="radio" name="cancel_reason" value="Không còn nhu cầu" required> Không còn nhu cầu</label>
            </div>
            <div class="reason-option">
                <label><input type="radio" name="cancel_reason" value="Tìm được giá tốt hơn"> Tìm được giá tốt hơn</label>
            </div>
            <div class="reason-option">
                <label><input type="radio" name="cancel_reason" value="Đổi ý muốn mua sản phẩm khác"> Đổi ý, muốn mua sản phẩm khác</label>
            </div>
            <div class="reason-option">
                <label><input type="radio" name="cancel_reason" value="Thời gian giao hàng quá lâu"> Thời gian giao hàng quá lâu</label>
            </div>
            <div class="reason-option">
                <label><input type="radio" name="cancel_reason" value="Nhập sai thông tin"> Nhập sai thông tin đơn hàng</label>
            </div>
            <div class="reason-option">
                <label><input type="radio" name="cancel_reason" value="Lý do khác" id="otherReason"> Lý do khác</label>
            </div>
            <div class="other-reason" id="otherInput">
                <textarea name="other_reason" class="form-control mt-2" placeholder="Vui lòng nhập lý do chi tiết..." rows="3"></textarea>
            </div>
            <div class="mt-4 text-end">
                <button type="button" class="btn btn-secondary me-3 px-4" onclick="closeCancelModal()">Hủy bỏ</button>
                <input type="hidden" name="csrf" value="<?= getCsrfToken() ?>"><button type="submit" name="cancel_order" value="1" class="btn btn-danger px-5">Xác nhận hủy đơn</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCancelModal() {
    document.getElementById('cancelModal').classList.add('active');
}
function closeCancelModal() {
    document.getElementById('cancelModal').classList.remove('active');
}

document.querySelectorAll('input[name="cancel_reason"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.getElementById('otherInput').style.display = this.value === 'Lý do khác' ? 'block' : 'none';
    });
});

document.getElementById('cancelModal').addEventListener('click', function(e) {
    if (e.target === this) closeCancelModal();
});
</script>

<?php include '../includes/footer.php'; ?>
