<?php
require_once __DIR__.'/account.php';
function account_text($value,int $max): string {
    if(!is_string($value)){throw new RuntimeException('Thông tin nhập không hợp lệ.');}
    $value=trim($value);
    if(mb_strlen($value)>$max||preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/',$value)){throw new RuntimeException('Thông tin quá dài hoặc chứa ký tự không hợp lệ.');}
    return $value;
}
function account_save_profile(PDO $pdo,array $user,array $input): void {
    $name=account_text($input['display_name']??null,100);$email=account_text($input['email']??'',100);$phone=account_text($input['phone']??'',10);
    if(mb_strlen($name)<2||preg_match('/[<>]/u',$name)){throw new RuntimeException('Họ và tên cần từ 2 đến 100 ký tự, không chứa thẻ HTML.');}
    if(($email===''&&account_public_email($user)!=='')||($email!==''&&(!filter_var($email,FILTER_VALIDATE_EMAIL)||str_ends_with(strtolower($email),'@social.invalid')))){throw new RuntimeException('Vui lòng nhập email liên hệ hợp lệ.');}
    if($phone!==''&&!preg_match('/^0[0-9]{9}$/D',$phone)){throw new RuntimeException('Số điện thoại cần 10 chữ số và bắt đầu bằng 0.');}
    $email=$email!==''?$email:$user['email'];
    $q=$pdo->prepare('SELECT id FROM users WHERE email=? AND id<>?');$q->execute([$email,$user['id']]);
    if($q->fetchColumn()){throw new RuntimeException('Email này đã được sử dụng bởi tài khoản khác.');}
    $pdo->prepare('UPDATE users SET display_name=?,display_name_custom=1,email=?,phone=? WHERE id=?')->execute([$name,$email,$phone,$user['id']]);
    account_user(true);
}
function account_address(PDO $pdo,int $uid,string $action,int $id,string $address='',bool $default=false): void {
    if($action!=='delete'&&mb_strlen($address)<10){throw new RuntimeException('Địa chỉ cần ít nhất 10 ký tự.');}
    $pdo->beginTransaction();
    try {
        $pdo->prepare('SELECT id FROM users WHERE id=? FOR UPDATE')->execute([$uid]);
        if($id>0){$q=$pdo->prepare('SELECT id FROM user_addresses WHERE id=? AND user_id=?');$q->execute([$id,$uid]);if(!$q->fetchColumn()){throw new RuntimeException('Địa chỉ không tồn tại hoặc không thuộc tài khoản này.');}}
        if($action==='delete'){
            if($id<1){throw new RuntimeException('Địa chỉ không hợp lệ.');}
            $pdo->prepare('DELETE FROM user_addresses WHERE id=? AND user_id=?')->execute([$id,$uid]);
        }else{
            $q=$pdo->prepare('SELECT id FROM user_addresses WHERE user_id=? AND address=? AND id<>?');$q->execute([$uid,$address,$id]);
            if($q->fetchColumn()){throw new RuntimeException('Địa chỉ này đã được lưu.');}
            if($default){$pdo->prepare('UPDATE user_addresses SET is_default=0 WHERE user_id=?')->execute([$uid]);}
            if($id>0){$pdo->prepare('UPDATE user_addresses SET address=?,is_default=? WHERE id=? AND user_id=?')->execute([$address,(int)$default,$id,$uid]);}
            else{$pdo->prepare('INSERT INTO user_addresses(user_id,address,is_default) VALUES (?,?,?)')->execute([$uid,$address,(int)$default]);}
        }
        $q=$pdo->prepare('SELECT id FROM user_addresses WHERE user_id=? AND is_default=1');$q->execute([$uid]);
        if(!$q->fetchColumn()){$pdo->prepare('UPDATE user_addresses SET is_default=1 WHERE user_id=? ORDER BY id LIMIT 1')->execute([$uid]);}
        $pdo->commit();
    }catch(Throwable $e){if($pdo->inTransaction()){$pdo->rollBack();}throw $e;}
}
function account_change_password(PDO $pdo,array $user,string $old,string $new,string $confirm): void {
    $q=$pdo->prepare('SELECT password,password_enabled FROM users WHERE id=?');$q->execute([$user['id']]);$stored=$q->fetch();
    if(!$stored||!$stored['password_enabled']||!password_verify($old,$stored['password'])){throw new RuntimeException('Mật khẩu hiện tại chưa đúng.');}
    if(strlen($new)<8||strlen($new)>72||$new!==$confirm){throw new RuntimeException('Mật khẩu mới cần 8–72 ký tự và xác nhận trùng khớp.');}
    $pdo->prepare('UPDATE users SET password=?,auth_version=auth_version+1,reset_token=NULL,reset_expires=NULL WHERE id=?')->execute([password_hash($new,PASSWORD_DEFAULT),$user['id']]);
    $_SESSION['auth_version']=(int)$user['auth_version']+1;session_regenerate_id(true);
    account_user(true);
}
function account_secret($value): string {
    if(!is_string($value)||strlen($value)>200){throw new RuntimeException('Mật khẩu không hợp lệ.');}
    return $value;
}
