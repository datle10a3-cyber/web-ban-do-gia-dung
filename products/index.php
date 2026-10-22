<?php
require_once __DIR__.'/../includes/init.php';
$page_title='Sản phẩm';
function catalog_input(string $key): string {return is_string($_GET[$key]??null)?trim($_GET[$key]):'';}
$search=mb_substr(catalog_input('search'),0,100);
$category=catalog_input('cat');$brand=catalog_input('brand');
$minInput=catalog_input('min_price');$maxInput=catalog_input('max_price');
$minPrice=$minInput!==''&&is_numeric($minInput)&&is_finite((float)$minInput)?max(0,min(1000000,(float)$minInput)):null;
$maxPrice=$maxInput!==''&&is_numeric($maxInput)&&is_finite((float)$maxInput)?max(0,min(1000000,(float)$maxInput)):null;
$sort=catalog_input('sort');if(!in_array($sort,['','price_asc','price_desc','name'],true)){$sort='';}
$filters=array_filter(['search'=>$search,'cat'=>$category,'brand'=>$brand,'min_price'=>$minPrice!==null?(string)$minPrice:'','max_price'=>$maxPrice!==null?(string)$maxPrice:'','sort'=>$sort],fn($v)=>$v!=='');
function catalog_url(array $changes=[]): string {
 global $filters;
 $query=array_filter(array_merge($filters,$changes),fn($v)=>$v!==''&&$v!==null);
 return BASE_URL.'/products/'.($query?'?'.http_build_query($query):'');
}
$categories=$pdo->query('SELECT id,name,parent_id FROM categories ORDER BY name')->fetchAll();
$brands=$pdo->query('SELECT name FROM brands ORDER BY name')->fetchAll();
$where=[];$params=[];
if($search!==''){$where[]='(p.name LIKE ? OR p.description LIKE ?)';$params[]='%'.$search.'%';$params[]='%'.$search.'%';}
if($category!==''){$where[]='(c.name=? OR c.parent_id=(SELECT id FROM categories WHERE name=? LIMIT 1))';$params[]=$category;$params[]=$category;}
if($brand!==''){$where[]='b.name=?';$params[]=$brand;}
if($minPrice!==null){$where[]='ROUND(p.price*(100-p.discount)/100)>=?';$params[]=(int)round($minPrice*1000000);}
if($maxPrice!==null){$where[]='ROUND(p.price*(100-p.discount)/100)<=?';$params[]=(int)round($maxPrice*1000000);}
$from=' FROM products p LEFT JOIN brands b ON b.id=p.brand_id LEFT JOIN categories c ON c.id=p.category_id';
$whereSql=$where?' WHERE '.implode(' AND ',$where):'';
$q=$pdo->prepare('SELECT COUNT(*)'.$from.$whereSql);$q->execute($params);$total=(int)$q->fetchColumn();
$perPage=15;$totalPages=max(1,(int)ceil($total/$perPage));$page=min($totalPages,max(1,(int)catalog_input('page')));$offset=($page-1)*$perPage;
$orderBy=['price_asc'=>'final_price ASC,p.id ASC','price_desc'=>'final_price DESC,p.id ASC','name'=>'p.name ASC,p.id ASC',''=>'p.featured DESC,p.created_at DESC,p.id DESC'][$sort];
$q=$pdo->prepare('SELECT p.*,b.name AS brand_name,ROUND(p.price*(100-p.discount)/100) AS final_price'.$from.$whereSql.' ORDER BY '.$orderBy.' LIMIT ? OFFSET ?');
foreach($params as $i=>$value){$q->bindValue($i+1,$value);}$q->bindValue(count($params)+1,$perPage,PDO::PARAM_INT);$q->bindValue(count($params)+2,$offset,PDO::PARAM_INT);$q->execute();$products=$q->fetchAll();
$wishlist=[];if(!empty($_SESSION['user_id'])){$q=$pdo->prepare('SELECT product_id FROM wishlist WHERE user_id=?');$q->execute([$_SESSION['user_id']]);$wishlist=$q->fetchAll(PDO::FETCH_COLUMN);}
require __DIR__.'/../includes/header.php';
$banner=['title'=>'Sản phẩm cho tổ ấm','description'=>'Chọn theo nhu cầu, tìm theo ngân sách.','image'=>'banners/banner1.jpg'];require __DIR__.'/../includes/page_banner.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/catalog.css?v=20261022a">
<main class="container catalog-page" id="products">
<div class="catalog-layout">
<aside class="catalog-sidebar" aria-label="Lọc sản phẩm">
<details class="catalog-filters" id="catalogFilters" open><summary><span><i class="fas fa-sliders" aria-hidden="true"></i> Bộ lọc tìm kiếm</span><i class="fas fa-chevron-down" aria-hidden="true"></i></summary>
<form method="get" action="<?= BASE_URL ?>/products/" class="catalog-filter-form">
<label for="filterSearch">Tìm sản phẩm</label><input id="filterSearch" class="form-control" type="search" name="search" maxlength="100" placeholder="Tên hoặc công dụng..." value="<?= sanitize($search) ?>">
<label for="filterCategory">Danh mục</label><select id="filterCategory" class="form-select" name="cat"><option value="">Tất cả danh mục</option><?php foreach($categories as $c): ?><option value="<?= sanitize($c['name']) ?>" <?= $category===$c['name']?'selected':'' ?>><?= sanitize($c['name']) ?></option><?php endforeach; ?></select>
<label for="filterBrand">Thương hiệu</label><select id="filterBrand" class="form-select" name="brand"><option value="">Tất cả thương hiệu</option><?php foreach($brands as $b): ?><option value="<?= sanitize($b['name']) ?>" <?= $brand===$b['name']?'selected':'' ?>><?= sanitize($b['name']) ?></option><?php endforeach; ?></select>
<fieldset><legend>Khoảng giá <small>(triệu đồng)</small></legend><div class="catalog-price-inputs"><div><label class="visually-hidden" for="minPrice">Giá từ, triệu đồng</label><input id="minPrice" class="form-control" type="number" min="0" max="1000000" step="0.1" name="min_price" placeholder="Từ" value="<?= $minPrice!==null?sanitize((string)$minPrice):'' ?>"></div><span aria-hidden="true">–</span><div><label class="visually-hidden" for="maxPrice">Giá đến, triệu đồng</label><input id="maxPrice" class="form-control" type="number" min="0" max="1000000" step="0.1" name="max_price" placeholder="Đến" value="<?= $maxPrice!==null?sanitize((string)$maxPrice):'' ?>"></div></div></fieldset>
<?php if($sort!==''): ?><input type="hidden" name="sort" value="<?= sanitize($sort) ?>"><?php endif; ?>
<button class="btn btn-primary" type="submit">Áp dụng bộ lọc</button><a class="catalog-reset" href="<?= BASE_URL ?>/products/">Xóa bộ lọc</a>
</form></details></aside>
<section class="catalog-results" id="catalogResults" aria-label="Danh sách sản phẩm">
<header class="catalog-toolbar"><div><h2><?= $total ?> sản phẩm</h2><p><?= $total?'Hiển thị '.($offset+1).'–'.min($offset+$perPage,$total):'Chưa có kết quả phù hợp' ?></p></div><div class="catalog-sort"><label for="sortSelect">Sắp xếp</label><select class="form-select" id="sortSelect"><option value="" <?= $sort===''?'selected':'' ?>>Nổi bật</option><option value="price_asc" <?= $sort==='price_asc'?'selected':'' ?>>Giá thấp đến cao</option><option value="price_desc" <?= $sort==='price_desc'?'selected':'' ?>>Giá cao đến thấp</option><option value="name" <?= $sort==='name'?'selected':'' ?>>Tên A–Z</option></select></div></header>
<?php if($filters): ?><nav class="catalog-active-filters" aria-label="Bộ lọc đang áp dụng"><?php foreach(['search'=>'Tìm: ','cat'=>'','brand'=>'','min_price'=>'Từ ','max_price'=>'Đến '] as $key=>$prefix): if(!isset($filters[$key]))continue; ?><a href="<?= sanitize(catalog_url([$key=>'','page'=>1])) ?>" aria-label="Bỏ bộ lọc <?= sanitize($prefix.$filters[$key]) ?>"><?= sanitize($prefix.$filters[$key].(in_array($key,['min_price','max_price'],true)?' triệu':'')) ?><span aria-hidden="true">×</span></a><?php endforeach; ?></nav><?php endif; ?>
<?php if(!$products): ?><div class="catalog-empty"><i class="fas fa-magnifying-glass" aria-hidden="true"></i><h3>Chưa tìm thấy sản phẩm</h3><p>Thử đổi từ khóa hoặc nới khoảng giá.</p><a class="btn btn-outline-primary" href="<?= BASE_URL ?>/products/">Xem tất cả sản phẩm</a></div>
<?php else: ?><div class="catalog-grid"><?php foreach($products as $p): $isWished=in_array($p['id'],$wishlist); ?>
<article class="card product-card catalog-product" data-product-id="<?= (int)$p['id'] ?>">
<div class="catalog-product-image"><a href="<?= BASE_URL ?>/products/detail.php?id=<?= (int)$p['id'] ?>"><img src="<?= UPLOAD_PATH ?>/<?= sanitize($p['image']?:'no-image.jpg') ?>" loading="lazy" alt="<?= sanitize($p['name']) ?>"></a><?php if($p['discount']>0): ?><span class="catalog-discount">−<?= (float)$p['discount'] ?>%</span><?php endif; ?><button type="button" class="btn wishlist-btn <?= $isWished?'btn-danger':'btn-outline-danger' ?>" data-id="<?= (int)$p['id'] ?>" aria-label="Yêu thích <?= sanitize($p['name']) ?>" aria-pressed="<?= $isWished?'true':'false' ?>"><i class="fa<?= $isWished?'s':'r' ?> fa-heart" aria-hidden="true"></i></button></div>
<div class="card-body"><span class="catalog-brand"><?= sanitize($p['brand_name']??'') ?></span><h3><a href="<?= BASE_URL ?>/products/detail.php?id=<?= (int)$p['id'] ?>"><?= sanitize($p['name']) ?></a></h3><div class="catalog-product-price"><strong><?= number_format($p['final_price'],0,',','.') ?>đ</strong><del><?= $p['discount']>0?number_format($p['price'],0,',','.').'đ':'' ?></del></div><div class="catalog-actions"><a class="btn btn-primary" href="<?= BASE_URL ?>/products/detail.php?id=<?= (int)$p['id'] ?>">Xem chi tiết</a><button class="btn btn-outline-primary add-to-cart" data-id="<?= (int)$p['id'] ?>" type="button" <?= (int)$p['stock']<1?'disabled':'' ?>><i class="fas fa-plus" aria-hidden="true"></i> <?= (int)$p['stock']<1?'Hết hàng':'Thêm vào giỏ' ?></button></div></div>
</article><?php endforeach; ?></div>
<?php if($totalPages>1): ?><nav class="catalog-pagination" aria-label="Phân trang sản phẩm"><?php if($page>1): ?><a href="<?= sanitize(catalog_url(['page'=>$page-1])) ?>" aria-label="Trang trước">←</a><?php endif; ?><?php for($i=max(1,$page-2);$i<=min($totalPages,$page+2);$i++): ?><a href="<?= sanitize(catalog_url(['page'=>$i])) ?>" <?= $i===$page?'aria-current="page"':'' ?>><?= $i ?></a><?php endfor; ?><?php if($page<$totalPages): ?><a href="<?= sanitize(catalog_url(['page'=>$page+1])) ?>" aria-label="Trang sau">→</a><?php endif; ?></nav><?php endif; endif; ?>
</section></div></main>
<script>if(window.matchMedia('(max-width: 767px)').matches){document.getElementById('catalogFilters').open=false;}</script>
<div class="modal fade catalog-modal" id="quickAddModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 shadow-lg border-0">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold">Chọn phân loại</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-3">
        <div class="text-center mb-4">
          <img id="modalImg" src="" class="rounded-3" style="width:120px;height:120px;object-fit:cover;">
          <h6 id="modalName" class="mt-3 mb-0 fw-bold"></h6>
          <p id="modalPrice" class="text-primary fs-5 fw-bold mb-0"></p>
        </div>

        <h6 class="fw-bold mb-3">Phân loại:</h6>
        <div id="modalVariants" class="d-flex flex-wrap gap-3 justify-content-center"></div>

        <div class="mt-4">
          <h6 class="fw-bold mb-3">Số lượng:</h6>
          <div class="d-flex align-items-center justify-content-center gap-3">
            <button class="btn btn-outline-secondary btn-lg" id="modalMinus">-</button>
            <input type="text" id="modalQty" value="1" class="form-control text-center" style="width:80px;" readonly>
            <button class="btn btn-outline-secondary btn-lg" id="modalPlus">+</button>
          </div>
        </div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-lg btn-primary px-5" id="modalAddToCart">Thêm vào giỏ</button>
      </div>
    </div>
  </div>
</div>

<script>
// Biến toàn cục
let currentProductId = null;
let selectedVariantId = 0;
let selectedStock = 999;

// Sắp xếp
document.getElementById('sortSelect')?.addEventListener('change', function() {
    const params = new URLSearchParams(location.search);
    this.value ? params.set('sort', this.value) : params.delete('sort');
    params.delete('page');
    location.search = params.toString();
});

document.querySelectorAll('.wishlist-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const isLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
        if (!isLoggedIn) {
            location.href = '<?= $base ?>/auth/login.php';
            return;
        }

        const id = this.dataset.id;
        const csrf = "<?= $_SESSION['csrf'] ?? '' ?>";

        this.disabled = true; 

        fetch('<?= $base ?>/user/wishlist_toggle.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'csrf=' + csrf + '&product_id=' + id
        })
        .then(r => r.json())
               .then(d => {
            if (d.success) {
                const added = d.added === true || d.action === 'added';

                
                this.classList.toggle('btn-danger', added);
                this.classList.toggle('btn-outline-danger', !added);
                this.innerHTML = added 
                    ? '<i class="fas fa-heart"></i>' 
                    : '<i class="far fa-heart"></i>';

               
                const badge = document.getElementById('wishlistCount');
                if (badge) {
                    badge.textContent = d.wishlist_count;
                    badge.style.display = d.wishlist_count > 0 ? 'inline-block' : 'none';
                    
                    
                    badge.classList.remove('bounce');
                    void badge.offsetWidth;  // trigger reflow
                    badge.classList.add('bounce');
                }

                
                showToast(
                    added ? '❤️ Đã thêm vào danh sách yêu thích' : '💔 Đã bỏ khỏi danh sách yêu thích',
                    added ? 'success' : 'info'
                );
            } else {
                showToast(d.message || 'Có lỗi xảy ra!', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Lỗi kết nối mạng!', 'error');
        })
        .finally(() => {
            this.disabled = false;
        });
    });
});


document.querySelectorAll('.add-to-cart').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault(); e.stopPropagation();
        currentProductId = this.dataset.id;

        fetch('<?= $base ?>/ajax/get_variants.php?id=' + currentProductId)
        .then(r => r.json())
        .then(data => {
            if(!data.success) return alert('Lỗi tải dữ liệu sản phẩm!');

            // Cập nhật modal
            document.getElementById('modalImg').src = '<?= $base ?>/assets/images/products/' + data.image;
            document.getElementById('modalName').textContent = data.name;
            document.getElementById('modalPrice').textContent = parseInt(data.final_price).toLocaleString('vi-VN') + 'đ';

            const container = document.getElementById('modalVariants');
            container.innerHTML = '';

          
            if(data.variants.length <= 1 && data.variants[0]?.id == 0){
                addToCart(currentProductId, 0, 1);
                return;
            }

            //  phân loại 
            data.variants.forEach((v, index) => {
                const div = document.createElement('div');
                div.className = 'variant-btn position-relative';
                if(v.stock <= 0) div.className += ' out-of-stock';

                div.innerHTML = `
                    <img src="<?= $base ?>/assets/images/products/${v.image}" class="rounded mb-2" style="width:70px;height:70px;object-fit:cover;">
                    <div class="small fw-bold">${v.color}</div>
                    ${v.capacity ? `<small class="d-block">${v.capacity}</small>` : ''}
                    ${v.price_add > 0 ? `<small class="text-danger d-block">+${v.price_add.toLocaleString()}đ</small>` : ''}
                    <small class="text-success fw-bold mt-1 d-block">Còn ${v.stock} sp</small>
                    ${v.stock <= 0 ? `<div class="position-absolute top-50 start-50 translate-middle text-danger fw-bold bg-white px-2 rounded">Hết hàng</div>` : ''}
                `;

                if(v.stock > 0){
                    div.style.cursor = 'pointer';
                    div.onclick = () => {
                        // Đổi ảnh chính + giá + tồn kho
                        document.getElementById('modalImg').src = '<?= $base ?>/assets/images/products/' + v.image;
                        const newPrice = (parseInt(data.final_price) + parseInt(v.price_add || 0));
                        document.getElementById('modalPrice').textContent = newPrice.toLocaleString('vi-VN') + 'đ';

                        // Cập nhật tồn kho + số lượng tối đa
                        selectedStock = v.stock;
                        document.getElementById('modalQty').value = 1;
                        document.getElementById('modalQty').max = v.stock;

                        // Highlight phân loại được chọn
                        document.querySelectorAll('#modalVariants .variant-btn').forEach(x => x.classList.remove('active'));
                        div.classList.add('active');

                        selectedVariantId = v.id;
                    };
                }
                container.appendChild(div);

                // Chọn mặc định cái đầu tiên có hàng
                if(index === 0 && v.stock > 0){
                    div.click(); 
                }
            });

            document.getElementById('modalQty').value = 1;
            new bootstrap.Modal(document.getElementById('quickAddModal')).show();
        })
        .catch(() => alert('Lỗi kết nối!'));
    });
});

// Hàm thêm vào giỏ
function addToCart(productId, variantId, qty){
    const fd = new URLSearchParams();
    fd.append('product_id', productId);
    fd.append('variant_id', variantId);
    fd.append('quantity', qty);
    fd.append('csrf', '<?= $_SESSION['csrf'] ?>');
    fd.append('ajax', '1');

    fetch('<?= $base ?>/cart/add.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:fd})
    .then(r=>r.json())
    .then(d=>{
        if(d.success){
            const badge = document.getElementById('cartCount');
            if(badge){
                badge.textContent = d.cart_count;
                badge.style.display = d.cart_count > 0 ? 'inline-block' : 'none';
                badge.classList.add('bounce');
                setTimeout(()=>badge.classList.remove('bounce'),600);
            }
            showToast(`Đã thêm ${qty} sản phẩm vào giỏ hàng!`, 'success');
        }else{
            alert(d.message || 'Không thể thêm vào giỏ hàng');
        }
    });
}

// Xử lý số lượng trong modal
document.getElementById('modalPlus').onclick = () => {
    let q = parseInt(document.getElementById('modalQty').value);
    if(q < selectedStock) document.getElementById('modalQty').value = ++q;
};
document.getElementById('modalMinus').onclick = () => {
    let q = parseInt(document.getElementById('modalQty').value);
    if(q > 1) document.getElementById('modalQty').value = --q;
};
document.getElementById('modalAddToCart').onclick = () => {
    const qty = parseInt(document.getElementById('modalQty').value);
    if(qty > selectedStock){
        alert(`Chỉ còn ${selectedStock} sản phẩm!`);
        return;
    }
    addToCart(currentProductId, selectedVariantId, qty);
    bootstrap.Modal.getInstance(document.getElementById('quickAddModal')).hide();
};

// Toast + CSS
function showToast(msg, type='success'){
    const t = document.createElement('div');
    t.textContent = msg;
    t.style.cssText = `position:fixed;bottom:30px;right:30px;padding:18px 32px;border-radius:16px;color:white;background:${type==='success'?'#dc3545':'#28a745'};box-shadow:0 10px 30px rgba(0,0,0,.3);z-index:9999;font-weight:700;animation:slideIn .4s`;
    document.body.appendChild(t);
    setTimeout(()=>t.remove(),3500);
}



</script>

<?php require_once '../includes/footer.php'; ?>