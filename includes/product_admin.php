<?php
require_once __DIR__.'/uploads.php';
require_once __DIR__.'/account_service.php';
function admin_save_product(PDO $pdo,int $id,array $in,array $files): int {
    $created=[];$directory=__DIR__.'/../assets/images/products';
    $pdo->beginTransaction();
    try{
        $name=account_text($in['name']??'',255);$description=account_text($in['description']??'',20000);
        $price=filter_var($in['price']??null,FILTER_VALIDATE_FLOAT);$discount=filter_var($in['discount']??0,FILTER_VALIDATE_FLOAT);
        $category=(int)($in['category_id']??0);$brand=(int)($in['brand_id']??0);
        if($name===''||$price===false||!is_finite($price)||$price<=0||$price>9999999999||$discount===false||!is_finite($discount)||$discount<0||$discount>=100){throw new RuntimeException('Tên, giá hoặc mức giảm giá không hợp lệ.');}
        foreach(['categories'=>$category,'brands'=>$brand] as $table=>$key){$q=$pdo->prepare('SELECT id FROM '.$table.' WHERE id=?');$q->execute([$key]);if(!$q->fetchColumn()){throw new RuntimeException('Danh mục hoặc thương hiệu không tồn tại.');}}
        $old=[];$variants=[];
        if($id){$q=$pdo->prepare('SELECT * FROM products WHERE id=? FOR UPDATE');$q->execute([$id]);$old=$q->fetch();if(!$old){throw new RuntimeException('Sản phẩm không tồn tại.');}$q=$pdo->prepare('SELECT * FROM product_variants WHERE product_id=? FOR UPDATE');$q->execute([$id]);$variants=array_column($q->fetchAll(),null,'id');}
        $image=$old['image']??'no-image.jpg';
        if(isset($files['image'])&&($files['image']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE){$image=shop_image_upload($files['image'],$directory,'prod_');$created[]=$image;}
        $gallery=json_decode(is_string($in['images_json']??null)?$in['images_json']:'[]',true);
        if(!is_array($gallery)||count($gallery)>12){throw new RuntimeException('Thư viện ảnh không hợp lệ, tối đa 12 ảnh.');}
        $allowed=json_decode($old['images']??'[]',true)?:[];$saved=[];
        foreach($gallery as $item){if(!is_string($item)){throw new RuntimeException('Ảnh không hợp lệ.');}if(str_starts_with($item,'data:')){$item=shop_image_data($item,$directory);$created[]=$item;}elseif(!in_array($item,$allowed,true)){throw new RuntimeException('Ảnh thư viện không thuộc sản phẩm.');}$saved[]=$item;}
        $flash=account_text($in['flash_sale_end']??'',30);if($flash!==''&&(!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(?::\d{2})?$/D',$flash)||strtotime($flash)===false)){throw new RuntimeException('Thời gian khuyến mãi không hợp lệ.');}
        $values=[$name,$description,$price,$discount,$image,json_encode($saved),$category,$brand,isset($in['featured'])?1:0,isset($in['bestseller'])?1:0,$flash!==''?str_replace('T',' ',$flash):null];
        if($id){$pdo->prepare('UPDATE products SET name=?,description=?,price=?,discount=?,image=?,images=?,category_id=?,brand_id=?,featured=?,bestseller=?,flash_sale_end=? WHERE id=?')->execute([...$values,$id]);}
        else{$pdo->prepare('INSERT INTO products(name,description,price,discount,image,images,category_id,brand_id,featured,bestseller,flash_sale_end,stock) VALUES (?,?,?,?,?,?,?,?,?,?,?,0)')->execute($values);$id=(int)$pdo->lastInsertId();}
        $colors=$in['v_color']??[];if(!is_array($colors)||count($colors)>100){throw new RuntimeException('Danh sách phân loại không hợp lệ.');}
        $kept=[];$stockTotal=0;$skus=[];
        foreach($colors as $k=>$rawColor){
            $color=account_text($rawColor,50);if($color===''){throw new RuntimeException('Phân loại cần tên màu sắc.');}
            $capacity=account_text($in['v_capacity'][$k]??'',50);$sku=account_text($in['v_sku'][$k]??'',100);
            $vid=(int)($in['v_id'][$k]??0);
            if(!$vid&&$sku!==''){foreach($variants as $v){if($v['sku']===$sku){$vid=(int)$v['id'];break;}}}
            if($vid&&(!isset($variants[$vid])||isset($kept[$vid]))){throw new RuntimeException('Phân loại không thuộc sản phẩm hoặc bị lặp.');}
            $qty=filter_var($in['v_stock'][$k]??null,FILTER_VALIDATE_INT);$extra=filter_var($in['v_price_add'][$k]??0,FILTER_VALIDATE_FLOAT);
            if($qty===false||$qty<0||$qty>999999||$extra===false||!is_finite($extra)||$extra<0||$extra>9999999999){throw new RuntimeException('Tồn kho hoặc giá phân loại không hợp lệ.');}
            if($sku===''){$sku='GD-'.$id.'-'.bin2hex(random_bytes(5));}
            if(isset($skus[$sku])){throw new RuntimeException('SKU bị lặp trong sản phẩm.');}$skus[$sku]=true;
            $q=$pdo->prepare('SELECT id FROM product_variants WHERE sku=? AND id<>?');$q->execute([$sku,$vid]);if($q->fetchColumn()){throw new RuntimeException('SKU đã được dùng bởi phân loại khác.');}
            $vImage=$vid?$variants[$vid]['image']:'';
            if(($files['v_image_new']['error'][$k]??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE){$file=[];foreach(['name','tmp_name','error','size'] as $field){$file[$field]=$files['v_image_new'][$field][$k]??null;}$vImage=shop_image_upload($file,$directory,'var_');$created[]=$vImage;}
            if($vid){$pdo->prepare('UPDATE product_variants SET sku=?,color=?,capacity=?,price_add=?,stock=?,image=? WHERE id=? AND product_id=?')->execute([$sku,$color,$capacity,$extra,$qty,$vImage,$vid,$id]);}
            else{$pdo->prepare('INSERT INTO product_variants(product_id,sku,color,capacity,price_add,stock,image) VALUES (?,?,?,?,?,?,?)')->execute([$id,$sku,$color,$capacity,$extra,$qty,$vImage]);$vid=(int)$pdo->lastInsertId();}
            $kept[$vid]=true;$stockTotal+=$qty;
        }
        // Retire omitted variants without destroying identifiers, historical images, or order references.
        foreach($variants as $vid=>$v){if(!isset($kept[$vid])){$pdo->prepare('UPDATE product_variants SET stock=0 WHERE id=? AND product_id=?')->execute([$vid,$id]);}}
        if(!$colors&&!$variants){$stockTotal=filter_var($in['stock']??($old['stock']??0),FILTER_VALIDATE_INT);if($stockTotal===false||$stockTotal<0||$stockTotal>999999){throw new RuntimeException('Tồn kho không hợp lệ.');}}
        $pdo->prepare('UPDATE products SET stock=? WHERE id=?')->execute([$stockTotal,$id]);$pdo->commit();return $id;
    }catch(Throwable $e){if($pdo->inTransaction()){$pdo->rollBack();}foreach($created as $name){shop_owned_file_delete($directory,$name,'');}throw $e;}
}
