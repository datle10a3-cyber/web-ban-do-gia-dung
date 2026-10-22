<?php 
require_once __DIR__ . '/../../includes/init.php';
if (!isAdmin()) {
    header('Location: ../../index.php');
    exit;
}
$base = "/web-ban-do-gia-dung";

$pending_count = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title ?? 'Admin') ?> - Gia Dụng Shop Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <link href="<?= $base ?>/assets/css/style.css" rel="stylesheet">
    
    <style>
        :root {
            --admin-primary: #4361ee;
            --admin-primary-dark: #3f37c9;
            --admin-sidebar: #1e293b;
            --admin-bg: #f8fafc;
        }
        
        body { 
            background: var(--admin-bg); 
            font-family: 'Segoe UI', sans-serif; 
            margin: 0;
        }
        
        /* HEADER + TOPBAR ĐẸP NHƯ SHOPEE ADMIN */
        .admin-header-wrapper {
            position: sticky;
            top: 0;
            z-index: 1030;
            background: white;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        
        .admin-topbar {
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            background: linear-gradient(135deg, #4361ee, #3f37c9);
            color: white;
        }
        
        .admin-brand {
            font-size: 2rem;
            font-weight: 900;
            color: white !important;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.7rem;
        }
        
        .admin-brand i { font-size: 2.2rem; color: #a5b4fc; }
        
        .admin-utils {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            flex-wrap: wrap;
        }
        
        .back-to-site {
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
            border: 1px solid rgba(255,255,255,0.3);
        }
        
        .back-to-site:hover {
            background: white;
            color: #4361ee;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }
        
        .user-dropdown .dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            color: white;
            text-decoration: none;
        }
        
        .user-dropdown img {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #a5b4fc;
            box-shadow: 0 0 15px rgba(165,180,252,0.5);
        }

        .admin-sidebar {
            width: 280px;
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 1020;
            overflow-y: auto;
            box-shadow: 8px 0 30px rgba(0,0,0,0.3);
            transition: transform 0.3s ease;
        }
        
        .sidebar-header {
            padding: 2.5rem 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            background: rgba(255,255,255,0.05);
        }
        
        .sidebar-header h3 {
            margin: 0;
            font-weight: 900;
            font-size: 1.6rem;
            background: linear-gradient(90deg, #a5b4fc, #c3dafe);
            -webkit-text-fill-color: transparent;
        }
        
        .sidebar-nav .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 1rem 1.5rem;
            margin: 0.4rem 1rem;
            border-radius: 14px;
            transition: all 0.3s;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.9rem;
            position: relative;
        }
        
        .sidebar-nav .nav-link:hover,
        .sidebar-nav .nav-link.active {
            background: rgba(67, 97, 238, 0.3);
            color: white;
            transform: translateX(10px);
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
        }
        
        .sidebar-nav .nav-link i {
            width: 26px;
            text-align: center;
            font-size: 1.2rem;
        }
        
        .badge-floating {
            position: absolute;
            top: 10px;
            right: 14px;
            font-size: 0.7rem;
            padding: 0.4rem 0.7rem;
            border-radius: 50px;
            background: #ef4444;
            color: white;
            font-weight: bold;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
        
        .main-content {
            margin-left: 280px;
            padding: 2rem;
            min-height: 100vh;
            transition: margin 0.3s ease;
        }
        
        .page-title-area {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 4px 25px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        @media (max-width: 992px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }
            .admin-sidebar.show { 
                transform: translateX(0); 
            }
            .main-content { 
                margin-left: 0; 
                padding: 1rem; 
            }
            .admin-topbar { 
                padding: 1rem; 
            }
        }
    </style>
</head>
<body>

<div class="admin-header-wrapper">
    <div class="admin-topbar">
       <a href="<?= $base ?>/index.php" class="admin-brand" target="_blank">
    <i class="fas fa-couch"></i> GIA DỤNG SHOP
</a>
        <div class="admin-utils">
            <a href="<?= $base ?>/index.php" class="back-to-site" target="_blank">
                <i class="fas fa-external-link-alt"></i> Xem trang khách
            </a>
            <div class="dropdown user-dropdown">
                <a class="dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
<?php
$admin_avatar = 'default_avatar.jpg';
$admin_avatar_version = time();

if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && !empty($user['avatar'])) {
            $fullPath = __DIR__ . '/../../assets/images/avatars/' . $user['avatar'];
            if (file_exists($fullPath)) {
                $admin_avatar = $user['avatar'];
                $admin_avatar_version = filemtime($fullPath);
            }   
        }
    } catch (Exception $e) {}
}
?>
<img src="<?= $base ?>/assets/images/avatars/<?= htmlspecialchars($admin_avatar) ?>?v=<?= $admin_avatar_version ?>"
     alt="Admin Avatar"
     class="rounded-circle"
     style="width:44px;height:44px;object-fit:cover;border:3px solid #a5b4fc;box-shadow:0 0 15px rgba(165,180,252,0.5);"
     onerror="this.src='<?= $base ?>/assets/images/avatars/default_avatar.jpg'">                    <span><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg">
                    <li><a class="dropdown-item" href="<?= $base ?>/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-header">
        <h3>ADMIN PANEL</h3>
    </div>
    <nav class="sidebar-nav mt-3">
        <a href="index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
            <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span>
        </a>

        <a href="manage_orders.php" class="nav-link <?= in_array(basename($_SERVER['PHP_SELF']), ['manage_orders.php','order_detail.php']) ? 'active' : '' ?>">
            <i class="fas fa-shopping-bag"></i> <span>Đơn hàng</span>
            <?php if($pending_count > 0): ?>
                <span class="badge-floating"><?= $pending_count ?></span>
            <?php endif; ?>
        </a>

        <a href="manage_products.php" class="nav-link <?= in_array(basename($_SERVER['PHP_SELF']), ['manage_products.php','product_add.php','product_edit.php']) ? 'active' : '' ?>">
            <i class="fas fa-box"></i> <span>Sản phẩm</span>
        </a>

        <a href="manage_categories.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'manage_categories.php' ? 'active' : '' ?>">
            <i class="fas fa-folder-open"></i> <span>Danh mục</span>
        </a>

        <a href="manage_brands.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'manage_brands.php' ? 'active' : '' ?>">
            <i class="fas fa-copyright"></i> <span>Thương hiệu</span>
        </a>

        <a href="manage_vouchers.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'manage_vouchers.php' ? 'active' : '' ?>">
            <i class="fas fa-ticket-alt"></i> <span>Voucher</span>
        </a>

        <a href="manage_users.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'manage_users.php' ? 'active' : '' ?>">
            <i class="fas fa-users"></i> <span>Người dùng</span>
        </a>

        <a href="manage_reviews.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'manage_reviews.php' ? 'active' : '' ?>">
            <i class="fas fa-star"></i> <span>Đánh giá</span>
        </a>

        <hr class="bg-white opacity-25 my-4 mx-3">

        <a href="<?= $base ?>/index.php" class="nav-link" target="_blank">
            <i class="fas fa-home"></i> <span>Trang chủ khách</span>
        </a>

        <a href="<?= $base ?>/auth/logout.php" class="nav-link text-danger">
            <i class="fas fa-sign-out-alt"></i> <span>Đăng xuất</span>
        </a>
    </nav>
</aside>

<main class="main-content">
