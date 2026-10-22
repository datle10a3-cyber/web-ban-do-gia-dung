<?php

require_once __DIR__.'/../includes/init.php'; 

header('Content-Type: application/json');
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit(json_encode(['success'=>false]));}


if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!isset($_POST['csrf']) || $_POST['csrf'] !== ($_SESSION['csrf'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'CSRF token không hợp lệ']);
    exit;
}

$upload_dir = __DIR__ . '/../assets/uploads/review/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true); 
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Không có file hoặc lỗi upload']);
    exit;
}

$file = $_FILES['file'];
$original_name = $file['name'];
$tmp_path = $file['tmp_name'];
$size = $file['size'];

$allowed_images = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$allowed_videos = ['video/mp4', 'video/quicktime', 'video/webm']; // mov = quicktime
$allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'mov', 'webm'];

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $tmp_path);
finfo_close($finfo);

$ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
$canonical=['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp','video/mp4'=>'mp4','video/quicktime'=>'mov','video/webm'=>'webm'];
if(!isset($canonical[$mime])){http_response_code(422);exit(json_encode(['success'=>false,'message'=>'Định dạng không hợp lệ.']));}
$ext=$canonical[$mime];
if(str_starts_with($mime,'image/')&&!@getimagesize($tmp_path)){http_response_code(422);exit(json_encode(['success'=>false,'message'=>'Ảnh không hợp lệ.']));}

if (!in_array($ext, $allowed_ext) || !in_array($mime, array_merge($allowed_images, $allowed_videos))) {
    echo json_encode(['success' => false, 'message' => 'Định dạng file không được phép']);
    exit;
}

$max_image_size = 10 * 1024 * 1024; 
$max_video_size = 50 * 1024 * 1024; 

if (in_array($mime, $allowed_images) && $size > $max_image_size) {
    echo json_encode(['success' => false, 'message' => 'Ảnh quá lớn (tối đa 10MB)']);
    exit;
}

if (in_array($mime, $allowed_videos) && $size > $max_video_size) {
    echo json_encode(['success' => false, 'message' => 'Video quá lớn (tối đa 50MB)']);
    exit;
}

$new_name = 'review_' . $_SESSION['user_id'] . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
$full_path = $upload_dir . $new_name;
$web_path = 'assets/uploads/review/' . $new_name;

if (move_uploaded_file($tmp_path, $full_path)) {
    chmod($full_path, 0644);

    echo json_encode([
        'success' => true,
        'path'    => $web_path,
        'type'    => str_starts_with($mime, 'image/') ? 'image' : 'video',
        'size'    => $size
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Upload thất bại, vui lòng thử lại']);
}
?>