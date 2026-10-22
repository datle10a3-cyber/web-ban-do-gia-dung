<?php 
require_once __DIR__.'/../includes/init.php';
if (!isAdmin()) {
    header('Location: ../index.php');
    exit;
}
$page_title = "Dashboard";

$total_users    = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_orders   = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$revenue        = $pdo->query("SELECT COALESCE(SUM(COALESCE(final_amount,total,0)),0) FROM orders WHERE status IN ('delivered','completed') AND payment_status='paid'")->fetchColumn();
$pending        = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn();
$low_stock      = $pdo->query("SELECT COUNT(*) FROM products WHERE stock <= 10 AND stock > 0")->fetchColumn();

$daily=$pdo->query("SELECT DATE(created_at) AS day,SUM(COALESCE(final_amount,total,0)) AS amount FROM orders WHERE status IN ('delivered','completed') AND payment_status='paid' AND created_at>=DATE_SUB(CURDATE(),INTERVAL 6 DAY) GROUP BY DATE(created_at)")->fetchAll(PDO::FETCH_KEY_PAIR);
$revenueDays=[];$revenueAmounts=[];for($i=6;$i>=0;$i--){$day=date('Y-m-d',strtotime('-'.$i.' days'));$revenueDays[]=date('d/m',strtotime($day));$revenueAmounts[]=(float)($daily[$day]??0);}
include 'includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="fas fa-tachometer-alt text-primary me-2"></i>Dashboard</h2>
    <div class="text-muted"><i class="far fa-calendar-alt me-2"></i><?= date('d/m/Y H:i') ?></div>
</div>

<div class="row g-4 mb-5">
    <div class="col-lg-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="me-4 text-primary">
                    <i class="fas fa-users fa-3x"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1">Tổng khách hàng</h6>
                    <h3 class="mb-0 fw-bold"><?= number_format($total_users) ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Sản phẩm -->
    <div class="col-lg-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="me-4 text-success">
                    <i class="fas fa-box fa-3x"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1">Tổng sản phẩm</h6>
                    <h3 class="mb-0 fw-bold"><?= number_format($total_products) ?></h3>
                    <?php if($low_stock > 0): ?>
                        <small class="text-danger fw-bold">Cảnh báo: <?= $low_stock ?> sản phẩm sắp hết</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="me-4 text-warning">
                    <i class="fas fa-shopping-cart fa-3x"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1">Tổng đơn hàng</h6>
                    <h3 class="mb-0 fw-bold"><?= number_format($total_orders) ?></h3>
                    <?php if($pending > 0): ?>
                        <small class="text-danger fw-bold">  <?= $pending ?> đơn đang chờ xử lý</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Doanh thu -->
    <div class="col-lg-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="me-4 text-danger">
                    <i class="fas fa-coins fa-3x"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1">Doanh thu hoàn thành</h6>
                    <h3 class="mb-0 fw-bold"><?= number_format($revenue) ?>đ</h3>
                    <small class="text-muted">Đơn đã giao + hoàn thành</small>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-bottom">
        <h5 class="mb-0 text-dark"><i class="fas fa-chart-line text-primary"></i> Doanh thu đơn đã thu tiền trong 7 ngày đặt hàng gần nhất</h5>
    </div>
    <div class="card-body">
        <canvas id="revenueChart" height="100"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('revenueChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($revenueDays) ?>,
        datasets: [{
            label: 'Doanh thu',
            data: <?= json_encode($revenueAmounts) ?>,
            borderColor: '#4361ee',
            backgroundColor: 'rgba(67, 97, 238, 0.05)',
            tension: 0.4,
            fill: true,
            pointRadius: 4
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { 
                ticks: { 
                    callback: value => value.toLocaleString('vi-VN') + 'đ',
                    font: { size: 12 }
                }
            }
        }
    }
});
</script>

<?php include 'includes/admin_footer.php'; ?>