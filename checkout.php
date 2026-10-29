<?php
/**
 * Checkout & Order Processing Page
 * File: checkout.php
 *
 * Checks cart contents, renders shipping form, processes order insertion
 * into orders and order_items tables within a PDO transaction, and displays success.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

// Check if viewing an already completed order receipt
$order_id = isset($_GET['order_id']) && is_numeric($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$is_success = isset($_GET['success']) && $_GET['success'] == 1 && $order_id > 0;

if ($is_success) {
    $page_title = 'Order Confirmation';
    require_once __DIR__ . '/includes/header.php';

    // Fetch order details for display
    try {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $order_id]);
        $order = $stmt->fetch();
    } catch (PDOException $e) {
        $order = false;
    }
    ?>

    <!-- Order Success Notification -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden text-center">
                <div class="bg-success text-white py-5 px-4">
                    <i class="bi bi-check-circle-fill display-3 mb-3"></i>
                    <h2 class="fw-bold mb-1">Thank You For Your Purchase!</h2>
                    <p class="text-white-50 mb-0">Your order has been placed successfully and is being processed.</p>
                </div>

                <div class="card-body p-4 p-md-5">
                    <?php if ($order): ?>
                        <div class="p-3 bg-light rounded-3 text-start mb-4 border">
                            <div class="row g-2">
                                <div class="col-sm-6">
                                    <span class="text-muted small">Order Number:</span>
                                    <div class="fw-bold text-dark">#ORD-<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></div>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-muted small">Date:</span>
                                    <div class="fw-bold text-dark"><?php echo date('M d, Y - h:i A', strtotime($order['created_at'])); ?></div>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-muted small">Recipient:</span>
                                    <div class="fw-semibold text-dark"><?php echo sanitize($order['customer_name']); ?></div>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-muted small">Total Paid:</span>
                                    <div class="fw-bold text-primary"><?php echo format_price($order['total_amount']); ?></div>
                                </div>
                                <div class="col-12 mt-2 pt-2 border-top">
                                    <span class="text-muted small">Shipping To:</span>
                                    <div class="text-dark"><?php echo sanitize($order['shipping_address']); ?>, <?php echo sanitize($order['city']); ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-center gap-3">
                        <a href="<?php echo BASE_URL; ?>/index.php" class="btn btn-primary rounded-pill px-4 py-2">
                            <i class="bi bi-house-door me-1"></i> Continue Shopping
                        </a>
                        <a href="javascript:window.print();" class="btn btn-outline-secondary rounded-pill px-4 py-2">
                            <i class="bi bi-printer me-1"></i> Print Receipt
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Ensure cart is not empty; if empty, redirect to index.php
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    set_flash_message('warning', 'Your shopping cart is empty.');
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// -------------------------------------------------------------
// Handle Checkout POST Submission
// -------------------------------------------------------------
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city    = trim($_POST['city'] ?? '');
    $zip     = trim($_POST['zip'] ?? '');

    // Validate inputs
    if (empty($name)) {
        $errors[] = 'Full name is required.';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }
    if (empty($address)) {
        $errors[] = 'Shipping address is required.';
    }
    if (empty($city)) {
        $errors[] = 'City is required.';
    }
    if (empty($zip)) {
        $errors[] = 'Zip / Postal code is required.';
    }

    if (empty($errors)) {
        // Collect product IDs from cart
        $raw_ids = array_keys($_SESSION['cart']);
        $product_ids = array_filter(array_map('intval', $raw_ids), fn($id) => $id > 0);

        if (empty($product_ids)) {
            set_flash_message('danger', 'Your cart is empty.');
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        }

        try {
            // Fetch live price and stock from database
            $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
            $p_stmt = $pdo->prepare("SELECT id, name, price, stock FROM products WHERE id IN ($placeholders)");
            $p_stmt->execute(array_values($product_ids));
            $db_products = $p_stmt->fetchAll();

            $products_map = [];
            foreach ($db_products as $row) {
                $products_map[(int)$row['id']] = $row;
            }

            // Calculate grand total
            $total_amount = 0.0;
            $items_to_order = [];

            foreach ($product_ids as $pid) {
                if (isset($products_map[$pid])) {
                    $qty = is_array($_SESSION['cart'][$pid]) ? (int)$_SESSION['cart'][$pid]['quantity'] : (int)$_SESSION['cart'][$pid];
                    if ($qty <= 0) continue;

                    $prod = $products_map[$pid];
                    $price = (float)$prod['price'];
                    $total_amount += ($price * $qty);

                    $items_to_order[] = [
                        'product_id' => $pid,
                        'quantity'   => $qty,
                        'price'      => $price,
                    ];
                }
            }

            if (empty($items_to_order)) {
                throw new Exception('No valid items found in cart.');
            }

            // Start atomic transaction
            $pdo->beginTransaction();

            $user_id = is_logged_in() ? (int)$_SESSION['user_id'] : null;
            $full_city = $city . ', ' . $zip;

            // 1. Insert into orders table
            $order_sql = "INSERT INTO orders (user_id, customer_name, customer_email, shipping_address, city, total_amount, status) 
                          VALUES (:user_id, :customer_name, :customer_email, :shipping_address, :city, :total_amount, 'completed')";
            $order_stmt = $pdo->prepare($order_sql);
            $order_stmt->execute([
                ':user_id'          => $user_id,
                ':customer_name'    => $name,
                ':customer_email'   => $email,
                ':shipping_address' => $address,
                ':city'             => $full_city,
                ':total_amount'     => $total_amount,
            ]);

            $new_order_id = (int)$pdo->lastInsertId();

            // 2. Insert items into order_items and deduct stock
            $item_sql = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                         VALUES (:order_id, :product_id, :quantity, :price)";
            $item_stmt = $pdo->prepare($item_sql);

            $stock_sql = "UPDATE products SET stock = GREATEST(0, stock - :qty) WHERE id = :product_id";
            $stock_stmt = $pdo->prepare($stock_sql);

            foreach ($items_to_order as $order_item) {
                $item_stmt->execute([
                    ':order_id'   => $new_order_id,
                    ':product_id' => $order_item['product_id'],
                    ':quantity'   => $order_item['quantity'],
                    ':price'      => $order_item['price'],
                ]);

                $stock_stmt->execute([
                    ':qty'        => $order_item['quantity'],
                    ':product_id' => $order_item['product_id'],
                ]);
            }

            // Commit transaction
            $pdo->commit();

            // Clear the shopping cart
            $_SESSION['cart'] = [];

            // Redirect to success view
            set_flash_message('success', 'Thank you! Your order #' . $new_order_id . ' has been placed.');
            header('Location: ' . BASE_URL . '/checkout.php?success=1&order_id=' . $new_order_id);
            exit;

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = 'Checkout failed: ' . $e->getMessage();
        }
    }
}

// Prefill information if user is logged in
$prefill_name  = $_SESSION['user_name'] ?? '';
$prefill_email = $_SESSION['user_email'] ?? '';

// Calculate summary totals for display
$raw_ids = array_keys($_SESSION['cart']);
$product_ids = array_filter(array_map('intval', $raw_ids), fn($id) => $id > 0);
$cart_preview = [];
$preview_total = 0.0;

if (!empty($product_ids)) {
    try {
        $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
        $stmt = $pdo->prepare("SELECT id, name, price FROM products WHERE id IN ($placeholders)");
        $stmt->execute(array_values($product_ids));
        $fetched = $stmt->fetchAll();

        foreach ($fetched as $p) {
            $pid = (int)$p['id'];
            $qty = is_array($_SESSION['cart'][$pid]) ? (int)$_SESSION['cart'][$pid]['quantity'] : (int)$_SESSION['cart'][$pid];
            $subtotal = (float)$p['price'] * $qty;
            $preview_total += $subtotal;
            $cart_preview[] = [
                'name'     => $p['name'],
                'price'    => (float)$p['price'],
                'quantity' => $qty,
                'subtotal' => $subtotal,
            ];
        }
    } catch (PDOException $e) {
        $cart_preview = [];
    }
}

$page_title = 'Secure Checkout';
require_once __DIR__ . '/includes/header.php';
?>

<div class="mb-4">
    <h2 class="fw-bold text-dark mb-1">
        <i class="bi bi-shield-check text-primary me-2"></i>Secure Checkout
    </h2>
    <p class="text-muted small">Please enter your shipping address to complete your order</p>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger rounded-4 shadow-sm py-3 mb-4">
        <ul class="mb-0 ps-3">
            <?php foreach ($errors as $err): ?>
                <li><?php echo sanitize($err); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Shipping Form Column -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5">
            <h5 class="fw-bold text-dark mb-4">
                <i class="bi bi-truck text-primary me-2"></i>Shipping Details
            </h5>

            <form action="<?php echo BASE_URL; ?>/checkout.php" method="POST" novalidate>
                <!-- Full Name -->
                <div class="mb-3">
                    <label for="name" class="form-label small fw-semibold text-secondary">Full Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo sanitize($prefill_name); ?>" placeholder="e.g. John Doe" required>
                </div>

                <!-- Email Address -->
                <div class="mb-3">
                    <label for="email" class="form-label small fw-semibold text-secondary">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo sanitize($prefill_email); ?>" placeholder="e.g. john@example.com" required>
                </div>

                <!-- Street Address -->
                <div class="mb-3">
                    <label for="address" class="form-label small fw-semibold text-secondary">Shipping Address</label>
                    <input type="text" class="form-control" id="address" name="address" placeholder="123 Tech Blvd, Suite 100" required>
                </div>

                <!-- City & Zip -->
                <div class="row g-3 mb-4">
                    <div class="col-md-7">
                        <label for="city" class="form-label small fw-semibold text-secondary">City</label>
                        <input type="text" class="form-control" id="city" name="city" placeholder="San Francisco" required>
                    </div>
                    <div class="col-md-5">
                        <label for="zip" class="form-label small fw-semibold text-secondary">Zip / Postal Code</label>
                        <input type="text" class="form-control" id="zip" name="zip" placeholder="94107" required>
                    </div>
                </div>

                <div class="p-3 bg-light rounded-3 border mb-4 small text-secondary">
                    <i class="bi bi-info-circle-fill text-primary me-1"></i> Demo Checkout: No real payment required. Order will be recorded immediately into database.
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill py-3 fw-bold shadow-sm">
                    Place Order (<?php echo format_price($preview_total); ?>)
                </button>
            </form>
        </div>
    </div>

    <!-- Order Summary Column -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 p-4 sticky-top" style="top: 90px;">
            <h5 class="fw-bold text-dark mb-3">Order Summary</h5>

            <div class="mb-3" style="max-height: 280px; overflow-y: auto;">
                <?php foreach ($cart_preview as $item): ?>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <div>
                            <div class="fw-semibold text-dark small"><?php echo sanitize($item['name']); ?></div>
                            <div class="text-muted small">Qty: <?php echo $item['quantity']; ?> &times; <?php echo format_price($item['price']); ?></div>
                        </div>
                        <div class="fw-bold text-dark small">
                            <?php echo format_price($item['subtotal']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Subtotal</span>
                <span class="fw-semibold"><?php echo format_price($preview_total); ?></span>
            </div>

            <div class="d-flex justify-content-between mb-3">
                <span class="text-muted">Shipping</span>
                <span class="text-success fw-semibold">FREE</span>
            </div>

            <hr>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="fw-bold fs-5 text-dark">Total</span>
                <span class="fw-bold fs-4 text-primary"><?php echo format_price($preview_total); ?></span>
            </div>

            <div class="text-muted small text-center">
                <i class="bi bi-lock-fill text-success me-1"></i> Encrypted Transaction
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
