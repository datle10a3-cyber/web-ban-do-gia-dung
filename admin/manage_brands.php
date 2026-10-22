<?php
require_once __DIR__.'/../includes/init.php';
if(!isAdmin()){http_response_code(403);header('Location: ../index.php');exit;}

$page_title = "Quản lý Thương hiệu";

// Xử lý thêm/sửa
$success = $error = '';
if($_POST && !isset($_POST['del']) && verifyCsrfToken($_POST['csrf']??'')){
    $name = trim($_POST['name'] ?? '');
    $id = (int)($_POST['id'] ?? 0);
    
    if($name === '') {
        $error = "Vui lòng nhập tên thương hiệu!";
    } else {
        try {
            if($id) {
                $pdo->prepare("UPDATE brands SET name = ? WHERE id = ?")->execute([$name, $id]);
                $success = "Cập nhật thương hiệu thành công!";
            } else {
                $pdo->prepare("INSERT INTO brands (name) VALUES (?)")->execute([$name]);
                $success = "Thêm thương hiệu mới thành công!";
            }
        } catch(Exception $e) {
            $error = "Lỗi: " . $e->getMessage();
        }
    }
}

// Xử lý xóa
if(isset($_POST['del']) && verifyCsrfToken($_POST['csrf']??'')){
    $del_id = (int)$_POST['del'];
    try {
        $q=$pdo->prepare('SELECT COUNT(*) FROM products WHERE brand_id=?');$q->execute([$del_id]);if($q->fetchColumn()){throw new RuntimeException('Thương hiệu còn sản phẩm.');}
        $stmt = $pdo->prepare("DELETE FROM brands WHERE id = ?");
        $stmt->execute([$del_id]);
        $success = "Xóa thương hiệu thành công!";
    } catch(Exception $e) {
        $error = "Không thể xóa (có thể đang có sản phẩm thuộc thương hiệu này)";
    }
}

// Lấy danh sách + tìm kiếm
$search = trim($_GET['search'] ?? '');
$where = $search ? "WHERE name LIKE ?" : "";
$params = $search ? ["%$search%"] : [];

$stmt = $pdo->prepare("SELECT * FROM brands $where ORDER BY name ASC");
$stmt->execute($params);
$brands = $stmt->fetchAll();
$total_brands = count($brands);

include 'includes/admin_header.php'; 
?>

<div class="container my-5">
    <!-- Top Bar -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0 text-primary fw-bold">
                <i class="fas fa-copyright me-2"></i>Quản lý Thương hiệu
            </h2>
            <small class="text-muted">Tổng: <strong><?= $total_brands ?></strong> thương hiệu</small>
        </div>
        <a href="../index.php" target="_blank" class="btn btn-outline-success btn-sm">
            <i class="fas fa-home me-1"></i>Trang chủ
        </a>
    </div>

    <!-- Alert -->
    <?php if($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Form Thêm/Sửa -->
        <div class="col-lg-5 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-plus-circle me-2"></i>
                        <span id="formTitle">Thêm thương hiệu mới</span>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="brandForm">
                        <input type="hidden" name="csrf" value="<?= getCsrfToken() ?>">
                        <input type="hidden" name="id" id="editId" value="">

                        <div class="mb-3">
                            <label class="form-label fw-bold">Tên thương hiệu</label>
                            <input type="text" name="name" id="editName" class="form-control" 
                                   placeholder="Nhập tên thương hiệu..." required>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-fill">
                                <i class="fas fa-save me-2"></i>
                                <span id="btnSubmitText">Thêm thương hiệu</span>
                            </button>
                            <button type="button" id="btnCancel" class="btn btn-secondary" style="display:none;">
                                <i class="fas fa-times me-2"></i>Hủy
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Danh sách -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>Danh sách thương hiệu
                    </h5>
                    <form method="GET" class="d-flex">
                        <input type="text" name="search" class="form-control form-control-sm" 
                               placeholder="Tìm kiếm..." value="<?= htmlspecialchars($search) ?>">
                        <button class="btn btn-light btn-sm ms-2">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                <div class="card-body p-0">
                    <?php if(empty($brands)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>Chưa có thương hiệu nào</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4" width="8%">STT</th>
                                        <th>Tên thương hiệu</th>
                                        <th width="20%" class="text-center">Sản phẩm</th>
                                        <th width="25%" class="text-center">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $stt = 1;
                                    foreach($brands as $b): 
                                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE brand_id = ?");
                                        $stmt->execute([$b['id']]);
                                        $product_count = $stmt->fetchColumn();
                                    ?>
                                    <tr>
                                        <td class="ps-4 text-muted"><?= $stt++ ?></td>
                                        <td class="fw-medium"><?= htmlspecialchars($b['name']) ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary"><?= $product_count ?></span>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-warning edit-brand-btn"
                                                    data-id="<?= $b['id'] ?>"
                                                    data-name="<?= htmlspecialchars($b['name'], ENT_QUOTES) ?>">
                                                <i class="fas fa-edit"></i> Sửa
                                            </button>
                                            <form method="post" class="d-inline" onsubmit="return confirm('Xác nhận thao tác này?')"><input type="hidden" name="csrf" value="<?= getCsrfToken() ?>"><input type="hidden" name="del" value="<?= (int)$b['id'] ?>"><button class="btn btn-sm btn-outline-danger">Xóa</button></form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
.document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.edit-brand-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('editId').value = this.dataset.id;
            document.getElementById('editName').value = this.dataset.name;
            document.getElementById('formTitle').textContent = 'Chỉnh sửa thương hiệu';
            document.getElementById('btnSubmitText').textContent = 'Cập nhật';
            document.getElementById('btnCancel').style.display = 'inline-block';
            document.getElementById('editName').focus();
        });
    });

    document.getElementById('btnCancel').addEventListener('click', function () {
        document.getElementById('editId').value = '';
        document.getElementById('editName').value = '';
        document.getElementById('formTitle').textContent = 'Thêm thương hiệu mới';
        document.getElementById('btnSubmitText').textContent = 'Thêm thương hiệu';
        this.style.display = 'none';
    });
})
</script>

<?php include 'includes/admin_footer.php'; ?>