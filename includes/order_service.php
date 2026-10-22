<?php
function order_change_status(PDO $pdo,int $id,string $next,?int $owner=null,string $reason='',bool $confirmCod=false): void {
    $pdo->beginTransaction();
    try{
        $q=$pdo->prepare('SELECT * FROM orders WHERE id=? FOR UPDATE');$q->execute([$id]);$order=$q->fetch();
        if(!$order||($owner!==null&&(int)$order['user_id']!==$owner)){throw new RuntimeException('Đơn hàng không tồn tại hoặc không thuộc tài khoản này.');}
        $current=$order['status'];
        $allowed=['pending'=>['processing','cancelled'],'processing'=>['shipping','cancelled'],'shipping'=>['delivered'],'delivered'=>['completed'],'completed'=>[],'cancelled'=>[]];
        if($owner!==null&&($next!=='cancelled'||$current!=='pending')){throw new RuntimeException('Chỉ có thể hủy đơn đang chờ xử lý.');}
        if($next!==$current&&!in_array($next,$allowed[$current]??[],true)){throw new RuntimeException('Không thể chuyển đơn sang trạng thái này.');}
        if($order['payment_method']==='vnpay'&&$order['payment_status']!=='paid'&&in_array($next,['processing','shipping','delivered','completed'],true)){throw new RuntimeException('Đơn VNPAY cần được xác nhận thanh toán trước khi xử lý giao hàng.');}
        if($confirmCod){
            if($owner!==null||$order['payment_method']!=='cod'||!in_array($next,['delivered','completed'],true)){throw new RuntimeException('Chỉ xác nhận thu COD cho đơn đã giao.');}
            $pdo->prepare("UPDATE orders SET payment_status='paid' WHERE id=?")->execute([$id]);$order['payment_status']='paid';
        }
        if($next==='completed'&&$order['payment_status']!=='paid'){throw new RuntimeException('Cần xác nhận đã thu tiền trước khi hoàn tất đơn.');}
        if($next==='cancelled'&&$current!=='cancelled'&&!$order['inventory_restored']){
            $q=$pdo->prepare('SELECT product_id,variant_id,quantity FROM order_items WHERE order_id=? ORDER BY product_id,variant_id');$q->execute([$id]);
            foreach($q->fetchAll() as $item){
                $pdo->prepare('UPDATE products SET stock=stock+? WHERE id=?')->execute([$item['quantity'],$item['product_id']]);
                if($item['variant_id']){$pdo->prepare('UPDATE product_variants SET stock=stock+? WHERE id=? AND product_id=?')->execute([$item['quantity'],$item['variant_id'],$item['product_id']]);}
            }
            $pdo->prepare('UPDATE orders SET inventory_restored=1,cancel_reason=? WHERE id=?')->execute([$reason,$id]);
        }
        $pdo->prepare('UPDATE orders SET status=? WHERE id=?')->execute([$next,$id]);$pdo->commit();
    }catch(Throwable $e){if($pdo->inTransaction()){$pdo->rollBack();}throw $e;}
}
