<?php
require_once __DIR__.'/../includes/init.php';
require_once __DIR__.'/../includes/account_service.php';
require_once __DIR__.'/../includes/uploads.php';
require_once __DIR__.'/../includes/voucher_service.php';
requireLogin();
$user=account_user();$uid=(int)$user['id'];$page_title='Hồ sơ cá nhân';
$tabs=['profile'=>'Thông tin cá nhân','vouchers'=>'Ưu đãi của bạn','password'=>'Mật khẩu','addresses'=>'Địa chỉ','activity'=>'Hoạt động'];
$tab=is_string($_GET['tab']??null)&&isset($tabs[$_GET['tab']])?$_GET['tab']:'profile';
$success=$_SESSION['profile_success']??'';unset($_SESSION['profile_success']);$error='';
$avatarDir=__DIR__.'/../assets/images/avatars';
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        if(!verifyCsrfToken($_POST['csrf']??null)){http_response_code(403);throw new RuntimeException('Phiên đã hết hạn. Vui lòng tải lại trang rồi thử lại.');}
        $action=$_POST['action']??'';
        switch($action){
            case 'profile': account_save_profile($pdo,$user,$_POST);$event='profile_update';$success='Đã cập nhật thông tin cá nhân.';break;
            case 'password':
                account_change_password($pdo,$user,account_secret($_POST['old_password']??''),account_secret($_POST['new_password']??''),account_secret($_POST['confirm_password']??''));
                $event='profile_update';$tab='password';$success='Đã đổi mật khẩu. Các phiên đăng nhập cũ đã hết hiệu lực.';break;
            case 'address_save':case 'address_delete':
                account_address($pdo,$uid,$action==='address_delete'?'delete':'save',(int)($_POST['addr_id']??0),account_text($_POST['address']??'',2000),isset($_POST['is_default']));
                $event=$action==='address_delete'?'address_delete':((int)($_POST['addr_id']??0)>0?'address_update':'address_add');$tab='addresses';$success='Đã cập nhật địa chỉ.';break;
            case 'avatar_upload':
                $new=shop_image_upload($_FILES['avatar']??[],$avatarDir,'avatar_'.$uid.'_',2097152);
                try{$pdo->prepare('UPDATE users SET avatar=? WHERE id=?')->execute([$new,$uid]);}
                catch(Throwable $e){shop_owned_file_delete($avatarDir,$new,'avatar_'.$uid.'_');throw $e;}
                shop_owned_file_delete($avatarDir,$user['avatar'],'avatar_'.$uid.'_');$event='avatar_update';$success='Đã cập nhật ảnh đại diện.';break;
            case 'avatar_delete':
                $pdo->prepare("UPDATE users SET avatar='default_avatar.jpg' WHERE id=?")->execute([$uid]);
                shop_owned_file_delete($avatarDir,$user['avatar'],'avatar_'.$uid.'_');$event='avatar_delete';$success='Đã dùng ảnh đại diện mặc định.';break;
            default:throw new RuntimeException('Thao tác không hợp lệ. Vui lòng tải lại trang.');
        }
        logUserActivity($uid,$event);account_user(true);$_SESSION['profile_success']=$success;
        header('Location: '.BASE_URL.'/user/profile.php?tab='.$tab,true,303);exit;
    }catch(Throwable $e){
        if($pdo->inTransaction()){$pdo->rollBack();}
        if(http_response_code()!==403){http_response_code(422);}
        $error=$e instanceof RuntimeException&&!($e instanceof PDOException)?$e->getMessage():'Chưa thể cập nhật thông tin. Vui lòng thử lại.';
        if($e instanceof PDOException){error_log('Account update failed: '.get_class($e));}
    }
}
$user=account_user(true);
$q=$pdo->prepare('SELECT * FROM user_addresses WHERE user_id=? ORDER BY is_default DESC,id');$q->execute([$uid]);$addresses=$q->fetchAll();
$q=$pdo->prepare('SELECT action_type,description,created_at FROM user_activities WHERE user_id=? ORDER BY created_at DESC,id DESC LIMIT 30');$q->execute([$uid]);$activities=$q->fetchAll();
$q=$pdo->prepare('SELECT provider FROM oauth_accounts WHERE user_id=?');$q->execute([$uid]);$providers=array_map('ucfirst',$q->fetchAll(PDO::FETCH_COLUMN));
$avatar=basename($user['avatar']??'default_avatar.jpg');if(!is_file($avatarDir.'/'.$avatar)){$avatar='default_avatar.jpg';}
$editing=null;$editId=(int)($_GET['edit_address']??0);foreach($addresses as $a){if((int)$a['id']===$editId){$editing=$a;}}
require __DIR__.'/../includes/header.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/account.css?v=20261022">
<main class="container account-page">
<nav class="shop-breadcrumb" aria-label="Điều hướng"><a href="<?= BASE_URL ?>/">Trang chủ</a><span>/</span><strong>Tài khoản</strong></nav>
<div class="account-heading"><img src="<?= BASE_URL ?>/assets/images/avatars/<?= sanitize($avatar) ?>" alt="Ảnh đại diện"><div><span class="eyebrow">TÀI KHOẢN CỦA BẠN</span><h1><?= sanitize(account_display_name($user)) ?></h1><p><?= $providers?'Đã kết nối '.sanitize(implode(' và ',$providers)):'Quản lý thông tin và trải nghiệm mua sắm của bạn.' ?></p></div></div>
<nav class="account-tabs" aria-label="Quản lý tài khoản"><?php foreach($tabs as $key=>$label): ?><a href="?tab=<?= $key ?>" <?= $key===$tab?'aria-current="page"':'' ?>><?= $label ?></a><?php endforeach; ?></nav>
<?php if($error): ?><p class="alert alert-danger" role="alert"><?= sanitize($error) ?></p><?php endif; ?>
<?php if($success): ?><p class="alert alert-success" role="status"><?= sanitize($success) ?></p><?php endif; ?>
<section class="shop-panel account-content">
<?php if($tab==='profile'): ?>
<h2>Thông tin cá nhân</h2>
<form method="post" class="account-form"><input type="hidden" name="csrf" value="<?= getCsrfToken() ?>"><input type="hidden" name="action" value="profile">
<div><label for="display_name">Họ và tên</label><input id="display_name" name="display_name" class="form-control" value="<?= sanitize(account_display_name($user)) ?>" autocomplete="name" minlength="2" maxlength="100" required></div>
<div><label for="email">Email liên hệ<?= account_public_email($user)===''?' (không bắt buộc)':'' ?></label><input id="email" name="email" class="form-control" type="email" value="<?= sanitize(account_public_email($user)) ?>" autocomplete="email" maxlength="100" <?= account_public_email($user)!==''?'required':'' ?>><?php if(account_public_email($user)===''): ?><small>Tài khoản mạng xã hội chưa cung cấp email. Bạn có thể bổ sung email liên hệ.</small><?php endif; ?></div>
<div><label for="phone">Số điện thoại</label><input id="phone" name="phone" class="form-control" type="tel" value="<?= sanitize($user['phone']) ?>" pattern="0[0-9]{9}" maxlength="10" autocomplete="tel" placeholder="Số điện thoại 10 chữ số"></div>
<?php if(!preg_match('/^(google|facebook)_[a-f0-9]{16}$/D',$user['username'])): ?><div><label for="username">Tên đăng nhập</label><input id="username" class="form-control" value="<?= sanitize($user['username']) ?>" readonly><small>Tên đăng nhập được giữ cố định; họ và tên có thể thay đổi.</small></div><?php endif; ?>
<div class="account-full"><button class="btn btn-primary" type="submit">Lưu thông tin</button></div></form>
<hr><h2>Ảnh đại diện</h2><form method="post" enctype="multipart/form-data" class="account-avatar-form"><input type="hidden" name="csrf" value="<?= getCsrfToken() ?>"><input type="hidden" name="action" value="avatar_upload"><label class="visually-hidden" for="avatar">Chọn ảnh đại diện</label><input id="avatar" type="file" name="avatar" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp" required><button class="btn btn-outline-primary">Cập nhật ảnh</button></form><small>JPG, PNG, GIF hoặc WebP, tối đa 2 MB.</small>
<?php if($avatar!=='default_avatar.jpg'): ?><form method="post" class="mt-3"><input type="hidden" name="csrf" value="<?= getCsrfToken() ?>"><input type="hidden" name="action" value="avatar_delete"><button class="btn btn-link text-danger p-0">Dùng ảnh mặc định</button></form><?php endif; ?>
<?php elseif($tab==='vouchers'): $myVouchers=voucher_available($pdo,$uid); ?><h2>Ưu đãi của bạn</h2><p class="text-muted">Mỗi mã dùng cho một đơn hàng. Lượt bên dưới là của riêng tài khoản bạn.</p>
<div class="row g-3"><?php foreach($myVouchers as $v): ?><div class="col-md-6"><article class="border rounded-3 p-3 h-100"><div class="d-flex justify-content-between gap-2 align-items-start"><strong class="text-primary"><?= sanitize($v['code']) ?></strong><span class="badge text-bg-success">Còn <?= (int)$v['user_remaining'] ?>/<?= (int)$v['per_user_limit'] ?> lượt</span></div><p class="h4 my-3">Giảm <?= $v['discount_type']==='percent'?(float)$v['discount_value'].'%':number_format($v['discount_value'],0,',','.').'đ' ?></p><p class="small mb-1">Đơn từ <?= number_format($v['min_order'],0,',','.') ?>đ<?= $v['new_customer_only']?' · Chỉ đơn đầu tiên':'' ?></p><small class="text-muted"><?= $v['expires_at']?'Đến '.date('d/m/Y H:i',strtotime($v['expires_at'])):'Áp dụng khi chương trình còn lượt' ?></small></article></div><?php endforeach; ?></div>
<?php if(!$myVouchers): ?><p>Chưa có mã phù hợp hoặc bạn đã dùng hết lượt. Bạn có thể quay lại khi shop có chương trình mới.</p><?php endif; ?><p class="small text-muted mt-3 mb-0">Mã được kiểm tra lại khi đặt hàng. Đơn đã đặt, kể cả đơn hủy, vẫn tính lượt sử dụng.</p>
<?php elseif($tab==='password'): ?><h2>Đổi mật khẩu</h2>
<?php if(!$user['password_enabled']): ?><p>Bạn đang đăng nhập bằng <?= sanitize(implode(' hoặc ',$providers)) ?>. Tài khoản chưa có mật khẩu riêng.</p><p>Bạn có thể tiếp tục dùng đăng nhập mạng xã hội. Để thiết lập mật khẩu, bổ sung email liên hệ rồi dùng chức năng <a href="<?= BASE_URL ?>/auth/forgot.php">quên mật khẩu</a>.</p>
<?php else: ?><form method="post" class="account-form"><input type="hidden" name="csrf" value="<?= getCsrfToken() ?>"><input type="hidden" name="action" value="password"><?php foreach(['old_password'=>'Mật khẩu hiện tại','new_password'=>'Mật khẩu mới','confirm_password'=>'Xác nhận mật khẩu mới'] as $key=>$label): ?><div class="account-full"><label for="<?= $key ?>"><?= $label ?></label><input id="<?= $key ?>" type="password" name="<?= $key ?>" class="form-control" autocomplete="<?= $key==='old_password'?'current-password':'new-password' ?>" required maxlength="72" <?= $key!=='old_password'?'minlength="8"':'' ?>></div><?php endforeach; ?><div><button class="btn btn-primary">Đổi mật khẩu</button></div></form><?php endif; ?>
<?php elseif($tab==='addresses'): ?><h2><?= $editing?'Sửa địa chỉ':'Thêm địa chỉ' ?></h2><form method="post" class="account-form"><input type="hidden" name="csrf" value="<?= getCsrfToken() ?>"><input type="hidden" name="action" value="address_save"><input type="hidden" name="addr_id" value="<?= (int)($editing['id']??0) ?>"><div class="account-full"><label for="address">Địa chỉ nhận hàng</label><textarea id="address" name="address" class="form-control" minlength="10" maxlength="2000" rows="3" autocomplete="street-address" required><?= sanitize($editing['address']??'') ?></textarea></div><label class="account-full"><input type="checkbox" name="is_default" <?= !empty($editing['is_default'])?'checked':'' ?>> Địa chỉ mặc định</label><div class="account-full"><button class="btn btn-primary">Lưu địa chỉ</button><?php if($editing): ?> <a href="?tab=addresses" class="btn btn-outline-primary">Hủy chỉnh sửa</a><?php endif; ?></div></form>
<div class="account-addresses"><?php foreach($addresses as $a): ?><article><p><?= nl2br(sanitize($a['address'])) ?></p><?php if($a['is_default']): ?><span class="badge text-bg-success">Mặc định</span><?php endif; ?><div><a href="?tab=addresses&edit_address=<?= (int)$a['id'] ?>" class="btn btn-sm btn-outline-primary">Sửa</a><form method="post"><input type="hidden" name="csrf" value="<?= getCsrfToken() ?>"><input type="hidden" name="action" value="address_delete"><input type="hidden" name="addr_id" value="<?= (int)$a['id'] ?>"><button class="btn btn-sm btn-outline-danger">Xóa địa chỉ</button></form></div></article><?php endforeach; ?><?php if(!$addresses): ?><p class="text-muted mt-4">Bạn chưa lưu địa chỉ nhận hàng.</p><?php endif; ?></div>
<?php else: ?><h2>Hoạt động gần đây</h2><?php $labels=['avatar_update'=>'Cập nhật ảnh đại diện','avatar_delete'=>'Đổi về ảnh mặc định','profile_update'=>'Cập nhật tài khoản','address_add'=>'Thêm địa chỉ','address_update'=>'Cập nhật địa chỉ','address_delete'=>'Xóa địa chỉ','wishlist_add'=>'Thêm sản phẩm yêu thích','wishlist_remove'=>'Bỏ yêu thích','order_placed'=>'Đặt hàng','login'=>'Đăng nhập']; ?><?php foreach($activities as $a): ?><div class="account-activity"><span><?= sanitize($labels[$a['action_type']]??'Cập nhật mua sắm') ?></span><time><?= date('d/m/Y H:i',strtotime($a['created_at'])) ?></time></div><?php endforeach; ?><?php if(!$activities): ?><p class="text-muted">Chưa có hoạt động nào.</p><?php endif; ?><?php endif; ?>
</section></main>
<?php require __DIR__.'/../includes/footer.php'; ?>
