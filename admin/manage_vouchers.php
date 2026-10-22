<?php
require_once __DIR__ . '/../includes/init.php';
if (!isAdmin()) {
    header('Location: ../index.php');
    exit;
}

$page_title = 'Quản lý Voucher';
$success = $_SESSION['success'] ?? ''; unset($_SESSION['success']); $error = '';

// Xử lý thêm/sửa voucher
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete']) && verifyCsrfToken($_POST['csrf'] ?? '')) {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $discount_type = $_POST['discount_type'] ?? 'fixed';
    $discount_value = (float)($_POST['discount_value'] ?? 0);
    $min_order = (float)($_POST['min_order'] ?? 0);
    $max_usage = (int)($_POST['max_usage'] ?? 1);
    $per_user_limit = filter_var($_POST['per_user_limit'] ?? 1, FILTER_VALIDATE_INT);
    $new_customer_only = isset($_POST['new_customer_only']) ? 1 : 0;
    $expires_at = ($_POST['expires_at'] ?? '') ?: null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $edit_id = (int)($_POST['edit_id'] ?? 0);

    $usage = 0;
    if ($edit_id > 0) {
        $q = $pdo->prepare('SELECT used_count FROM vouchers WHERE id=?');
        $q->execute([$edit_id]); $usage = (int)$q->fetchColumn();
    }
    if (!preg_match('/^[A-Z0-9_-]{1,30}$/',$code) || !in_array($per_user_limit,[1,2],true) || ($new_customer_only && $per_user_limit!==1) || !is_finite($discount_value) || !is_finite($min_order) || $discount_value <= 0 || !in_array($discount_type, ['fixed','percent'], true) || $min_order < 0 || $max_usage < max(1,$usage) || ($discount_type === 'percent' && $discount_value > 100)) {
        $error = 'Kiểm tra thông tin mã và giới hạn 1–2 lượt/tài khoản. Mã đơn đầu tiên chỉ được dùng 1 lần.';
    } else {
        try {
            if ($edit_id > 0) {
                $stmt = $pdo->prepare("UPDATE vouchers SET code=?, discount_type=?, discount_value=?, min_order=?, max_usage=?, expires_at=?, is_active=?, per_user_limit=?, new_customer_only=? WHERE id=?");
                $stmt->execute([$code, $discount_type, $discount_value, $min_order, $max_usage, $expires_at, $is_active, $per_user_limit, $new_customer_only, $edit_id]);
                $_SESSION['success'] = 'Cập nhật voucher thành công!';
            } else {
                $stmt = $pdo->prepare("INSERT INTO vouchers (code, discount_type, discount_value, min_order, max_usage, expires_at, is_active, per_user_limit, new_customer_only) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$code, $discount_type, $discount_value, $min_order, $max_usage, $expires_at, $is_active, $per_user_limit, $new_customer_only]);
                $_SESSION['success'] = 'Thêm voucher thành công!';
            }
            header('Location: manage_vouchers.php');
            exit;
        } catch (PDOException $e) {
            $error = 'Chưa thể lưu. Kiểm tra mã trùng, ngày hết hạn và giá trị nhập.';
        }
    }
}

// Xóa voucher
if (isset($_POST['delete']) && verifyCsrfToken($_POST['csrf'] ?? '')) {
    try {
        $pdo->prepare("UPDATE vouchers SET is_active=0 WHERE id = ?")->execute([(int)$_POST['delete']]);
        $_SESSION['success'] = 'Đã ngừng mã giảm giá, giữ nguyên lịch sử sử dụng.';
        header('Location: manage_vouchers.php');
        exit;
    } catch (PDOException $e) {
        $error = 'Lỗi xóa: ' . $e->getMessage();
    }
}

// Lấy danh sách voucher
try {
    $vouchers = $pdo->query("SELECT * FROM vouchers ORDER BY created_at DESC,id DESC")->fetchAll();
} catch (PDOException $e) {
    $vouchers = [];
    if (empty($error)) {
        $error = 'Bảng vouchers chưa được tạo. Vui lòng chạy file SQL: admin/create_vouchers_table.sql';
    }
}

// Lấy voucher để sửa
$edit_voucher = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM vouchers WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_voucher = $stmt->fetch();
}

include 'includes/admin_header.php';
?>

<div class="top-bar">
    <h2 class="mb-0"><i class="fas fa-ticket-alt me-2 text-primary"></i>Quản lý Voucher</h2>
    <div class="user-info">
        <span class="text-muted"><?= date('d/m/Y H:i') ?></span>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($success): ?><div class="alert alert-success" role="status"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card card-modern" data-aos="fade-right">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i><?= $edit_voucher ? 'Sửa' : 'Thêm' ?> Voucher</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf" value="<?= getCsrfToken() ?>">
                    <input type="hidden" name="edit_id" value="<?= $edit_voucher['id'] ?? 0 ?>">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Mã voucher <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control" value="<?= htmlspecialchars($edit_voucher['code'] ?? '') ?>" required placeholder="VD: SALE50">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Loại giảm giá</label>
                        <select name="discount_type" class="form-select">
                            <option value="fixed" <?= ($edit_voucher['discount_type'] ?? 'fixed') == 'fixed' ? 'selected' : '' ?>>Giảm số tiền (VNĐ)</option>
                            <option value="percent" <?= ($edit_voucher['discount_type'] ?? '') == 'percent' ? 'selected' : '' ?>>Giảm phần trăm (%)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Giá trị giảm <span class="text-danger">*</span></label>
                        <input type="number" name="discount_value" class="form-control" value="<?= $edit_voucher['discount_value'] ?? '' ?>" required min="0" step="0.01">
                        <small class="text-muted">Nhập số tiền (VNĐ) hoặc phần trăm (%)</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Đơn hàng tối thiểu</label>
                        <input type="number" name="min_order" class="form-control" value="<?= $edit_voucher['min_order'] ?? 0 ?>" min="0" step="1000">
                        <small class="text-muted">Đơn hàng tối thiểu để áp dụng voucher (VNĐ)</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Tổng lượt phát hành toàn shop</label>
                        <input type="number" name="max_usage" class="form-control" value="<?= $edit_voucher['max_usage'] ?? 100 ?>" min="<?= max(1,(int)($edit_voucher['used_count']??0)) ?>" required>
                        <small class="text-muted">Tổng lượt phát hành, không thấp hơn số lượt đã dùng.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold" for="per_user_limit">Lượt dùng mỗi tài khoản</label>
                        <select id="per_user_limit" name="per_user_limit" class="form-select">
                            <option value="1" <?= (int)($edit_voucher['per_user_limit']??1)===1?'selected':'' ?>>1 lần / tài khoản</option>
                            <option value="2" <?= (int)($edit_voucher['per_user_limit']??1)===2?'selected':'' ?>>2 lần / tài khoản</option>
                        </select>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" name="new_customer_only" id="new_customer_only" <?= !empty($edit_voucher['new_customer_only'])?'checked':'' ?>>
                        <label class="form-check-label" for="new_customer_only">Chỉ dành cho đơn đầu tiên</label>
                        <small class="d-block text-muted">Tài khoản chưa từng đặt đơn. Giới hạn 1 lần; hủy đơn không hoàn lượt mã.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Hết hạn</label>
                        <input type="datetime-local" name="expires_at" class="form-control" value="<?= !empty($edit_voucher['expires_at']) ? date('Y-m-d\TH:i', strtotime($edit_voucher['expires_at'])) : '' ?>">
                        <small class="text-muted">Để trống nếu không giới hạn thời gian</small>
                    </div>

                    <div class="mb-3 form-check form-switch">
                        <input type="checkbox" name="is_active" class="form-check-input" id="is_active" <?= ($edit_voucher['is_active'] ?? 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">Kích hoạt</label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-save me-2"></i><?= $edit_voucher ? 'Cập nhật' : 'Thêm mới' ?>
                    </button>
                    <?php if ($edit_voucher): ?>
                        <a href="manage_vouchers.php" class="btn btn-secondary w-100 mt-2">Hủy</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card card-modern" data-aos="fade-left">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Danh sách Voucher (<?= count($vouchers) ?>)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Mã</th>
                                <th>Giảm giá</th>
                                <th>Đơn tối thiểu</th>
                                <th>Lượt sử dụng</th>
                                <th>Hết hạn</th>
                                <th>Trạng thái</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($vouchers)): ?>
                                <tr><td colspan="7" class="text-center py-4 text-muted">Chưa có voucher nào</td></tr>
                            <?php else: ?>
                                <?php foreach ($vouchers as $v): ?>
                                    <tr>
                                        <td><strong class="text-primary"><?= htmlspecialchars($v['code']) ?></strong><small class="d-block text-muted"><?= (int)$v['per_user_limit'] ?> lần/tài khoản<?= $v['new_customer_only']?' · Đơn đầu tiên':'' ?></small></td>
                                        <td>
                                            <?php if ($v['discount_type'] == 'percent'): ?>
                                                <span class="badge bg-success"><?= $v['discount_value'] ?>%</span>
                                            <?php else: ?>
                                                <span class="badge bg-info"><?= number_format($v['discount_value']) ?>đ</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $v['min_order'] > 0 ? number_format($v['min_order']) . 'đ' : '<span class="text-muted">Không</span>' ?></td>
                                        <td><span class="badge bg-warning">Shop còn <?= max(0,(int)$v['max_usage']-(int)$v['used_count']) ?>/<?= (int)$v['max_usage'] ?> lượt</span><small class="d-block text-muted">Đã dùng <?= (int)$v['used_count'] ?> lượt</small></td>
                                        <td><?= $v['expires_at'] ? date('d/m/Y H:i', strtotime($v['expires_at'])) : '<span class="text-muted">Không hạn</span>' ?></td>
                                        <td>
                                            <?php if ($v['is_active']): ?>
                                                <span class="badge bg-success">Hoạt động</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Tắt</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="?edit=<?= $v['id'] ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                            <form method="post" class="d-inline" onsubmit="return confirm('Xác nhận thao tác này?')"><input type="hidden" name="csrf" value="<?= getCsrfToken() ?>"><input type="hidden" name="delete" value="<?= (int)$v['id'] ?>"><button class="btn btn-sm btn-outline-danger">Ngừng mã</button></form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>

