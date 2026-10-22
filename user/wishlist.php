<?php
include '../config/database.php';
include '../includes/functions.php';
requireLogin();
include '../includes/header.php';

$user_id = $_SESSION['user_id'];

$wishlist = getWishlist($pdo, $user_id);
$search = trim($_GET['search'] ?? '');

if ($search !== '') {
    $wishlist = array_filter($wishlist, fn($i) => 
        mb_stripos($i['name'], $search) !== false || 
        mb_stripos($i['description'] ?? '', $search) !== false
    );
}
?>

<div class="container py-5">
    <!-- Tiêu đề + số lượng -->
    <div class="text-center mb-5">
        <h2 class="display-6 fw-bold text-primary">
            <i class="fas fa-heart text-danger"></i> 
            Sản phẩm yêu thích 
            <span class="badge bg-danger fs-5"><?= count(getWishlist($pdo, $user_id)) ?></span>
        </h2>
    </div>

    <!-- Tìm kiếm -->
    <form method="GET" class="mb-5">
        <div class="input-group shadow-sm" style="max-width: 600px; margin: 0 auto;">
            <input type="text" name="search" class="form-control form-control-lg border-0" 
                   placeholder="Tìm trong danh sách yêu thích..." value="<?= htmlspecialchars($search) ?>">
            <button class="btn btn-primary btn-lg px-4">
                <i class="fas fa-search"></i>
            </button>
            <?php if ($search): ?>
                <a href="wishlist.php" class="btn btn-outline-secondary btn-lg">Xóa</a>
            <?php endif; ?>
        </div>
    </form>

    <?php if (empty($wishlist)): ?>
        <div class="text-center py-5">
            <i class="fas fa-heart-broken fa-6x text-danger mb-4"></i>
            <h3 class="text-muted">Danh sách yêu thích trống</h3>
            <p>Hãy thêm những sản phẩm bạn thích để lưu lại nhé!</p>
            <a href="<?= $base ?>/products/" class="btn btn-primary btn-lg px-5">
                <i class="fas fa-shopping-bag"></i> Tiếp tục mua sắm
            </a>
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
            <?php foreach ($wishlist as $item): 
                $final = $item['price'] * (100 - $item['discount']) / 100;
            ?>
                <div class="col">
                    <div class="card h-100 shadow-sm border-0 rounded-3 overflow-hidden position-relative">
                        <!-- Hình ảnh + link chi tiết -->
                        <a href="<?= $base ?>/products/detail.php?id=<?= $item['id'] ?>" class="text-decoration-none">
                            <img src="<?= $base ?>/assets/images/products/<?= htmlspecialchars($item['image'] ?? 'no-image.jpg') ?>" 
                                 class="card-img-top" style="height: 220px; object-fit: cover;"
                                 onerror="this.src='<?= $base ?>/assets/images/no-image.jpg'">
                        </a>

                        <div class="card-body d-flex flex-column p-4">

                        <h6 class="card-title fw-bold mb-3" style="height: 50px; overflow: hidden;">
                                <a href="<?= $base ?>/products/detail.php?id=<?= $item['id'] ?>" 
                                   class="text-dark text-decoration-none">
                                    <?= htmlspecialchars($item['name']) ?>
                                </a>
                            </h6>

                            <div class="mb-4">
                                <div class="text-danger fw-bold fs-4">
                                    <?= number_format($final, 0, '', '.') ?>₫
                                </div>
                                <?php if ($item['discount'] > 0): ?>
                                    <del class="text-muted small"><?= number_format($item['price'], 0, '', '.') ?>₫</del>
                                    <span class="badge bg-danger ms-2">-<?= $item['discount'] ?>%</span>
                                <?php endif; ?>
                            </div>

                            <div class="mt-auto d-grid gap-2">
                                <button type="button" class="btn btn-outline-danger remove-wishlist" data-id="<?= $item['id'] ?>">
                                    <i class="fas fa-heart-broken"></i> Bỏ yêu thích
                                </button>

                                <a href="<?= $base ?>/products/detail.php?id=<?= $item['id'] ?>" 
                                   class="btn btn-primary">
                                    <i class="fas fa-eye"></i> Xem chi tiết
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function updateHeaderWishlistCount(count) {
    const badge = document.getElementById('wishlistCount');
    if (!badge) return;

    if (count > 0) {
        badge.textContent = count;
        badge.style.display = 'flex';
        badge.classList.remove('bounce');
        void badge.offsetWidth;
        badge.classList.add('bounce');
    } else {
        badge.style.display = 'none';
    }
}

document.querySelectorAll('.remove-wishlist').forEach(btn => {
    btn.addEventListener('click', function() {
        if (!confirm('Bỏ sản phẩm này khỏi danh sách yêu thích?')) return;

        const productId = this.dataset.id;
        const card = this.closest('.col');

        fetch('<?= $base ?>/user/wishlist_toggle.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'csrf=<?= $_SESSION['csrf'] ?>&product_id=' + productId
        })
        .then(r => r.json())
        .then(data => {
            if (data.success && !data.added) {
                card.style.transition = 'opacity 0.5s, transform 0.5s';
                card.style.opacity = '0';
                card.style.transform = 'scale(0.92)';

                setTimeout(() => {
                    card.remove();

                    // Cập nhật số ở trang hiện tại
                    const pageBadge = document.querySelector('.badge.bg-danger.fs-5');
                    if (pageBadge) {
                        pageBadge.textContent = data.wishlist_count;
                        if (data.wishlist_count === 0) {
                            setTimeout(() => location.reload(), 600);
                        }
                    }

                    updateHeaderWishlistCount(data.wishlist_count);

                    const toast = document.createElement('div');
                    toast.innerHTML = '<i class="fas fa-heart-broken me-2"></i>Đã bỏ yêu thích!';
                    toast.style.cssText = 'position:fixed;bottom:30px;right:30px;background:#dc3545;color:#fff;padding:16px 32px;border-radius:16px;box-shadow:0 10px 30px rgba(220,53,69,0.4);z-index:9999;font-weight:600;animation:slideIn 0.4s forwards';
                    document.body.appendChild(toast);
                    setTimeout(() => toast.remove(), 2500);
                }, 500);
            } else if (data.login) {
                alert('Vui lòng đăng nhập lại!');
                location.href = '<?= $base ?>/auth/login.php';
            } else {
                alert(data.message || 'Có lỗi xảy ra!');
            }
        })
        .catch(() => alert('Lỗi kết nối mạng!'));
    });
});

const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateY(100px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
`;
document.head.appendChild(style);
</script>t>
<style>
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

<?php include '../includes/footer.php'; ?>