<?php
require_once __DIR__.'/checkout_service.php';
function cart_sync(PDO $pdo,int $uid,array $cart): void {
    $pdo->beginTransaction();
    try{
        $pdo->prepare('SELECT id FROM users WHERE id=? FOR UPDATE')->execute([$uid]);
        $pdo->prepare('DELETE FROM cart WHERE user_id=?')->execute([$uid]);
        $q=$pdo->prepare('INSERT INTO cart(user_id,product_id,variant_id,quantity) VALUES (?,?,?,?)');
        foreach($cart as $item){$q->execute([$uid,$item['product_id'],$item['variant_id']?:null,$item['qty']]);}
        $pdo->commit();
    }catch(Throwable $e){if($pdo->inTransaction()){$pdo->rollBack();}throw $e;}
}
function cart_for_login(PDO $pdo,int $uid,array $guest): array {
    $q=$pdo->prepare('SELECT product_id,variant_id,quantity AS qty FROM cart WHERE user_id=?');$q->execute([$uid]);$rows=$q->fetchAll();
    $cart=[];
    foreach(array_merge($rows,array_values($guest)) as $item){
        $key=(int)$item['product_id'].'_'.(int)($item['variant_id']??0);
        $candidate=$item;$candidate['qty']=(int)$item['qty']+(int)($cart[$key]['qty']??0);
        try{$checked=checkout_items($pdo,[$key=>$candidate]);$cart[$key]=$checked[$key];}catch(RuntimeException $e){continue;}
    }
    return $cart;
}
