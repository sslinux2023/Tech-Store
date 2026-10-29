<?php
/**
 * Shopping Cart Page
 * File: cart.php
 *
 * Handles cart item additions, quantity updates, and removals.
 * Queries product details via WHERE id IN (...) and displays the cart summary.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

// Ensure cart array exists in session
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// -------------------------------------------------------------
// 1. Handle POST action: 'add'
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity   = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    if ($quantity < 1) {
        $quantity = 1;
    }

    if ($product_id > 0) {
        // Increment quantity if product already in cart, otherwise set it
        if (isset($_SESSION['cart'][$product_id])) {
            if (is_array($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id]['quantity'] += $quantity;
            } else {
                $_SESSION['cart'][$product_id] += $quantity;
            }
        } else {
            $_SESSION['cart'][$product_id] = $quantity;
        }

        set_flash_message('success', 'Product successfully added to your cart!');
    }

    header('Location: ' . BASE_URL . '/cart.php');
    exit;
}

// -------------------------------------------------------------
// 2. Handle POST action: 'update' (quantity update)
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity   = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    if ($product_id > 0 && isset($_SESSION['cart'][$product_id])) {
        if ($quantity <= 0) {
            unset($_SESSION['cart'][$product_id]);
            set_flash_message('info', 'Item removed from your cart.');
        } else {
            if (is_array($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id]['quantity'] = $quantity;
            } else {
                $_SESSION['cart'][$product_id] = $quantity;
            }
            set_flash_message('success', 'Cart updated successfully!');
        }
    }

    header('Location: ' . BASE_URL . '/cart.php');
    exit;
}

// -------------------------------------------------------------
// 3. Handle action: 'remove' (via GET or POST)
// -------------------------------------------------------------
if (
    (isset($_GET['action']) && $_GET['action'] === 'remove' && isset($_GET['id'])) ||
    (isset($_POST['action']) && $_POST['action'] === 'remove' && isset($_POST['product_id']))
) {
    $remove_id = isset($_GET['id']) ? (int)$_GET['id'] : (int)$_POST['product_id'];
    if ($remove_id > 0 && isset($_SESSION['cart'][$remove_id])) {
        unset($_SESSION['cart'][$remove_id]);
        set_flash_message('info', 'Item removed from your cart.');
    }

    header('Location: ' . BASE_URL . '/cart.php');
    exit;
}

// -------------------------------------------------------------
// 4. Handle action: 'clear'
// -------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'clear') {
    $_SESSION['cart'] = [];
    set_flash_message('info', 'Shopping cart cleared.');
    header('Location: ' . BASE_URL . '/cart.php');
    exit;
}

// -------------------------------------------------------------
// 5. Fetch product records for items in cart using WHERE id IN (...)
// -------------------------------------------------------------
$cart_products = [];
$total_price   = 0.0;

$raw_ids = array_keys($_SESSION['cart']);
$product_ids = array_filter(array_map('intval', $raw_ids), fn($id) => $id > 0);

if (!empty($product_ids)) {
    try {
        $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
        $stmt->execute(array_values($product_ids));
        $fetched = $stmt->fetchAll();

        // Index products by ID for fast lookup
        $products_by_id = [];
        foreach ($fetched as $prod) {
            $products_by_id[(int)$prod['id']] = $prod;
        }

        // Build list with quantities and sub-totals
        foreach ($product_ids as $pid) {
            if (isset($products_by_id[$pid])) {
                $qty = is_array($_SESSION['cart'][$pid]) ? (int)$_SESSION['cart'][$pid]['quantity'] : (int)$_SESSION['cart'][$pid];
                if ($qty <= 0) continue;

                $prod = $products_by_id[$pid];
                $subtotal = (float)$prod['price'] * $qty;
                $total_price += $subtotal;

                $cart_products[] = [
                    'product'  => $prod,
                    'quantity' => $qty,
                    'subtotal' => $subtotal,
                ];
            }
        }
    } catch (PDOException $e) {
        $cart_products = [];
    }
}

$page_title = 'Shopping Cart';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Cart Header Section -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1 text-dark">
            <i class="bi bi-cart3 text-primary me-2"></i>Shopping Cart
        </h2>
        <p class="text-muted small mb-0">Review your selected electronics before proceeding to checkout</p>
    </div>
    <?php if (!empty($cart_products)): ?>
        <a href="<?php echo BASE_URL; ?>/cart.php?action=clear" class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="return confirm('Clear all items from your cart?');">
            <i class="bi bi-trash3 me-1"></i>Clear Cart
        </a>
    <?php endif; ?>
</div>

<?php if (empty($cart_products)): ?>
    <!-- Empty Cart Screen -->
    <div class="card border-0 shadow-sm rounded-4 text-center py-5 px-3">
        <div class="card-body">
            <div class="text-muted mb-3" style="font-size: 4rem;">
                <i class="bi bi-cart-x"></i>
            </div>
            <h4 class="fw-bold text-dark mb-2">Your Shopping Cart is Empty</h4>
            <p class="text-muted mb-4">Looks like you haven't added any phones, tablets, or accessories yet.</p>
            <a href="<?php echo BASE_URL; ?>/index.php" class="btn btn-primary rounded-pill px-4 py-2">
                <i class="bi bi-arrow-left me-1"></i> Start Shopping
            </a>
        </div>
    </div>
<?php else: ?>
    <!-- Cart Table and Summary -->
    <div class="row g-4">
        <!-- Products Table Column -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4 py-3">Product</th>
                                <th class="py-3">Price</th>
                                <th class="py-3" style="min-width: 170px;">Quantity</th>
                                <th class="py-3">Subtotal</th>
                                <th class="text-end pe-4 py-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart_products as $item): ?>
                                <?php 
                                    $p = $item['product'];
                                    $qty = $item['quantity'];
                                    $subtotal = $item['subtotal'];
                                    $img_src = !empty($p['image']) ? BASE_URL . '/' . ltrim($p['image'], '/') : BASE_URL . '/images/placeholder.svg';
                                ?>
                                <tr>
                                    <!-- Product Info -->
                                    <td class="ps-4 py-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <a href="<?php echo BASE_URL; ?>/product.php?id=<?php echo (int)$p['id']; ?>">
                                                <img src="<?php echo sanitize($img_src); ?>" alt="<?php echo sanitize($p['name']); ?>" style="width: 60px; height: 60px; object-fit: contain; background: #f8fafc; border-radius: 8px; padding: 4px;" onerror="this.onerror=null; this.src='<?php echo BASE_URL; ?>/images/placeholder.svg';">
                                            </a>
                                            <div>
                                                <a href="<?php echo BASE_URL; ?>/product.php?id=<?php echo (int)$p['id']; ?>" class="fw-bold text-dark text-decoration-none">
                                                    <?php echo sanitize($p['name']); ?>
                                                </a>
                                                <div class="text-muted small">In Stock: <?php echo (int)$p['stock']; ?></div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Price -->
                                    <td>
                                        <span class="fw-semibold text-secondary">
                                            <?php echo format_price($p['price']); ?>
                                        </span>
                                    </td>

                                    <!-- Update Quantity Form -->
                                    <td>
                                        <form action="<?php echo BASE_URL; ?>/cart.php" method="POST" class="d-flex align-items-center gap-2 flex-nowrap">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="product_id" value="<?php echo (int)$p['id']; ?>">
                                            <input type="number" name="quantity" class="form-control form-control-sm text-center fw-bold" value="<?php echo $qty; ?>" min="1" max="<?php echo (int)$p['stock']; ?>" style="width: 70px;" required>
                                            <button type="submit" class="btn btn-outline-secondary btn-sm text-nowrap">
                                                Update
                                            </button>
                                        </form>
                                    </td>

                                    <!-- Subtotal -->
                                    <td>
                                        <span class="fw-bold text-dark">
                                            <?php echo format_price($subtotal); ?>
                                        </span>
                                    </td>

                                    <!-- Remove Action Form -->
                                    <td class="text-end pe-4">
                                        <form action="<?php echo BASE_URL; ?>/cart.php" method="POST" class="d-inline" onsubmit="return confirm('Remove this item?');">
                                            <input type="hidden" name="action" value="remove">
                                            <input type="hidden" name="product_id" value="<?php echo (int)$p['id']; ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm rounded-circle p-2" title="Remove Item">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4">
                <a href="<?php echo BASE_URL; ?>/index.php" class="btn btn-outline-secondary rounded-pill px-4">
                    <i class="bi bi-arrow-left me-1"></i> Continue Shopping
                </a>
            </div>
        </div>

        <!-- Order Summary Column -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 sticky-top" style="top: 90px;">
                <h5 class="fw-bold mb-3 text-dark">Order Summary</h5>

                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Total Items</span>
                    <span class="fw-semibold"><?php echo get_cart_count(); ?></span>
                </div>

                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Subtotal</span>
                    <span class="fw-semibold"><?php echo format_price($total_price); ?></span>
                </div>

                <div class="d-flex justify-content-between mb-3">
                    <span class="text-muted">Shipping</span>
                    <span class="text-success fw-semibold">FREE</span>
                </div>

                <hr>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <span class="fw-bold fs-5 text-dark">Total Price</span>
                    <span class="fw-bold fs-4 text-primary"><?php echo format_price($total_price); ?></span>
                </div>

                <a href="<?php echo BASE_URL; ?>/checkout.php" class="btn btn-primary btn-lg w-100 py-3 rounded-pill fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2">
                    <span>Proceed to Checkout</span>
                    <i class="bi bi-arrow-right"></i>
                </a>

                <div class="text-center mt-3 small text-muted">
                    <i class="bi bi-shield-lock-fill text-success me-1"></i> Safe and secure checkout
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
