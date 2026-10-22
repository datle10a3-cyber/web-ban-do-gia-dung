<?php
require_once __DIR__ . '/../includes/init.php';
if (!isAdmin()) {
    header('Location: ../index.php');
    exit;
}

$page_title = 'Quản lý Đánh giá';
$success = $_SESSION['success'] ?? '';
$error   = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf'] ?? '')) {
    $action     = $_POST['action'] ?? '';
    $review_id  = (int)($_POST['review_id'] ?? 0);
    
    if ($review_id <= 0) {
        $error = 'ID đánh giá không hợp lệ!';
    } else {
        try {
            if ($action === 'delete') {
                require_once __DIR__.'/../includes/reviews.php';
                $q=$pdo->prepare('SELECT images,video FROM product_reviews WHERE id=?');$q->execute([$review_id]);$review=$q->fetch();
                if($review){review_delete_media($review);}
                $pdo->prepare("DELETE FROM product_reviews WHERE id = ?")->execute([$review_id]);
                $_SESSION['success'] = 'Xóa đánh giá thành công!';
            }
            
            elseif ($action === 'approve') {
                $pdo->prepare("UPDATE product_reviews SET is_approved = 1 WHERE id = ?")->execute([$review_id]);
                $_SESSION['success'] = 'Duyệt đánh giá thành công!';
            }
            
            elseif ($action === 'reply') {
                $reply_text = trim($_POST['reply_text'] ?? '');
                if ($reply_text) {
                    $pdo->prepare("UPDATE product_reviews SET admin_reply = ?, admin_reply_at = NOW() WHERE id = ?")
                        ->execute([$reply_text, $review_id]);
                    $_SESSION['success'] = 'Phản hồi đã được gửi!';
                } else {
                    $error = 'Vui lòng nhập nội dung phản hồi!';
                }
            }
        } catch (Exception $e) {
            $error = 'Lỗi hệ thống: ' . $e->getMessage();
        }
    }
    
    header('Location: manage_reviews.php?' . http_build_query($_GET));
    exit;
}

$search        = trim($_GET['search'] ?? '');
$filter_rating = (int)($_GET['rating'] ?? 0);
$filter_status = $_GET['status'] ?? 'all';
$page          = max(1, (int)($_GET['page'] ?? 1));
$limit         = 20;
$offset        = ($page - 1) * $limit;

$where = ['r.id IS NOT NULL'];
$params = [];

if ($filter_status === 'pending')  $where[] = "r.is_approved = 0";
if ($filter_status === 'approved') $where[] = "r.is_approved = 1";
if ($filter_rating > 0)            { $where[] = "r.rating = ?"; $params[] = $filter_rating; }
if ($search !== '') {
    $where[] = "(p.name LIKE ? OR u.username LIKE ? OR u.display_name LIKE ? OR u.email LIKE ? OR r.review LIKE ?)";
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like, $like, $like]);
}

$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Đếm tổng
$countStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM product_reviews r
    JOIN products p ON r.product_id = p.id
    JOIN users u ON r.user_id = u.id
    $where_clause
");
$countStmt->execute($params);$total=$countStmt->fetchColumn();

$total_pages = max(1, ceil($total / $limit));

$stmt = $pdo->prepare("
    SELECT 
        r.*, 
        p.name AS product_name, 
        p.image AS product_image,
        COALESCE(u.display_name,u.username) AS username, u.email, u.avatar,
        o.order_code
    FROM product_reviews r
    JOIN products p ON r.product_id = p.id
    JOIN users u ON r.user_id = u.id
    LEFT JOIN orders o ON r.order_id = o.id
    $where_clause
    ORDER BY r.created_at DESC
    LIMIT ? OFFSET ?
");
$params[] = $limit;
$params[] = $offset;
$stmt->execute($params);
$reviews = $stmt->fetchAll();

include 'includes/admin_header.php';
?>

<div class="top-bar">
    <h2 class="mb-0"><i class="fas fa-star-half-alt me-2 text-warning"></i>Quản lý Đánh giá</h2>
    <div class="user-info">
        <span class="text-muted">Tổng: <strong><?= number_format($total) ?></strong> đánh giá</span>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card card-modern mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Tìm sản phẩm, khách hàng, nội dung..." 
                       value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-2">
                <select name="rating" class="form-select">
                    <option value="0">Tất cả sao</option>
                    <?php for($i=5;$i>=1;$i--): ?>
                        <option value="<?= $i ?>" <?= $filter_rating==$i?'selected':'' ?>><?= $i ?> sao</option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="all" <?= $filter_status==='all'?'selected':'' ?>>Tất cả</option>
                    <option value="approved" <?= $filter_status==='approved'?'selected':'' ?>>Đã duyệt</option>
                    <option value="pending" <?= $filter_status==='pending'?'selected':'' ?>>Chờ duyệt</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Tìm</button>
            </div>
            <div class="col-md-2">
                <a href="manage_reviews.php" class="btn btn-secondary w-100"><i class="fas fa-sync"></i> Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card card-modern">
    <div class="card-body p-0">
        <?php if (empty($reviews)): ?>
            <div class="text-center py-6 text-muted">
                <i class="fas fa-star fa-4x mb-3 opacity-20"></i>
                <h5>Chưa có đánh giá nào</h5>
            </div>
        <?php else: ?>
            <?php foreach ($reviews as $r): ?>
                <div class="border-bottom p-4 hover-bg">
                    <div class="row align-items-start">
                        <div class="col-auto">
                            <img src="../assets/images/products/<?= htmlspecialchars($r['product_image'] ?? 'no-image.jpg') ?>" 
                                 class="rounded shadow-sm" style="width:90px;height:90px;object-fit:cover;">
                        </div>
                        <div class="col">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="mb-1">
                                        <a href="../products/detail.php?id=<?= $r['product_id'] ?>" target="_blank" class="text-decoration-none text-primary">
                                            <?= htmlspecialchars($r['product_name']) ?>
                                        </a>
                                    </h6>
                                    <small class="text-muted">
                                        <i class="fas fa-user"></i> <?= $r['is_anonymous'] ? 'Khách hàng ẩn danh' : htmlspecialchars($r['username']) ?>
                                        • Đơn: <?= $r['order_code'] ?: 'N/A' ?>
                                        • <?= date('d/m/Y H:i', strtotime($r['created_at'])) ?>
                                    </small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-warning text-dark fs-5"><?= $r['rating'] ?> ★</span><br>
                                    <span class="badge <?= $r['is_approved'] ? 'bg-success' : 'bg-danger' ?> mt-1">
                                        <?= $r['is_approved'] ? 'Đã duyệt' : 'Chờ duyệt' ?>
                                    </span>
                                </div>
                            </div>

                            <p class="mb-2"><?= nl2br(htmlspecialchars($r['review'] ?: '(Không có nội dung)')) ?></p>

                            <!-- Hiển thị ảnh + video -->
                            <?php if ($r['images'] || $r['video']): ?>
                                <div class="row g-2 mb-3">
                                    <?php if ($r['images']): 
                                        foreach(json_decode($r['images'], true) as $img): ?>
                                            <div class="col-auto">
                                                <a href="../<?= $img ?>" target="_blank">
                                                    <img src="../<?= $img ?>" class="img-thumbnail" style="width:100px;height:100px;object-fit:cover;">
                                                </a>
                                            </div>
                                        <?php endforeach;
                                    endif; ?>
                                    <?php if ($r['video']): ?>
                                        <div class="col-auto">
                                            <video src="../<?= $r['video'] ?>" controls class="rounded" style="width:200px;"></video>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Phản hồi admin -->
                            <?php if ($r['admin_reply']): ?>
                                <div class="bg-light border-start border-primary border-4 ps-3 py-2 rounded mb-3">
                                    <strong class="text-primary"><i class="fas fa-reply"></i> Phản hồi của Shop:</strong><br>
                                    <?= nl2br(htmlspecialchars($r['admin_reply'])) ?>
                                    <small class="text-muted d-block"><?= date('d/m/Y H:i', strtotime($r['admin_reply_at'])) ?></small>
                                </div>
                            <?php endif; ?>

                            <div class="btn-group btn-group-sm">
                                <?php if (!$r['is_approved']): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="csrf" value="<?= getCsrfToken() ?>">
                                        <input type="hidden" name="review_id" value="<?= $r['id'] ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button class="btn btn-success btn-sm"><i class="fas fa-check"></i> Duyệt</button>
                                    </form>
                                <?php endif; ?>

                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#reply<?= $r['id'] ?>">
                                    <i class="fas fa-reply"></i> Trả lời
                                </button>

                                <form method="POST" class="d-inline" onsubmit="return confirm('Xóa vĩnh viễn đánh giá này? (Không thể khôi phục)');">
                                    <input type="hidden" name="csrf" value="<?= getCsrfToken() ?>">
                                    <input type="hidden" name="review_id" value="<?= $r['id'] ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Xóa</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Trả lời -->
                <div class="modal fade" id="reply<?= $r['id'] ?>">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="POST">
                                <div class="modal-header">
                                    <h5 class="modal-title">Phản hồi đánh giá</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="csrf" value="<?= getCsrfToken() ?>">
                                    <input type="hidden" name="review_id" value="<?= $r['id'] ?>">
                                    <input type="hidden" name="action" value="reply">
                                    <textarea name="reply_text" class="form-control" rows="5" required 
                                              placeholder="Nhập phản hồi tích cực, lịch sự..."><?= htmlspecialchars($r['admin_reply'] ?? '') ?></textarea>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Gửi</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Phân trang -->
    <?php if ($total_pages > 1): ?>
        <div class="card-footer bg-transparent">
            <nav>
                <ul class="pagination justify-content-center">
                    <?php for($i=1; $i<=$total_pages; $i++): ?>
                        <li class="page-item <?= $i==$page?'active':'' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page'=>$i])) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/admin_footer.php'; ?>
