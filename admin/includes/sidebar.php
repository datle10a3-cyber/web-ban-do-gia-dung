<div class="sidebar bg-gradient-primary text-white min-vh-100 p-4 position-sticky top-0" style="width: 280px;">
    <div class="text-center mb-5">
        <h3 class="fw-bold"><i class="fas fa-store-alt me-2"></i>GIA DỤNG PRO</h3>
        <hr class="bg-white opacity-50">
        <small>Xin chào, <strong><?= sanitize($_SESSION['display_name']??'Quản trị viên') ?></strong></small>
    </div>
    <nav class="nav flex-column">
        <a href="index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        <a href="manage_orders.php" class="nav-link <?= in_array(basename($_SERVER['PHP_SELF']), ['manage_orders.php', 'order_detail.php']) ? 'active' : '' ?>">
            <i class="fas fa-shopping-bag"></i> Đơn hàng 
            <?php $pending = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn(); ?>
            <?php if($pending): ?><span class="badge bg-danger ms-2"><?= $pending ?></span><?php endif; ?>
        </a>
        <a href="manage_products.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'manage_products.php' ? 'active' : '' ?>">
            <i class="fas fa-box"></i> Sản phẩm
        </a>
        <a href="manage_categories.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'manage_categories.php' ? 'active' : '' ?>">
            <i class="fas fa-tags"></i> Danh mục
        </a>
        <a href="manage_brands.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'manage_brands.php' ? 'active' : '' ?>">
            <i class="fas fa-copyright"></i> Thương hiệu
        </a>
        <a href="manage_users.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'manage_users.php' ? 'active' : '' ?>">
            <i class="fas fa-users"></i> Người dùng
        </a>
        <hr class="bg-white opacity-50 my-4">
        <a href="../../logout.php" class="nav-link text-danger">
            <i class="fas fa-sign-out-alt"></i> Đăng xuất
        </a>
    </nav>
</div>