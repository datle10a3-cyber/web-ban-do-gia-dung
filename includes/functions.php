<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function getCsrfToken() {
    return $_SESSION['csrf_token'];
}

require_once __DIR__.'/account.php';
function verifyCsrfToken($token) {
    return is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}

function isLoggedIn() {
    return account_user() !== null;
}

function isAdmin() {
    return isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? BASE_URL . '/';
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
}

function sanitize($data) {
    return htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8');
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function getFeaturedProducts($pdo, $limit = 6) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE featured = 1 ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Featured Products Error: " . $e->getMessage());
        return [];  // Trả rỗng nếu fail, tránh crash
    }
}

// Lấy sản phẩm flash sale - Cải thiện fallback
function getFlashSaleProducts($pdo, $limit = 4) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE flash_sale_end > NOW() ORDER BY RAND() LIMIT ?");
        $stmt->execute([$limit]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($results)) {
            // Fallback: Sản phẩm discount > 0
            $fallback = $pdo->prepare("SELECT * FROM products WHERE discount > 0 ORDER BY RAND() LIMIT ?");
            $fallback->execute([$limit]);
            return $fallback->fetchAll(PDO::FETCH_ASSOC);
        }
        return $results;
    } catch (PDOException $e) {
        error_log("Flash Sale Query Error: " . $e->getMessage());
        return [];
    }
}

function getCategoriesTree($pdo, $parent_id = null, $level = 0) {
    $sql = "SELECT * FROM categories WHERE parent_id = ? ORDER BY name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$parent_id ?? 0]);  
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $tree = [];
    foreach ($categories as $cat) {
        $cat['level'] = $level;
        $cat['children'] = getCategoriesTree($pdo, $cat['id'], $level + 1);
        $tree[] = $cat;
    }
    return $tree;
}

function getBrands($pdo) {
    $sql = "SELECT * FROM brands ORDER BY name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getFilteredProducts($pdo, $category_id = null, $brand_id = null, $search = null, $min_price = null, $max_price = null, $sort = 'name ASC', $limit = null) {
    $sql = "SELECT p.*, c.name as category_name, b.name as brand_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            LEFT JOIN brands b ON p.brand_id = b.id 
            WHERE 1=1";
    $params = [];

    if ($category_id) {
        $sql .= " AND (p.category_id = ? OR EXISTS (SELECT 1 FROM categories sub WHERE sub.parent_id = ? AND p.category_id = sub.id))";
        $params[] = $category_id;
        $params[] = $category_id;
    }
    if ($brand_id) {
        $sql .= " AND p.brand_id = ?";
        $params[] = $brand_id;
    }
    if ($search) {
        $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
    }
    if ($min_price !== null) {
        $sql .= " AND p.price >= ?";
        $params[] = $min_price;
    }
    if ($max_price !== null) {
        $sql .= " AND p.price <= ?";
        $params[] = $max_price;
    }

    $sql .= " ORDER BY $sort";
    if ($limit) {
        $sql .= " LIMIT ?";
        $params[] = $limit;
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Filtered Products Error: " . $e->getMessage());
        return [];
    }
}

function getWishlist($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT w.product_id, p.* 
            FROM wishlist w 
            LEFT JOIN products p ON w.product_id = p.id 
            WHERE w.user_id = ? 
            ORDER BY w.created_at DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Wishlist fetch error: " . $e->getMessage());
        return [];
    }
}
function logUserActivity($user_id, $action_type, $description = null) {
    global $pdo;
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        $stmt = $pdo->prepare("
            INSERT INTO user_activities 
                (user_id, action_type, description, ip_address) 
            VALUES 
                (?, ?, ?, ?)
        ");
        $stmt->execute([
            $user_id,
            $action_type,
            $description ? mb_substr($description, 0, 255, 'UTF-8') : null,
            $ip
        ]);
    } catch (Exception $e) {
        error_log("Failed to log user activity: " . $e->getMessage());
    }
}

function addToCartDb($pdo, $user_id, $product_id, $variant_id = null, $quantity = 1) {
    try {
        $stmt = $pdo->prepare("SELECT id, name FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$product) return false;

        if ($variant_id) {
            $vstmt = $pdo->prepare("SELECT color, capacity, stock FROM product_variants WHERE id = ? AND product_id = ?");
            $vstmt->execute([$variant_id, $product_id]);
            $variant = $vstmt->fetch(PDO::FETCH_ASSOC);
            if (!$variant || $variant['stock'] < $quantity) {
                return false; 
            }
        }

        $sql = "SELECT id, quantity FROM cart 
                WHERE user_id = ? AND product_id = ? 
                AND variant_id " . ($variant_id ? "= ?" : "IS NULL");
        $params = $variant_id ? [$user_id, $product_id, $variant_id] : [$user_id, $product_id];

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $pdo->prepare("UPDATE cart SET quantity = quantity + ? WHERE id = ?")
                ->execute([$quantity, $existing['id']]);
        } else {
            $pdo->prepare("INSERT INTO cart (user_id, product_id, variant_id, quantity) VALUES (?, ?, ?, ?)")
                ->execute([$user_id, $product_id, $variant_id, $quantity]);
        }

        $display = $product['name'];
        if ($variant_id && !empty($variant['color'])) {
            $display .= " ({$variant['color']}" . ($variant['capacity'] ? " - {$variant['capacity']}" : "") . ")";
        }
        logUserActivity($user_id, 'cart_add', $display . " ×{$quantity}");

        return true;
    } catch (Exception $e) {
        error_log("addToCartDb error: " . $e->getMessage());
        return false;
    }
}


function mergeGuestCartToUser($pdo, $user_id) {
    if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        return; 
    }

    foreach ($_SESSION['cart'] as $item) {
        $product_id = (int)($item['product_id'] ?? 0);
        $variant_id = !empty($item['variant_id']) ? (int)$item['variant_id'] : null;
        $quantity   = max(1, (int)($item['qty'] ?? 1));

        if ($product_id > 0) {
            addToCartDb($pdo, $user_id, $product_id, $variant_id, $quantity);
        }
    }

    unset($_SESSION['cart']);
}
?>
