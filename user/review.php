<?php
require_once __DIR__.'/../includes/init.php';
require_once __DIR__.'/../includes/reviews.php';
requireLogin();

$page_title = 'Đánh giá sản phẩm';

$order_id = (int)($_GET['order_id'] ?? 0);
$user_id  = $_SESSION['user_id'];

if ($order_id <= 0) {
    header('Location: orders.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT o.*, COALESCE(u.display_name,u.username) AS username 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    WHERE o.id = ? AND o.user_id = ? AND o.status IN ('delivered', 'completed')
");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die('<div class="container py-5 text-center"><h3 class="text-danger">Đơn hàng không hợp lệ hoặc chưa giao thành công.</h3><a href="orders.php" class="btn btn-primary mt-3">Quay lại</a></div>');
}

$stmt = $pdo->prepare("
    SELECT 
        oi.product_id,
        oi.variant_id,
        oi.quantity,
        oi.price,
        p.name AS product_name,
        p.image AS product_image,
        pv.image AS variant_image,      
        pv.color,
        pv.capacity
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    LEFT JOIN product_variants pv ON oi.variant_id = pv.id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$success = $error = $first_product_id = 0;
$edit_review_id = (int)($_GET['edit_review_id'] ?? 0);
$edit_data = null;

if ($edit_review_id > 0 && $_SERVER['REQUEST_METHOD'] !== 'POST' && empty($_POST)) {    $stmt = $pdo->prepare("
        SELECT rating, review, images, video, is_anonymous, product_id 
        FROM product_reviews 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$edit_review_id, $user_id]);
    $edit_data = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!is_string($_POST['csrf']??null)||!hash_equals($_SESSION['csrf'],$_POST['csrf'])){http_response_code(403);exit('Phiên đã hết hạn.');}
    $ratings   = $_POST['rating'] ?? [];
    $comments  = $_POST['comment'] ?? [];
    $anonymous = $_POST['anonymous'] ?? [];
    $media     = $_POST['media'] ?? [];

    $valid = is_array($ratings)&&is_array($comments)&&is_array($anonymous)&&is_array($media);
    foreach ($products as $p) {
        $pid = $p['product_id'];
        if (!$valid||!isset($ratings[$pid])||!is_scalar($ratings[$pid])||!is_string($comments[$pid]??'')||mb_strlen($comments[$pid]??'')>5000||!is_string($media[$pid]??'[]')||$ratings[$pid]<1||$ratings[$pid]>5) {
            $valid = false;
            break;
        }
    }

    if (!$valid) {
        $error = 'Vui lòng chọn số sao cho tất cả sản phẩm!';
    } else {
        $pdo->beginTransaction();
        try {
        $stmt = $pdo->prepare("
            INSERT INTO product_reviews 
            (user_id, product_id, variant_id, order_id, rating, review, images, video, is_anonymous, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
                rating = VALUES(rating),
                review = VALUES(review),
                images = VALUES(images),
                video = VALUES(video),
                is_anonymous = VALUES(is_anonymous)
        ");

        foreach ($products as $p) {
            $pid = $p['product_id'];
            $vid = $p['variant_id'] ?? null;

            $mediaJson = $media[$pid] ?? '[]';
            $mediaArr  = json_decode($mediaJson, true);
            if(!is_array($mediaArr)||count($mediaArr)>6){throw new RuntimeException('Danh sách ảnh/video không hợp lệ.');}

            $imagePaths = [];
            $videoPath  = '';
            foreach ($mediaArr as $m) {
                if(!is_array($m)||!is_string($m['path']??null)||!is_string($m['type']??null)){http_response_code(422);exit('Tệp đánh giá không hợp lệ.');}
                try{$path=review_media_path($m['path'],(int)$user_id,$m['type']);}catch(RuntimeException $e){http_response_code(422);exit(sanitize($e->getMessage()));}
                if($m['type']==='image')$imagePaths[]=$path;
                if($m['type']==='video')$videoPath=$path;
            }
            $imagesJson = !empty($imagePaths) ? json_encode($imagePaths) : null;

            $stmt->execute([
                $user_id,
                $pid,
                $vid,
                $order_id,
                (int)$ratings[$pid],
                trim($comments[$pid] ?? ''),
                $imagesJson,
                $videoPath,
                !empty($anonymous[$pid]) ? 1 : 0
            ]);

            if (!$first_product_id) $first_product_id = $pid;
        }

        $pdo->commit();$success = 'Gửi đánh giá thành công! Cảm ơn bạn đã góp ý.';
        }catch(Throwable $e){if($pdo->inTransaction()){$pdo->rollBack();}$error='Chưa thể lưu đánh giá. Vui lòng kiểm tra nội dung và tệp đính kèm.';}
    }
}

include '../includes/header.php';
?>

<style>
    :root {--primary:#1976D2;--light-blue:#E3F2FD;--radius:16px;--shadow:0 6px 25px rgba(0,0,0,0.1);}
    body{background:var(--light-blue);font-family:'Segoe UI',sans-serif;}
    .review-container{background:#fff;border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden;margin:2rem auto;max-width:900px;}
    .review-header{background:linear-gradient(135deg,#1976D2,#42A5F5);color:#fff;padding:2.5rem 2rem;text-align:center;}
    .review-header h2{font-size:2rem;margin:0;font-weight:700;}
    .product-review{padding:2rem;border-bottom:1px solid #eee;}
    .product-review:last-child{border-bottom:none;}
    .product-info{display:flex;gap:1.2rem;align-items:center;margin-bottom:1rem;}
    .product-info img{width:90px;height:90px;object-fit:cover;border-radius:12px;border:2px solid #eee;}
    .product-name{font-weight:600;color:var(--primary);}
    .variant{font-size:0.92rem;color:#666;}
    .rating-stars{direction:rtl;unicode-bidi:bidi-override;display:inline-block;margin:1.2rem 0;}
    .rating-stars input{display:none;}
    .rating-stars label{font-size:2.8rem;color:#ddd;cursor:pointer;padding:0 6px;}
    .rating-stars label:before{content:"★";}
    .rating-stars label:hover,.rating-stars label:hover~label,.rating-stars input:checked~label{color:#FFD700 !important;}
    textarea.form-control{border-radius:12px;border:1.5px solid #ddd;padding:1rem;min-height:100px;resize:vertical;}
    .media-upload-zone{border:2px dashed #ccc;border-radius:12px;padding:1.5rem;text-align:center;background:#f8fbff;cursor:pointer;margin-top:1rem;}
    .media-upload-zone.dragover{border-color:var(--primary);background:#e3f2fd;}
    .media-preview{display:flex;flex-wrap:wrap;gap:10px;margin-top:10px;}
    .media-item{position:relative;width:100px;height:100px;border-radius:8px;overflow:hidden;border:2px solid #eee;}
    .media-item img,.media-item video{width:100%;height:100%;object-fit:cover;}
    .media-remove{position:absolute;top:4px;right:4px;background:rgba(0,0,0,0.7);color:#fff;width:24px;height:24px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;}
    .anonymous-check{margin:1rem 0;color:#555;}
    .btn-submit{background:linear-gradient(135deg,#1976D2,#42A5F5);color:#fff;border:none;padding:1rem 3.5rem;border-radius:50px;font-weight:600;font-size:1.2rem;}
    .success-message{background:#d4edda;color:#155724;padding:2.5rem;border-radius:var(--radius);text-align:center;}
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

<div class="container py-4">
    <div class="review-container">
        <div class="review-header">
            <h2>Đánh giá sản phẩm</h2>
            <p>Đơn hàng #<?= str_pad($order_id,6,'0',STR_PAD_LEFT) ?> • <?= date('d/m/Y',strtotime($order['created_at'])) ?></p>
        </div>

        <div class="p-4">
            <?php if ($success): ?>
                <div class="success-message">
                    <h4><?= $success ?></h4><br>
                    <a href="../products/detail.php?id=<?= $first_product_id ?>#reviews" class="btn btn-warning btn-lg px-5">Xem đánh giá của bạn ngay</a>
                    <a href="orders.php" class="btn btn-success btn-lg px-5 ms-3">Quay lại đơn hàng</a>
                </div>
            <?php else: ?>
                <form method="POST" id="reviewForm">
                    <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">

                    <?php foreach ($products as $p): 
                        $pid = $p['product_id'];
                        $variant = [];
                        if ($p['color']) $variant[] = "Màu: ".$p['color'];
                        if ($p['capacity']) $variant[] = "Dung tích: ".$p['capacity'];
                    ?>
                        <div class="product-review">
                            <div class="product-info">
<?php 
$review_thumb = 'no-image.jpg';

if (!empty($p['order_item_image'])) {
    $review_thumb = $p['order_item_image'];
} elseif (!empty($p['variant_image'])) {
    $review_thumb = $p['variant_image'];
} elseif (!empty($p['product_image'])) {
    $review_thumb = $p['product_image'];
}
?>
<img src="../assets/images/products/<?= htmlspecialchars($review_thumb) ?>" 
     onerror="this.src='../assets/images/products/no-image.jpg'"
     alt="<?= htmlspecialchars($p['product_name']) ?>">                                <div>
                                    <div class="product-name"><?= htmlspecialchars($p['product_name']) ?></div>
                                    <?php if ($variant): ?><div class="variant"><?= implode(' | ',$variant) ?></div><?php endif; ?>
                                    <small class="text-muted">SL: <?= $p['quantity'] ?> × <?= number_format($p['price']) ?>đ</small>
                                </div>
                            </div>
<div class="rating-stars" data-current="<?= $edit_data && $edit_data['product_id'] == $pid ? $edit_data['rating'] : 0 ?>">
    <input type="radio" name="rating[<?= $pid ?>]" value="5" id="s5_<?= $pid ?>" <?= ($edit_data && $edit_data['product_id'] == $pid && $edit_data['rating']==5)?'checked':'' ?> required><label for="s5_<?= $pid ?>"></label>
    <input type="radio" name="rating[<?= $pid ?>]" value="4" id="s4_<?= $pid ?>" <?= ($edit_data && $edit_data['product_id'] == $pid && $edit_data['rating']==4)?'checked':'' ?>><label for="s4_<?= $pid ?>"></label>
    <input type="radio" name="rating[<?= $pid ?>]" value="3" id="s3_<?= $pid ?>" <?= ($edit_data && $edit_data['product_id'] == $pid && $edit_data['rating']==3)?'checked':'' ?>><label for="s3_<?= $pid ?>"></label>
    <input type="radio" name="rating[<?= $pid ?>]" value="2" id="s2_<?= $pid ?>" <?= ($edit_data && $edit_data['product_id'] == $pid && $edit_data['rating']==2)?'checked':'' ?>><label for="s2_<?= $pid ?>"></label>
    <input type="radio" name="rating[<?= $pid ?>]" value="1" id="s1_<?= $pid ?>" <?= ($edit_data && $edit_data['product_id'] == $pid && $edit_data['rating']==1)?'checked':'' ?>><label for="s1_<?= $pid ?>"></label>
</div>

<textarea name="comment[<?= $pid ?>]" class="form-control" placeholder="Chia sẻ trải nghiệm của bạn..." rows="3"><?= $edit_data && $edit_data['product_id'] == $pid ? htmlspecialchars($edit_data['review']) : '' ?></textarea>

<div class="form-check anonymous-check">
    <input class="form-check-input" type="checkbox" name="anonymous[<?= $pid ?>]" id="anon_<?= $pid ?>" <?= ($edit_data && $edit_data['product_id'] == $pid && $edit_data['is_anonymous'])?'checked':'' ?>>
    <label class="form-check-label" for="anon_<?= $pid ?>">Ẩn tên (hiển thị là Khách hàng)</label>
</div>

<!-- Ảnh + video cũ khi sửa -->
<?php if ($edit_data && $edit_data['product_id'] == $pid): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const pid = <?= $pid ?>;
        const hidden = document.getElementById('media_' + pid);
        let list = [];
        
        <?php 
        if (!empty($edit_data['images'])) {
            $imgs = json_decode($edit_data['images'], true);
            foreach($imgs as $img) echo "list.push({type:'image', path:'$img'});\n";
        }
        if (!empty($edit_data['video'])) echo "list.push({type:'video', path:'".$edit_data['video']."'});\n";
        ?>
        
        hidden.value = JSON.stringify(list);
        // Render lại preview
        const preview = document.getElementById('preview_' + pid);
        preview.innerHTML = '';
        list.forEach((item,i) => {
            const div = document.createElement('div');
            div.className = 'media-item';
            if(item.type==='image'){
                div.innerHTML = `<img src="../${item.path}?t=${Date.now()}"><div class="media-remove" onclick="this.parentElement.remove();list.splice(${i},1);hidden.value=JSON.stringify(list);render_${pid}();">×</div>`;
            } else {
                div.innerHTML = `<video src="../${item.path}?t=${Date.now()}" controls></video><div class="media-remove" onclick="this.parentElement.remove();list.splice(${i},1);hidden.value=JSON.stringify(list);render_${pid}();">×</div>`;
            }
            preview.appendChild(div);
        });
    });
    </script>
<?php endif; ?>

                           

                            <div class="media-upload-zone" data-pid="<?= $pid ?>">
                                <p>Thêm ảnh (tối đa 5) hoặc video (1)</p>
                                <input type="file" multiple accept="image/*,video/*" style="display:none">
                            </div>
                            <div class="media-preview" id="preview_<?= $pid ?>"></div>
                            <input type="hidden" name="media[<?= $pid ?>]" id="media_<?= $pid ?>" value="[]">
                        </div>
                    <?php endforeach; ?>

                    <?php if ($error): ?><div class="alert alert-danger mt-3"><?= $error ?></div><?php endif; ?>

                    <div class="text-center mt-4">
                        <button type="submit" class="btn-submit">Gửi tất cả đánh giá</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.querySelectorAll('.media-upload-zone').forEach(zone => {
    const pid = zone.dataset.pid;
    const input = zone.querySelector('input[type="file"]');
    const preview = document.getElementById('preview_' + pid);
    const hidden = document.getElementById('media_' + pid);

    let list = [];
    try {
        list = JSON.parse(hidden.value || '[]');
    } catch(e) {
        list = [];
    }

    const render = () => {
        preview.innerHTML = '';
        list.forEach((item, i) => {
            const div = document.createElement('div');
            div.className = 'media-item';
            if (item.type === 'image') {
                div.innerHTML = `<img src="../${item.path}?t=${Date.now()}">
                                 <div class="media-remove" onclick="removeItem(${i})">×</div>`;
            } else {
                div.innerHTML = `<video src="../${item.path}?t=${Date.now()}" controls></video>
                                 <div class="media-remove" onclick="removeItem(${i})">×</div>`;
            }
            preview.appendChild(div);
        });
    };

    window.removeItem = function(i) {
        list.splice(i, 1);
        hidden.value = JSON.stringify(list);
        render();
    };

    const upload = file => {
        const imagesCount = list.filter(x => x.type === 'image').length;
        const hasVideo = list.some(x => x.type === 'video');

        if (file.type.startsWith('video/')) {
            if (hasVideo) return Swal.fire('Lỗi', 'Chỉ được upload 1 video', 'warning');
        } else {
            if (imagesCount >= 5) return Swal.fire('Lỗi', 'Tối đa 5 ảnh', 'warning');
        }

        const fd = new FormData();
        fd.append('file', file);
        fd.append('csrf', '<?= $_SESSION['csrf'] ?>');

        fetch('../ajax/upload_review_media.php', {method: 'POST', body: fd})
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                list.push({type: file.type.startsWith('image/') ? 'image' : 'video', path: res.path});
                hidden.value = JSON.stringify(list);
                render();
            } else {
                Swal.fire('Upload thất bại', res.message || 'Lỗi server', 'error');
            }
        })
        .catch(() => Swal.fire('Lỗi', 'Không kết nối được server', 'error'));
    };

    zone.onclick = () => input.click();
    input.onchange = () => Array.from(input.files).forEach(upload);

    zone.ondragover = e => { e.preventDefault(); zone.classList.add('dragover'); };
    zone.ondragleave = () => zone.classList.remove('dragover');
    zone.ondrop = e => {
        e.preventDefault();
        zone.classList.remove('dragover');
        Array.from(e.dataTransfer.files).forEach(upload);
    };

    render();
});
</script>

<?php include '../includes/footer.php'; ?>
