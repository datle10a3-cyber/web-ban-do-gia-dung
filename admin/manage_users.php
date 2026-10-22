<?php
require_once __DIR__.'/../includes/init.php';
if(!isAdmin()) {
    header('Location: ../index.php');
    exit;
}
$page_title = "Quản lý người dùng";

// Xử lý cập nhật role
$success = $error = '';
if($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete']) && verifyCsrfToken($_POST['csrf'] ?? '')) {
    $user_id = (int)($_POST['user_id'] ?? 0);
    $role = $_POST['role'] ?? '';
    if($user_id > 0 && in_array($role, ['customer', 'seller', 'admin'])) {
        if($user_id == 1) {
            $error = "Không thể thay đổi quyền của Admin mặc định!";
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
                $stmt->execute([$role, $user_id]);
                $success = "Cập nhật quyền thành công!";
            } catch(Exception $e) {
                $error = "Lỗi: " . $e->getMessage();
            }
        }
    } else {
        $error = "Dữ liệu không hợp lệ!";
    }
}

// Xử lý xóa user
if(isset($_POST['delete']) && verifyCsrfToken($_POST['csrf'] ?? '')) {
    $del_id = (int)$_POST['delete'];
    if($del_id == 1 || $del_id == (int)$_SESSION['user_id']) {
        $error = "Không thể xóa Admin mặc định!";
    } else {
        try {
            $q=$pdo->prepare('SELECT COUNT(*) FROM orders WHERE user_id=?');$q->execute([$del_id]);if($q->fetchColumn()){throw new RuntimeException('Tài khoản có đơn hàng; cần giữ lại lịch sử.');}
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$del_id]);
            $success = "Xóa người dùng thành công!";
        } catch(Exception $e) {
            $error = "Lỗi xóa: " . $e->getMessage();
        }
    }
    header("Location: manage_users.php");
    exit;
}

// Tìm kiếm + phân trang
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 15;
$offset = ($page - 1) * $per_page;

$where = $search ? "WHERE username LIKE ? OR email LIKE ? OR display_name LIKE ?" : "";
$params = $search ? ["%$search%", "%$search%", "%$search%"] : [];

$total_stmt = $pdo->prepare("SELECT COUNT(*) FROM users $where");
$total_stmt->execute($params);
$total_users = $total_stmt->fetchColumn();
$total_pages = ceil($total_users / $per_page);

$stmt = $pdo->prepare("SELECT id,username,display_name,email,phone,role,avatar,created_at FROM users $where ORDER BY id DESC LIMIT $per_page OFFSET $offset");
$stmt->execute($params);
$users = $stmt->fetchAll();
?>

<?php include 'includes/admin_header.php'; ?>

<div class="top-bar">
    <h2 class="mb-0"><i class="fas fa-users me-2 text-primary"></i>Quản lý Người dùng</h2>
    <div class="user-info">
        <span class="text-muted">Tổng: <?= $total_users ?> người dùng</span>
    </div>
</div>

<div class="card card-modern mb-4" data-aos="fade-up">
    <div class="card-body">
        <form class="d-flex gap-2" method="GET">
            <input type="text" name="search" class="form-control" placeholder="Tìm username, email..." value="<?=htmlspecialchars($search)?>">
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Tìm</button>
        </form>
    </div>
</div>

    <?php if($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-lg border-0">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-list"></i> Danh sách người dùng</h5>
            <small><?= $total_users ?> người dùng</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center">ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Quyền</th>
                            <th>Ngày đăng ký</th>
                            <th class="text-center">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($users)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fas fa-users-slash fa-3x mb-3"></i><br>
                                <h5>Chưa có người dùng nào</h5>
                            </td>
                        </tr>
                        <?php else: foreach($users as $u): 
                            $is_main_admin = $u['id'] == 1;
                            $role_badge = match($u['role']) {
                                'admin' => 'bg-danger',
                                'seller' => 'bg-info',
                                'customer' => 'bg-success',
                                default => 'bg-secondary'
                            };
                            $role_text = ucfirst($u['role']);
                        ?>
                        <tr <?= $is_main_admin ? 'class="table-warning"' : '' ?>>
                            <td class="text-center fw-bold">#<?= $u['id'] ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar me-3 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                                        <?= sanitize(mb_strtoupper(mb_substr(account_display_name($u),0,2))) ?>
                                    </div>
                                    <div>
                                        <strong><?= sanitize(account_display_name($u)) ?></strong>
                                        <?php if($is_main_admin): ?><span class="badge bg-warning text-dark ms-2">Admin chính</span><?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td><?= sanitize(account_public_email($u)) ?></td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="csrf" value="<?= getCsrfToken() ?>">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <select name="role" class="form-select form-select-sm <?= $role_badge ?> text-white" style="width:130px;" <?= $is_main_admin ? 'disabled' : '' ?> onchange="this.form.submit()">
                                        <option value="customer" <?= $u['role']=='customer'?'selected':'' ?>>Khách hàng</option>
                                        <option value="seller" <?= $u['role']=='seller'?'selected':'' ?>>Người bán</option>
                                        <option value="admin" <?= $u['role']=='admin'?'selected':'' ?>>Admin</option>
                                    </select>
                                </form>
                                <span class="badge <?= $role_badge ?> ms-2"><?= $role_text ?></span>
                            </td>
                            <td><small class="text-muted"><?= date('d/m/Y H:i', strtotime($u['created_at'])) ?></small></td>
                            <td class="text-center">
                                <a href="user_detail.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary" title="Xem hồ sơ">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if(!$is_main_admin): ?>
                                <form method="post" class="d-inline" onsubmit="return confirm('Xác nhận thao tác này?')"><input type="hidden" name="csrf" value="<?= getCsrfToken() ?>"><input type="hidden" name="delete" value="<?= (int)$u['id'] ?>"><button class="btn btn-sm btn-outline-danger">Xóa</button></form>
                                <?php else: ?>
                                <span class="text-muted small">Bảo vệ</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if($total_pages > 1): ?>
        <div class="card-footer bg-light">
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>">Trước</a>
                    </li>
                    <?php for($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>">Sau</a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.avatar {
    font-weight: bold;
    font-size: 0.9rem;
}
.table tbody tr:hover {
    background-color: rgba(25, 118, 210, 0.05) !important;
}
.form-select:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}
</style>

<?php include 'includes/admin_footer.php'; ?>