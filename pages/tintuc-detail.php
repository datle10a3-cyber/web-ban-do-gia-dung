<?php 
$base = "/web-ban-do-gia-dung";

$slug = $_GET['slug'] ?? '';

$articles = [
    'top-10-tu-lanh-tiet-kiem-dien-2025' => [
        'title' => 'Top 10 tủ lạnh tiết kiệm điện đáng mua nhất 2025',
        'date'  => '18/11/2025',
        'views' => '15.842',
        'img'   => 'news-featured.jpg',
        'desc'  => 'Tổng hợp công nghệ Inverter mới nhất, giảm tới 60% tiền điện...'
    ],
    'sale-cuoi-nam-giam-50-qua-5-trieu' => [
        'title' => 'Sale cuối năm: Giảm tới 50% + Quà tặng 5 triệu',
        'date'  => '15/11/2025',
        'views' => '21.356',
        'img'   => 'news-sale.jpg',
        'desc'  => 'Chỉ từ nay đến 31/12 – Cơ hội vàng sở hữu đồ gia dụng cao cấp!'
    ],
    'may-giat-cua-truoc-hay-cua-tren' => [
        'title' => 'Máy giặt cửa trước vs cửa trên: Nên chọn loại nào 2025?',
        'date'  => '12/11/2025',
        'views' => '9.874',
        'img'   => 'news-maygiat.jpg',
        'desc'  => 'So sánh chi tiết ưu nhược điểm, giá bán, thương hiệu tốt nhất...'
    ],
    'meo-chon-sofa-khong-lun' => [
        'title' => '5 mẹo chọn sofa bền đẹp, không lún chỉ sau 1 năm',
        'date'  => '10/11/2025',
        'views' => '7.291',
        'img'   => 'news-sofa.jpg',
        'desc'  => 'Kinh nghiệm thực tế từ 5.000+ khách hàng tại Gia Dụng Shop...'
    ],
    'black-friday-2025' => [
        'title' => 'Black Friday 2025: Giảm sốc tới 70%',
        'date'  => '15/11/2025',
        'views' => '28.913',
        'img'   => 'hot-blackfriday.jpg',
        'desc'  => 'Siêu sale lớn nhất trong năm – Đừng bỏ lỡ!'
    ],
    'may-giat-duoi-10-trieu' => [
        'title' => 'Top 5 máy giặt dưới 10 triệu đáng mua nhất',
        'date'  => '10/11/2025',
        'views' => '13.567',
        'img'   => 'hot-maygiat.jpg',
        'desc'  => 'Hiệu năng mạnh mẽ, tiết kiệm điện, giá siêu hời!'
    ],
];

$current = $articles[$slug] ?? null;
$page_title = $current ? $current['title'] : 'Bài viết không tồn tại';

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';
?>

<section class="py-5 bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">

                <?php if ($current): ?>
                    <article>
                        <h1 class="display-4 fw-bold text-primary text-center mb-4">
                            <?= htmlspecialchars($current['title']) ?>
                        </h1>
                        <div class="text-center text-muted mb-5">
                            <span><i class="far fa-calendar-alt me-2"></i><?= $current['date'] ?></span>
                            <span class="mx-3">•</span>
                            <span><i class="far fa-eye me-2"></i><?= $current['views'] ?> lượt xem</span>
                        </div>

                        <img src="<?= $base ?>/assets/images/<?= $current['img'] ?>" 
                             class="img-fluid rounded-4 shadow-lg mb-5" style="max-height:520px; object-fit:cover; width:100%;" 
                             alt="<?= htmlspecialchars($current['title']) ?>">

                        <div class="bg-white rounded-4 shadow p-5">
                            <p class="lead fs-4 text-muted"><?= $current['desc'] ?></p>
                            <hr>
                            <p class="fs-5 text-muted">Nội dung chi tiết đang được cập nhật trong thời gian sớm nhất!</p>
                            <p class="fs-5">Bạn cần tư vấn ngay? Gọi hotline <strong class="text-danger">0365539435</strong> (miễn phí) hoặc inbox Zalo/Fanpage để được hỗ trợ 24/7 nhé!</p>
                        </div>

                        <div class="text-center mt-5">
                            <a href="<?= $base ?>/pages/tintuc.php" class="btn btn-outline-primary btn-lg px-5 rounded-pill">
                                Quay lại tin tức
                            </a>
                        </div>
                    </article>

                <?php else: ?>
                    <div class="text-center py-5">
                        <h1 class="display-3 text-danger">404 - Không tìm thấy bài viết</h1>
                        <p class="fs-3 text-muted">Xin lỗi, bài viết bạn cần tìm không tồn tại hoặc đã bị xóa.</p>
                        <a href="<?= $base ?>/pages/tintuc.php" class="btn btn-primary btn-lg px-5 mt-3">Về trang tin tức</a>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</section>

<?php require_once '../includes/footer.php'; ?>