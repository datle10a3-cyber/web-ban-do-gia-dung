<?php
function chat_normalize(string $s): string {
    $s=mb_strtolower($s,'UTF-8');
    $groups=['a'=>'àáạảãâầấậẩẫăằắặẳẵ','e'=>'èéẹẻẽêềếệểễ','i'=>'ìíịỉĩ','o'=>'òóọỏõôồốộổỗơờớợởỡ','u'=>'ùúụủũưừứựửữ','y'=>'ỳýỵỷỹ','d'=>'đ'];
    foreach($groups as $ascii=>$chars){foreach(mb_str_split($chars) as $char){$s=str_replace($char,$ascii,$s);}}
    return $s;
}
function chat_search_context(string $message, array $previous): array {
    $text=chat_normalize($message);
    $map=['tu lanh'=>'Tủ lạnh','may giat'=>'Máy giặt','ban ghe'=>'Bàn ghế','sofa'=>'Bàn ghế','tivi'=>'Tivi','tv'=>'Tivi','dieu hoa'=>'Điều hòa / Máy lạnh','may lanh'=>'Điều hòa / Máy lạnh','loc nuoc'=>'Máy lọc nước','quat'=>'Quạt điện / Máy làm mát','lo vi song'=>'Lò vi sóng / Lò nướng','lo nuong'=>'Lò vi sóng / Lò nướng'];
    $context=$previous;
    foreach($map as $word=>$label){if(preg_match('/\b'.preg_quote($word,'/').'\b/',$text)){
        if(($context['category']??'')!==$label){$context=['category'=>$label];}
        break;
    }}
    $amount='(\d+(?:[.,]\d+)?)';
    if(preg_match('/(?:tu\s+)?'.$amount.'\s*(?:trieu|tr)?\s*(?:den|toi|-)\s*'.$amount.'\s*(?:trieu|tr)\b/',$text,$m)){
        $context['min']=(int)round((float)str_replace(',','.',$m[1])*1000000);
        $context['max']=(int)round((float)str_replace(',','.',$m[2])*1000000);
    }elseif(preg_match('/'.$amount.'\s*(?:trieu|tr)\b/',$text,$m)){
        $context['max']=(int)round((float)str_replace(',','.',$m[1])*1000000);unset($context['min']);
    }elseif(preg_match('/re hon|gia thap hon/',$text)&&!empty($context['last_price'])){
        $context['max']=max(0,(int)$context['last_price']-1);unset($context['min']);
    }
    if(preg_match('/khong (?:gioi han|can) ngan sach|bo gioi han gia/',$text)){unset($context['min'],$context['max']);}
    return $context;
}
function chat_catalog(PDO $pdo,array $context): array {
    $where=['p.stock>0'];$params=[];
    if(!empty($context['category'])){$where[]='c.name=?';$params[]=$context['category'];}
    if(isset($context['min'])){$where[]='ROUND(p.price*(100-p.discount)/100)>=?';$params[]=$context['min'];}
    if(isset($context['max'])){$where[]='ROUND(p.price*(100-p.discount)/100)<=?';$params[]=$context['max'];}
    $q=$pdo->prepare('SELECT p.id,p.name,c.name AS category,p.description,ROUND(p.price*(100-p.discount)/100) AS price,p.stock FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE '.implode(' AND ',$where).' ORDER BY p.featured DESC,p.price*(100-p.discount)/100 ASC,p.id LIMIT 6');
    $q->execute($params);$rows=$q->fetchAll();
    foreach($rows as &$row){$row['description']=mb_substr(trim(strip_tags($row['description']??'')),0,350);}
    return $rows;
}
function chat_ai_result(string $raw,array $catalog): ?array {
    $raw=preg_replace('/^```(?:json)?\s*|\s*```$/i','',trim($raw));
    $data=json_decode($raw,true);
    if(!is_array($data)||!is_string($data['reply']??null)||trim($data['reply'])===''){return null;}
    $products=[];$byId=array_column($catalog,null,'id');
    foreach(array_slice(is_array($data['product_ids']??null)?$data['product_ids']:[],0,3) as $id){
        if((is_int($id)||(is_string($id)&&ctype_digit($id)))&&isset($byId[$id])){
            $p=$byId[$id];$products[$id]=['id'=>(int)$p['id'],'name'=>$p['name'],'price'=>(int)$p['price']];
        }
    }
    // Plain text bubbles never interpret model HTML/Markdown as executable content.
    $reply=preg_replace('/\*\*([^*]+)\*\*/u','$1',trim($data['reply']));
    return ['reply'=>mb_substr($reply,0,1800),'products'=>array_values($products)];
}
