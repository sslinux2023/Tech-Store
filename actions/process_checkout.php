<?php
/**
 * Action: Process Checkout & Database Transaction
 * File: actions/process_checkout.php
 *
 * Validates shipping input, begins a PDO atomic transaction, creates order records,
 * inserts line items, updates inventory stock, and empties the cart.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Verify POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/checkout.php');
    exit;
}

$cart_items = $_SESSION['cart'] ?? [];

// Verify cart is populated
if (empty($cart_items)) {
    set_flash_message('danger', 'Your cart is empty. Cannot process checkout.');
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Sanitize & validate shipping inputs
$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$address = trim($_POST['address'] ?? '');
$city    = trim($_POST['city'] ?? '');

$errors = [];

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

if (!empty($errors)) {
    set_flash_message('danger', implode(' ', $errors));
    header('Location: ' . BASE_URL . '/checkout.php');
    exit;
}

// Calculate total cart value
$total_amount = get_cart_total();
$user_id = is_logged_in() ? (int)$_SESSION['user_id'] : null;

try {
    // Begin atomic transaction to ensure data integrity
    $pdo->beginTransaction();

    // 1. Insert into orders table
    $order_stmt = $pdo->prepare("
        INSERT INTO orders (user_id, customer_name, customer_email, shipping_address, city, total_amount, status) 
        VALUES (:user_id, :customer_name, :customer_email, :shipping_address, :city, :total_amount, 'completed')
    ");
    $order_stmt->execute([
        ':user_id'          => $user_id,
        ':customer_name'    => $name,
        ':customer_email'   => $email,
        ':shipping_address' => $address,
        ':city'             => $city,
        ':total_amount'     => $total_amount,
    ]);

    $order_id = (int)$pdo->lastInsertId();

    // 2. Prepare statements for order_items and stock decrement
    $item_stmt = $pdo->prepare("
        INSERT INTO order_items (order_id, product_id, quantity, price) 
        VALUES (:order_id, :product_id, :quantity, :price)
    ");

    $stock_stmt = $pdo->prepare("
        UPDATE products 
        SET stock = GREATEST(0, stock - :quantity) 
        WHERE id = :product_id
    ");

    // 3. Insert each cart item and deduct inventory
    foreach ($cart_items as $item) {
        $prod_id  = (int)$item['id'];
        $quantity = (int)$item['quantity'];
        $price    = (float)$item['price'];

        $item_stmt->execute([
            ':order_id'   => $order_id,
            ':product_id' => $prod_id,
            ':quantity'   => $quantity,
            ':price'      => $price,
        ]);

        $stock_stmt->execute([
            ':quantity'   => $quantity,
            ':product_id' => $prod_id,
        ]);
    }

    // Commit all database operations
    $pdo->commit();

    // 4. Empty the user's shopping cart
    unset($_SESSION['cart']);

    set_flash_message('success', 'Thank you! Your order #' . $order_id . ' has been placed successfully.');
    header('Location: ' . BASE_URL . '/checkout.php?success=1&order_id=' . $order_id);
    exit;

} catch (PDOException $e) {
    // Roll back all changes if any query failed
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    set_flash_message('danger', 'Checkout failed due to a database error: ' . $e->getMessage());
    header('Location: ' . BASE_URL . '/checkout.php');
    exit;
}

