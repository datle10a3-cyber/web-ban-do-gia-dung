<?php
require_once __DIR__.'/../includes/init.php';
require_once __DIR__.'/../vendor/autoload.php';
$error='';$success='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $email=is_string($_POST['email']??null)?trim($_POST['email']):'';
    if(!is_string($_POST['csrf']??null)||!hash_equals($_SESSION['csrf'],$_POST['csrf'])){http_response_code(403);$error='Phiên đã hết hạn. Vui lòng tải lại trang.';}
    elseif(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>100||str_ends_with(strtolower($email),'@social.invalid')){$error='Vui lòng nhập email liên hệ hợp lệ.';}
    elseif(($_SESSION['reset_requested_at']??0)>time()-60){$error='Bạn vui lòng đợi một phút trước khi yêu cầu lại.';}
    else{
        $_SESSION['reset_requested_at']=time();
        require_once __DIR__.'/../config/mail.php';
        $host=(string)app_env('MAIL_HOST',defined('MAIL_HOST')?MAIL_HOST:'');
        $username=(string)app_env('MAIL_USERNAME',defined('MAIL_USERNAME')?MAIL_USERNAME:'');
        $password=(string)app_env('MAIL_PASSWORD',defined('MAIL_PASSWORD')?MAIL_PASSWORD:'');
        $from=(string)app_env('MAIL_FROM',defined('MAIL_FROM')?MAIL_FROM:$username);
        $ready=!app_env('MAIL_DISABLED',false)&&$host!==''&&filter_var($username,FILTER_VALIDATE_EMAIL)&&$password!==''&&!preg_match('/your|xxxx|example/i',$username.' '.$password);
        if(!$ready){$error='Email khôi phục chưa được cấu hình. Bạn có thể đăng nhập Google/Facebook hoặc liên hệ shop để được hỗ trợ.';}
        else{
            $q=$pdo->prepare('SELECT id,email FROM users WHERE email=?');$q->execute([$email]);$user=$q->fetch();
            $success='Nếu email có tài khoản tại shop, bạn sẽ nhận được liên kết đặt lại mật khẩu. Liên kết có hiệu lực 30 phút.';
            if($user){
                $raw=bin2hex(random_bytes(32));$hash=hash('sha256',$raw);
                try{
                    $pdo->prepare('UPDATE users SET reset_token=?,reset_expires=DATE_ADD(NOW(),INTERVAL 30 MINUTE) WHERE id=?')->execute([$hash,$user['id']]);
                    $mail=new \PHPMailer\PHPMailer\PHPMailer(true);$mail->isSMTP();$mail->SMTPDebug=0;$mail->Timeout=8;
                    $mail->Host=$host;$mail->SMTPAuth=true;$mail->Username=$username;$mail->Password=$password;
                    $mail->Port=(int)app_env('MAIL_PORT',defined('MAIL_PORT')?MAIL_PORT:587);
                    $mail->SMTPSecure=$mail->Port===465?\PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS:\PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->CharSet='UTF-8';$mail->setFrom($from,'Gia Dụng Shop');$mail->addAddress($email);
                    $mail->Subject='Đặt lại mật khẩu Gia Dụng Shop';$mail->Body="Bạn đã yêu cầu đặt lại mật khẩu. Mở liên kết sau trong 30 phút:\n".APP_URL.'/auth/reset.php?token='.$raw."\nNếu không phải bạn yêu cầu, hãy bỏ qua email này.";$mail->send();
                }catch(Throwable $e){$pdo->prepare('UPDATE users SET reset_token=NULL,reset_expires=NULL WHERE id=? AND reset_token=?')->execute([$user['id'],$hash]);$success='';$error='Chưa gửi được email khôi phục. Vui lòng thử lại sau hoặc liên hệ shop.';error_log('Password recovery delivery failed: '.get_class($e));}
            }
        }
    }
}
$page_title='Quên mật khẩu';require __DIR__.'/../includes/auth_header.php';
?><section class="auth-card"><h1>Quên mật khẩu</h1><p>Nhập email liên hệ của tài khoản để nhận liên kết khôi phục.</p><?php if($error): ?><p class="auth-alert error" role="alert"><?= sanitize($error) ?></p><?php endif; ?><?php if($success): ?><p class="auth-alert success" role="status"><?= sanitize($success) ?></p><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?= sanitize($_SESSION['csrf']) ?>"><label for="email">Email liên hệ</label><input id="email" name="email" type="email" autocomplete="email" maxlength="100" required><button class="auth-submit">Gửi liên kết khôi phục</button></form><p class="auth-switch"><a href="login.php">Quay lại đăng nhập</a></p></section><?php require __DIR__.'/../includes/auth_footer.php'; ?>
