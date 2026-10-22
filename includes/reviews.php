<?php
function review_media_path(string $path,int $uid,string $type): string {
    if(!preg_match('~^assets/uploads/review/review_'.$uid.'_[0-9]+_[a-f0-9]{16}\.(jpg|jpeg|png|gif|webp|mp4|mov|webm)$~D',$path,$m)){throw new RuntimeException('Tệp đánh giá không thuộc tài khoản này.');}
    $image=in_array($m[1],['jpg','jpeg','png','gif','webp'],true);
    if(($type==='image')!==$image||!in_array($type,['image','video'],true)||!is_file(__DIR__.'/../'.$path)){throw new RuntimeException('Tệp đánh giá không hợp lệ.');}
    return $path;
}
function review_delete_media(array $review): void {
    $paths=json_decode($review['images']??'[]',true);$paths=is_array($paths)?$paths:[];
    if(!empty($review['video'])){$paths[]=$review['video'];}
    foreach($paths as $path){
        if(!is_string($path)||!preg_match('~^assets/uploads/review/review_[0-9]+_[0-9]+_[a-f0-9]{16}\.(?:jpg|jpeg|png|gif|webp|mp4|mov|webm)$~D',$path)){continue;}
        $root=realpath(__DIR__.'/../assets/uploads/review');$file=realpath(__DIR__.'/../'.$path);
        if($root&&$file&&str_starts_with($file,$root.DIRECTORY_SEPARATOR)&&is_file($file)){@unlink($file);}
    }
}
