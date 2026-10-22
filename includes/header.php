<?php
require_once __DIR__ . '/functions.php';
account_user();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$base = "/web-ban-do-gia-dung";
$userHeaderData = null;
$wishlistCount = 0;
$avatarFileName = 'default_avatar.jpg';
$avatarVersion = time();

if (isset($_SESSION['user_id']) && isset($pdo)) {
    try {
        $stmt = $pdo->prepare("
            SELECT u.avatar,
                   (SELECT COUNT(*) FROM wishlist WHERE user_id = u.id) AS wishlist_count
            FROM users u WHERE u.id = ? LIMIT 1
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $userHeaderData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($userHeaderData) {
            $wishlistCount = (int)$userHeaderData['wishlist_count'];
            if (!empty($userHeaderData['avatar'])) {
                $fullPath = __DIR__ . '/../assets/images/avatars/' . $userHeaderData['avatar'];
                if (file_exists($fullPath)) {
                    $avatarFileName = $userHeaderData['avatar'];
                    $avatarVersion = filemtime($fullPath);
                }
            }
        }
    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' : ''; ?>Gia Dụng Shop</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/ui-lightness/jquery-ui.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= $base ?>/assets/css/banners.css?v=20261022a">
    <style>
        :root {
            /* Tông xanh dương nhạt siêu sang – chuẩn Apple 2025 */
            --primary: #4e8cff;
            --primary-hover: #3a78ff;
            --primary-light: #e0f2fe;
            --primary-lighter: #f0f9ff;
            --primary-glass: rgba(78, 140, 255, 0.12);
            --primary-glow: rgba(78, 140, 255, 0.30);

            --accent: #00d4b8;
            --accent-hover: #00b89c;

            --text: #0f172a;
            --text-light: #475569;
            --text-lighter: #64748b;

            --bg: #f8fcff;
            --bg-glass: rgba(248, 252, 255, 0.95);
            --bg-card: #f0f9ff;

            --border: #d1e9ff;
            --border-light: #e6f3ff;

            --shadow-sm: 0 4px 20px rgba(78, 140, 255, 0.10);
            --shadow-md: 0 12px 40px rgba(78, 140, 255, 0.18);
            --shadow-lg: 0 20px 60px rgba(78, 140, 255, 0.22);
            --shadow-glow: 0 0 35px rgba(78, 140, 255, 0.30);

            --radius: 18px;
            --radius-sm: 14px;
            --radius-lg: 26px;

            --transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            --transition-fast: all 0.25s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        *::selection {
            background: var(--primary);
            color: white;
        }

        body {
            font-family: 'Manrope', 'Inter', system-ui, sans-serif;
            background: linear-gradient(135deg, #f0f8ff 0%, #e0f2fe 40%, #c4e0ff 100%);
            color: var(--text);
            line-height: 1.6;
            font-weight: 500;
            overflow-x: hidden;
            min-height: 100vh;
        }

        /* Top Promotion Bar – thu nhỏ + màu xanh nhạt sang trọng */
        .top-promo {
            background: linear-gradient(90deg, #4e8cff, #00d4b8);
            color: white;
            font-size: 0.82rem;
            font-weight: 600;
            padding: 0.45rem 0;
            text-align: center;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(12px);
        }

        .top-promo::before {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            animation: shimmer 5s infinite;
        }

        @keyframes shimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        /* Main Header – thu nhỏ 10%, mỏng hơn, sang hơn */
        .main-header {
            background: var(--bg-glass);
            backdrop-filter: blur(28px);
            -webkit-backdrop-filter: blur(28px);
            border-bottom: 1px solid var(--border-light);
            position: sticky;
            top: 0;
            z-index: 1100;
            padding: 0.6rem 0; /* thu nhỏ 10% */
            box-shadow: var(--shadow-sm);
        }

        .header-inner {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        /* Logo – nâng cấp cực đẹp, icon hiện đại + gradient lung linh */
        .logo {
            font-size: 1.85rem; /* thu nhỏ nhẹ */
            font-weight: 900;
            letter-spacing: -1.2px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            transition: var(--transition);
        }

        .logo:hover {
            transform: scale(1.07);
        }

        .logo i {
            font-size: 2.1rem;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            filter: drop-shadow(0 6px 16px var(--primary-glow));
            animation: logoPulse 4s ease-in-out infinite;
        }

        @keyframes logoPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.12); }
        }

        /* Search Bar – thu nhỏ nhẹ */
        .search-container {
            flex: 1;
            max-width: 620px;
            margin: 0 1.5rem;
        }

        .search-form {
            position: relative;
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow-md);
            background: white;
            transition: var(--transition);
        }

        .search-form:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }

        .search-form .form-control {
            height: 48px; /* thu nhỏ 10% */
            border: none;
            padding: 0 1.4rem;
            font-size: 0.95rem;
            font-weight: 500;
            background: transparent;
        }

        .search-form .form-control::placeholder {
            color: var(--text-lighter);
            font-weight: 400;
        }

        .search-form .form-control:focus {
            background: var(--primary-lighter);
            box-shadow: none;
        }

        .search-form .btn-search {
            height: 48px;
            width: 58px;
            background: linear-gradient(135deg, var(--primary), var(--primary-hover));
            border: none;
            color: white;
            font-size: 1.3rem;
            transition: var(--transition);
        }

        .search-form .btn-search:hover {
            background: var(--primary-hover);
            transform: scale(1.1);
        }

        /* User Actions – thu nhỏ nhẹ */
        .user-actions {
            display: flex;
            align-items: center;
            gap: 1.1rem;
        }

        .action-item {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: var(--text-light);
            transition: var(--transition);
            padding: 0.55rem 0.75rem;
            border-radius: var(--radius-sm);
        }

        .action-item:hover {
            color: var(--primary);
            transform: translateY(-6px);
            background: var(--primary-glass);
        }

        .action-item i {
            font-size: 1.55rem;
            margin-bottom: 0.35rem;
            transition: var(--transition);
        }

        .action-item:hover i {
            transform: scale(1.2);
        }

        .action-item span {
            font-size: 0.78rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .badge-noti {
            position: absolute;
            top: -2px;
            right: -2px;
            font-size: 0.68rem;
            min-width: 18px;
            height: 18px;
            border-radius: 50%;
            background: var(--accent);
            color: white;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 3px 12px rgba(0, 212, 184, 0.4);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.15); }
        }

        /* Cart Button – thu nhỏ */
        .cart-btn {
            width: 52px;
            height: 52px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-lg);
            transition: var(--transition);
            border: 3px solid rgba(255,255,255,0.5);
        }

        .cart-btn:hover {
            transform: scale(1.15) rotate(12deg);
            box-shadow: var(--shadow-glow);
        }

        .cart-btn i {
            font-size: 1.7rem;
        }

        /* Avatar – thu nhỏ nhẹ */
        .avatar-img {
            width: 44px;
            height: 44px;
            border: 3px solid white;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: var(--shadow-md);
            transition: var(--transition);
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }

        .avatar-img:hover {
            transform: scale(1.2);
            outline-color: var(--accent);
            box-shadow: var(--shadow-glow);
        }

        /* Main Navigation – thu nhỏ */
        .main-nav {
            background: white;
            padding: 0.9rem 0;
            margin-top: 0.6rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-light);
        }

        .main-nav .nav-link {
            font-size: 0.96rem;
            font-weight: 700;
            color: var(--text) !important;
            padding: 0.7rem 1.3rem !important;
            border-radius: var(--radius-sm);
            transition: var(--transition);
            position: relative;
        }

        .main-nav .nav-link::after {
            content: '';
            position: absolute;
            bottom: 8px;
            left: 50%;
            width: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            transition: var(--transition);
            transform: translateX(-50%);
            border-radius: 2px;
        }

        .main-nav .nav-link:hover::after,
        .main-nav .nav-link.active::after {
            width: 60%;
        }

        .main-nav .nav-link:hover,
        .main-nav .nav-link.active {
            background: var(--primary-lighter);
            color: var(--primary) !important;
            transform: translateY(-4px);
        }

        .main-nav .nav-link i {
            margin-right: 0.5rem;
            font-size: 1.15rem;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .search-container { margin: 0.75rem 0; max-width: 100%; }
            .user-actions { gap: 1rem; }
            .action-item span { font-size: 0.75rem; }
            .main-nav .nav-link { padding: 0.65rem 1rem !important; font-size: 0.92rem; }
        }

        @media (max-width: 576px) {
            .logo { font-size: 1.65rem; }
            .logo i { font-size: 1.9rem; }
            .cart-btn { width: 48px; height: 48px; }
            .avatar-img { width: 40px; height: 40px; }
            .search-form .form-control { height: 45px; font-size: 0.93rem; }
        }
        @keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-6px); }
}
#cartCount.bounce,
#wishlistCount.bounce {
    animation: bounce 1.8s infinite;
}
@keyframes bounce {
    0%, 100% { transform: translate(-50%, -50%) scale(1); }
    50% { transform: translate(-50%, -65%) scale(1.1); }
}

#wishlistCount.bounce {
    animation: heartbeat 0.8s ease-in-out infinite;
}
@keyframes heartbeat {
    0%   { transform: translate(-50%, -50%) scale(1); }
    20%  { transform: translate(-50%, -50%) scale(1.4); }
    40%  { transform: translate(-50%, -50%) scale(0.9); }
    60%  { transform: translate(-50%, -50%) scale(1.3); }
    80%  { transform: translate(-50%, -50%) scale(1.1); }
    100% { transform: translate(-50%, -50%) scale(1); }
}
    </style>
</head>
<body>

<div class="top-promo">
    <div class="container-fluid px-4">
        <i class="fas fa-truck-fast me-2"></i>
        Miễn phí vận chuyển toàn quốc đơn | 
<i class="fas fa-phone-alt ms-2 me-2"></i>
        hotline: 0365539435 để được hỗ trợ nhanh!
    </div>
</div>

<!-- Main Header -->
<header class="main-header">
    <div class="header-inner">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">

 <a href="<?= $base ?>/index.php" class="logo">
    <i class="fas fa-house" style="font-size: 2.4rem; filter: drop-shadow(0 0 12px rgba(78,140,255,0.4)); animation: float 4s ease-in-out infinite;"></i>
    <span>Gia Dụng Shop</span>
</a>

            <!-- Search -->
            <div class="search-container">
                <form method="GET" action="<?= $base ?>/products/index.php" class="search-form d-flex" role="search">
                    <input id="search-input" class="form-control ui-autocomplete-input" type="search" name="search"
                           aria-label="Tìm kiếm sản phẩm" placeholder="Tìm kiếm sản phẩm gia dụng..."
                           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    <button class="btn btn-search" type="submit" aria-label="Tìm kiếm">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>

            <div class="user-actions">
    <a href="<?= $base ?>/user/wishlist.php" class="action-item position-relative">
        <i class="far fa-heart"></i>
        <span>Yêu thích</span>
        <?php if ($wishlistCount > 0): ?>
            <span id="wishlistCount" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger bounce" style="font-size:0.68rem; animation: heartbeat 1.5s ease-in-out infinite;">
                <?= $wishlistCount ?>
            </span>
        <?php else: ?>
            <span id="wishlistCount" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger bounce" style="font-size:0.68rem; display:none;">
                0
            </span>
        <?php endif; ?>
    </a>

    <a href="<?= $base ?>/user/orders.php" class="action-item">
        <i class="fas fa-truck"></i>
        <span>Đơn hàng</span>
    </a>

    <div class="dropdown avatar-dropdown">
        <button type="button" class="dropdown-toggle header-account-toggle" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Tài khoản">
            <?php if (isLoggedIn()): ?>
                <img src="<?= $base ?>/assets/images/avatars/<?= htmlspecialchars($avatarFileName) ?>?v=<?= $avatarVersion ?>"
                     alt="Avatar" class="avatar-img"
                     onerror="this.src='<?= $base ?>/assets/images/avatars/default_avatar.jpg?v=<?= time() ?>'">
            <?php else: ?>
                <i class="fas fa-user-circle" style="font-size: 46px; color: var(--primary); filter: drop-shadow(0 4px 12px var(--primary-glow));"></i>
            <?php endif; ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" style="border-radius: var(--radius);">
            <?php if (isLoggedIn()): ?>
                <li><a class="dropdown-item fw-semibold" href="<?= $base ?>/user/profile.php"><i class="fas fa-user me-2"></i><?= sanitize($_SESSION['display_name']??'Khách hàng') ?></a></li>
                <?php if (isAdmin()): ?>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-primary fw-bold" href="<?= $base ?>/admin/index.php"><i class="fas fa-crown me-2"></i>Admin Panel</a></li>
                <?php endif; ?>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="<?= $base ?>/auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Đăng xuất</a></li>
            <?php else: ?>
                <li><a class="dropdown-item" href="<?= $base ?>/auth/login.php"><i class="fas fa-sign-in-alt me-2"></i>Đăng nhập</a></li>
                <li><a class="dropdown-item" href="<?= $base ?>/auth/register.php"><i class="fas fa-user-plus me-2"></i>Đăng ký</a></li>
            <?php endif; ?>
        </ul>
    </div>

    <a href="<?= $base ?>/cart/index.php" class="cart-btn position-relative">
        <i class="fas fa-shopping-bag"></i>
        <?php $cart_count = !empty($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?>
        <span id="cartCount" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger text-white bounce"
              style="font-size:0.7rem; <?= $cart_count == 0 ? 'display:none;' : '' ?>">
            <?= $cart_count ?>
        </span>
    </a>
</div>
        </div>

<nav class="main-nav">
    <div class="container-fluid">
        <ul class="nav justify-content-center gap-3 flex-wrap">
            <?php 
            // Lấy đường dẫn hiện tại (loại bỏ $base để so sánh chính xác)
            $current_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $current_path = str_replace($base, '', $current_path);
            if ($current_path === false) $current_path = '/';
            if ($current_path == '') $current_path = '/index.php';
            ?>

           <li class="nav-item">
    <a class="nav-link<?= ($current_path == '/' || $current_path == '/index.php') ? ' active' : '' ?>" 
       href="<?= $base ?>/index.php">
        <i class="fas fa-home"></i> Trang chủ
    </a>  
</li>

            <li class="nav-item">
                <a class="nav-link<?= strpos($current_path, '/products') === 0 ? ' active' : '' ?>" 
                   href="<?= $base ?>/products/">
                    <i class="fas fa-th-large"></i> Sản phẩm
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link<?= $current_path == '/pages/about.php' ? ' active' : '' ?>" 
                   href="<?= $base ?>/pages/about.php">
                    <i class="fas fa-heart"></i> Giới thiệu
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link<?= $current_path == '/pages/tintuc.php' ? ' active' : '' ?>" 
                   href="<?= $base ?>/pages/tintuc.php">
                    <i class="fas fa-newspaper"></i> Tin tức
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link<?= $current_path == '/pages/stores.php' ? ' active' : '' ?>" 
                   href="<?= $base ?>/pages/stores.php">
                    <i class="fas fa-store"></i> Cửa hàng
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link<?= $current_path == '/pages/support.php' ? ' active' : '' ?>" 
                   href="<?= $base ?>/pages/support.php">
                    <i class="fas fa-headset"></i> Hỗ trợ
                </a>
            </li>
        </ul>
    </div>
</nav>
    </div>
</header>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(function() {
    const baseUrl = <?= json_encode($base) ?>;

    $("#search-input").autocomplete({
        source: baseUrl + "/products/search.php",
        minLength: 2,
        select: function(event, ui) {
            location.href = baseUrl + "/products/detail.php?id=" + ui.item.id;
        }
    }).autocomplete("instance")._renderItem = function(ul, item) {
        return $("<li>").append(
            '<div class="d-flex align-items-center p-3" style="border-radius: 12px; margin: 4px 8px; transition: all 0.3s;">' +
                '<img src="' + item.image + '" width="56" height="56" class="me-3 rounded shadow-sm" style="object-fit:cover; border: 2px solid var(--primary-light);">' +
                '<div>' +
                    '<strong class="d-block" style="color: var(--primary);">' + item.label + '</strong>' +
                    '<small class="text-success fw-bold">' + (item.price ? item.price : 'Liên hệ') + '</small>' +
                '</div>' +
            '</div>'
        ).appendTo(ul);
    };
});
</script>
