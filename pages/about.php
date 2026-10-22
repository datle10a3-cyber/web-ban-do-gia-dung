<?php 
$base = "/web-ban-do-gia-dung";
$page_title = "Giới thiệu - Gia Dụng Shop";

require_once '../config/database.php';
require_once '../includes/functions.php';

require_once '../includes/header.php'; 
?>

<?php $banner = ['title' => 'Về Gia Dụng Shop', 'description' => 'Tiện nghi cho tổ ấm, tận tâm trong từng lựa chọn.', 'image' => 'hero-about.jpg']; require __DIR__ . '/../includes/page_banner.php'; ?>

<!-- CON SỐ NỔI BẬT -->
<section class="py-5 bg-white">
    <div class="container">
        <div class="row text-center g-5">
            <div class="col-6 col-md-3">
                <h2 class="display-3 fw-bold text-primary mb-2">100</h2>
                <p class="fs-4 text-muted">Cửa hàng</p>
            </div>
            <div class="col-6 col-md-3">
                <h2 class="display-3 fw-bold text-primary mb-2">10+</h2>
                <p class="fs-4 text-muted">Năm hoạt động</p>
            </div>
            <div class="col-6 col-md-3">
                <h2 class="display-3 fw-bold text-primary mb-2">10 triệu+</h2>
                <p class="fs-4 text-muted">Khách hàng</p>
            </div>
            <div class="col-6 col-md-3">
                <h2 class="display-3 fw-bold text-primary mb-2">100%</h2>
                <p class="fs-4 text-muted">Hàng chính hãng</p>
            </div>
        </div>
    </div>
</section>

<!-- VỀ CHÚNG TÔI -->
<section class="py-5">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6 order-lg-2">
                <img src="<?= $base ?>/assets/images/showroom.jpg" class="img-fluid rounded-4 shadow-lg" alt="Showroom Gia Dụng Shop">
            </div>
            <div class="col-lg-6 order-lg-1">
                <h2 class="display-5 fw-bold mb-4">Về Gia Dụng Shop</h2>
                <p class="lead text-muted mb-4">
                    Thành lập năm 2025, từ một nhóm sinh viên KTPM02, đến nay chúng tôi đã có <strong>100 showroom</strong> trên toàn quốc.
                </p>
                <div class="row g-4">
                    <div class="col-12">
                        <div class="d-flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-primary fs-3 me-3"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold">Chính hãng 100%</h5>
                                <p class="text-muted mb-0">Hoàn tiền 300% nếu phát hiện hàng giả</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-truck text-success fs-3 me-3"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold">Giao & lắp đặt miễn phí</h5>
                                <p class="text-muted mb-0">Toàn quốc – nhanh trong 24h</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-headset text-warning fs-3 me-3"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold">Hỗ trợ 24/7</h5>
                                <p class="text-muted mb-0">Hotline: 0365539435 luôn sẵn sàng</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ĐỐI TÁC -->
<section class="py-5 bg-light">
    <div class="container text-center">
        <h2 class="display-5 fw-bold mb-5">Đối tác chiến lược</h2>
        <div class="row justify-content-center g-5 align-items-center">
            <div class="col-6 col-md-3 col-lg-2"><img src="<?= $base ?>/assets/images/brands/samsung.jpg" class="img-fluid" alt="Samsung"></div>
            <div class="col-6 col-md-3 col-lg-2"><img src="<?= $base ?>/assets/images/brands/lg.jpg" class="img-fluid" alt="LG"></div>
            <div class="col-6 col-md-3 col-lg-2"><img src="<?= $base ?>/assets/images/brands/panasonic.jpg" class="img-fluid" alt="Panasonic"></div>
            <div class="col-6 col-md-3 col-lg-2"><img src="<?= $base ?>/assets/images/brands/electrolux.jpg" class="img-fluid" alt="Electrolux"></div>
            <div class="col-6 col-md-3 col-lg-2"><img src="<?= $base ?>/assets/images/brands/hitachi.jpg" class="img-fluid" alt="Hitachi"></div>
            <div class="col-6 col-md-3 col-lg-2"><img src="<?= $base ?>/assets/images/brands/toshiba.jpg" class="img-fluid" alt="Toshiba"></div>
        </div>
    </div>
</section>

<!-- CTA CUỐI -->
<section class="py-5 text-white text-center bg-primary">
    <div class="container">
        <h2 class="display-4 fw-bold mb-4">Sẵn sàng nâng tầm cuộc sống gia đình bạn</h2>
        <p class="fs-3 mb-5">Hơn 10.000.000 khách hàng đã tin tưởng</p>
        <a href="<?= $base ?>/products/" class="btn btn-warning btn-lg px-5 py-4 fw-bold fs-4">
            BẮT ĐẦU MUA SẮM
        </a>
    </div>
</section>

<style>
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

<?php require_once '../includes/footer.php'; ?>