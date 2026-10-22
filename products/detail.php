<?php 
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/header.php';

$base = '/web-ban-do-gia-dung';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { 
    echo '<div class="container py-5 text-center"><h3 class="text-danger">Sản phẩm không tồn tại!</h3></div>'; 
    require_once __DIR__ . '/../includes/footer.php'; 
    exit; 
}

$stmt = $pdo->prepare("
    SELECT p.*, b.name AS brand_name, 
           (p.price * (100 - p.discount) / 100) AS base_price 
    FROM products p 
    LEFT JOIN brands b ON p.brand_id = b.id 
    WHERE p.id = ?
");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$product) { 
    echo '<div class="container py-5 text-center"><h3 class="text-danger">Sản phẩm không tồn tại!</h3></div>'; 
    require_once __DIR__ . '/../includes/footer.php'; 
    exit; 
}

$variantsStmt = $pdo->prepare("
    SELECT id, color, capacity, price_add, stock, image 
    FROM product_variants 
    WHERE product_id = ? AND stock > 0 
    ORDER BY color, capacity
");
$variantsStmt->execute([$id]);
$variants = $variantsStmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($variants)) {
    $variants = [[
        'id' => 0,
        'color' => 'Mặc định',
        'capacity' => '',
        'price_add' => 0,
        'stock' => $product['stock'] ?? 999,
        'image' => $product['image'] ?? 'no-image.jpg'
    ]];
}

$mainProductImage = $product['image'] ?? 'no-image.jpg';
$galleryImages = [$mainProductImage];

if (!empty($product['images'])) {
    $extra = json_decode($product['images'], true);
    if (is_array($extra)) {
        $galleryImages = array_merge($galleryImages, $extra);
    }
}
$galleryImages = array_unique($galleryImages);
$galleryImages = array_slice($galleryImages, 0, 10);
if (empty($galleryImages)) $galleryImages = ['no-image.jpg'];

$isWished = false;
if (!empty($_SESSION['user_id'])) {
    $checkWish = $pdo->prepare("SELECT 1 FROM wishlist WHERE user_id = ? AND product_id = ?");
    $checkWish->execute([$_SESSION['user_id'], $id]);
    $isWished = (bool)$checkWish->fetchColumn();
}

$related = $pdo->query("SELECT id, name, image, price, discount, (price * (100 - discount)/100) AS final_price FROM products WHERE id != $id ORDER BY RAND() LIMIT 10")->fetchAll();

$user_id = $_SESSION['user_id'] ?? 0;
$avg = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total FROM product_reviews WHERE product_id = ? AND is_approved=1");
$avg->execute([$id]);
$ratingInfo = $avg->fetch();
$avgRating = round($ratingInfo['avg_rating'] ?? 0, 1);
$totalReviews = $ratingInfo['total'];

$reviews = $pdo->prepare("SELECT r.*, COALESCE(u.display_name,'Khách hàng') as user_name, r.created_at FROM product_reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = ? AND r.is_approved=1 ORDER BY r.created_at DESC LIMIT 50");
$reviews->execute([$id]);
$reviews = $reviews->fetchAll();

$canReview = false;
if ($user_id) {
    $check = $pdo->prepare("SELECT 1 FROM orders o JOIN order_items oi ON o.id = oi.order_id WHERE o.user_id = ? AND oi.product_id = ? AND o.status IN ('delivered','completed') AND NOT EXISTS (SELECT 1 FROM product_reviews pr WHERE pr.user_id = ? AND pr.product_id = ? AND pr.order_id = o.id) LIMIT 1");
    $check->execute([$user_id, $id, $user_id, $id]);
    $canReview = (bool)$check->fetch();
}
?>

<style>
    #reviewList p.mt-2 {
    margin-left: 0 !important;
    padding-left: 0;
}
    body { background: #E3F2FD; }
    .rounded-4 { border-radius: 1rem !important; }
    .hover-lift:hover { transform: translateY(-8px); transition: .35s; }
    .line-clamp-2 { 
        display: -webkit-box; 
        -webkit-line-clamp: 2; 
        -webkit-box-orient: vertical; 
        overflow: hidden;
        line-clamp: 2;
    }
    #toast { 
        position: fixed; 
        top: 30px; 
        right: 30px; 
        z-index: 9999; 
        padding: 16px 28px; 
        border-radius: 12px; 
        color: white; 
        font-weight: bold; 
        box-shadow: 0 10px 30px rgba(0,0,0,0.3); 
        display: none;
    }
    #toast.show { display: block; animation: fade 4s forwards; }
    @keyframes fade { 0%{opacity:0} 15%{opacity:1} 85%{opacity:1} 100%{opacity:0} }
    .thumb-img { cursor:pointer; border:2px solid transparent; opacity:0.8; transition:.3s; }
    .thumb-img.active { border-color:#1976D2; opacity:1; transform:scale(1.1); }
    .thumb-img:hover { opacity:1; }

    .variant-btn {
        border: 2px solid #ddd;
        padding: 12px;
        margin: 6px 8px 6px 0;
        border-radius: 12px;
        cursor: pointer;
        transition: all .2s;
        width: 135px;
        text-align: center;
        background: #fff;
    }
    .variant-btn.active {
        border-color: #1976D2 !important;
        box-shadow: 0 0 0 4px #e3f2fd;
        background: #e3f2fd;
    }
    .variant-btn img {
        width: 70px; height: 70px; object-fit: cover; border-radius: 8px; margin-bottom: 8px;
    }
    .variant-btn.out-of-stock {
        opacity: 0.5; cursor: not-allowed; position: relative;
    }
    .variant-btn.out-of-stock::after {
        content: "Hết hàng"; position: absolute; top: 50%; left: 0; right: 0; color: red; font-weight: bold; background: rgba(255,255,255,0.9);
    }
    #addToCartBtn, #buyNowBtn { opacity: 0.6; pointer-events: none; }
    .variant-selected #addToCartBtn, .variant-selected #buyNowBtn { opacity: 1; pointer-events: auto; }
    
    .wishlist-btn {
        position: absolute;
        top: 20px;
        right: 20px;
        width: 56px;
        height: 56px;
        background: white;
        border-radius: 50%;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 100;
        transition: all .3s ease;
    }
    .wishlist-btn:hover { transform: scale(1.15); }
    .wishlist-btn i {
        font-size: 26px;
        transition: all .3s;
    }
    .wishlist-btn.wished i {
        color: #e74c3c;
        animation: heartbeat 1.2s ease-in-out;
    }
    @keyframes heartbeat {
        0% { transform: scale(1); }
        20% { transform: scale(1.3); }
        40% { transform: scale(1); }
        60% { transform: scale(1.3); }
        100% { transform: scale(1); }
    }
    @keyframes bounce { 0%,100%{transform:scale(1)} 50%{transform:scale(1.5)} }
    .bounce { animation: bounce 0.6s; }
    html {
    scroll-behavior: smooth;
    scroll-padding-top: 100px;
}

body {
    scrollbar-width: thin;
    scrollbar-color: #1976D2 #f1f8ff;
}

::-webkit-scrollbar {
    width: 10px;
}
::-webkit-scrollbar-track {
    background: #f1f8ff;
    border-radius: 10px;
}
::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #1976D2, #0D47A1);
    border-radius: 10px;
}
::-webkit-scrollbar-thumb:hover {
    background: #0D47A1;
}
    :root {
        --primary: #1976D2;
        --primary-dark: #0D47A1;
        --light: #E3F2FD;
        --danger: #dc3545;
        --success: #28a745;
    }
    body { background: var(--light); font-family: 'Segoe UI', sans-serif; }

    .hero-bg {
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        z-index: -1;
    }
    .hero-bg img {
        height: 100%;
        width: 100%;
        object-fit: cover;
        filter: brightness(0.65);
    }
    .btn-outline-white {
        color: white;
        border-color: white;
    }
    .btn-outline-white:hover {
        background: white;
        color: #1976D2;
    }
    @media (max-width: 768px) {
        .display-3 { font-size: 2.8rem; }
        .fs-2 { font-size: 1.5rem !important; }
    }
html {
    scroll-behavior: smooth;
    scroll-padding-top: 100px;
}

body {
    scrollbar-width: thin;
    scrollbar-color: #1976D2 #f1f8ff;
}

::-webkit-scrollbar {
    width: 10px;
}
::-webkit-scrollbar-track {
    background: #f1f8ff;
    border-radius: 10px;
}
::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #1976D2, #0D47A1);
    border-radius: 10px;
}
::-webkit-scrollbar-thumb:hover {
    background: #0D47A1;
}
</style>

<div class="container py-5 position-relative">

    <button type="button" class="btn btn-outline-secondary mb-3" id="backBtn">
        <i class="bi bi-arrow-left"></i> Quay lại
    </button>

    <div class="wishlist-btn <?= $isWished ? 'wished' : '' ?>" id="wishlistBtn">
        <i class="bi <?= $isWished ? 'bi-heart-fill text-danger' : 'bi-heart' ?>"></i>
    </div>

    <div class="row g-5">

        <div class="col-lg-5">
            <div class="bg-white rounded-4 shadow-sm p-3 mb-3">
                <img id="mainImg" src="<?= $base ?>/assets/images/products/<?= htmlspecialchars($mainProductImage) ?>" 
                     class="w-100 rounded-4" style="height:500px;object-fit:cover;cursor:zoom-in;">
            </div>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <?php foreach($galleryImages as $i => $img): ?>
                    <img src="<?= $base ?>/assets/images/products/<?= htmlspecialchars($img) ?>"
                         class="thumb-img <?= $i===0?'active':'' ?> rounded"
                         style="width:90px;height:80px;object-fit:cover;cursor:pointer;" 
                         onclick="document.getElementById('mainImg').src=this.src; 
                                  document.querySelectorAll('.thumb-img').forEach(e=>e.classList.remove('active')); 
                                  this.classList.add('active')">
                <?php endforeach; ?>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="bg-white rounded-4 shadow-sm p-4 variant-container" id="productInfo">
                <h3 class="fw-bold text-dark mb-3"><?= htmlspecialchars($product['name']) ?></h3>

               <div class="text-warning fs-4 mb-2">
    <?php 
    $displayRating = ($totalReviews > 0) ? $avgRating : 5; 
    for($i=1; $i<=5; $i++): 
    ?>
        <i class="bi <?= $i <= $displayRating ? 'bi-star-fill' : 'bi-star' ?>"></i>
    <?php endfor; ?>
    <span class="text-dark fs-5 ms-2"><?= $totalReviews > 0 ? number_format($avgRating, 1) : '5.0' ?></span>
    <span class="text-secondary fs-6">(<?= $totalReviews > 0 ? "$totalReviews đánh giá" : 'Chưa có đánh giá' ?>)</span>
</div>

                <?php if (count($variants) > 1): ?>
                <div class="mb-4">
                    <h5 class="fw-bold mb-3">Chọn phân loại:</h5>
                    <div class="d-flex flex-wrap">
                        <?php foreach($variants as $v): 
                            $disabled = $v['stock'] <= 0;
                        ?>
                            <div class="variant-btn <?= $disabled?'out-of-stock':'' ?>"
                                 data-id="<?= $v['id'] ?>"
                                 data-price-add="<?= $v['price_add'] ?>"
                                 data-stock="<?= $v['stock'] ?>"
                                 data-image="<?= $v['image'] ?>"
                                 data-name="<?= htmlspecialchars($v['color'] . ($v['capacity'] ? ' - ' . $v['capacity'] : '')) ?>">
                                <img src="<?= $base ?>/assets/images/products/<?= $v['image'] ?>" alt="">
                                <div class="small fw-bold"><?= $v['color'] ?></div>
                                <?php if ($v['capacity']): ?><small><?= $v['capacity'] ?></small><?php endif; ?>
                                <?php if ($v['price_add'] > 0): ?>
                                    <small class="text-danger d-block">+<?= number_format($v['price_add'],0,'','.') ?>đ</small>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="bg-light p-4 rounded-4 mb-4 d-flex align-items-center gap-4">
                    <h2 class="text-primary fw-bold m-0" id="displayPrice">Vui lòng chọn phân loại</h2>
                </div>

                <div class="mb-4 d-flex align-items-center gap-4">
                    <span class="fw-bold">Số lượng:</span>
                    <div class="input-group" style="width:160px;">
                        <button class="btn btn-outline-secondary fs-4" id="minus" disabled>-</button>
                        <input id="qty" type="text" class="form-control text-center fw-bold fs-4" value="1" readonly disabled>
                        <button class="btn btn-outline-secondary fs-4" id="plus" disabled>+</button>
                    </div>
                    <span class="text-secondary ms-3" id="stockInfo">Vui lòng chọn phân loại</span>
                </div>

                <div class="d-flex gap-3">
                    <button id="addToCartBtn" class="btn btn-lg fw-bold text-white" style="background:#1976D2;">
                        Thêm vào giỏ
                    </button>
                    <button id="buyNowBtn" class="btn btn-lg fw-bold text-white" style="background:#FF9800;" disabled>
                        Mua ngay
                        <?php if (empty($_SESSION['user_id'])): ?>
    <div class="text-center mt-3">
        <small class="text-muted">
            <i class="fas fa-info-circle me-1"></i>
            Bạn cần <a href="../auth/login.php" class="text-primary fw-bold">đăng nhập</a> để mua ngay</a>
        </small>
    </div>
<?php endif; ?>
                    </button>
                </div>

                <div class="alert alert-info mt-4" id="selectedAlert" style="display:none;">
                    <strong>Đã chọn:</strong> <span id="selectedVariant"></span>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-4 shadow-sm p-5 mt-5">
        <h4 class="fw-bold text-primary mb-4 text-center">CHI TIẾT SẢN PHẨM</h4>
        <div class="row g-3 fs-5">
            <div class="col-md-4 text-secondary">Thương thương hiệu</div>
            <div class="col-md-8 fw-bold"><?= $product['brand_name'] ?? 'Không rõ' ?></div>
            <div class="col-md-4 text-secondary">Bảo hành</div>
            <div class="col-md-8 fw-bold text-success">12 tháng</div>
            <div class="col-md-4 text-secondary">Xuất xứ</div>
            <div class="col-md-8 fw-bold">Việt Nam</div>
        </div>
    </div>

    <div class="bg-white rounded-4 shadow-sm p-5 my-5">
        <h4 class="fw-bold text-primary mb-4 text-center">MÔ TẢ SẢN PHẨM</h4>
        <p class="fs-5 text-dark lh-lg">
            <?= $product['description'] ? nl2br(htmlspecialchars($product['description'])) : 'Sản phẩm chất lượng cao, thiết kế hiện đại, tiết kiệm điện.' ?>
        </p>
    </div>

    <div class="bg-white rounded-4 shadow-sm p-5 my-5" id="review-section">
    <h4 class="fw-bold text-primary mb-4 text-center">ĐÁNH GIÁ SẢN PHẨM (<?= $totalReviews ?>)</h4>
        <div class="row justify-content-center text-center mb-5">
            <div class="col-auto">
              <div class="display-4 fw-bold text-primary"><?= $totalReviews > 0 ? number_format($avgRating, 1) : '5.0' ?>/5</div>
<div class="text-warning fs-2">
    <?php 
    $displayRating = ($totalReviews > 0) ? $avgRating : 5;
    for($i=1; $i<=5; $i++): 
    ?>
        <i class="bi <?= $i <= $displayRating ? 'bi-star-fill' : 'bi-star' ?>"></i>
    <?php endfor; ?>
</div>
                <small class="text-secondary"><?= $totalReviews ?> đánh giá</small>
            </div>
        </div>

<?php if ($user_id && $canReview): ?>
<div class="border rounded-4 p-4 mb-5 bg-light">
    <h5 class="fw-bold mb-3 text-primary">Viết đánh giá của bạn</h5>
    <form id="reviewForm">
        <input type="hidden" name="product_id" value="<?= $id ?>">
        
        <div class="mb-4">
            <label class="form-label fw-bold">Chọn số sao <span class="text-danger">*</span></label>
            <div class="star-rating fs-1 text-warning" id="reviewStars" style="cursor:pointer;">
                <i class="bi bi-star" data-value="5"></i>
                <i class="bi bi-star" data-value="4"></i>
                <i class="bi bi-star" data-value="3"></i>
                <i class="bi bi-star" data-value="2"></i>
                <i class="bi bi-star" data-value="1"></i>
            </div>
            <input type="hidden" name="rating" id="selectedRating" required>
        </div>

        <div class="mb-4">
            <textarea name="review" class="form-control" rows="5" placeholder="Chia sẻ trải nghiệm của bạn về sản phẩm này..." required></textarea>
        </div>

        <button type="submit" class="btn btn-primary btn-lg px-5">Gửi đánh giá</button>
    </form>
</div>

<script>
// Xử lý chọn sao
document.querySelectorAll('#reviewStars i').forEach(star => {
    star.addEventListener('click', function() {
        const value = this.dataset.value;
        document.getElementById('selectedRating').value = value;
        
        document.querySelectorAll('#reviewStars i').forEach((s, index) => {
            if ((5 - index) <= value) {
                s.className = 'bi bi-star-fill text-warning';
            } else {
                s.className = 'bi bi-star';
            }
        });
    });

    // Hover effect
    star.addEventListener('mouseover', function() {
        const value = this.dataset.value;
        document.querySelectorAll('#reviewStars i').forEach((s, index) => {
            if ((5 - index) <= value) {
                s.classList.add('text-warning');
            }
        });
    });
});

document.getElementById('reviewStars').addEventListener('mouseleave', function() {
    const selected = document.getElementById('selectedRating').value;
    document.querySelectorAll('#reviewStars i').forEach((s, index) => {
        if (selected && (5 - index) <= selected) {
            s.className = 'bi bi-star-fill text-warning';
        } else {
            s.className = 'bi bi-star';
        }
    });
});

</script>

<?php elseif ($user_id): ?>
    <div class="alert alert-info text-center fs-5">Mua hàng và đánh giá sản phẩm</div>
<?php else: ?>
    <div class="alert alert-light text-center fs-5 p-4">
        <a href="<?= $base ?>/auth/login.php" class="btn btn-primary btn-lg">Đăng nhập</a> để viết đánh giá
    </div>
<?php endif; ?>

        <div id="reviewList">
            <?php if ($reviews): foreach($reviews as $r): ?>
            <div class="border-bottom pb-4 mb-4">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <strong>
                            <?= $r['is_anonymous'] == 1 ? 'Khách hàng' : htmlspecialchars($r['user_name']) ?>
                        </strong>
                        <span class="text-warning ms-2">
                            <?php for($i=1;$i<=5;$i++): ?>
                                <i class="bi <?= $i<=$r['rating']?'bi-star-fill':'bi-star' ?>"></i>
                            <?php endfor; ?>
                        </span>
                        <small class="text-secondary ms-3">
                            <?= date('d/m/Y H:i', strtotime($r['created_at'])) ?>
                        </small>
                    </div>

                    <?php if($user_id && $r['user_id'] == $user_id): ?>
                    <div class="d-flex gap-1">
                        <button type="button"
                                class="btn btn-sm btn-outline-primary"
                                data-id="<?= $r['id'] ?>"
                                data-rating="<?= $r['rating'] ?>"
                                data-text="<?= htmlspecialchars($r['review'], ENT_QUOTES) ?>"
                                onclick="editReview(this)">
                            Sửa
                        </button>
                        <button type="button"
                                class="btn btn-sm btn-outline-danger"
                                onclick="deleteReview(<?= $r['id'] ?>)">
                            Xóa
                        </button>
                    </div>
                    <?php endif; ?>
                </div>

                <p class="mt-2 mb-0 text-dark"><?= nl2br(htmlspecialchars($r['review'])) ?></p>

                <!-- Ảnh + Video của khách -->
                <?php if (!empty($r['images']) || !empty($r['video'])): ?>
                <div class="mt-3 row g-3">
                    <?php 
                    $images = !empty($r['images']) ? json_decode($r['images'], true) : [];
                    if (is_array($images)): 
                        foreach($images as $img): ?>
                            <div class="col-4 col-md-3 col-lg-2">
                                <img src="<?= $base ?>/<?= htmlspecialchars($img) ?>" 
                                     class="img-fluid rounded border shadow-sm" 
                                     style="height:120px; object-fit:cover; cursor:pointer;"
                                     onclick="window.open(this.src, '_blank')">
                            </div>
                        <?php endforeach; 
                    endif; ?>

                    <?php if (!empty($r['video'])): ?>
                        <div class="col-12 col-md-6">
                            <video controls class="w-100 rounded border shadow-sm" style="max-height:400px;">
                                <source src="<?= $base ?>/<?= htmlspecialchars($r['video']) ?>" type="video/mp4">
                                Trình duyệt không hỗ trợ video.
                            </video>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- PHẢN HỒI CỦA ADMIN  -->
                <?php if (!empty($r['admin_reply'])): ?>
                <div class="bg-light border-start border-primary border-4 ps-4 py-3 mt-4 rounded">
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-shop text-primary fs-4 me-2"></i>
                        <strong class="text-primary">Phản hồi từ Shop</strong>
                        <small class="text-muted ms-3">
                            <?= $r['admin_reply_at'] ? date('d/m/Y H:i', strtotime($r['admin_reply_at'])) : '' ?>
                        </small>
                    </div>
                    <p class="mb-0 text-dark"><?= nl2br(htmlspecialchars($r['admin_reply'])) ?></p>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; else: ?>
                <p class="text-center text-secondary fs-5">Chưa có đánh giá nào. Hãy là người đầu tiên!</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="bg-white rounded-4 shadow-sm p-5 mt-4">
        <h4 class="fw-bold text-primary mb-4 text-center">SẢN PHẨM TƯƠNG TỰ</h4>
        <div class="row row-cols-2 row-cols-md-5 g-4">
            <?php foreach($related as $r): ?>
            <div class="col">
                <a href="detail.php?id=<?= $r['id'] ?>" class="text-decoration-none text-dark">
                    <div class="card border-0 shadow-sm hover-lift rounded-4">
                        <img class="card-img-top rounded-top" src="<?= $base ?>/assets/images/products/<?= $r['image'] ?>" style="height:180px;object-fit:cover;">
                        <div class="card-body">
                            <p class="small line-clamp-2 mb-2"><?= htmlspecialchars($r['name']) ?></p>
                            <span class="fw-bold text-danger"><?= number_format($r['final_price'],0,'','.') ?>đ</span>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<div id="toast">Đã thêm vào giỏ hàng!</div>

<script>
const basePrice = <?= $product['base_price'] ?>;
let selectedVariant = null;
let qty = 1;

document.getElementById('wishlistBtn').addEventListener('click', function() {
    <?php if(empty($_SESSION['user_id'])): ?>
        window.location.href = '<?= $base ?>/auth/login.php';
        return;
    <?php endif; ?>

    fetch('<?= $base ?>/user/wishlist_toggle.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'product_id=<?= $id ?>&csrf=<?= $_SESSION['csrf'] ?>'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const btn = this;
            const icon = btn.querySelector('i');
            const isAdded = data.added === true || data.action === 'added';

            if (isAdded) {
                btn.classList.add('wished');
                icon.classList.replace('bi-heart', 'bi-heart-fill');
                icon.classList.add('text-danger');
            } else {
                btn.classList.remove('wished');
                icon.classList.replace('bi-heart-fill', 'bi-heart');
                icon.classList.remove('text-danger');
            }

            const badge = document.getElementById('wishlistCount');
            if (badge) {
                badge.textContent = data.wishlist_count;
                badge.style.display = data.wishlist_count > 0 ? 'inline-block' : 'none';
                badge.classList.remove('bounce');
                void badge.offsetWidth; // trigger reflow
                badge.classList.add('bounce');
            }

            // Toast thông báo
            const toast = document.getElementById('toast');
            toast.textContent = isAdded ? '❤️ Đã thêm vào danh sách yêu thích' : '💔 Đã bỏ khỏi danh sách yêu thích';
            toast.style.background = isAdded ? '#e74c3c' : '#17a2b8';
            toast.innerHTML = (isAdded ? ' ' : ' ') + toast.textContent;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3000);
        } else {
            const toast = document.getElementById('toast');
            toast.textContent = data.message || 'Có lỗi xảy ra!';
            toast.style.background = '#dc3545';
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3000);
        }
    })
    .catch(err => {
        console.error(err);
        const toast = document.getElementById('toast');
        toast.textContent = 'Lỗi kết nối, vui lòng thử lại!';
        toast.style.background = '#dc3545';
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3000);
    });
});

const backBtn = document.getElementById('backBtn');
if (backBtn) {
    backBtn.addEventListener('click', () => {
        const ref = document.referrer;
        if (ref && ref !== location.href && ref.includes(location.host)) {
            window.history.back();
        } else {
            window.location.href = '<?= $base ?>/products/index.php';
        }
    });
}


function selectVariant(el) {
    if (el.classList.contains('out-of-stock')) return;

    document.querySelectorAll('.variant-btn').forEach(b => b.classList.remove('active'));
    el.classList.add('active');

    selectedVariant = {
        id: el.dataset.id,
        priceAdd: parseFloat(el.dataset.priceAdd) || 0,
        stock: parseInt(el.dataset.stock),
        image: el.dataset.image,
        name: el.dataset.name
    };

    qty = 1;
    document.getElementById('qty').value = qty;
    document.getElementById('displayPrice').innerHTML = `<h2 class="text-primary fw-bold m-0">${((basePrice + selectedVariant.priceAdd) * qty).toLocaleString('vi-VN')}đ</h2>`;
    document.getElementById('stockInfo').innerHTML = `<span class="text-success fw-bold">Còn ${selectedVariant.stock} sản phẩm</span>`;
    document.getElementById('selectedVariant').innerText = selectedVariant.name;
    document.getElementById('selectedAlert').style.display = 'block';
    document.getElementById('mainImg').src = '<?= $base ?>/assets/images/products/' + selectedVariant.image;
    document.getElementById('productInfo').classList.add('variant-selected');
    document.getElementById('minus').disabled = false;
    document.getElementById('plus').disabled = false;
    document.getElementById('qty').disabled = false;
    document.getElementById('buyNowBtn').disabled = false;
}

document.querySelectorAll('.variant-btn').forEach(btn => btn.addEventListener('click', () => selectVariant(btn)));

document.getElementById('plus').onclick = () => {
    if (selectedVariant && qty < selectedVariant.stock) {
        qty++;
        document.getElementById('qty').value = qty;
        document.getElementById('displayPrice').innerHTML = `<h2 class="text-primary fw-bold m-0">${((basePrice + selectedVariant.priceAdd) * qty).toLocaleString('vi-VN')}đ</h2>`;
    }
};

document.getElementById('minus').onclick = () => {
    if (qty > 1) {
        qty--;
        document.getElementById('qty').value = qty;
        document.getElementById('displayPrice').innerHTML = `<h2 class="text-primary fw-bold m-0">${((basePrice + selectedVariant.priceAdd) * qty).toLocaleString('vi-VN')}đ</h2>`;
    }
};

document.getElementById('addToCartBtn').addEventListener('click', function() {
    if (!selectedVariant) return alert('Vui lòng chọn phân loại!');

    const formData = new URLSearchParams();
    formData.append('product_id', <?= $product['id'] ?>);
    formData.append('variant_id', selectedVariant.id);
    formData.append('quantity', qty);
    formData.append('csrf', '<?= $_SESSION['csrf'] ?>');
    formData.append('ajax', '1');

    fetch('<?= $base ?>/cart/add.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
           const cartCount = document.getElementById('cartCount');
if (cartCount) {
    cartCount.textContent = data.cart_count;
    
    cartCount.style.display = data.cart_count > 0 ? 'block' : 'none';
    
    cartCount.classList.remove('bounce');
    void cartCount.offsetWidth; // trigger reflow
    cartCount.classList.add('bounce');
}
            document.getElementById('toast').textContent = `Đã thêm ${qty} sản phẩm vào giỏ hàng!`;
            document.getElementById('toast').style.background = '#28a745';
            document.getElementById('toast').classList.add('show');
            setTimeout(() => document.getElementById('toast').classList.remove('show'), 3000);
        } else {
            alert(data.message || 'Không thể thêm vào giỏ hàng');
        }
    })
    .catch(() => alert('Lỗi mạng, vui lòng thử lại!'));
});

document.getElementById('buyNowBtn').addEventListener('click', function () {
    if (selectedVariant === null) {
        alert('Vui lòng chọn phân loại hàng!');
        return;
    }

    <?php if (empty($_SESSION['user_id'])): ?>
        // Chưa đăng nhập → đẩy về login, sau khi login xong quay lại trang này và tự động mua ngay
        const returnUrl = encodeURIComponent(window.location.href);
        window.location.href = '../auth/login.php?redirect=' + returnUrl + '&buynow=1';
        return;
    <?php endif; ?>

    const productData = {
        csrf: <?= json_encode($_SESSION['csrf']) ?>,
        product_id: <?= $product['id'] ?>,
        name: <?= json_encode($product['name']) ?>,
        price: <?= $product['base_price'] ?> + selectedVariant.priceAdd,
        image: selectedVariant.image || <?= json_encode($product['image'] ?? 'no-image.jpg') ?>,
        qty: qty,
        variant_id: selectedVariant.id,
        variant_name: selectedVariant.name
    };

    fetch('../cart/buynow.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(productData)
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            window.location.href = '../checkout.php?direct=1';
        } else {
            alert('Có lỗi khi xử lý đơn hàng. Vui lòng thử lại!');
        }
    })
    .catch(() => {
        alert('Lỗi kết nối. Vui lòng kiểm tra mạng và thử lại!');
    });
});

document.querySelectorAll('.star-rating i').forEach(star => {
    star.addEventListener('click', function(){
        const value = this.dataset.value;
        document.getElementById('selectedRating').value = value;
        document.querySelectorAll('.star-rating i').forEach((s, idx) => {
            const starNum = 5 - idx;
            s.classList.toggle('bi-star-fill', starNum <= value);
            s.classList.toggle('bi-star', starNum > value);
        });
    });
});

document.getElementById('reviewForm')?.addEventListener('submit', function(e){
    e.preventDefault();
    if (!document.getElementById('selectedRating').value) return alert('Vui lòng chọn số sao!');
    const formData = new FormData(this);
    fetch('<?= $base ?>/ajax/add_review.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            document.getElementById('toast').textContent = 'Cảm ơn bạn đã đánh giá!';
            document.getElementById('toast').style.background = '#28a745';
            document.getElementById('toast').classList.add('show');
            setTimeout(() => location.reload(), 2000);
        } else alert(res.message || 'Lỗi');
    });
});
if (location.hash === '#review-section') {
    history.replaceState(null, null, location.pathname + location.search);

    setTimeout(() => {
        const el = document.querySelector('#review-section');
        if (el) el.scrollIntoView({ behavior: 'smooth' });
    }, 600);
}

function editReview(btn) {
    const id = btn.dataset.id;
    const fd = new FormData();
    fd.append('id', id);
    fd.append('csrf', '<?= $_SESSION['csrf'] ?>');

    fetch('<?= $base ?>/ajax/edit_review.php', {
        method: 'POST',
        body: fd
    })
    .then(r => {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
    })
    .then(res => {
        if (res.success && res.redirect) {
            window.location.href = res.redirect;
        } else {
            alert(res.message || 'Không thể sửa');
        }
    })
    .catch(err => {
        console.error(err);
        alert('Lỗi mạng! Mở F12 → Network xem request có trả về 200 không bố ơi!');
    });
}

function deleteReview(id) {
    if (!confirm('Xóa đánh giá này vĩnh viễn?')) return;

    const fd = new FormData();
    fd.append('id', id);
    fd.append('csrf', '<?= $_SESSION["csrf"] ?? "" ?>');

    fetch('<?= $base ?>/ajax/delete_review.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                document.querySelector(`button[onclick="deleteReview(${id})"]`)
                    ?.closest('.border-bottom')
                    ?.remove();

                const t = document.getElementById('toast');
                t.textContent = 'Đã xóa đánh giá!';
                t.style.background = '#dc3545';
                t.classList.add('show');
                setTimeout(() => t.classList.remove('show'), 3000);
            } else {
                alert(res.message || 'Không thể xóa');
            }
        })
        .catch(e => {
            console.error(e);
            alert('Lỗi server – xem Network tab');
        });
}

</script>
<script>

window.addEventListener('pageshow', function(e) {

    if (e.persisted) {
        
        return;
    }
});

if (location.hash === '#review-section') {
    history.replaceState(null, null, location.pathname + location.search);
    setTimeout(() => {
        document.querySelector('#review-section')?.scrollIntoView({behavior: 'smooth'});
    }, 300);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
