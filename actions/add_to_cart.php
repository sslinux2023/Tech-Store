<?php
/**
 * Action: Add Product to Cart
 * File: actions/add_to_cart.php
 *
 * Validates product availability and stock from database using PDO,
 * and increments or creates the line-item in the $_SESSION['cart'] array.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$product_id = isset($_POST['product_id']) && is_numeric($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity   = isset($_POST['quantity']) && is_numeric($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

if ($product_id <= 0 || $quantity <= 0) {
    set_flash_message('danger', 'Invalid product or quantity specified.');
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Fetch the product from database to verify price, stock, and existence
try {
    $stmt = $pdo->prepare("SELECT id, name, price, image, stock FROM products WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $product_id]);
    $product = $stmt->fetch();
} catch (PDOException $e) {
    set_flash_message('danger', 'Database error occurred. Please try again.');
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

if (!$product) {
    set_flash_message('danger', 'The requested product does not exist.');
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$available_stock = (int)$product['stock'];
if ($available_stock <= 0) {
    set_flash_message('warning', 'Sorry, ' . sanitize($product['name']) . ' is out of stock.');
    header('Location: ' . BASE_URL . '/product.php?id=' . $product_id);
    exit;
}

// Initialize session cart if not present
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Check current quantity in cart
$current_cart_qty = isset($_SESSION['cart'][$product_id]) ? (int)$_SESSION['cart'][$product_id]['quantity'] : 0;
$new_total_qty = $current_cart_qty + $quantity;

// Prevent adding more items than currently in stock
if ($new_total_qty > $available_stock) {
    $new_total_qty = $available_stock;
    set_flash_message('warning', 'Only ' . $available_stock . ' units available. Cart updated to maximum available stock.');
} else {
    set_flash_message('success', 'Added ' . sanitize($product['name']) . ' to your cart.');
}

// Store product snapshot in session cart
$_SESSION['cart'][$product_id] = [
    'id'       => (int)$product['id'],
    'name'     => $product['name'],
    'price'    => (float)$product['price'],
    'image'    => $product['image'],
    'quantity' => $new_total_qty,
];

// Redirect user to cart page or previous page
$redirect_url = isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : BASE_URL . '/cart.php';
header('Location: ' . $redirect_url);
exit;

