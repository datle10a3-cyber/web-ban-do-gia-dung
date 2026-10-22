<?php
function account_display_name(array $user): string {
    $name=trim((string)($user['display_name']??''));
    if($name!==''){return $name;}
    $username=(string)($user['username']??'');
    if(preg_match('/^(google|facebook)_[a-f0-9]{16}$/D',$username,$m)){return 'Khách hàng '.ucfirst($m[1]);}
    return $username!==''?$username:'Khách hàng';
}
function account_public_email(array $user): string {
    $email=(string)($user['email']??'');
    return str_ends_with(strtolower($email),'@social.invalid')?'':$email;
}
function account_sign_in(array $user): void {
    global $pdo;
    require_once __DIR__.'/cart_service.php';
    $previous=(int)($_SESSION['user_id']??0);
    $guest=$previous===0?($_SESSION['cart']??[]):[];
    if(isset($pdo)){
        $cart=cart_for_login($pdo,(int)$user['id'],$guest);
        if($guest){cart_sync($pdo,(int)$user['id'],$cart);}
        $_SESSION['cart']=$cart;$_SESSION['selected_cart_items']=array_keys($cart);
    }
    unset($_SESSION['checkout_mode'],$_SESSION['checkout_cart_override'],$_SESSION['checkout_request_key'],$_SESSION['direct_buy']);
    if(session_status()===PHP_SESSION_ACTIVE){session_regenerate_id(true);}
    $_SESSION['user_id']=(int)$user['id'];$_SESSION['username']=$user['username'];$_SESSION['role']=$user['role'];
    $_SESSION['display_name']=account_display_name($user);$_SESSION['user_avatar']=$user['avatar']??'default_avatar.jpg';
    $_SESSION['auth_version']=(int)($user['auth_version']??1);
    unset($_SESSION['chat_history'],$_SESSION['chat_search'],$_SESSION['chat_last_products']);
}
function account_user(bool $refresh=false): ?array {
    global $pdo;
    static $cachedId=null,$cachedUser=null;
    $uid=(int)($_SESSION['user_id']??0);
    if(!$uid||!isset($pdo)){return null;}
    if(!$refresh&&$cachedId===$uid){return $cachedUser;}
    $q=$pdo->prepare('SELECT id,username,email,phone,avatar,role,display_name,display_name_custom,password_enabled,auth_version FROM users WHERE id=?');$q->execute([$uid]);$user=$q->fetch();
    if(!$user||(isset($_SESSION['auth_version'])&&(int)$_SESSION['auth_version']!==(int)$user['auth_version'])){
        foreach(['user_id','username','display_name','role','user_avatar','auth_version','chat_history','checkout_request_key','checkout_cart_override','facebook_flow'] as $key){unset($_SESSION[$key]);}
        $cachedId=null;$cachedUser=null;return null;
    }
    $_SESSION['username']=$user['username'];$_SESSION['display_name']=account_display_name($user);$_SESSION['role']=$user['role'];$_SESSION['user_avatar']=$user['avatar'];$_SESSION['auth_version']=(int)$user['auth_version'];
    $cachedId=$uid;$cachedUser=$user;return $user;
}
