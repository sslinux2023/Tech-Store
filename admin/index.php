<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: " . BASE_URL . "/index.php");
    exit;
}

// Fetch all products with their category names using PDO prepared statement
try {
    $stmt = $pdo->prepare("
        SELECT p.*, c.name AS category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        ORDER BY p.id DESC
    ");
    $stmt->execute();
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    $products = [];
    $error = "Database query error: " . $e->getMessage();
}

// Quick Inventory Statistics
$total_products = count($products);
$total_stock = 0;
foreach ($products as $p) {
    $total_stock += (int)$p['stock'];
}

$page_title = 'Admin Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Admin Header Bar -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-danger rounded-pill px-3 py-2 text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Admin Portal</span>
            <span class="text-muted small">| Inventory Management</span>
        </div>
        <h2 class="fw-bold text-dark mt-2 mb-0">
            <i class="bi bi-speedometer2 text-primary me-2"></i>Product Catalog
        </h2>
    </div>
    <div>
        <a href="<?php echo BASE_URL; ?>/admin/add_product.php" class="btn btn-primary btn-lg rounded-pill px-4 shadow-sm fw-semibold d-inline-flex align-items-center gap-2">
            <i class="bi bi-plus-circle-fill fs-5"></i>
            <span>Add New Product</span>
        </a>
    </div>
</div>

<!-- Dashboard Metric Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary-subtle text-primary p-3 rounded-4">
                    <i class="bi bi-boxes fs-3"></i>
                </div>
                <div>
                    <span class="text-muted small">Total Products</span>
                    <h3 class="fw-bold text-dark mb-0"><?php echo $total_products; ?></h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-success-subtle text-success p-3 rounded-4">
                    <i class="bi bi-check2-circle fs-3"></i>
                </div>
                <div>
                    <span class="text-muted small">Total Units in Stock</span>
                    <h3 class="fw-bold text-dark mb-0"><?php echo $total_stock; ?></h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-12 col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-info-subtle text-info p-3 rounded-4">
                    <i class="bi bi-shield-check fs-3"></i>
                </div>
                <div>
                    <span class="text-muted small">Security Level</span>
                    <h3 class="fw-bold text-dark mb-0">Admin Enforced</h3>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Products Management Table -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-5">
    <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0 text-dark">Live Store Products</h5>
        <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-2"><?php echo $total_products; ?> Items</span>
    </div>

    <?php if (empty($products)): ?>
        <div class="text-center py-5 px-3">
            <i class="bi bi-inbox text-muted display-4 mb-3"></i>
            <h5 class="fw-bold text-dark">No Products in Database</h5>
            <p class="text-muted mb-3">Get started by creating your first catalog item.</p>
            <a href="<?php echo BASE_URL; ?>/admin/add_product.php" class="btn btn-primary rounded-pill px-4">
                <i class="bi bi-plus-circle me-1"></i> Add Product
            </a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4 py-3" style="width: 70px;">ID</th>
                        <th class="py-3" style="width: 90px;">Image</th>
                        <th class="py-3">Name</th>
                        <th class="py-3">Category</th>
                        <th class="py-3">Price</th>
                        <th class="py-3">Stock</th>
                        <th class="text-end pe-4 py-3" style="width: 180px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                        <?php 
                            $img_src = !empty($p['image']) ? BASE_URL . '/' . ltrim($p['image'], '/') : BASE_URL . '/images/placeholder.svg';
                            $in_stock = (int)$p['stock'] > 0;
                        ?>
                        <tr>
                            <!-- Product ID -->
                            <td class="ps-4 text-muted fw-bold">#<?php echo (int)$p['id']; ?></td>

                            <!-- Product Thumbnail -->
                            <td>
                                <img src="<?php echo sanitize($img_src); ?>" alt="<?php echo sanitize($p['name']); ?>" style="width: 55px; height: 55px; object-fit: contain; background: #f8fafc; border-radius: 8px; padding: 4px; border: 1px solid #e2e8f0;">
                            </td>

                            <!-- Product Name & Description Snippet -->
                            <td>
                                <a href="<?php echo BASE_URL; ?>/product.php?id=<?php echo (int)$p['id']; ?>" target="_blank" class="fw-bold text-dark text-decoration-none">
                                    <?php echo sanitize($p['name']); ?>
                                </a>
                                <div class="text-muted small text-truncate" style="max-width: 280px;">
                                    <?php echo sanitize($p['description']); ?>
                                </div>
                            </td>

                            <!-- Category -->
                            <td>
                                <span class="badge bg-light text-primary border rounded-pill px-3 py-2 fw-semibold">
                                    <?php echo sanitize($p['category_name'] ?? 'Uncategorized'); ?>
                                </span>
                            </td>

                            <!-- Price -->
                            <td>
                                <span class="fw-bold text-dark">
                                    <?php echo format_price($p['price']); ?>
                                </span>
                            </td>

                            <!-- Stock -->
                            <td>
                                <?php if ($in_stock): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1">
                                        <?php echo (int)$p['stock']; ?> in stock
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-1">
                                        Out of stock
                                    </span>
                                <?php endif; ?>
                            </td>

                            <!-- Actions -->
                            <td class="text-end pe-4">
                                <div class="d-inline-flex gap-2">
                                    <!-- Edit Button -->
                                    <a href="<?php echo BASE_URL; ?>/admin/edit_product.php?id=<?php echo (int)$p['id']; ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3" title="Edit Product">
                                        <i class="bi bi-pencil-square me-1"></i> Edit
                                    </a>

                                    <!-- Delete Button Form -->
                                    <form action="<?php echo BASE_URL; ?>/admin/delete_product.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete \'<?php echo addslashes(sanitize($p['name'])); ?>\'? This action cannot be undone.');">
                                        <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3" title="Delete Product">
                                            <i class="bi bi-trash3 me-1"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
