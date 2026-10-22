<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/chatbox.css?v=20261022">
<div class="shop-chat" id="shopChat" data-endpoint="<?= BASE_URL ?>/ajax/chat.php" data-csrf="<?= sanitize($_SESSION['csrf']) ?>" data-user="<?= (int)($_SESSION['user_id']??0) ?>">
<button class="sc-launcher" id="chatOpen" aria-controls="chatPanel" aria-expanded="false"><i class="fas fa-comment-dots" aria-hidden="true"></i><span>Tư vấn mua sắm</span></button>
<section id="chatPanel" class="sc-panel" role="dialog" aria-modal="false" aria-labelledby="chatTitle" hidden>
<header class="sc-heading"><span class="sc-avatar"><i class="fas fa-leaf" aria-hidden="true"></i></span><div><h2 id="chatTitle">Trợ lý Gia Dụng Shop</h2><small>Giúp bạn chọn điều phù hợp</small></div><button id="chatClear" aria-label="Xóa hội thoại" title="Xóa hội thoại"><i class="fas fa-trash-can" aria-hidden="true"></i></button><button id="chatClose" aria-label="Đóng khung chat">×</button></header>
<div class="sc-messages" id="chatMessages" role="log" aria-live="polite" aria-relevant="additions"></div>
<div class="sc-quick" aria-label="Câu hỏi gợi ý"><button type="button">Tủ lạnh dưới 10 triệu</button><button type="button">Theo dõi đơn hàng</button><button type="button">Cách thanh toán</button></div>
<form id="chatForm" class="sc-form"><label for="chatInput" class="visually-hidden">Tin nhắn của bạn</label><input id="chatInput" placeholder="Bạn đang tìm sản phẩm nào?" maxlength="1000" autocomplete="off" required><button id="chatSend" aria-label="Gửi tin nhắn" type="submit"><i class="fas fa-arrow-up" aria-hidden="true"></i></button></form>
<div class="sc-contact"><span>Cần hỗ trợ thêm?</span><a href="tel:0365539435">Gọi shop</a><a href="https://zalo.me/0365539435" target="_blank" rel="noopener noreferrer">Chat Zalo ↗</a></div>
</section></div>
<script src="<?= BASE_URL ?>/assets/js/chatbox.js?v=20261022b" defer></script>
