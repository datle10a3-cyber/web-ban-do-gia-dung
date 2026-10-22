<?php
require_once __DIR__.'/../includes/init.php';
$page_title='Giỏ hàng';
$cart=$_SESSION['cart']??[];
$selected_items=array_values(array_intersect($_SESSION['selected_cart_items']??array_keys($cart),array_keys($cart)));
$_SESSION['selected_cart_items']=$selected_items;
$total_price=0;$total_selected_items=0;$total_quantity=0;
foreach($cart as $key=>$item){if(in_array($key,$selected_items,true)){$total_price+=(float)$item['price']*max(1,(int)$item['qty']);$total_selected_items++;$total_quantity+=max(1,(int)$item['qty']);}}
require __DIR__.'/../includes/header.php';
$banner=['title'=>'Giỏ hàng của bạn','description'=>count($cart).' sản phẩm · Chọn những món bạn muốn thanh toán.','image'=>'banners/banner1.jpg'];require __DIR__.'/../includes/page_banner.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/cart.css?v=20261022b">
<main class="container cart-page">
<?php if(!$cart): ?>
<section class="cart-empty"><img src="<?= BASE_URL ?>/assets/images/empty-cart.png" alt="Giỏ hàng trống"><h2>Giỏ hàng đang trống</h2><a href="<?= BASE_URL ?>/products/" class="btn btn-primary">Tiếp tục mua sắm</a></section>
<?php else: ?>
<div class="cart-layout"><section class="cart-list" aria-label="Sản phẩm trong giỏ">
<div class="cart-select-bar"><label for="selectAll"><input type="checkbox" id="selectAll" <?= count($selected_items)===count($cart)?'checked':'' ?>>Chọn tất cả (<?= count($cart) ?>)</label><button type="button" class="btn btn-link text-danger" onclick="clearAllCart()"><i class="bi bi-trash" aria-hidden="true"></i> Xóa tất cả</button></div>
<?php foreach($cart as $key=>$item): $qty=max(1,(int)($item['qty']??1));$price=(float)($item['price']??0); ?>
<article class="cart-item"><input type="checkbox" class="item-checkbox" data-key="<?= sanitize((string)$key) ?>" aria-label="Chọn <?= sanitize($item['name']) ?>" <?= in_array($key,$selected_items,true)?'checked':'' ?>>
<img class="cart-item-image" src="<?= UPLOAD_PATH ?>/<?= sanitize($item['image']??'no-image.jpg') ?>" alt="<?= sanitize($item['name']) ?>">
<div class="cart-item-info"><h2><?= sanitize($item['name']) ?></h2><?php if(!empty($item['variant_name'])): ?><p><?= sanitize($item['variant_name']) ?></p><?php endif; ?><strong><?= number_format($price,0,',','.') ?>đ</strong></div>
<div class="cart-item-tools"><div class="cart-quantity"><button type="button" class="qty-btn update-qty" data-key="<?= sanitize((string)$key) ?>" data-change="-1" aria-label="Giảm số lượng <?= sanitize($item['name']) ?>">−</button><input class="qty-input form-control" type="text" value="<?= $qty ?>" readonly aria-label="Số lượng <?= sanitize($item['name']) ?>"><button type="button" class="qty-btn update-qty" data-key="<?= sanitize((string)$key) ?>" data-change="1" aria-label="Tăng số lượng <?= sanitize($item['name']) ?>">+</button></div><strong aria-label="Thành tiền"><?= number_format($price*$qty,0,',','.') ?>đ</strong><a href="#" class="remove-item" data-key="<?= sanitize((string)$key) ?>" aria-label="Xóa <?= sanitize($item['name']) ?>"><i class="bi bi-trash3" aria-hidden="true"></i> Xóa</a></div></article>
<?php endforeach; ?>
</section><aside id="summaryCard" class="cart-summary" aria-label="Tóm tắt giỏ hàng"><h2>Thông tin đơn hàng</h2><dl><div><dt>Đã chọn</dt><dd><?= $total_selected_items ?> sản phẩm · <?= $total_quantity ?> món</dd></div><div><dt>Tạm tính</dt><dd><?= number_format($total_price,0,',','.') ?>đ</dd></div><div><dt>Vận chuyển</dt><dd>Miễn phí</dd></div><div class="cart-total"><dt>Tổng cộng</dt><dd><?= number_format($total_price,0,',','.') ?>đ</dd></div></dl>
<?php if(!empty($_SESSION['user_id'])): ?><button id="checkoutBtn" type="button" class="btn btn-primary" <?= $total_selected_items===0?'disabled':'' ?>>Thanh toán<?= $total_selected_items?' ('.$total_selected_items.')':'' ?> <i class="fas fa-arrow-right ms-2" aria-hidden="true"></i></button>
<?php else: ?><a class="btn btn-primary" href="<?= BASE_URL ?>/auth/login.php?redirect=<?= rawurlencode(BASE_URL.'/checkout.php') ?>">Đăng nhập để thanh toán</a><p class="cart-signin-note">Đăng nhập để đặt hàng và sử dụng mã giảm giá.</p><?php endif; ?>
<a href="<?= BASE_URL ?>/products/" class="btn btn-outline-primary">Tiếp tục mua sắm</a><div class="cart-assurances"><span><i class="fas fa-shield-alt" aria-hidden="true"></i>Chính hãng</span><span><i class="fas fa-sync-alt" aria-hidden="true"></i>Hỗ trợ đổi trả</span><span><i class="fas fa-truck" aria-hidden="true"></i>Giao miễn phí</span></div></aside></div>
<?php endif; ?>
<p id="cartError" class="text-danger small mt-3" role="alert" hidden></p>
</main>
<dialog id="cartConfirmDialog" class="cart-confirm-dialog" aria-labelledby="cartConfirmTitle"><form method="dialog"><h2 id="cartConfirmTitle">Xác nhận giỏ hàng</h2><p id="cartConfirmMessage"></p><div><button class="btn btn-outline-primary" value="cancel" autofocus>Giữ lại</button><button class="btn btn-danger" value="accept">Xóa</button></div></form></dialog>
<script>
function confirmCartAction(message) {
    const dialog=document.getElementById('cartConfirmDialog');
    document.getElementById('cartConfirmMessage').textContent=message;
    dialog.returnValue='cancel';
    return new Promise(resolve=>{dialog.addEventListener('close',()=>resolve(dialog.returnValue==='accept'),{once:true});dialog.showModal();});
}
async function clearAllCart() {
    if (!await confirmCartAction('Xóa tất cả sản phẩm khỏi giỏ hàng?')) return;

    fetch('clear.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'csrf_token=<?= $_SESSION['csrf_token'] ?? '' ?>'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Không thể xóa giỏ hàng');
        }
    })
    .catch(() => alert('Lỗi mạng!'));
}
const csrfToken = "<?= $_SESSION['csrf_token'] ?? '' ?>";

document.querySelectorAll('.update-qty').forEach(btn => {
    btn.addEventListener('click', function() {
        const key = this.dataset.key;
        const change = this.dataset.change;
        const original = this.innerHTML;
        this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        this.disabled = true;

        fetch('update.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({key,change,csrf_token:csrfToken})})
            .then(r=>r.json()).then(data=>{if(!data.success)throw Error(data.message||'Không đủ tồn kho.');location.reload();})
            .catch(() => {
                this.innerHTML = original;
                this.disabled = false;
                alert('Không thể cập nhật số lượng. Vui lòng kiểm tra tồn kho và thử lại.');
            });
    });
});

document.querySelectorAll('.remove-item').forEach(btn => {
    btn.addEventListener('click', async function(e) {
        e.preventDefault();
        if (!await confirmCartAction('Xóa sản phẩm này khỏi giỏ hàng?')) return;

        const key = this.dataset.key;
        
        fetch('remove.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `key=${key}&csrf_token=${csrfToken}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Không thể xóa sản phẩm');
            }
        })
        .catch(() => alert('Lỗi mạng, vui lòng thử lại!'));
    });
});

document.querySelectorAll('.item-checkbox').forEach(cb => {
    cb.addEventListener('change', function() {
        const key = this.dataset.key;
        const selected = this.checked ? 1 : 0;

        fetch('select_item.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `key=${key}&selected=${selected}&csrf_token=${csrfToken}`
        })
        .then(() => location.reload());

        const allChecked = document.querySelectorAll('.item-checkbox').length === 
                          document.querySelectorAll('.item-checkbox:checked').length;
        document.getElementById('selectAll').checked = allChecked;
    });
});

document.getElementById('selectAll')?.addEventListener('change', async function() {
    const checked = this.checked;
    this.disabled = true;
    try {
        await Promise.all(Array.from(document.querySelectorAll('.item-checkbox')).filter(cb => cb.checked !== checked).map(async cb => {
            const response = await fetch('select_item.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `key=${cb.dataset.key}&selected=${checked ? 1 : 0}&csrf_token=${csrfToken}`
            });
            if (!response.ok || (await response.text()).trim() !== 'ok') throw Error('Không thể cập nhật lựa chọn. Vui lòng tải lại trang.');
        }));
        location.reload();
    } catch (error) {
        const message = document.getElementById('cartError');
        message.textContent = error.message;
        message.hidden = false;
        this.disabled = false;
    }
});

document.getElementById('checkoutBtn')?.addEventListener('click', function() {
    const selected = Array.from(document.querySelectorAll('.item-checkbox:checked'))
                          .map(cb => cb.dataset.key);

    if (selected.length === 0) {
        alert('Vui lòng chọn ít nhất 1 sản phẩm để thanh toán!');
        return;
    }

    window.location.href = '../checkout.php?items=' + selected.join(',');
});
</script>
<?php require __DIR__.'/../includes/footer.php'; ?>
