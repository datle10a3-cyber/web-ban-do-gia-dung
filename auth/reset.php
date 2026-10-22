<?php
require_once __DIR__.'/../includes/init.php';
header('Cache-Control: no-store');header('Referrer-Policy: no-referrer');
$token=is_string($_POST['token']??$_GET['token']??null)?($_POST['token']??$_GET['token']):'';
$hash=hash('sha256',$token);$valid=false;$error='';$success='';
if(preg_match('/^[a-f0-9]{64}$/D',$token)){$q=$pdo->prepare('SELECT id FROM users WHERE reset_token=? AND reset_expires>NOW()');$q->execute([$hash]);$valid=(bool)$q->fetchColumn();}
if($_SERVER['REQUEST_METHOD']==='POST'){
    $new=is_string($_POST['password']??null)?$_POST['password']:'';$confirm=is_string($_POST['confirm_password']??null)?$_POST['confirm_password']:'';
    if(!is_string($_POST['csrf']??null)||!hash_equals($_SESSION['csrf'],$_POST['csrf'])){http_response_code(403);$error='Phiên đã hết hạn. Vui lòng tải lại trang.';}
    elseif(!$valid){http_response_code(422);$error='Liên kết không hợp lệ hoặc đã hết hạn.';}
    elseif(strlen($new)<8||strlen($new)>72||$new!==$confirm){$error='Mật khẩu cần 8–72 ký tự và xác nhận trùng khớp.';}
    else{
        $pdo->beginTransaction();
        try{
            $q=$pdo->prepare('SELECT * FROM users WHERE reset_token=? AND reset_expires>NOW() FOR UPDATE');$q->execute([$hash]);$user=$q->fetch();
            if(!$user){throw new RuntimeException('Liên kết đã hết hạn hoặc đã được sử dụng.');}
            $pdo->prepare('UPDATE users SET password=?,password_enabled=1,auth_version=auth_version+1,reset_token=NULL,reset_expires=NULL,display_name=COALESCE(display_name,?) WHERE id=?')->execute([password_hash($new,PASSWORD_DEFAULT),account_display_name($user),$user['id']]);
            $pdo->commit();$valid=false;$success='Đã đặt lại mật khẩu. Bạn có thể đăng nhập bằng mật khẩu mới.';
        }catch(Throwable $e){if($pdo->inTransaction()){$pdo->rollBack();}$error='Liên kết đã hết hạn hoặc không thể sử dụng. Vui lòng yêu cầu liên kết mới.';}
    }
}
if(!$valid&&!$success&&!$error){$error='Liên kết không hợp lệ hoặc đã hết hạn.';}
$page_title='Đặt lại mật khẩu';require __DIR__.'/../includes/auth_header.php';
?><section class="auth-card"><h1>Đặt lại mật khẩu</h1><?php if($error): ?><p class="auth-alert error" role="alert"><?= sanitize($error) ?></p><?php endif; ?><?php if($success): ?><p class="auth-alert success" role="status"><?= sanitize($success) ?></p><?php endif; ?><?php if($valid): ?><form method="post"><input type="hidden" name="csrf" value="<?= sanitize($_SESSION['csrf']) ?>"><input type="hidden" name="token" value="<?= sanitize($token) ?>"><label for="password">Mật khẩu mới</label><input id="password" name="password" type="password" autocomplete="new-password" minlength="8" maxlength="72" required><label for="confirm_password">Xác nhận mật khẩu mới</label><input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" minlength="8" maxlength="72" required><button class="auth-submit">Lưu mật khẩu</button></form><?php endif; ?><p class="auth-switch"><a href="login.php">Đăng nhập</a> · <a href="forgot.php">Yêu cầu liên kết mới</a></p></section><?php require __DIR__.'/../includes/auth_footer.php'; ?>
