<?php
/**
 * Action: Update Cart & Item Removal
 * File: actions/update_cart.php
 *
 * Handles quantity adjustments, line-item deletions, and complete cart resets.
 * Validates requested quantities against live inventory in the database.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Accept POST or GET (for simple delete links)
$method = $_SERVER['REQUEST_METHOD'];

// Determine the intended action
$action = $_REQUEST['action'] ?? 'update';
$product_id = isset($_REQUEST['product_id']) && is_numeric($_REQUEST['product_id']) ? (int)$_REQUEST['product_id'] : 0;

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

switch ($action) {
    case 'update':
        if ($method !== 'POST') {
            break;
        }
        $new_qty = isset($_POST['quantity']) && is_numeric($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

        if ($product_id > 0 && isset($_SESSION['cart'][$product_id])) {
            if ($new_qty <= 0) {
                // If quantity dropped to 0, remove item
                unset($_SESSION['cart'][$product_id]);
                set_flash_message('info', 'Item removed from your cart.');
            } else {
                // Check stock availability in database
                try {
                    $stmt = $pdo->prepare("SELECT stock, name FROM products WHERE id = :id LIMIT 1");
                    $stmt->execute([':id' => $product_id]);
                    $product = $stmt->fetch();
                } catch (PDOException $e) {
                    $product = false;
                }

                if ($product) {
                    $stock = (int)$product['stock'];
                    if ($new_qty > $stock) {
                        $_SESSION['cart'][$product_id]['quantity'] = $stock;
                        set_flash_message('warning', 'Only ' . $stock . ' units of ' . sanitize($product['name']) . ' are available.');
                    } else {
                        $_SESSION['cart'][$product_id]['quantity'] = $new_qty;
                        set_flash_message('success', 'Cart updated successfully.');
                    }
                }
            }
        }
        break;

    case 'remove':
        if ($product_id > 0 && isset($_SESSION['cart'][$product_id])) {
            $item_name = $_SESSION['cart'][$product_id]['name'] ?? 'Item';
            unset($_SESSION['cart'][$product_id]);
            set_flash_message('info', 'Removed ' . sanitize($item_name) . ' from your cart.');
        }
        break;

    case 'clear':
        $_SESSION['cart'] = [];
        set_flash_message('info', 'Your shopping cart has been cleared.');
        break;

    default:
        set_flash_message('warning', 'Invalid cart operation.');
        break;
}

header('Location: ' . BASE_URL . '/cart.php');
exit;

