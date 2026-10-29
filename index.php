<?php
/**
 * Homepage & Product Catalog
 * File: index.php
 *
 * Displays modern hero banner, category filters, search input,
 * and responsive product grid with Add to Cart forms posting to cart.php.
 */

$page_title = 'Upgrade Your Tech | Modern Electronics Store';
require_once __DIR__ . '/includes/header.php';

// Fetch all categories
try {
    $cat_stmt = $pdo->query("SELECT id, name FROM categories ORDER BY id ASC");
    $categories = $cat_stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

// Category filter & search handling
$selected_category = isset($_GET['category']) && is_numeric($_GET['category']) ? (int)$_GET['category'] : 0;
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build prepared statement
$sql = "SELECT p.*, c.name AS category_name 
        FROM products p 
        JOIN categories c ON p.category_id = c.id";
$params = [];
$clauses = [];

if ($selected_category > 0) {
    $clauses[] = "p.category_id = :cat_id";
    $params[':cat_id'] = $selected_category;
}

if (!empty($search_query)) {
    $clauses[] = "(p.name LIKE :search_name OR p.description LIKE :search_desc)";
    $params[':search_name'] = '%' . $search_query . '%';
    $params[':search_desc'] = '%' . $search_query . '%';
}

if (!empty($clauses)) {
    $sql .= " WHERE " . implode(' AND ', $clauses);
}

$sql .= " ORDER BY p.id DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    $products = [];
}
?>

<!-- Hero Banner Card -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5 bg-dark text-white position-relative" style="background: linear-gradient(135deg, #090d16 0%, #1e1b4b 100%);">
    <div class="card-body p-4 p-md-5">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <span class="badge bg-primary bg-opacity-25 text-info border border-info border-opacity-25 rounded-pill px-3 py-2 mb-3 fw-semibold">
                    <i class="bi bi-stars me-1"></i> New Flagship Electronics 2026
                </span>
                <h1 class="display-5 fw-bold mb-3 text-white">
                    Upgrade Your Tech with <span class="text-info">TechStore</span>.
                </h1>
                <p class="text-secondary lead mb-4">
                    Explore top-rated smartphones, powerful tablets, and high-speed fast chargers engineered for supreme performance.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="#catalog" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold">
                        <i class="bi bi-bag-fill me-1"></i> Shop Collection
                    </a>
                    <a href="<?php echo BASE_URL; ?>/index.php?category=1" class="btn btn-outline-light rounded-pill px-4 py-2">
                        View Phones
                    </a>
                </div>
            </div>
            <div class="col-lg-5 text-center d-none d-lg-block">
                <img src="<?php echo BASE_URL; ?>/images/ultraphone.png" alt="Flagship Phone" class="img-fluid" style="max-height: 240px; filter: drop-shadow(0 15px 25px rgba(6, 182, 212, 0.3)); transform: rotate(-5deg);" onerror="this.onerror=null; this.src='<?php echo BASE_URL; ?>/images/placeholder.svg';">
            </div>
        </div>
    </div>
</div>

<!-- Catalog Navigation & Filter Bar -->
<div id="catalog" class="row align-items-center mb-4 g-3">
    <!-- Category Pills -->
    <div class="col-lg-7">
        <div class="d-flex flex-wrap gap-2">
            <a href="<?php echo BASE_URL; ?>/index.php" class="btn btn-sm rounded-pill px-3 py-2 <?php echo ($selected_category === 0 && empty($search_query)) ? 'btn-primary' : 'btn-outline-secondary bg-white'; ?>">
                <i class="bi bi-grid-fill me-1"></i> All Products
            </a>
            <?php foreach ($categories as $cat): ?>
                <a href="<?php echo BASE_URL; ?>/index.php?category=<?php echo (int)$cat['id']; ?>" class="btn btn-sm rounded-pill px-3 py-2 <?php echo ($selected_category === (int)$cat['id']) ? 'btn-primary' : 'btn-outline-secondary bg-white'; ?>">
                    <?php if ($cat['name'] === 'Phones'): ?>
                        <i class="bi bi-phone me-1"></i>
                    <?php elseif ($cat['name'] === 'Tablets'): ?>
                        <i class="bi bi-tablet me-1"></i>
                    <?php else: ?>
                        <i class="bi bi-headphones me-1"></i>
                    <?php endif; ?>
                    <?php echo sanitize($cat['name']); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Search Form -->
    <div class="col-lg-5">
        <form action="<?php echo BASE_URL; ?>/index.php" method="GET" class="d-flex gap-2">
            <?php if ($selected_category > 0): ?>
                <input type="hidden" name="category" value="<?php echo $selected_category; ?>">
            <?php endif; ?>
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0 text-muted">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Search devices..." value="<?php echo sanitize($search_query); ?>">
                <button class="btn btn-primary px-3" type="submit">Search</button>
                <?php if (!empty($search_query)): ?>
                    <a href="<?php echo BASE_URL; ?>/index.php<?php echo $selected_category > 0 ? '?category=' . $selected_category : ''; ?>" class="btn btn-outline-secondary" title="Clear Search">
                        <i class="bi bi-x-lg"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Header line for filtered count -->
<div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
    <h5 class="fw-bold mb-0 text-dark">
        <?php 
            if (!empty($search_query)) {
                echo 'Search results for "' . sanitize($search_query) . '"';
            } elseif ($selected_category > 0) {
                $cname = 'Products';
                foreach ($categories as $c) {
                    if ((int)$c['id'] === $selected_category) {
                        $cname = $c['name'];
                        break;
                    }
                }
                echo sanitize($cname);
            } else {
                echo 'Featured Products';
            }
        ?>
        <span class="badge bg-secondary-subtle text-secondary rounded-pill fs-6 ms-2"><?php echo count($products); ?> items</span>
    </h5>
    <?php if ($selected_category > 0 || !empty($search_query)): ?>
        <a href="<?php echo BASE_URL; ?>/index.php" class="text-decoration-none small text-muted">
            <i class="bi bi-arrow-counterclockwise"></i> Show All
        </a>
    <?php endif; ?>
</div>

<!-- Product Grid -->
<?php if (empty($products)): ?>
    <div class="card border-0 shadow-sm rounded-4 text-center py-5 px-3">
        <div class="card-body">
            <i class="bi bi-inbox text-muted display-4 mb-3"></i>
            <h4 class="fw-bold">No Products Found</h4>
            <p class="text-muted">No items matched your current filter criteria.</p>
            <a href="<?php echo BASE_URL; ?>/index.php" class="btn btn-primary rounded-pill px-4">
                View All Items
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 g-4">
        <?php foreach ($products as $prod): ?>
            <?php 
                $in_stock = (int)$prod['stock'] > 0;
                $img_src = !empty($prod['image']) ? BASE_URL . '/' . ltrim($prod['image'], '/') : BASE_URL . '/images/placeholder.svg';
            ?>
            <div class="col">
                <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden d-flex flex-column">
                    <!-- Image Box -->
                    <div class="p-3 bg-white text-center position-relative border-bottom" style="height: 200px; display: flex; align-items: center; justify-content: center;">
                        <span class="badge <?php echo $in_stock ? 'bg-success' : 'bg-danger'; ?> position-absolute top-0 end-0 m-3 rounded-pill small">
                            <?php echo $in_stock ? 'In Stock' : 'Sold Out'; ?>
                        </span>
                        <a href="<?php echo BASE_URL; ?>/product.php?id=<?php echo (int)$prod['id']; ?>">
                            <img src="<?php echo sanitize($img_src); ?>" alt="<?php echo sanitize($prod['name']); ?>" style="max-height: 160px; max-width: 100%; object-fit: contain;" onerror="this.onerror=null; this.src='<?php echo BASE_URL; ?>/images/placeholder.svg';">
                        </a>
                    </div>

                    <!-- Card Body -->
                    <div class="card-body d-flex flex-column p-4">
                        <span class="text-uppercase text-muted fw-bold small mb-1" style="font-size: 0.75rem;">
                            <?php echo sanitize($prod['category_name']); ?>
                        </span>

                        <h6 class="fw-bold text-dark mb-2">
                            <a href="<?php echo BASE_URL; ?>/product.php?id=<?php echo (int)$prod['id']; ?>" class="text-dark text-decoration-none">
                                <?php echo sanitize($prod['name']); ?>
                            </a>
                        </h6>

                        <p class="text-muted small mb-3 flex-grow-1" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                            <?php echo sanitize($prod['description']); ?>
                        </p>

                        <!-- Price and Add to Cart Form -->
                        <div class="pt-3 border-top d-flex align-items-center justify-content-between mt-auto">
                            <span class="fw-bold fs-5 text-primary">
                                <?php echo format_price($prod['price']); ?>
                            </span>

                            <?php if ($in_stock): ?>
                                <form action="<?php echo BASE_URL; ?>/cart.php" method="POST" class="m-0">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="product_id" value="<?php echo (int)$prod['id']; ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" class="btn btn-outline-primary btn-sm rounded-pill px-3" title="Add to Cart">
                                        <i class="bi bi-cart-plus me-1"></i> Add
                                    </button>
                                </form>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-sm rounded-pill px-3" disabled>
                                    Sold Out
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>