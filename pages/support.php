<?php 
$base = "/web-ban-do-gia-dung";
$page_title = "Hỗ trợ khách hàng - Gia Dụng Shop";

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';
?>

<!-- HERO HỖ TRỢ -->
<?php $banner = ['title' => 'Hỗ trợ khách hàng', 'description' => 'Giải đáp và đồng hành cùng bạn khi mua sắm.', 'image' => 'support-hero.jpg']; require __DIR__ . '/../includes/page_banner.php'; ?>

<div class="container py-5">
    <div class="row g-5">

        <!-- CÁC KÊNH HỖ TRỢ NHANH -->
        <div class="col-12 mb-5">
            <div class="row g-4 text-center">

                <div class="col-md-3 col-6">
                    <div class="card border-0 shadow-lg rounded-4 p-4 hover-lift h-100">
                        <div class="mb-4">
                            <i class="fas fa-phone-volume text-primary" style="font-size: 3.5rem;"></i>
                        </div>
                        <h5 class="fw-bold mb-3">Hotline miễn phí</h5>
                        <h4 class="text-danger fw-bold">0365539435</h4>
                        <p class="text-muted small">(Miễn phí toàn quốc - 7h30 → 22h)</p>
                        <a href="tel:0365539435" class="btn btn-outline-danger btn-sm mt-2">Gọi ngay</a>
                    </div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="card border-0 shadow-lg rounded-4 p-4 hover-lift h-100">
                        <div class="mb-4">
                            <i class="fab fa-facebook-messenger text-primary" style="font-size: 3.5rem;"></i>
                        </div>
                        <h5 class="fw-bold mb-3">Chat Facebook</h5>
                        <p class="text-muted">Phản hồi trong vòng 30 giây</p>
                        <a href="https://m.me/giadungshop" target="_blank" class="btn btn-primary btn-sm mt-2">
                            <i class="fab fa-facebook-f me-2"></i>Nhắn tin ngay
                        </a>
                    </div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="card border-0 shadow-lg rounded-4 p-4 hover-lift h-100">
                        <div class="mb-4">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/9/91/Icon_of_Zalo.svg"
                                 alt="Zalo" class="img-fluid" style="width: 60px;">
                        </div>
                        <h5 class="fw-bold mb-3">Zalo OA</h5>
                        <p class="text-muted">Quét mã hoặc click để chat</p>
                        <a href="https://zalo.me/0365539435" target="_blank" class="btn btn-primary btn-sm mt-2">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/9/91/Icon_of_Zalo.svg"
                                 alt="Zalo" style="width: 20px; margin-right: 6px;">
                            Chat Zalo
                        </a>
                    </div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="card border-0 shadow-lg rounded-4 p-4 hover-lift h-100">
                        <div class="mb-4">
                            <i class="fas fa-envelope text-primary" style="font-size: 3.5rem;"></i>
                        </div>
                        <h5 class="fw-bold mb-3">Email hỗ trợ</h5>
                        <p class="text-muted small">hotro@giadungshop.vn</p>
                        <a href="mailto:hotro@giadungshop.vn" class="btn btn-outline-primary btn-sm mt-2">Gửi email</a>
                    </div>
                </div>

            </div>
        </div>

        <!-- CÂU HỎI THƯỜNG GẶP -->
        <div class="col-lg-8">
            <h2 class="fw-bold text-primary mb-5 text-center text-lg-start">
                <i class="fas fa-question-circle me-3"></i>Câu hỏi thường gặp (FAQ)
            </h2>

            <div class="accordion shadow-lg rounded-4 overflow-hidden" id="faqPro">

                <!-- FAQ 1 -->
                <div class="accordion-item border-0 mb-3 rounded-4 overflow-hidden shadow-sm">
                    <h2 class="accordion-header" id="heading1">
                        <button class="accordion-button collapsed fw-bold fs-5 bg-white text-dark rounded-4" type="button" 
                                data-bs-toggle="collapse" data-bs-target="#collapse1">
                            <span class="me-3 text-primary">01</span>
                            Làm sao để kiểm tra tình trạng đơn hàng của tôi?
                        </button>
                    </h2>
                    <div id="collapse1" class="accordion-collapse collapse" data-bs-parent="#faqPro">
                        <div class="accordion-body bg-light pt-4">
                            <p class="mb-3">Bạn có thể tra cứu nhanh qua 3 cách:</p>
                            <ul class="list-unstyled">
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Vào trang <a href="<?= $base ?>/pages/tra-cuu-don-hang.php" class="text-primary fw-bold">Tra cứu đơn hàng</a></li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Gọi hotline <strong class="text-danger">0365539435</strong> (miễn phí)</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Inbox Fanpage/Zalo OA → cung cấp mã đơn</li>
                            </ul>
                            <small class="text-muted">Phản hồi trong vòng 30 giây</small>
                        </div>
                    </div>
                </div>

                <!-- FAQ 2 -->
                <div class="accordion-item border-0 mb-3 rounded-4 overflow-hidden shadow-sm">
                    <h2 class="accordion-header" id="heading2">
                        <button class="accordion-button collapsed fw-bold fs-5 bg-white text-dark rounded-4" type="button" 
                                data-bs-toggle="collapse" data-bs-target="#collapse2">
                            <span class="me-3 text-primary">02</span>
                            Chính sách bảo hành & đổi trả sản phẩm?
                        </button>
                    </h2>
                    <div id="collapse2" class="accordion-collapse collapse" data-bs-parent="#faqPro">
                        <div class="accordion-body bg-light pt-4">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <h6 class="fw-bold text-success"><i class="fas fa-shield-alt me-2"></i> Bảo hành chính hãng</h6>
                                    <p class="small mb-2">Tủ lạnh, máy giặt: <strong>2–10 năm</strong> (tùy hãng)</p>
                                    <p class="small mb-0">Nội thất, sofa: <strong>1–5 năm</strong></p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="fw-bold text-danger"><i class="fas fa-exchange-alt me-2"></i> Đổi trả miễn phí</h6>
                                    <p class="small mb-0">Đổi mới 1-1 trong <strong>30 ngày</strong> nếu lỗi nhà sản xuất</p>
                                </div>
                            </div>
                            <div class="mt-3 p-3 bg-white rounded-3 border">
                                <small>Đặc biệt: Bảo hành điện tử – không cần phiếu giấy</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FAQ 3 -->
                <div class="accordion-item border-0 mb-3 rounded-4 overflow-hidden shadow-sm">
                    <h2 class="accordion-header" id="heading3">
                        <button class="accordion-button collapsed fw-bold fs-5 bg-white text-dark rounded-4" type="button" 
                                data-bs-toggle="collapse" data-bs-target="#collapse3">
                            <span class="me-3 text-primary">03</span>
                            Có trả góp 0% lãi suất không? Hồ sơ cần gì?
                        </button>
                    </h2>
                    <div id="collapse3" class="accordion-collapse collapse" data-bs-parent="#faqPro">
                        <div class="accordion-body bg-light pt-4">
                            <div class="text-center mb-4">
                                <h4 class="text-success fw-bold">TRẢ GÓP 0% – DUYỆT TRONG 10 PHÚT</h4>
                            </div>
                            <div class="row text-center g-3">
                                <div class="col-4">
                                    <div class="bg-white rounded-3 p-3 shadow-sm">
                                        <h5 class="text-primary">18</h5>
                                        <small>Ngân hàng & tài chính</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="bg-white rounded-3 p-3 shadow-sm">
                                        <h5 class="text-primary">0₫</h5>
                                        <small>Trả trước</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="bg-white rounded-3 p-3 shadow-sm">
                                        <h5 class="text-primary">36</h5>
                                        <small>Tháng kỳ hạn</small>
                                    </div>
                                </div>
                            </div>
                            <div class="alert alert-warning mt-3 small">
                                <i class="fas fa-id-card me-2"></i>
                                Chỉ cần <strong>CMND/CCCD</strong> – Không cần chứng minh thu nhập
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FAQ 4 -->
                <div class="accordion-item border-0 mb-3 rounded-4 overflow-hidden shadow-sm">
                    <h2 class="accordion-header" id="heading4">
                        <button class="accordion-button collapsed fw-bold fs-5 bg-white text-dark rounded-4" type="button" 
                                data-bs-toggle="collapse" data-bs-target="#collapse4">
                            <span class="me-3 text-primary">04</span>
                            Chính sách giao hàng & lắp đặt toàn quốc?
                        </button>
                    </h2>
                    <div id="collapse4" class="accordion-collapse collapse" data-bs-parent="#faqPro">
                        <div class="accordion-body bg-light pt-4">
                            <table class="table table-bordered bg-white">
                                <thead class="table-success">
                                    <tr>
                                        <th>Giá trị đơn hàng</th>
                                        <th>Phí giao hàng</th>
                                        <th>Lắp đặt</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Từ 10 triệu</td>
                                        <td class="text-success fw-bold">Miễn phí 100%</td>
                                        <td class="text-success fw-bold">Miễn phí</td>
                                    </tr>
                                    <tr>
                                        <td>Từ 5 triệu</td>
                                        <td class="text-success fw-bold">Miễn phí</td>
                                        <td>200k–500k</td>
                                    </tr>
                                    <tr>
                                        <td>Dưới 5 triệu</td>
                                        <td>30k–150k</td>
                                        <td>Theo thực tế</td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="bg-primary text-white p-3 rounded-3 text-center mt-3">
                                <i class="fas fa-truck me-2"></i>
                                <strong>Nội thành HN & HCM: Giao nhanh 2–4h – Miễn phí hoàn toàn</strong>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="text-center mt-5">
                <p class="text-muted">Không tìm thấy câu hỏi của bạn?</p>
                <a href="<?= $base ?>/pages/support.php" class="btn btn-outline-primary btn-lg px-5 rounded-pill fw-bold">
                    <i class="fas fa-headset me-2"></i>Liên hệ ngay để được hỗ trợ 24/7
                </a>
            </div>
        </div>

        <!-- FORM GỬI YÊU CẦU HỖ TRỢ -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-lg rounded-4 p-4 sticky-top" style="top: 100px;">
                <h4 class="fw-bold text-primary mb-4 text-center">Gửi yêu cầu hỗ trợ</h4>
                <form action="<?= $base ?>/pages/send-support.php" method="POST">
                    <div class="mb-3">
                        <input type="text" name="name" class="form-control form-control-lg rounded-pill" placeholder="Họ và tên" required>
                    </div>
                    <div class="mb-3">
                        <input type="tel" name="phone" class="form-control form-control-lg rounded-pill" placeholder="Số điện thoại" required>
                    </div>
                    <div class="mb-3">
                        <input type="email" name="email" class="form-control form-control-lg rounded-pill" placeholder="Email (không bắt buộc)">
                    </div>
                    <div class="mb-3">
                        <textarea name="message" rows="4" class="form-control rounded-4" placeholder="Nội dung cần hỗ trợ..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill fw-bold">
                        Gửi yêu cầu ngay
                    </button>
                </form>
                <div class="text-center mt-3">
                    <small class="text-muted">Chúng tôi sẽ phản hồi trong vòng 30 phút!</small>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
    .text-shadow { text-shadow: 2px 2px 10px rgba(0,0,0,0.9); }
    .hover-lift { transition: all 0.4s ease; }
    .hover-lift:hover { transform: translateY(-12px); box-shadow: 0 25px 50px rgba(0,0,0,0.18)!important; }
    .accordion-button:not(.collapsed) { background-color: #e3f2fd !important; color: #0d6efd !important; }
    .accordion-button:focus { box-shadow: none; }
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