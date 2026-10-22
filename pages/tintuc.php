<?php 
$base = "/web-ban-do-gia-dung";
$page_title = "Tin tức & Kinh nghiệm - Gia Dụng Shop";

require_once '../config/database.php';
require_once '../includes/functions.php';

require_once '../includes/header.php'; 
?>

<!-- HERO SECTION -->
<?php $banner = ['title' => 'Tin tức & kinh nghiệm', 'description' => 'Gợi ý chọn mua và sử dụng đồ gia dụng.', 'image' => 'news-hero.jpg']; require __DIR__ . '/../includes/page_banner.php'; ?>

<div class="container py-5">
    <div class="row g-5">

        <div class="col-12 mb-5">
            <a href="<?= $base ?>/pages/tintuc-detail/top-10-tu-lanh-tiet-kiem-dien-2025" class="text-decoration-none">
                <div class="card border-0 shadow-lg rounded-4 overflow-hidden hover-lift">
                    <div class="row g-0 align-items-center">
                        <div class="col-lg-7">
                            <img src="<?= $base ?>/assets/images/news-featured.jpg" class="img-fluid w-100 h-100" style="object-fit:cover; min-height:380px;" alt="Top 10 tủ lạnh tiết kiệm điện 2025">
                        </div>
                        <div class="col-lg-5 p-5 bg-white">
                            <span class="badge bg-danger fs-6 px-3 py-2 mb-3">NỔI BẬT</span>
                            <h2 class="display-5 fw-bold text-dark mb-3">
                                Top 10 tủ lạnh tiết kiệm điện đáng mua nhất 2025
                            </h2>
                            <p class="text-muted fs-5 mb-4">
                                Tổng hợp công nghệ Inverter mới nhất, giảm tới 60% điện, ưu đãi riêng chỉ có tại Gia Dụng Shop!
                            </p>
                            <div class="d-flex align-items-center text-muted mb-4">
                                <span><i class="far fa-calendar-alt me-2"></i>18/11/2025</span>
                                <span class="mx-3">•</span>
                                <span><i class="far fa-eye me-2"></i>15.842 lượt xem</span>
                            </div>
                            <span class="btn btn-primary btn-lg px-5 fw-bold">ĐỌC NGAY</span>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- DANH SÁCH TIN KHÁC -->
        <div class="col-lg-8">

            <a href="<?= $base ?>/pages/tintuc-detail/sale-cuoi-nam-giam-50-qua-5-trieu" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 hover-lift">
                    <div class="row g-0">
                        <div class="col-md-5">
                            <img src="<?= $base ?>/assets/images/news-sale.jpg" class="img-fluid h-100" style="object-fit:cover;" alt="Sale cuối năm">
                        </div>
                        <div class="col-md-7 p-4 bg-white">
                            <span class="badge bg-success mb-2">KHUYẾN MÃI</span>
                            <h5 class="fw-bold mb-2">Sale cuối năm: Giảm tới 50% + Quà tặng 5 triệu</h5>
                            <p class="text-muted mb-3">Chỉ từ nay đến 31/12 – Cơ hội vàng sở hữu đồ gia dụng cao cấp!</p>
                            <small class="text-muted"><i class="far fa-calendar-alt"></i> 15/11/2025 • 21.356 lượt xem</small>
                        </div>
                    </div>
                </div>
            </a>

            <a href="<?= $base ?>/pages/tintuc-detail/may-giat-cua-truoc-hay-cua-tren" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 hover-lift">
                    <div class="row g-0">
                        <div class="col-md-5">
                            <img src="<?= $base ?>/assets/images/news-maygiat.jpg" class="img-fluid h-100" style="object-fit:cover;" alt="Máy giặt cửa trước vs cửa trên">
                        </div>
                        <div class="col-md-7 p-4 bg-white">
                            <span class="badge bg-info mb-2">KINH NGHIỆM</span>
                            <h5 class="fw-bold mb-2">Máy giặt cửa trước vs cửa trên: Nên chọn loại nào 2025?</h5>
                            <p class="text-muted mb-3">So sánh chi tiết ưu nhược điểm, giá bán, thương hiệu tốt nhất...</p>
                            <small class="text-muted"><i class="far fa-calendar-alt"></i> 12/11/2025 • 9.874 lượt xem</small>
                        </div>
                    </div>
                </div>
            </a>

            <a href="<?= $base ?>/pages/tintuc-detail/meo-chon-sofa-khong-lun" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5 hover-lift">
                    <div class="row g-0">
                        <div class="col-md-5">
                            <img src="<?= $base ?>/assets/images/news-sofa.jpg" class="img-fluid h-100" style="object-fit:cover;" alt="Mẹo chọn sofa">
                        </div>
                        <div class="col-md-7 p-4 bg-white">
                            <span class="badge bg-warning mb-2">MẸO HAY</span>
                            <h5 class="fw-bold mb-2">5 mẹo chọn sofa bền đẹp, không lún chỉ sau 1 năm</h5>
                            <p class="text-muted mb-3">Kinh nghiệm thực tế từ 5.000+ khách hàng tại Gia Dụng Shop...</p>
                            <small class="text-muted"><i class="far fa-calendar-alt"></i> 10/11/2025 • 7.291 lượt xem</small>
                        </div>
                    </div>
                </div>
            </a>

            <div class="text-center">
                <a href="#" class="btn btn-outline-primary btn-lg px-5 fw-bold">XEM THÊM TIN TỨC</a>
            </div>
        </div>

        <!-- SIDEBAR -->
        <div class="col-lg-4">
            <!-- Tìm kiếm -->
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                <h5 class="fw-bold mb-3">Tìm kiếm tin tức</h5>
                <form action="<?= $base ?>/tim-kiem-tin-tuc.php" method="GET">
                    <div class="input-group">
                        <input type="text" name="q" class="form-control rounded-start-pill" placeholder="Nhập từ khóa..." required>
                        <button class="btn btn-primary rounded-end-pill"><i class="fas fa-search"></i></button>
                    </div>
                </form>
            </div>

            <!-- Chuyên mục -->
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                <h5 class="fw-bold mb-4 text-primary">Chuyên mục</h5>
                <div class="list-group list-group-flush">
                    <a href="<?= $base ?>/tin-khuyen-mai" class="list-group-item list-group-item-action py-3">Khuyến mãi HOT</a>
                    <a href="<?= $base ?>/kinh-nghiem-tu-lanh" class="list-group-item list-group-item-action py-3">Tủ lạnh - Máy lạnh</a>
                    <a href="<?= $base ?>/kinh-nghiem-may-giat" class="list-group-item list-group-item-action py-3">Máy giặt - Máy sấy</a>
                    <a href="<?= $base ?>/kinh-nghiem-noi-that" class="list-group-item list-group-item-action py-3">Nội thất & Sofa</a>
                    <a href="<?= $base ?>/huong-dan-bao-hanh" class="list-group-item list-group-item-action py-3">Hướng dẫn bảo hành</a>
                </div>
            </div>

            <!-- Tin nổi bật -->
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h5 class="fw-bold mb-4 text-primary">Tin nổi bật</h5>
                <a href="<?= $base ?>/pages/tintuc-detail/black-friday-2025" class="text-decoration-none text-dark d-block mb-4">
                    <img src="<?= $base ?>/assets/images/hot-blackfriday.jpg" class="img-fluid rounded-3 mb-3" alt="Black Friday">
                    <h6 class="fw-bold">Black Friday 2025: Giảm sốc tới 70%</h6>
                    <small class="text-muted">15/11/2025</small>
                </a>
                <a href="<?= $base ?>/pages/tintuc-detail/may-giat-duoi-10-trieu" class="text-decoration-none text-dark d-block">
                    <img src="<?= $base ?>/assets/images/hot-maygiat.jpg" class="img-fluid rounded-3 mb-3" alt="Máy giặt dưới 10 triệu">
                    <h6 class="fw-bold">Top 5 máy giặt dưới 10 triệu đáng mua nhất</h6>
                    <small class="text-muted">10/11/2025</small>
                </a>
            </div>
        </div>
    </div>
</div>

<style>
    .text-shadow { 
        text-shadow: 2px 2px 8px rgba(0,0,0,0.8); 
    }
    .hover-lift { 
        transition: all 0.4s ease; 
    }
    .hover-lift:hover { 
        transform: translateY(-12px); 
        box-shadow: 0 25px 50px rgba(0,0,0,0.18)!important; 
    }
    .position-absolute.w-100 {
        max-width: 90%;
    }
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