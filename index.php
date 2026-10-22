<?php 
require_once 'includes/init.php';
$page_title = 'Trang chủ'; 
 
include 'includes/header.php'; 

$page_title = 'Gia Dụng Shop - Uy Tín & Chất Lượng';
$base = "/web-ban-do-gia-dung";

// Lấy sản phẩm ngẫu nhiên 
$all_random = $pdo->query("
    SELECT p.*, b.name AS brand_name,
           (p.price * (100 - p.discount)/100) AS final_price
    FROM products p 
    LEFT JOIN brands b ON p.brand_id = b.id
    ORDER BY RAND() 

")->fetchAll(PDO::FETCH_ASSOC);

// Lấy wishlist của user (nếu đăng nhập)
$wishlist_ids = [];
if (!empty($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT product_id FROM wishlist WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $wishlist_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Chia đều 12 sản phẩm mỗi section (3 hàng x 4 cột)
$featured     = array_slice($all_random, 0,  12);
$bestsellers  = array_slice($all_random, 12, 12);
$newarrivals  = array_slice($all_random, 24);
?>

<link rel="icon" href="<?= $base ?>/assets/images/favicon.png">

<style>
    :root {
        --primary: #1976D2;
        --primary-dark: #0D47A1;
        --light: #E3F2FD;
        --danger: #dc3545;
        --success: #28a745;
    }
    body { background: var(--light); font-family: 'Segoe UI', sans-serif; }

    .section-title {
        font-size: 2.8rem; font-weight: 800; color: var(--primary-dark); text-align: center;
        margin: 4.5rem 0 3.5rem; position: relative;
    }
    .section-title::after {
        content: ''; position: absolute; bottom: -14px; left: 50%; transform: translateX(-50%);
        width: 120px; height: 7px; background: linear-gradient(90deg, var(--primary), #42a5f5); border-radius: 10px;
    }

    /* GRID 4 SẢN PHẨM MỖI HÀNG */
    .products-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 2.8rem;
    }
    @media (max-width: 1200px) { .products-grid { grid-template-columns: repeat(3, 1fr); gap: 2.4rem; } }
    @media (max-width: 992px) { .products-grid { grid-template-columns: repeat(2, 1fr); gap: 2.2rem; } }
    @media (max-width: 576px) { .products-grid { grid-template-columns: 1fr; gap: 1.8rem; } }

    /* CARD GIỐNG HỆT TRANG PRODUCTS */
    .product-card {
        height: 100%;
        display: flex;
        flex-direction: column;
        border-radius: 24px !important;
        overflow: hidden;
        box-shadow: 0 10px 35px rgba(25,118,210,0.2) !important;
        transition: all 0.4s ease !important;
        background: white;
    }
    .product-card:hover {
        transform: translateY(-18px);
        box-shadow: 0 35px 80px rgba(25,118,210,0.4) !important;
    }

    .product-img-wrapper {
        height: 280px;
        background: #fff;
        padding: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .product-img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        transition: transform 0.6s ease;
    }
    .product-card:hover .product-img { transform: scale(1.1); }

    .title-clamp {
        overflow: hidden;
        display: -webkit-box;
        -webkit-box-orient: vertical;
        line-height: 1.4;
        height: 3.2em;
    }

    .wishlist-btn {
        width: 56px !important;
        height: 56px !important;
        font-size: 1.7rem !important;
        box-shadow: 0 8px 25px rgba(220,53,69,0.4) !important;
    }

    /* Scrollbar đẹp */
    html { scroll-behavior: smooth; scroll-padding-top: 100px; }
    body { scrollbar-width: thin; scrollbar-color: #1976D2 #f1f8ff; }
    ::-webkit-scrollbar { width: 10px; }
    ::-webkit-scrollbar-track { background: #f1f8ff; border-radius: 10px; }
    ::-webkit-scrollbar-thumb { background: linear-gradient(135deg, #1976D2, #0D47A1); border-radius: 10px; }
    ::-webkit-scrollbar-thumb:hover { background: #0D47A1; }

    /* Sửa modal bị header che */
    .modal-dialog {
        margin-top: 80px !important; /* Đẩy modal xuống dưới header fixed */
    }
    @media (max-width: 768px) {
        .modal-dialog { margin-top: 70px !important; }
    }
    /* Hiệu ứng chung */
    .transition-all {
        transition: all 0.4s ease !important;
    }

    /* Variant button  */
    .variant-btn {
        border: 3px solid #e0e0e0 !important;
        border-radius: 16px;
        padding: 14px;
        text-align: center;
        cursor: pointer;
        transition: all 0.35s ease;
        background: white;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        position: relative;
        overflow: hidden;
    }

    .variant-btn:hover:not(.out-of-stock) {
        transform: translateY(-6px);
        box-shadow: 0 12px 30px rgba(25,118,210,0.25) !important;
        border-color: #90caf9 !important;
    }

    .variant-btn:hover:not(.out-of-stock) img {
        transform: scale(1.15);
    }

    .variant-btn img {
        transition: transform 0.4s ease;
        border-radius: 10px;
    }

    /* Khi được chọn */
    .variant-btn.active {
        border-color: #1976D2 !important;
        background: linear-gradient(135deg, #e3f2fd, #bbdefb) !important;
        box-shadow: 0 0 0 5px rgba(25,118,210,0.3), 0 12px 35px rgba(25,118,210,0.35) !important;
        transform: translateY(-4px) scale(1.02);
        animation: pulseGlow 1.5s infinite alternate;
    }

    /* Hiệu ứng pulse nhẹ khi chọn */
    @keyframes pulseGlow {
        from { box-shadow: 0 0 0 5px rgba(25,118,210,0.3), 0 12px 35px rgba(25,118,210,0.35); }
        to { box-shadow: 0 0 0 8px rgba(25,118,210,0.4), 0 15px 40px rgba(25,118,210,0.45); }
    }

    /* Out of stock */
    .variant-btn.out-of-stock {
        opacity: 0.5;
        cursor: not-allowed;
        filter: grayscale(80%);
    }
    .variant-btn.out-of-stock::after {
        content: "Hết hàng";
        position: absolute;
        top: 50%; left: 50%;
        transform: translate(-50%, -50%);
        background: rgba(220,53,69,0.95);
        color: white;
        font-weight: bold;
        padding: 6px 14px;
        border-radius: 8px;
        font-size: 0.9rem;
    }

    /* Ảnh chính trong modal khi thay đổi */
    #modalImg {
        border: 4px solid transparent;
        box-shadow: 0 8px 25px rgba(0,0,0,0.2);
    }
    #modalImg.selected {
        border-color: #1976D2;
        box-shadow: 0 12px 35px rgba(25,118,210,0.4);
    }

    /* Nút số lượng đẹp hơn */
    #modalMinus, #modalPlus {
        transition: all 0.3s ease;
    }
    #modalMinus:hover, #modalPlus:hover {
        background: #1976D2 !important;
        color: white !important;
        transform: scale(1.1);
    }
    #toast {
    position: fixed;
    bottom: 30px;
    left: 50%;
    transform: translateX(-50%);
    min-width: 300px;
    max-width: 90%;
    padding: 16px 24px;
    border-radius: 12px;
    color: white;
    font-weight: 600;
    font-size: 1rem;
    text-align: center;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    z-index: 10000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.4s ease;
    backdrop-filter: blur(10px);
}
#toast.show {
    opacity: 1;
    visibility: visible;
    transform: translateX(-50%) translateY(-10px);
}

/* Responsive cho mobile */
@media (max-width: 576px) {
    #toast {
        min-width: 80%;
        font-size: 0.95rem;
        padding: 14px 20px;
    }
}
</style>


<main class="container restored-home">
<?php $banner = ['title' => "Tiện nghi\ncho mọi nhà.", 'description' => 'Gia dụng thiết thực, chọn mua dễ dàng.', 'image' => 'banners/banner1.jpg', 'link' => BASE_URL . '/products/', 'button' => 'Mua sắm ngay', 'inside_container' => true, 'home' => true]; require __DIR__ . '/includes/page_banner.php'; ?>
<?php require __DIR__ . '/includes/home_categories.php'; ?>
<div class="container py-5">
    <!-- Section Tại sao chọn chúng tôi -->
    <section class="bg-white py-5 my-5 rounded-4 shadow-sm">
        <div class="container">
            <h2 class="section-title">Tại Sao Chọn Gia Dụng Shop?</h2>
            <div class="row text-center g-5">
                <div class="col-md-4">
                    <i class="fas fa-truck why-choose-us"></i>
                    <h5 class="mt-3 fw-bold">Giao Hàng Siêu Tốc</h5>
                    <p>2-4h nội thành • Miễn phí ship toàn quốc</p>
                </div>
                <div class="col-md-4">
                    <i class="fas fa-shield-alt why-choose-us"></i>
                    <h5 class="mt-3 fw-bold">Bảo Hành Vàng</h5>
                    <p>10 năm động cơ • Đổi mới 7-30 ngày</p>
                </div>
                <div class="col-md-4">
                    <i class="fas fa-credit-card why-choose-us"></i>
                    <h5 class="mt-3 fw-bold">Trả Góp 0%</h5>
                    <p>Không trả trước • Duyệt online 5 phút</p>
                </div>
            </div>
        </div>
    </section>
</div>

    <h2 class="section-title">Sản phẩm nổi bật</h2>
    <div class="products-grid mb-5">
        <?php foreach($featured as $p): 
            $is_wished = in_array($p['id'], $wishlist_ids);
            $img_file = $p['image'] ?? 'no-image.jpg';
        ?>
            <div>
                <div class="card product-card position-relative h-100">
                    <button class="btn wishlist-btn <?= $is_wished ? 'btn-danger' : 'btn-outline-danger' ?> position-absolute top-0 end-0 m-3 z-3" data-id="<?= $p['id'] ?>">
                        <i class="fa<?= $is_wished?'s':'r' ?> fa-heart"></i>
                    </button>

                    <?php if ($p['discount'] > 0): ?>
                        <div class="position-absolute top-0 start-0 text-white px-3 py-1 fw-bold z-3" style="background:#1976D2;font-size:0.9rem;">
                            -<?= $p['discount'] ?>%
                        </div>
                    <?php endif; ?>

                    <a href="<?= $base ?>/products/detail.php?id=<?= $p['id'] ?>" class="d-block">
                        <div class="product-img-wrapper">
                            <img src="<?= $base ?>/assets/images/products/<?= $img_file ?>" class="product-img" alt="<?= htmlspecialchars($p['name']) ?>">
                        </div>
                    </a>

                    <div class="card-body d-flex flex-column">
                        <span class="small fw-bold mb-2" style="color:#1976D2;"><?= htmlspecialchars($p['brand_name'] ?? 'Gia Dụng Shop') ?></span>
                        <h6 class="fw-bold title-clamp mb-3">
                            <a href="<?= $base ?>/products/detail.php?id=<?= $p['id'] ?>" class="text-decoration-none text-dark"><?= htmlspecialchars($p['name']) ?></a>
                        </h6>

                        <div class="mt-auto">
                            <div class="d-flex align-items-end gap-2 mb-3">
                                <span class="fw-bold fs-4" style="color:#1976D2;"><?= number_format($p['final_price'], 0, '', '.') ?>đ</span>
                                <?php if ($p['discount'] > 0): ?>
                                    <del class="text-muted small"><?= number_format($p['price'], 0, '', '.') ?>đ</del>
                                <?php endif; ?>
                            </div>

                            <div class="d-grid gap-2">
                                <a href="<?= $base ?>/products/detail.php?id=<?= $p['id'] ?>" class="btn text-white fw-bold" style="background:#1976D2;">Xem chi tiết</a>
                                <button class="btn fw-bold add-to-cart" data-id="<?= $p['id'] ?>" style="background:white;color:#FF9800;border:2px solid #FF9800;">
                                    Thêm vào giỏ
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <h2 class="section-title">Sản phẩm bán chạy nhất</h2>
    <div class="products-grid mb-5">
        <?php foreach($bestsellers as $p): 
            $is_wished = in_array($p['id'], $wishlist_ids);
            $img_file = $p['image'] ?? 'no-image.jpg';
        ?>
            <div>
                <div class="card product-card position-relative h-100">
                    <!-- Giống hệt phần trên, em copy để ngắn gọn -->
                    <button class="btn wishlist-btn <?= $is_wished ? 'btn-danger' : 'btn-outline-danger' ?> position-absolute top-0 end-0 m-3 z-3" data-id="<?= $p['id'] ?>">
                        <i class="fa<?= $is_wished?'s':'r' ?> fa-heart"></i>
                    </button>

                    <?php if ($p['discount'] > 0): ?>
                        <div class="position-absolute top-0 start-0 text-white px-3 py-1 fw-bold z-3" style="background:#1976D2;font-size:0.9rem;">
                            -<?= $p['discount'] ?>%
                        </div>
                    <?php endif; ?>

                    <a href="<?= $base ?>/products/detail.php?id=<?= $p['id'] ?>" class="d-block">
                        <div class="product-img-wrapper">
                            <img src="<?= $base ?>/assets/images/products/<?= $img_file ?>" class="product-img" alt="<?= htmlspecialchars($p['name']) ?>">
                        </div>
                    </a>

                    <div class="card-body d-flex flex-column">
                        <span class="small fw-bold mb-2" style="color:#1976D2;"><?= htmlspecialchars($p['brand_name'] ?? 'Gia Dụng Shop') ?></span>
                        <h6 class="fw-bold title-clamp mb-3">
                            <a href="<?= $base ?>/products/detail.php?id=<?= $p['id'] ?>" class="text-decoration-none text-dark"><?= htmlspecialchars($p['name']) ?></a>
                        </h6>

                        <div class="mt-auto">
                            <div class="d-flex align-items-end gap-2 mb-3">
                                <span class="fw-bold fs-4" style="color:#1976D2;"><?= number_format($p['final_price'], 0, '', '.') ?>đ</span>
                                <?php if ($p['discount'] > 0): ?>
                                    <del class="text-muted small"><?= number_format($p['price'], 0, '', '.') ?>đ</del>
                                <?php endif; ?>
                            </div>

                            <div class="d-grid gap-2">
                                <a href="<?= $base ?>/products/detail.php?id=<?= $p['id'] ?>" class="btn text-white fw-bold" style="background:#1976D2;">Xem chi tiết</a>
                                <button class="btn fw-bold add-to-cart" data-id="<?= $p['id'] ?>" style="background:white;color:#FF9800;border:2px solid #FF9800;">
                                    Thêm vào giỏ
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <h2 class="section-title">Sản phẩm mới nhất</h2>
    <div class="products-grid">
        <?php foreach($newarrivals as $p): 
            $is_wished = in_array($p['id'], $wishlist_ids);
            $img_file = $p['image'] ?? 'no-image.jpg';
        ?>
            <div>
                <div class="card product-card position-relative h-100">
                    <button class="btn wishlist-btn <?= $is_wished ? 'btn-danger' : 'btn-outline-danger' ?> position-absolute top-0 end-0 m-3 z-3" data-id="<?= $p['id'] ?>">
                        <i class="fa<?= $is_wished?'s':'r' ?> fa-heart"></i>
                    </button>

                    <?php if ($p['discount'] > 0): ?>
                        <div class="position-absolute top-0 start-0 text-white px-3 py-1 fw-bold z-3" style="background:#1976D2;font-size:0.9rem;">
                            -<?= $p['discount'] ?>%
                        </div>
                    <?php endif; ?>

                    <a href="<?= $base ?>/products/detail.php?id=<?= $p['id'] ?>" class="d-block">
                        <div class="product-img-wrapper">
                            <img src="<?= $base ?>/assets/images/products/<?= $img_file ?>" class="product-img" alt="<?= htmlspecialchars($p['name']) ?>">
                        </div>
                    </a>

                    <div class="card-body d-flex flex-column">
                        <span class="small fw-bold mb-2" style="color:#1976D2;"><?= htmlspecialchars($p['brand_name'] ?? 'Gia Dụng Shop') ?></span>
                        <h6 class="fw-bold title-clamp mb-3">
                            <a href="<?= $base ?>/products/detail.php?id=<?= $p['id'] ?>" class="text-decoration-none text-dark"><?= htmlspecialchars($p['name']) ?></a>
                        </h6>

                        <div class="mt-auto">
                            <div class="d-flex align-items-end gap-2 mb-3">
                                <span class="fw-bold fs-4" style="color:#1976D2;"><?= number_format($p['final_price'], 0, '', '.') ?>đ</span>
                                <?php if ($p['discount'] > 0): ?>
                                    <del class="text-muted small"><?= number_format($p['price'], 0, '', '.') ?>đ</del>
                                <?php endif; ?>
                            </div>

                            <div class="d-grid gap-2">
                                <a href="<?= $base ?>/products/detail.php?id=<?= $p['id'] ?>" class="btn text-white fw-bold" style="background:#1976D2;">Xem chi tiết</a>
                                <button class="btn fw-bold add-to-cart" data-id="<?= $p['id'] ?>" style="background:white;color:#FF9800;border:2px solid #FF9800;">
                                    Thêm vào giỏ
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</main>
<div class="modal fade" id="quickAddModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable"> <!-- Thêm scrollable -->
    <div class="modal-content rounded-4 shadow-lg border-0">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold">Chọn phân loại</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-3">
         <div class="text-center mb-4">
          <img id="modalImg" src="" class="rounded-3" style="width:120px;height:120px;object-fit:cover;">
          <h6 id="modalName" class="mt-3 mb-0 fw-bold"></h6>
          <p id="modalPrice" class="text-primary fs-5 fw-bold mb-0"></p>
        </div>
        <h6 class="fw-bold mb-3">Phân loại:</h6>
        <div id="modalVariants" class="d-flex flex-wrap gap-3 justify-content-center"></div>
        <div class="mt-4">
          <h6 class="fw-bold mb-3">Số lượng:</h6>
          <div class="d-flex align-items-center justify-content-center gap-3">
            <button class="btn btn-outline-secondary btn-lg" id="modalMinus">-</button>
            <input type="text" id="modalQty" value="1" class="form-control text-center" style="width:80px;" readonly>
            <button class="btn btn-outline-secondary btn-lg" id="modalPlus">+</button>
          </div>
        </div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-lg btn-primary px-5" id="modalAddToCart">Thêm vào giỏ</button>
      </div>
    </div>
  </div>
</div>



<div id="toast"></div>

<script src="<?= $base ?>/assets/js/bootstrap.bundle.min.js"></script>
<script>
let currentProductId = null;
let selectedVariantId = 0;
let selectedStock = 999;

document.querySelectorAll('.wishlist-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const isLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
        if (!isLoggedIn) {
            location.href = '<?= $base ?>/auth/login.php';
            return;
        }

        const id = this.dataset.id;
        const csrf = "<?= $_SESSION['csrf'] ?? '' ?>";

        this.disabled = true; 

        fetch('<?= $base ?>/user/wishlist_toggle.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'csrf=' + csrf + '&product_id=' + id
        })
        .then(r => r.json())
               .then(d => {
            if (d.success) {
                const added = d.added === true || d.action === 'added';

                
                this.classList.toggle('btn-danger', added);
                this.classList.toggle('btn-outline-danger', !added);
                this.innerHTML = added 
                    ? '<i class="fas fa-heart"></i>' 
                    : '<i class="far fa-heart"></i>';

               
                const badge = document.getElementById('wishlistCount');
                if (badge) {
                    badge.textContent = d.wishlist_count;
                    badge.style.display = d.wishlist_count > 0 ? 'inline-block' : 'none';
                    
                    
                    badge.classList.remove('bounce');
                    void badge.offsetWidth;  // trigger reflow
                    badge.classList.add('bounce');
                }

                
                showToast(
                    added ? '❤️ Đã thêm vào danh sách yêu thích' : '💔 Đã bỏ khỏi danh sách yêu thích',
                    added ? 'success' : 'info'
                );
            } else {
                showToast(d.message || 'Có lỗi xảy ra!', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Lỗi kết nối mạng!', 'error');
        })
        .finally(() => {
            this.disabled = false;
        });
    });
});


document.querySelectorAll('.add-to-cart').forEach(btn => {
    btn.addEventListener('click', function(e){
        e.preventDefault(); 
        e.stopPropagation();
        currentProductId = this.dataset.id;

        fetch('<?= $base ?>/ajax/get_variants.php?id=' + currentProductId)
        .then(r => r.json())
        .then(data => {
            if(!data.success){ showToast('Lỗi tải dữ liệu!', 'danger'); return; }

            document.getElementById('modalImg').src = '<?= $base ?>/assets/images/products/' + data.image;
            document.getElementById('modalName').textContent = data.name;
            document.getElementById('modalPrice').textContent = parseInt(data.final_price).toLocaleString('vi-VN') + 'đ';

            const container = document.getElementById('modalVariants');
            container.innerHTML = '';

            if(data.variants.length <= 1 && data.variants[0]?.id == 0){
                addToCart(currentProductId, 0, 1);
                return;
            }

            data.variants.forEach((v, i) => {
                const div = document.createElement('div');
                div.className = 'variant-btn position-relative';
                if(v.stock <= 0) div.classList.add('out-of-stock');

                div.innerHTML = `
                    <img src="<?= $base ?>/assets/images/products/${v.image}" class="rounded mb-2" style="width:70px;height:70px;object-fit:cover;">
                    <div class="small fw-bold">${v.color}</div>
                    ${v.capacity ? `<small class="d-block">${v.capacity}</small>` : ''}
                    ${v.price_add > 0 ? `<small class="text-danger d-block">+${v.price_add.toLocaleString()}đ</small>` : ''}
                    <small class="text-success fw-bold mt-1 d-block">Còn ${v.stock} sp</small>
                    ${v.stock <= 0 ? `<div class="position-absolute top-50 start-50 translate-middle text-danger fw-bold bg-white px-2 rounded">Hết hàng</div>` : ''}
                `;

                if(v.stock > 0){
                    div.onclick = () => {
                        document.getElementById('modalImg').src = '<?= $base ?>/assets/images/products/' + v.image;
                        const newPrice = (parseInt(data.final_price) + parseInt(v.price_add || 0));
                        document.getElementById('modalPrice').textContent = newPrice.toLocaleString('vi-VN') + 'đ';
                        selectedStock = v.stock;
                        document.getElementById('modalQty').value = 1;
                        document.querySelectorAll('#modalVariants .variant-btn').forEach(x => x.classList.remove('active'));
                        div.classList.add('active');
                        selectedVariantId = v.id;
                    };
                }
                container.appendChild(div);
                if(i === 0 && v.stock > 0){
                    div.click();
                }
            });

            document.getElementById('modalQty').value = 1;
            new bootstrap.Modal(document.getElementById('quickAddModal')).show();
        })
        .catch(() => showToast('Lỗi kết nối!', 'danger'));
    });
});

document.getElementById('modalPlus').onclick = () => {
    let q = parseInt(document.getElementById('modalQty').value);
    if(q < selectedStock) document.getElementById('modalQty').value = ++q;
};
document.getElementById('modalMinus').onclick = () => {
    let q = parseInt(document.getElementById('modalQty').value);
    if(q > 1) document.getElementById('modalQty').value = --q;
};
document.getElementById('modalAddToCart').onclick = () => {
    const qty = parseInt(document.getElementById('modalQty').value);
    if(qty > selectedStock){
        showToast(`Chỉ còn ${selectedStock} sản phẩm!`, 'danger');
        return;
    }
    addToCart(currentProductId, selectedVariantId, qty);
    bootstrap.Modal.getInstance(document.getElementById('quickAddModal')).hide();
};

function addToCart(p, v, q){
    const fd = new URLSearchParams();
    fd.append('product_id', p);
    fd.append('variant_id', v);
    fd.append('quantity', q);
    fd.append('csrf', '<?= $_SESSION['csrf'] ?>');
    fd.append('ajax', '1');

    fetch('<?= $base ?>/cart/add.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: fd
    })
    .then(r => r.json())
    .then(d => {
        if(d.success){
            const badge = document.getElementById('cartCount');
            if(badge){
                badge.textContent = d.cart_count;
                badge.style.display = d.cart_count > 0 ? 'block' : 'none';
                badge.classList.add('bounce');
                setTimeout(() => badge.classList.remove('bounce'), 600);
            }
            showToast(`Đã thêm ${q} sản phẩm vào giỏ hàng!`, 'success');
        } else {
            showToast(d.message || 'Không thể thêm vào giỏ!', 'danger');
        }
    });
}

function showToast(msg, type='success'){
    const t = document.getElementById('toast');
    t.innerHTML = '<i class="fas fa-check-circle me-2"></i>' + msg;
    t.style.background = type === 'success' ? '#28a745' : type === 'danger' ? '#dc3545' : '#17a2b8';
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3500);
}

const style = document.createElement('style');
style.textContent = `
    @keyframes bounce { 0%,100% {transform:scale(1)} 50% {transform:scale(1.5)} }
    .bounce { animation: bounce .6s }
`;
document.head.appendChild(style);
</script>

<?php include 'includes/footer.php'; ?>
