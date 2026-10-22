<?php
require_once __DIR__.'/../includes/init.php';
if(!isAdmin()){http_response_code(403);header('Location: ../index.php');exit;}
$page_title = "Quản lý danh mục";

$success = $error = '';
if($_POST && !isset($_POST['del']) && verifyCsrfToken($_POST['csrf']??'')){
    $name = trim($_POST['name']??'');
    $id = (int)($_POST['id']??0);
    if($name){
        if($id){
            $pdo->prepare("UPDATE categories SET name=? WHERE id=?")->execute([$name,$id]);
            $success = "Cập nhật thành công!";
        }else{
            $pdo->prepare("INSERT INTO categories (name) VALUES (?)")->execute([$name]);
            $success = "Thêm danh mục thành công!";
        }
    }else $error = "Tên không được để trống!";
}
if(isset($_POST['del']) && verifyCsrfToken($_POST['csrf']??'')){
    $target=(int)$_POST['del'];$q=$pdo->prepare('SELECT (SELECT COUNT(*) FROM products WHERE category_id=?)+(SELECT COUNT(*) FROM categories WHERE parent_id=?)');$q->execute([$target,$target]);
    if($q->fetchColumn()){$error='Danh mục còn sản phẩm hoặc danh mục con; chưa thể xóa.';}else{$pdo->prepare('DELETE FROM categories WHERE id=?')->execute([$target]);}
    if(!$error){$success = "Xóa thành công!";}
}
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
?>
<?php include 'includes/admin_header.php'; ?>

<div class="flex-grow-1 p-5">
    <h1 class="mb-4 display-6 text-primary">Quản lý danh mục</h1>
    <div class="row">
        <div class="col-md-5">
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white">Thêm / Sửa danh mục</div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf" value="<?=getCsrfToken()?>">
                        <input type="hidden" name="id" id="editId">
                        <div class="mb-3">
                            <input type="text" name="name" id="editName" class="form-control form-control-lg" placeholder="Tên danh mục" required>
                        </div>
                        <button type="submit" class="btn btn-success btn-lg">Lưu</button>
                        <button type="button" onclick="document.getElementById('editId').value='';document.getElementById('editName').value='';" class="btn btn-secondary">Hủy</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white">Danh sách danh mục</div>
                <div class="card-body">
                    <table class="table table-hover">
                        <thead class="table-dark"><tr><th>ID</th><th>Tên</th><th>Thao tác</th></tr></thead>
                        <tbody>
                            <?php foreach($categories as $c): ?>
                            <tr>
                                <td>#<?=$c['id']?></td>
                                <td><?=$c['name']?></td>
                                <td>
                                    <button onclick="document.getElementById('editId').value='<?=$c['id']?>';document.getElementById('editName').value='<?=$c['name']?>'" class="btn btn-warning btn-sm">Sửa</button>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Xác nhận thao tác này?')"><input type="hidden" name="csrf" value="<?= getCsrfToken() ?>"><input type="hidden" name="del" value="<?= (int)$c['id'] ?>"><button class="btn btn-sm btn-outline-danger">Xóa</button></form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/admin_footer.php'; ?>
