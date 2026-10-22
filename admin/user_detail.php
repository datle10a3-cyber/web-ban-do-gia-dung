<?php
require_once __DIR__.'/../includes/init.php';
if(!isAdmin()){http_response_code(403);exit('Không có quyền truy cập.');}
$q=$pdo->prepare('SELECT id,username,display_name,email,phone,role,created_at FROM users WHERE id=?');$q->execute([(int)($_GET['id']??0)]);$person=$q->fetch();
if(!$person){http_response_code(404);exit('Không tìm thấy tài khoản.');}
$q=$pdo->prepare('SELECT id,order_code,status,final_amount FROM orders WHERE user_id=? ORDER BY id DESC LIMIT 20');$q->execute([$person['id']]);$orders=$q->fetchAll();
$page_title='Thông tin người dùng';require __DIR__.'/includes/admin_header.php';
?><main class="container py-4"><a href="manage_users.php">Quay lại người dùng</a><h1 class="h3 mt-3"><?= sanitize(account_display_name($person)) ?></h1><dl><dt>Email liên hệ</dt><dd><?= sanitize(account_public_email($person))?:'Chưa bổ sung' ?></dd><dt>Số điện thoại</dt><dd><?= sanitize($person['phone'])?:'Chưa bổ sung' ?></dd><dt>Vai trò</dt><dd><?= sanitize($person['role']) ?></dd></dl><h2 class="h5">Đơn hàng gần đây</h2><?php foreach($orders as $order): ?><p><a href="order_detail.php?id=<?= (int)$order['id'] ?>"><?= sanitize($order['order_code']) ?></a> · <?= sanitize($order['status']) ?> · <?= number_format($order['final_amount'],0,',','.') ?>đ</p><?php endforeach; ?></main><?php require __DIR__.'/includes/admin_footer.php'; ?>
