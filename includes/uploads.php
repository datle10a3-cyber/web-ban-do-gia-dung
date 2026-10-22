<?php
function shop_image_upload(array $file,string $directory,string $prefix,int $max=5242880): string {
    if(($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK||!is_string($file['tmp_name']??null)||!is_uploaded_file($file['tmp_name'])||(int)($file['size']??0)>$max){throw new RuntimeException('Chọn ảnh hợp lệ, dung lượng không vượt giới hạn cho phép.');}
    $info=@getimagesize($file['tmp_name']);$types=['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];
    if(!$info||!isset($types[$info['mime']])||$info[0]>6000||$info[1]>6000){throw new RuntimeException('Chỉ nhận ảnh JPG, PNG, GIF hoặc WebP, kích thước tối đa 6000px.');}
    if(!is_dir($directory)&&!mkdir($directory,0755,true)){throw new RuntimeException('Không thể lưu ảnh.');}
    $name=$prefix.bin2hex(random_bytes(16)).'.'.$types[$info['mime']];
    if(!move_uploaded_file($file['tmp_name'],$directory.'/'.$name)){throw new RuntimeException('Không thể lưu ảnh.');}
    return $name;
}
function shop_owned_file_delete(string $directory,string $name,string $prefix): void {
    if(basename($name)!==$name||!str_starts_with($name,$prefix)){return;}
    $root=realpath($directory);$path=realpath($directory.'/'.$name);
    if($root&&$path&&str_starts_with($path,$root.DIRECTORY_SEPARATOR)&&is_file($path)){@unlink($path);}
}
function shop_image_data(string $data,string $directory): string {
    if(strlen($data)>7500000||!preg_match('~^data:image/(?:jpeg|png|gif|webp);base64,(.+)$~sD',$data,$m)){throw new RuntimeException('Ảnh thư viện không hợp lệ.');}
    $bytes=base64_decode($m[1],true);$info=$bytes!==false?@getimagesizefromstring($bytes):false;
    $types=['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];
    if(!$info||!isset($types[$info['mime']])||strlen($bytes)>5242880||$info[0]>6000||$info[1]>6000){throw new RuntimeException('Ảnh thư viện không hợp lệ hoặc quá lớn.');}
    $name='gallery_'.bin2hex(random_bytes(16)).'.'.$types[$info['mime']];
    if(file_put_contents($directory.'/'.$name,$bytes,LOCK_EX)===false){throw new RuntimeException('Không thể lưu ảnh thư viện.');}
    return $name;
}
