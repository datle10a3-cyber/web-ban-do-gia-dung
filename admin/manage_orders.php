<?php
require_once __DIR__.'/../includes/init.php';
if(!isAdmin()){http_response_code(403);header('Location: ../index.php');exit;}
$page_title = "Quản lý đơn hàng";

$search = trim($_GET['s'] ?? '');
$where = '';
$params = [];

if ($search !== '') {
    $where = "WHERE o.order_code LIKE ? 
           OR o.id LIKE ? 
           OR u.username LIKE ? OR u.display_name LIKE ? 
           OR u.email LIKE ? 
           OR o.fullname LIKE ? 
           OR o.phone LIKE ? 
           OR o.shipping_address LIKE ?";
    $like = "%$search%";
    $params = [$like, $like, $like, $like, $like, $like, $like, $like];
}

$sql = "SELECT o.*, COALESCE(u.display_name,u.username) AS username, u.email,
               (SELECT COALESCE(pv.image, p.image, 'no-image.jpg')
                FROM order_items oi2
                LEFT JOIN product_variants pv ON oi2.variant_id = pv.id AND oi2.variant_id > 0
                LEFT JOIN products p ON oi2.product_id = p.id
                WHERE oi2.order_id = o.id
                ORDER BY oi2.id ASC
                LIMIT 1) AS thumbnail
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        $where 
        ORDER BY o.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include 'includes/admin_header.php'; ?>

<div class="top-bar d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Quản lý Đơn hàng</h2>
    <form class="d-flex gap-2">
        <input type="text" name="s" class="form-control" placeholder="Tìm mã đơn, khách hàng, sđt..." 
               value="<?= htmlspecialchars($search) ?>" autofocus>
        <button class="btn btn-primary">Tìm kiếm</button>
    </form>
</div>

<div class="card shadow-sm">
    <div class="card-body table-responsive">
        <?php if (empty($orders)): ?>
            <div class="text-center py-5 text-muted">
                <p>Chưa có đơn hàng nào</p>
            </div>
        <?php else: ?>
            <table class="table table-hover align-middle mb-0">
                <thead class="table-primary">
                    <tr>
                        <th width="6%" class="text-center">Ảnh</th>
                        <th width="11%">Mã đơn</th>
                        <th width="22%">Khách hàng</th>
                        <th width="12%" class="text-end">Tổng tiền</th>
                        <th width="12%">Thanh toán</th>
                        <th width="11%">Trạng thái</th>
                        <th width="14%">Ngày đặt</th>
                        <th width="10%" class="text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o):
                        $status_vietnam = [
                            'pending'     => 'Chờ xác nhận',
                            'processing'  => 'Đang xử lý',
                            'shipping'    => 'Đang giao',
                            'delivered'   => 'Đã giao',
                            'completed'   => 'Hoàn thành',
                            'cancelled'   => 'Đã hủy'
                        ];
                        $current_status = $o['status'] ?? 'pending';
                        $status_text    = $status_vietnam[$current_status] ?? 'Không xác định';
                        $status_badge   = match($current_status) {
                            'pending'     => 'warning',
                            'processing'  => 'info',
                            'shipping'    => 'primary',
                            'delivered'   => 'success',
                            'completed'   => 'success',
                            'cancelled'   => 'danger',
                            default       => 'secondary'
                        };
                        $amount = $o['final_amount'] ?? $o['total'] ?? 0;
                        $thumb  = $o['thumbnail'] ?? 'no-image.jpg';
                    ?>
                    <tr>
                        <td class="text-center">
                            <img src="<?= $base ?>/assets/images/products/<?= htmlspecialchars($thumb) ?>" 
                                 alt="Sản phẩm" 
                                 class="rounded border shadow-sm" 
                                 style="width:56px; height:56px; object-fit:cover;"
                                 onerror="this.src='<?= $base ?>/assets/images/no-image.jpg'">
                        </td>

                        <td>
                            <strong class="text-primary">
                                <?= htmlspecialchars($o['order_code'] ?? 'GD' . str_pad($o['id'], 8, '0', STR_PAD_LEFT)) ?>
                            </strong>
                        </td>
                        <td>
                            <div>
                                <strong><?= htmlspecialchars($o['fullname'] ?: $o['username'] ?: 'Khách lẻ') ?></strong><br>
                                <small class="text-muted">
                                    <?= htmlspecialchars($o['phone'] ?: $o['email'] ?: '') ?>
                                </small>
                                <?php if (!empty($o['voucher_code'])): ?>
                                <br><small class="text-success">
                                    Voucher: <strong><?= htmlspecialchars($o['voucher_code']) ?></strong>
                                    <?= $o['discount_amount'] > 0 ? ' (-' . number_format($o['discount_amount']) . 'đ)' : '' ?>
                                </small>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="text-end fw-bold text-danger">
                            <?= number_format($amount) ?>đ
                        </td>
                        <td>
                            <?php
                            $pm = $o['payment_method'] ?? 'cod';
                            $pm_text = [
                                'cod'           => '<span class="text-warning fw-bold">COD</span>',
                                'bank_transfer' => '<span class="text-info fw-bold">Chuyển khoản</span>',
                                'momo'          => '<span class="text-danger fw-bold">MoMo</span>',
                                'zalopay'       => '<span class="text-success fw-bold">ZaloPay</span>',
                                'transfer'      => '<span class="text-info fw-bold">Chuyển khoản</span>',
                                'vnpay'         => '<span class="text-primary fw-bold">VNPAY</span>',
                                  'card'          => '<span class="text-primary fw-bold">Thẻ</span>',
                            ];
                            echo $pm_text[$pm] ?? '<span class="text-muted">COD</span>';
                            if (in_array($pm, ['bank_transfer','momo','zalopay','transfer','card','vnpay']) && $o['payment_status'] === 'paid') {
                                echo ' <small class="text-success">Đã thanh toán</small>';
                            }
                            ?>
                        </td>
                        <td>
                            <span class="badge bg-<?= $status_badge ?> fs-6">
                                <?= $status_text ?>
                            </span>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                        <td class="text-center">
                            <a href="order_detail.php?id=<?= $o['id'] ?>" 
                               class="btn btn-sm btn-info" title="Xem chi tiết">
                                Xem
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>