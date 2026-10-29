<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: " . BASE_URL . "/index.php");
    exit;
}

// Accept ID via POST (preferred) or GET
$product_id = isset($_POST['id']) ? (int)$_POST['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

if ($product_id <= 0) {
    set_flash_message('danger', 'Invalid product ID specified.');
    header("Location: " . BASE_URL . "/admin/index.php");
    exit;
}

try {
    // Check if product exists and get its name
    $check_stmt = $pdo->prepare("SELECT id, name, image FROM products WHERE id = :id LIMIT 1");
    $check_stmt->execute([':id' => $product_id]);
    $product = $check_stmt->fetch();

    if (!$product) {
        set_flash_message('warning', 'Product not found or already deleted.');
        header("Location: " . BASE_URL . "/admin/index.php");
        exit;
    }

    // Execute deletion using prepared statement
    $del_stmt = $pdo->prepare("DELETE FROM products WHERE id = :id");
    $del_stmt->execute([':id' => $product_id]);

    // If product had a custom uploaded file, clean up disk
    if (!empty($product['image']) && strpos($product['image'], 'prod_') !== false) {
        $img_file = realpath(__DIR__ . '/../' . ltrim($product['image'], '/'));
        if ($img_file && file_exists($img_file)) {
            @unlink($img_file);
        }
    }

    set_flash_message('success', 'Product "' . sanitize($product['name']) . '" was successfully deleted.');

} catch (PDOException $e) {
    // Handle foreign key constraint violation (e.g. item exists in past customer order)
    if ($e->getCode() == '23000') {
        set_flash_message('warning', 'Cannot delete "' . sanitize($product['name'] ?? 'Product') . '" because it is linked to past customer orders. To hide it from customers, please edit its stock to 0 instead.');
    } else {
        set_flash_message('danger', 'Error deleting product: ' . $e->getMessage());
    }
}

header("Location: " . BASE_URL . "/admin/index.php");
exit;
