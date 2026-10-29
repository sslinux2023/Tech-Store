<?php
/**
 * Single Product View
 * File: product.php
 *
 * Fetches product by $_GET['id'] and displays large visual, price, stock,
 * technical description, and the Add to Cart form posting to cart.php.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

// Validate ID
$product_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Fetch product details
try {
    $stmt = $pdo->prepare("
        SELECT p.*, c.name AS category_name 
        FROM products p 
        JOIN categories c ON p.category_id = c.id 
        WHERE p.id = :id 
        LIMIT 1
    ");
    $stmt->execute([':id' => $product_id]);
    $product = $stmt->fetch();
} catch (PDOException $e) {
    $product = false;
}

if (!$product) {
    $page_title = 'Product Not Found';
    require_once __DIR__ . '/includes/header.php';
    ?>
    <div class="text-center py-5">
        <div class="card border-0 shadow-sm rounded-4 p-5 max-w-600 mx-auto">
            <i class="bi bi-exclamation-circle text-warning display-3 mb-3"></i>
            <h3 class="fw-bold">Product Not Found</h3>
            <p class="text-muted">The electronic device you are looking for does not exist or has been removed.</p>
            <a href="<?php echo BASE_URL; ?>/index.php" class="btn btn-primary rounded-pill px-4 mt-2">
                <i class="bi bi-arrow-left me-1"></i> Return to Store
            </a>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$page_title = $product['name'];
require_once __DIR__ . '/includes/header.php';

$in_stock = (int)$product['stock'] > 0;
$stock_count = (int)$product['stock'];
$img_src = !empty($product['image']) ? BASE_URL . '/' . ltrim($product['image'], '/') : BASE_URL . '/images/placeholder.svg';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php" class="text-decoration-none">Home</a></li>
        <li class="breadcrumb-item">
            <a href="<?php echo BASE_URL; ?>/index.php?category=<?php echo (int)$product['category_id']; ?>" class="text-decoration-none">
                <?php echo sanitize($product['category_name']); ?>
            </a>
        </li>
        <li class="breadcrumb-item active" aria-current="page"><?php echo sanitize($product['name']); ?></li>
    </ol>
</nav>

<!-- Product Details Card -->
<div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 mb-5">
    <div class="row g-5 align-items-center">
        <!-- Image Column -->
        <div class="col-lg-6 text-center">
            <div class="p-4 bg-white rounded-4 border d-flex align-items-center justify-content-center" style="min-height: 380px;">
                <img src="<?php echo sanitize($img_src); ?>" alt="<?php echo sanitize($product['name']); ?>" class="img-fluid" style="max-height: 320px; object-fit: contain;" onerror="this.onerror=null; this.src='<?php echo BASE_URL; ?>/images/placeholder.svg';">
            </div>
        </div>

        <!-- Details Column -->
        <div class="col-lg-6">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge bg-light text-primary border rounded-pill px-3 py-2 fw-semibold">
                    <?php echo sanitize($product['category_name']); ?>
                </span>
                <?php if ($in_stock): ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2">
                        <i class="bi bi-check-circle-fill me-1"></i> In Stock (<?php echo $stock_count; ?> available)
                    </span>
                <?php else: ?>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-2">
                        <i class="bi bi-x-circle-fill me-1"></i> Sold Out
                    </span>
                <?php endif; ?>
            </div>

            <h1 class="fw-bold text-dark mb-3"><?php echo sanitize($product['name']); ?></h1>

            <div class="mb-4">
                <span class="text-primary fw-bold display-6">
                    <?php echo format_price($product['price']); ?>
                </span>
                <span class="text-muted small ms-2">Free Express Delivery Included</span>
            </div>

            <div class="mb-4">
                <h6 class="fw-bold text-secondary text-uppercase small">Description</h6>
                <p class="text-muted leading-relaxed" style="font-size: 1.05rem;">
                    <?php echo nl2br(sanitize($product['description'])); ?>
                </p>
            </div>

            <!-- Add to Cart Form posting to cart.php -->
            <?php if ($in_stock): ?>
                <form action="<?php echo BASE_URL; ?>/cart.php" method="POST" class="mb-4">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>">

                    <div class="row g-3 align-items-end">
                        <div class="col-auto">
                            <label for="quantity" class="form-label small fw-bold text-secondary mb-1">Quantity</label>
                            <input type="number" id="quantity" name="quantity" class="form-control text-center fw-bold" value="1" min="1" max="<?php echo $stock_count; ?>" style="width: 80px;" required>
                        </div>
                        <div class="col">
                            <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill py-3 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2">
                                <i class="bi bi-cart-plus-fill fs-5"></i>
                                <span>Add to Cart</span>
                            </button>
                        </div>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert alert-secondary rounded-3 py-3 mb-4">
                    <i class="bi bi-bell me-2"></i> This item is currently out of stock.
                </div>
            <?php endif; ?>

            <div class="row g-3 pt-3 border-top text-secondary small">
                <div class="col-sm-4 d-flex align-items-center gap-2">
                    <i class="bi bi-truck text-primary fs-5"></i>
                    <span>Free Shipping</span>
                </div>
                <div class="col-sm-4 d-flex align-items-center gap-2">
                    <i class="bi bi-shield-check text-primary fs-5"></i>
                    <span>2-Year Warranty</span>
                </div>
                <div class="col-sm-4 d-flex align-items-center gap-2">
                    <i class="bi bi-arrow-repeat text-primary fs-5"></i>
                    <span>30-Day Returns</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
