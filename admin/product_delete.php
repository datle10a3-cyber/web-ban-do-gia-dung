<?php
require_once __DIR__.'/../includes/init.php';
if(!isAdmin()){http_response_code(403);exit('Không có quyền truy cập.');}
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit('Chỉ chấp nhận POST.');}
if(!verifyCsrfToken($_POST['csrf']??null)){http_response_code(403);exit('Phiên đã hết hạn.');}
try{
    $id=(int)($_POST['id']??0);$pdo->beginTransaction();
    $q=$pdo->prepare('SELECT id FROM products WHERE id=? FOR UPDATE');$q->execute([$id]);if(!$q->fetchColumn()){throw new RuntimeException('Sản phẩm không tồn tại.');}
    $q=$pdo->prepare('SELECT COUNT(*) FROM order_items WHERE product_id=?');$q->execute([$id]);if($q->fetchColumn()){throw new RuntimeException('Sản phẩm đã có đơn hàng; không thể xóa lịch sử. Có thể chỉnh tồn kho về 0 để ngừng bán.');}
    $pdo->prepare('DELETE FROM products WHERE id=?')->execute([$id]);$pdo->commit();$_SESSION['admin_success']='Đã xóa sản phẩm chưa có đơn hàng.';
}catch(Throwable $e){if($pdo->inTransaction()){$pdo->rollBack();}$_SESSION['admin_error']=$e instanceof RuntimeException&&!($e instanceof PDOException)?$e->getMessage():'Chưa thể xóa sản phẩm.';}
header('Location: '.BASE_URL.'/admin/manage_products.php',true,303);exit;
