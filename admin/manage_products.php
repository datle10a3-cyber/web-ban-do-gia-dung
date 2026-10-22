<?php
require_once __DIR__.'/../includes/init.php';
if(!isAdmin()){http_response_code(403);header('Location: ../index.php');exit;}
$page_title = "Quản lý sản phẩm";

$success = $_SESSION['admin_success']??'';$error=$_SESSION['admin_error']??'';unset($_SESSION['admin_success'],$_SESSION['admin_error']);
$is_edit = isset($_GET['edit']) && ($edit_id = (int)$_GET['edit']) > 0;
$product = ['images' => '[]', 'flash_sale_end' => null];
$variants = [];

if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!verifyCsrfToken($_POST['csrf']??null)){http_response_code(403);$error='Phiên đã hết hạn.';}
    else{try{require_once __DIR__.'/../includes/product_admin.php';$savedId=admin_save_product($pdo,$is_edit?$edit_id:0,$_POST,$_FILES);header('Location: ?edit='.$savedId.'&success=1',true,303);exit;}catch(Throwable $e){http_response_code(422);$error=$e instanceof RuntimeException&&!($e instanceof PDOException)?$e->getMessage():'Chưa thể lưu sản phẩm.';}}
}

$search = trim($_GET['search'] ?? '');
$where  = $search ? "WHERE p.name LIKE :s1 OR c.name LIKE :s2 OR b.name LIKE :s3" : "";
$params = $search ? [':s1'=>"%$search%",':s2'=>"%$search%",':s3'=>"%$search%"] : [];

$stmt = $pdo->prepare("
    SELECT p.*, c.name AS cat_name, b.name AS brand_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    LEFT JOIN brands b ON p.brand_id = b.id 
    $where ORDER BY p.id DESC
");
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$brands     = $pdo->query("SELECT * FROM brands ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

if ($is_edit) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$edit_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    if ($product) {
        $stmt = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ? ORDER BY id");
        $stmt->execute([$edit_id]);
        $variants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $error = "Sản phẩm không tồn tại!";
        $is_edit = false;
    }
}
?>
<?php include 'includes/admin_header.php'; ?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0"><i class="fas fa-box text-primary me-2"></i>Quản lý Sản phẩm</h2>
        <span class="badge bg-primary fs-5">Tổng: <?= count($products) ?> sản phẩm</span>
    </div>

    <?php if(!empty($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            Thao tác thành công!
        </div>
    <?php endif; ?>
    <?php if($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?=$success?>
        </div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?=$error?>
        </div>
    <?php endif; ?>

    <!-- FORM THÊM / SỬA SẢN PHẨM -->
    <div class="card shadow-lg border-0 mb-5">
        <div class="card-header bg-gradient bg-primary text-white py-3">
            <h4 class="mb-0"><?= $is_edit ? "SỬA SẢN PHẨM ID #$edit_id" : "THÊM SẢN PHẨM MỚI" ?></h4>
        </div>
        <div class="card-body p-4">
            <form method="POST" enctype="multipart/form-data" autocomplete="off">
                <input type="hidden" name="csrf" value="<?=getCsrfToken()?>">
                <input type="hidden" name="current_image" value="<?=htmlspecialchars($product['image'] ?? '')?>">
                <input type="hidden" name="images_json" id="imagesJson" value='<?=htmlspecialchars($product['images'] ?? '[]')?>'>

                <div class="row g-4">

                    <!-- Tên & Giá -->
                    <div class="col-lg-8">
                        <label class="form-label fw-bold text-primary">Tên sản phẩm <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-lg" value="<?=htmlspecialchars($product['name'] ?? '')?>" required>
                    </div>
                    <div class="col-lg-2">
                        <label class="form-label fw-bold">Giá gốc (VNĐ) <span class="text-danger">*</span></label>
                        <input type="number" name="price" class="form-control form-control-lg" value="<?=$product['price'] ?? ''?>" step="1000" required>
                    </div>
                    <div class="col-lg-1">
                        <label class="form-label fw-bold">Giảm giá (%)</label>
                        <input type="number" name="discount" class="form-control" value="<?=$product['discount'] ?? '0'?>" min="0" max="99.99" step="0.01">
                    </div>
                    <div class="col-lg-1">
                        <label class="form-label fw-bold">Flash Sale</label>
                        <input type="datetime-local" name="flash_sale_end" class="form-control" value="<?=$product['flash_sale_end'] ?? ''?>">
                    </div>

                    <!-- Danh mục & Thương hiệu -->
                    <div class="col-lg-6">
                        <label class="form-label fw-bold">Danh mục <span class="text-danger">*</span></label>
                        <select name="category_id" class="form-select" required>
                            <option value="">-- Chọn danh mục --</option>
                            <?php foreach($categories as $c): ?>
                                <option value="<?=$c['id']?>" <?=($product['category_id']??0)==$c['id']?'selected':''?>>
                                    <?=htmlspecialchars($c['name'])?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-lg-6">
                        <label class="form-label fw-bold">Thương hiệu <span class="text-danger">*</span></label>
                        <select name="brand_id" class="form-select" required>
                            <option value="">-- Chọn thương hiệu --</option>
                            <?php foreach($brands as $b): ?>
                                <option value="<?=$b['id']?>" <?=($product['brand_id']??0)==$b['id']?'selected':''?>>
                                    <?=htmlspecialchars($b['name'])?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Ảnh chính -->
                    <div class="col-lg-6">
                        <label class="form-label fw-bold">Ảnh chính</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                        <?php if (!empty($product['image'])): ?>
                            <div class="mt-2">
                                <img src="../assets/images/products/<?=htmlspecialchars($product['image'])?>" class="rounded shadow" style="max-height:180px;">
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Gallery ảnh phụ -->
                    <div class="col-lg-6">
                        <label class="form-label fw-bold">Ảnh phụ (tối đa 10)</label>
                        <div id="gallery-dropzone" class="border border-3 border-dashed rounded-3 p-5 text-center bg-light" style="cursor:pointer;">
                            <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                            <p class="mb-0 text-muted">Kéo thả hoặc click để thêm ảnh</p>
                            <input type="file" id="gallery-input" multiple accept="image/*" style="display:none;">
                        </div>
                        <div id="gallery-preview" class="row mt-3 g-3"></div>
                    </div>

                    <!-- Nổi bật -->
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="featured" id="featured" <?=($product['featured']??0)==1?'checked':''?>>
                            <label class="form-check-label fw-bold text-success" for="featured">
                                Đánh dấu sản phẩm Nổi bật (hiển thị trang chủ)
                            </label>
                        </div>
                    </div>

                    <!-- Bán chạy nhất -->
<div class="col-12 mt-3">
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="bestseller" id="bestseller" <?= ($product['bestseller'] ?? 0) == 1 ? 'checked' : '' ?>>
        <label class="form-check-label fw-bold text-danger" for="bestseller">
            Đánh dấu sản phẩm Bán chạy nhất (hiển thị trang chủ)
        </label>
    </div>
</div>

                    <!-- Mô tả -->
                    <div class="col-12">
                        <label class="form-label fw-bold">Mô tả chi tiết</label>
                        <textarea name="description" rows="6" class="form-control"><?=htmlspecialchars($product['description'] ?? '')?></textarea>
                    </div>

                    <!-- BIẾN THỂ -->
                    <div class="col-12 mt-4">
                        <hr class="border-3">
                        <h4 class="text-primary fw-bold mb-3">Phân loại hàng (Biến thể)</h4>
                        <div id="variants-container" class="border rounded-3 p-4 bg-light">
                            <?php if ($is_edit && $variants): foreach($variants as $i => $v): ?>
                            <div class="row g-3 align-items-center mb-3 variant-row p-3 border rounded bg-white shadow-sm">
                                <div class="col-2">
                                    <input type="hidden" name="v_id[<?=$i?>]" value="<?= (int)($v['id']??0) ?>"><input type="text" name="v_sku[<?=$i?>]" class="form-control" placeholder="SKU" value="<?=htmlspecialchars($v['sku']??'')?>">
                                    <small class="text-muted">Tự sinh nếu để trống</small>
                                </div>
                                <div class="col-2">
                                    <input type="text" name="v_color[<?=$i?>]" class="form-control" placeholder="Màu sắc *" value="<?=htmlspecialchars($v['color']??'')?>" required>
                                </div>
                                <div class="col-2">
                                    <input type="text" name="v_capacity[<?=$i?>]" class="form-control" placeholder="Dung tích/Kích thước" value="<?=htmlspecialchars($v['capacity']??'')?>">
                                </div>
                                <div class="col-2">
                                    <input type="number" name="v_price_add[<?=$i?>]" class="form-control" placeholder="± Giá" value="<?=$v['price_add']??0?>">
                                </div>
                                <div class="col-1">
                                    <input type="number" name="v_stock[<?=$i?>]" class="form-control" placeholder="Tồn" value="<?=$v['stock']??0?>" required min="0">
                                </div>
                                <div class="col-2">
                                    <input type="file" name="v_image_new[<?=$i?>]" class="form-control form-control-sm" accept="image/*">
                                    <?php if (!empty($v['image'])): ?>
                                        <img src="../assets/images/products/<?=htmlspecialchars($v['image'])?>" 
                                             class="mt-2 rounded shadow-sm" 
                                             style="max-height:60px; object-fit:cover; border:2px solid #ddd;">
                                    <?php endif; ?>
                                </div>
                                <div class="col-1 text-end">
                                    <button type="button" class="btn btn-danger btn-sm rounded-circle remove-variant" title="Xóa variant">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; endif; ?>
                        </div>
                        <button type="button" id="add-variant" class="btn btn-success mt-3 shadow-sm">
                            <i class="fas fa-plus me-2"></i>Thêm phân loại mới
                        </button>
                    </div>

                    <div class="col-12 text-end mt-4">
                        <button type="submit" class="btn btn-primary btn-lg px-6 shadow">
                            <i class="fas fa-save me-2"></i>
                            <?= $is_edit ? "CẬP NHẬT SẢN PHẨM" : "THÊM SẢN PHẨM MỚI" ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- DANH SÁCH SẢN PHẨM -->
    <div class="card shadow-lg border-0">
        <div class="card-header bg-gradient bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Tất cả sản phẩm</h5>
            <input type="text" class="form-control w-30" placeholder="Tìm kiếm nhanh..." value="<?=htmlspecialchars($search)?>" 
                   onkeydown="if(event.key==='Enter') location='?search='+encodeURIComponent(this.value)">
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Ảnh</th>
                            <th>Tên sản phẩm</th>
                            <th>Giá</th>
                            <th>Danh mục</th>
                            <th>Thương hiệu</th>
                            <th>Kho</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($products as $p): 
                            $final_price = $p['price'] * (100 - $p['discount']) / 100;
                        ?>
                        <tr>
                            <td>#<?=$p['id']?></td>
                            <td>
                                <img src="../assets/images/products/<?=htmlspecialchars($p['image'] ?: 'default.jpg')?>" 
                                     class="rounded" style="width:70px;height:70px;object-fit:cover;">
                            </td>
                            <td>
                                <strong><?=htmlspecialchars($p['name'])?></strong><br>
                                <?= $p['featured'] ? '<span class="badge bg-warning text-dark me-1">Nổi bật</span>' : '' ?>
                                <?= $p['bestseller'] ? '<span class="badge bg-success me-1">Bán chạy</span>' : '' ?>
                                <?= (!empty($p['flash_sale_end']) && strtotime($p['flash_sale_end']) > time()) ? '<span class="badge bg-danger animate__animated animate__flash infinite">Flash Sale</span>' : '' ?>
                            </td>
                            <td>
                                <strong class="text-primary"><?=number_format($final_price)?>đ</strong>
                                <?php if($p['discount']>0): ?>
                                    <br><del class="text-muted small"><?=number_format($p['price'])?>đ</del>
                                <?php endif; ?>
                            </td>
                            <td><?=htmlspecialchars($p['cat_name'] ?? '—')?></td>
                            <td><?=htmlspecialchars($p['brand_name'] ?? '—')?></td>
                            <td><span class="badge bg-<?=($p['stock']>0?'success':'danger')?> fs-6"><?=$p['stock']?></span></td>
                            <td>
                                <?php if($p['stock'] <= 0): ?>
                                    <span class="text-danger fw-bold">Hết hàng</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="?edit=<?=$p['id']?>" class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i> Sửa
                                </a>
                                <form method="post" action="product_delete.php" class="d-inline" onsubmit="return confirm('Xác nhận xóa sản phẩm chưa có đơn hàng?')"><input type="hidden" name="csrf" value="<?=getCsrfToken()?>"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button class="btn btn-danger btn-sm">Xóa</button></form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script>
// Biến đếm variant
let variantIdx = <?= $is_edit && $variants ? count($variants) : 1000 ?>;

// Thêm variant mới
document.getElementById('add-variant').onclick = function() {
    const html = `
    <div class="row g-3 align-items-center mb-3 variant-row p-3 border rounded bg-white shadow-sm">
        <div class="col-2"><input type="text" name="v_sku[${variantIdx}]" class="form-control" placeholder="SKU (tự động nếu trống)"></div>
        <div class="col-2"><input type="text" name="v_color[${variantIdx}]" class="form-control" placeholder="Màu sắc *" required></div>
        <div class="col-2"><input type="text" name="v_capacity[${variantIdx}]" class="form-control" placeholder="Dung tích/Kích thước"></div>
        <div class="col-2"><input type="number" name="v_price_add[${variantIdx}]" class="form-control" value="0" placeholder="± Giá"></div>
        <div class="col-1"><input type="number" name="v_stock[${variantIdx}]" class="form-control" placeholder="Tồn" required></div>
        <div class="col-2"><input type="file" name="v_image_new[${variantIdx}]" class="form-control form-control-sm" accept="image/*"></div>
        <div class="col-1 text-end"><button type="button" class="btn btn-danger btn-sm rounded-circle remove-variant"><i class="fas fa-times"></i></button></div>
    </div>`;
    document.getElementById('variants-container').insertAdjacentHTML('beforeend', html);
    variantIdx++;
};

// Xóa variant
document.addEventListener('click', e => {
    if (e.target.closest('.remove-variant')) {
        e.target.closest('.variant-row').remove();
        updateImagesJson();
    }
});

// Gallery ảnh phụ
document.getElementById('gallery-dropzone').addEventListener('click', () => document.getElementById('gallery-input').click());
document.getElementById('gallery-dropzone').addEventListener('dragover', e => { e.preventDefault(); e.target.style.borderColor = '#0d6efd'; });
document.getElementById('gallery-dropzone').addEventListener('dragleave', e => e.target.style.borderColor = '#dee2e6');
document.getElementById('gallery-dropzone').addEventListener('drop', e => {
    e.preventDefault();
    e.target.style.borderColor = '#dee2e6';
    handleGalleryFiles(e.dataTransfer.files);
});
document.getElementById('gallery-input').addEventListener('change', e => handleGalleryFiles(e.target.files));

function handleGalleryFiles(files) {
    const preview = document.getElementById('gallery-preview');
    [...files].forEach(file => {
        if (!file.type.startsWith('image/')) return;
        const reader = new FileReader();
        reader.onload = e => {
            const div = document.createElement('div');
            div.className = 'col-3 position-relative';
            div.innerHTML = `
                <img src="${e.target.result}" class="img-thumbnail" style="height:120px;object-fit:cover;">
                <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 rounded-circle" onclick="this.parentElement.remove(); updateImagesJson();">×</button>
            `;
            preview.appendChild(div);
            updateImagesJson();
        };
        reader.readAsDataURL(file);
    });
}

function updateImagesJson() {
    const imgs = Array.from(document.querySelectorAll('#gallery-preview img')).map(img => img.src);
    document.getElementById('imagesJson').value = JSON.stringify(imgs);
}

document.addEventListener('DOMContentLoaded', () => {
    const saved = <?= json_encode(json_decode($product['images'] ?? '[]', true)) ?>;
    if (Array.isArray(saved) && saved.length > 0) {
        const preview = document.getElementById('gallery-preview');
        saved.forEach(filename => {
            if (!filename) return;
            const div = document.createElement('div');
            div.className = 'col-3 position-relative';
            div.innerHTML = `
                <img src="../assets/images/products/${filename}" class="img-thumbnail" style="height:120px;object-fit:cover;">
                <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 rounded-circle" onclick="this.parentElement.remove(); updateImagesJson();">×</button>
            `;
            preview.appendChild(div);
        });
        updateImagesJson();
    }
});
</script>

<?php include 'includes/admin_footer.php'; ?>