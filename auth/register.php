<?php
require_once __DIR__.'/../includes/init.php';
require_once __DIR__.'/../config/oauth.php';
if(isset($_SESSION['user_id'])){header('Location: '.BASE_URL.'/');exit;}
$error='';$username=is_string($_POST['username']??null)?trim($_POST['username']):'';$email=is_string($_POST['email']??null)?trim($_POST['email']):'';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $password=is_string($_POST['password']??null)?$_POST['password']:'';$confirm=is_string($_POST['confirm_password']??null)?$_POST['confirm_password']:'';
    if(!is_string($_POST['csrf']??null)||!hash_equals($_SESSION['csrf'],$_POST['csrf'])){$error='Phiên đã hết hạn. Vui lòng thử lại.';}
    elseif(mb_strlen($username)<3 || mb_strlen($username)>50){$error='Tên đăng nhập cần từ 3 đến 50 ký tự.';}
    elseif(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>100){$error='Vui lòng nhập email hợp lệ.';}
    elseif(strlen($password)<8||strlen($password)>72){$error='Mật khẩu cần từ 8 đến 72 ký tự.';}
    elseif($password!==$confirm){$error='Mật khẩu xác nhận chưa khớp.';}
    else{
        try{
            $q=$pdo->prepare('SELECT id FROM users WHERE username=? OR email=?');$q->execute([$username,$email]);
            if($q->fetchColumn()){$error='Tên đăng nhập hoặc email đã được sử dụng.';}
            else{
                $pdo->prepare("INSERT INTO users(username,email,password,role) VALUES (?,?,?,'customer')")->execute([$username,$email,password_hash($password,PASSWORD_DEFAULT)]);
                $_SESSION['auth_success']='Tạo tài khoản thành công. Bạn có thể đăng nhập.';header('Location: '.BASE_URL.'/auth/login.php');exit;
            }
        }catch(PDOException $e){error_log('Registration: '.$e->getMessage());$error='Chưa thể tạo tài khoản. Vui lòng thử lại.';}
    }
}
$page_title='Đăng ký';require __DIR__.'/../includes/auth_header.php';
?>
<section class="auth-card"><h1>Tạo tài khoản</h1>
<?php if($error): ?><p class="auth-alert error" role="alert"><?= sanitize($error) ?></p><?php endif; ?>
<form method="post"><input type="hidden" name="csrf" value="<?= sanitize($_SESSION['csrf']) ?>">
<label for="username">Tên đăng nhập</label><input id="username" name="username" minlength="3" maxlength="50" value="<?= sanitize($username) ?>" autocomplete="username" required>
<label for="email">Email</label><input id="email" name="email" type="email" maxlength="100" value="<?= sanitize($email) ?>" autocomplete="email" required>
<label for="password">Mật khẩu</label><div class="auth-password"><input id="password" name="password" type="password" minlength="8" maxlength="72" placeholder="Ít nhất 6 ký tự" autocomplete="new-password" required><button type="button" data-password="password" aria-label="Hiện mật khẩu" aria-pressed="false">Hiện</button></div>
<label for="confirm_password">Nhập lại mật khẩu</label><input id="confirm_password" name="confirm_password" type="password" minlength="8" maxlength="72" autocomplete="new-password" required>
<button type="submit" class="auth-submit">Tạo tài khoản</button></form>
<?php require __DIR__.'/../includes/auth_social.php'; ?>
<p class="auth-switch">Đã có tài khoản? <a href="login.php">Đăng nhập</a></p>
</section><?php require __DIR__.'/../includes/auth_footer.php'; ?>
