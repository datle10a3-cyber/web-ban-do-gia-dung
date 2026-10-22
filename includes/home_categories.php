<?php
$homeCategories = $pdo->query('SELECT name FROM categories WHERE parent_id IS NULL ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);
// Inline appliance icons stay sharp and do not depend on an external icon font.
$categoryIcons = [
    'Tủ lạnh' => '<rect x="7" y="3" width="18" height="26" rx="2"/><path d="M7 13h18M11 7v3m0 7v6"/>',
    'Máy giặt' => '<rect x="4" y="3" width="24" height="26" rx="2"/><circle cx="16" cy="19" r="7"/><path d="M4 9h24M8 6h1m3 0h1M10 19c3-4 7 4 12 0"/>',
    'Bàn ghế' => '<path d="M3 16h26M6 16v12m20-12v12M8 16V6h7v10m5 0V6h7v10M9 10h5m7 0h5"/>',
    'Tivi' => '<rect x="3" y="5" width="26" height="19" rx="2"/><path d="M12 29h8m-4-5v5M7 9h18"/>',
    'Điều hòa / Máy lạnh' => '<rect x="2" y="5" width="28" height="12" rx="3"/><path d="M6 13h20M8 21c-3 3 3 4 0 8m8-8c-3 3 3 4 0 8m8-8c-3 3 3 4 0 8M24 9h2"/>',
    'Máy lọc nước' => '<rect x="8" y="3" width="17" height="26" rx="2"/><path d="M8 12h17m-11 3h6v3m-6 7h7M16 18v2"/>',
    'Quạt điện / Máy làm mát' => '<circle cx="16" cy="12" r="10"/><circle cx="16" cy="12" r="2"/><path d="M16 10c-7-6 5-9 3-1m-1 4c9-2 4 9-1 3m-3-3c-3 9-10-1-2-2M16 22v5m-7 2h14"/>',
    'Lò vi sóng / Lò nướng' => '<rect x="2" y="7" width="28" height="19" rx="2"/><rect x="6" y="11" width="16" height="11" rx="1"/><path d="M26 12v2m0 4v2M6 28h3m14 0h3"/>',
];
$categoryLabels = ['Điều hòa / Máy lạnh'=>'Điều hòa', 'Quạt điện / Máy làm mát'=>'Quạt & làm mát', 'Lò vi sóng / Lò nướng'=>'Lò vi sóng & nướng'];
?>
<nav class="home-appliance-categories" aria-label="Danh mục sản phẩm">
<?php foreach ($homeCategories as $categoryName): ?>
<a href="<?= BASE_URL ?>/products/?cat=<?= rawurlencode($categoryName) ?>"><span class="appliance-icon"><svg viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $categoryIcons[$categoryName] ?? '<rect x="5" y="5" width="22" height="22" rx="3"/><path d="M5 16h22m-11-11v22"/>' ?></svg></span><span><?= sanitize($categoryLabels[$categoryName] ?? $categoryName) ?></span></a>
<?php endforeach; ?>
</nav>
