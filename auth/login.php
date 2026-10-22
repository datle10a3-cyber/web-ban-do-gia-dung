<?php
require_once __DIR__.'/../includes/init.php';
require_once __DIR__.'/../config/oauth.php';
if(isset($_SESSION['user_id'])){header('Location: '.BASE_URL.'/');exit;}
if(isset($_GET['redirect']) && is_string($_GET['redirect'])){
    $path=parse_url($_GET['redirect'],PHP_URL_PATH);
    if(is_string($path) && str_starts_with($path,BASE_URL.'/')){$_SESSION['redirect_after_login']=$path;}
}
$error=$_SESSION['oauth_error']??'';unset($_SESSION['oauth_error']);
$success=$_SESSION['auth_success']??'';unset($_SESSION['auth_success']);
$username=is_string($_POST['username']??null)?trim($_POST['username']):'';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $password=is_string($_POST['password']??null)?$_POST['password']:'';
    if(!is_string($_POST['csrf']??null)||!hash_equals($_SESSION['csrf'],$_POST['csrf'])){$error='Phiên đã hết hạn. Vui lòng thử lại.';}
    elseif(!$username || !$password){$error='Vui lòng nhập tài khoản và mật khẩu.';}
    else{
        $q=$pdo->prepare('SELECT * FROM users WHERE username=? OR email=?');$q->execute([$username,$username]);$user=$q->fetch();
        if($user && !empty($user['password_enabled']) && password_verify($password,$user['password'])){
            account_sign_in($user);
            $redirect=$_SESSION['redirect_after_login']??BASE_URL.'/';unset($_SESSION['redirect_after_login']);
            if(!is_string($redirect)||!str_starts_with($redirect,BASE_URL.'/')||str_contains($redirect,"\r")||str_contains($redirect,"\n")){$redirect=BASE_URL.'/';}
            header('Location: '.$redirect);exit;
        }
        $error='Tài khoản hoặc mật khẩu chưa đúng.';
    }
}
$page_title='Đăng nhập';require __DIR__.'/../includes/auth_header.php';
?>
<section class="auth-card"><h1>Đăng nhập</h1>
<?php if($error): ?><p class="auth-alert error" role="alert"><?= sanitize($error) ?></p><?php endif; ?>
<?php if($success): ?><p class="auth-alert success" role="status"><?= sanitize($success) ?></p><?php endif; ?>
<form method="post"><input type="hidden" name="csrf" value="<?= sanitize($_SESSION['csrf']) ?>">
<label for="username">Email hoặc tên đăng nhập</label><input id="username" name="username" value="<?= sanitize($username) ?>" maxlength="100" autocomplete="username" required>
<div class="auth-label-row"><label for="password">Mật khẩu</label><a href="forgot.php">Quên mật khẩu?</a></div>
<div class="auth-password"><input id="password" name="password" type="password" autocomplete="current-password" required><button type="button" data-password="password" aria-label="Hiện mật khẩu" aria-pressed="false">Hiện</button></div>
<button type="submit" class="auth-submit">Đăng nhập</button></form>
<?php require __DIR__.'/../includes/auth_social.php'; ?>
<p class="auth-switch">Chưa có tài khoản? <a href="register.php">Đăng ký</a></p>
</section><?php require __DIR__.'/../includes/auth_footer.php'; ?>
