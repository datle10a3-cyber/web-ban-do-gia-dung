<?php 
$base = "/web-ban-do-gia-dung";
$page_title = "Hệ thống cửa hàng - Gia Dụng Shop";

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';
?>

<?php $banner = ['title' => 'Hệ thống cửa hàng', 'description' => 'Tìm địa chỉ gần bạn để trải nghiệm sản phẩm.', 'image' => 'stores-hero.jpg']; require __DIR__ . '/../includes/page_banner.php'; ?>

<div class="container py-5">

    <div class="row mb-5">
        <div class="col-lg-8 mx-auto text-center">
            <h2 class="fw-bold text-primary mb-4">Gia Dụng Shop - Mạng lưới rộng khắp</h2>
            <p class="fs-5 text-muted">Dù chỉ mới thành lập, Gia Dụng Shop tự hào sở hữu hệ thống cửa hàng hiện đại tại các thành phố lớn. Đến với chúng tôi, bạn sẽ được trải nghiệm dịch vụ tư vấn chuyên sâu, trưng bày sản phẩm thực tế và ưu đãi độc quyền chỉ có tại cửa hàng.</p>
            <a href="<?= $base ?>/pages/support.php" class="btn btn-primary btn-lg px-5 fw-bold mt-3">Liên hệ ngay</a>
        </div>
    </div>

    <div class="row g-4">

        <div class="col-lg-4">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden h-100 hover-lift">
                <img src="<?= $base ?>/assets/images/store-hanoi.jpg" class="card-img-top" style="height:250px; object-fit:cover;" alt="Cửa hàng Hà Nội">
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold text-primary mb-3">Chi nhánh Hà Nội</h5>
                    <ul class="list-unstyled text-muted mb-3">
                        <li class="mb-2"><i class="fas fa-map-marker-alt me-2 text-danger"></i>Số 95 Gia Thượng, Quận Long Biên</li>
                        <li class="mb-2"><i class="fas fa-phone me-2 text-success"></i>Hotline: 024.1234.5678</li>
                        <li class="mb-2"><i class="fas fa-clock me-2 text-info"></i>Giờ mở cửa: 8:00 - 22:00 (hàng ngày)</li>
                        <li><i class="fas fa-globe me-2 text-primary"></i>Điểm nổi bật: Khu trưng bày tủ lạnh lớn nhất miền Bắc</li>
                    </ul>
                    <div class="d-flex gap-2">
                        <a href="https://maps.google.com?q=95+Gia+Thượng,Long+Biên,Hà+Nội" target="_blank" class="btn btn-outline-primary flex-fill">
                            <i class="fas fa-directions me-1"></i>Chỉ đường
                        </a>
                        <a href="tel:02412345678" class="btn btn-primary">
                            <i class="fas fa-phone me-1"></i>Gọi ngay
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden h-100 hover-lift">
                <img src="<?= $base ?>/assets/images/store-hcm.jpg" class="card-img-top" style="height:250px; object-fit:cover;" alt="Cửa hàng TP.HCM">
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold text-primary mb-3">Chi nhánh TP.HCM</h5>
                    <ul class="list-unstyled text-muted mb-3">
                        <li class="mb-2"><i class="fas fa-map-marker-alt me-2 text-danger"></i>344 Cộng Hòa, P.13, Q.Tân Bình</li>
                        <li class="mb-2"><i class="fas fa-phone me-2 text-success"></i>Hotline: 028.5678.9999</li>
                        <li class="mb-2"><i class="fas fa-clock me-2 text-info"></i>Giờ mở cửa: 7:30 - 21:30 (hàng ngày)</li>
                        <li><i class="fas fa-globe me-2 text-primary"></i>Điểm nổi bật: Trưng bày sofa & bàn ghế nhập khẩu</li>
                    </ul>
                    <div class="d-flex gap-2">
                        <a href="https://maps.google.com?q=344+Cộng+Hòa,Tân+Bình,TP.HCM" target="_blank" class="btn btn-outline-primary flex-fill">
                            <i class="fas fa-directions me-1"></i>Chỉ đường
                        </a>
                        <a href="tel:02856789999" class="btn btn-primary">
                            <i class="fas fa-phone me-1"></i>Gọi ngay
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden h-100 hover-lift">
                <img src="<?= $base ?>/assets/images/store-danang.jpg" class="card-img-top" style="height:250px; object-fit:cover;" alt="Cửa hàng Đà Nẵng">
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold text-primary mb-3">Chi nhánh Đà Nẵng</h5>
                    <ul class="list-unstyled text-muted mb-3">
                        <li class="mb-2"><i class="fas fa-map-marker-alt me-2 text-danger"></i>123 Nguyễn Văn Linh, Q.Hải Châu</li>
                        <li class="mb-2"><i class="fas fa-phone me-2 text-success"></i>Hotline: 0236.4567.8888</li>
                        <li class="mb-2"><i class="fas fa-clock me-2 text-info"></i>Giờ mở cửa: 8:00 - 21:00 (hàng ngày)</li>
                        <li><i class="fas fa-globe me-2 text-primary"></i>Điểm nổi bật: Khu máy giặt sấy hiện đại</li>
                    </ul>
                    <div class="d-flex gap-2">
                        <a href="https://maps.google.com?q=123+Nguyễn+Văn+Linh,Hải+Châu,Đà+Nẵng" target="_blank" class="btn btn-outline-primary flex-fill">
                            <i class="fas fa-directions me-1"></i>Chỉ đường
                        </a>
                        <a href="tel:023645678888" class="btn btn-primary">
                            <i class="fas fa-phone me-1"></i>Gọi ngay
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="row mt-5">
        <div class="col-12">
            <h3 class="fw-bold text-center mb-4 text-primary">Bản đồ hệ thống cửa hàng</h3>
            <div class="ratio ratio-21x9 rounded-4 shadow-lg overflow-hidden">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3919.5000000000005!2d105.85000000000001!3d21.028511!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMjjCsDAxJzM0LjQiTiAxMDXCsTAxJzAwLjAiRQ!5e0!3m2!1svi!2s!4v1630000000000" 
                        width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
            <p class="text-center text-muted mt-3 small">Click "Chỉ đường" để xem chi tiết trên Google Maps</p>
        </div>
    </div>

</div>

<style>
    .text-shadow { text-shadow: 2px 2px 8px rgba(0,0,0,0.8); }
    .hover-lift { transition: all 0.4s ease; }
    .hover-lift:hover { transform: translateY(-10px); box-shadow: 0 20px 40px rgba(0,0,0,0.15)!important; }
    .position-absolute.w-100 { max-width: 90%; }
    @media (max-width: 768px) {
        .display-2 { font-size: 2.5rem; }
        .fs-3 { font-size: 1.4rem !important; }
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