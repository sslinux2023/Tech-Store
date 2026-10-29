<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: " . BASE_URL . "/index.php");
    exit;
}

// Validate Product ID
$product_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    set_flash_message('danger', 'Invalid product ID specified.');
    header("Location: " . BASE_URL . "/admin/index.php");
    exit;
}

// Fetch Existing Product Data
try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        set_flash_message('danger', 'Product not found.');
        header("Location: " . BASE_URL . "/admin/index.php");
        exit;
    }
} catch (PDOException $e) {
    set_flash_message('danger', 'Database error: ' . $e->getMessage());
    header("Location: " . BASE_URL . "/admin/index.php");
    exit;
}

// Fetch categories for select dropdown
try {
    $cat_stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
    $categories = $cat_stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

$errors = [];
$name        = $product['name'];
$description = $product['description'];
$price       = $product['price'];
$stock       = $product['stock'];
$category_id = $product['category_id'];
$image_path  = $product['image'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = trim($_POST['price'] ?? '');
    $stock       = trim($_POST['stock'] ?? '0');
    $category_id = (int)($_POST['category_id'] ?? 0);

    // Validate textual fields
    if (empty($name)) {
        $errors[] = 'Product name is required.';
    }

    if (empty($description)) {
        $errors[] = 'Product description is required.';
    }

    if (!is_numeric($price) || (float)$price < 0) {
        $errors[] = 'Please provide a valid non-negative price.';
    }

    if (!is_numeric($stock) || (int)$stock < 0) {
        $errors[] = 'Stock quantity must be a non-negative integer.';
    }

    if ($category_id <= 0) {
        $errors[] = 'Please select a valid category.';
    }

    // Process Optional Image Replacement
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Image upload error code: ' . (int)$_FILES['image']['error'];
        } else {
            $file_tmp  = $_FILES['image']['tmp_name'];
            $file_name = $_FILES['image']['name'];
            $file_size = $_FILES['image']['size'];

            // Maximum allowed file size: 5MB
            if ($file_size > 5 * 1024 * 1024) {
                $errors[] = 'Image file exceeds 5MB size limit.';
            }

            // Extension check
            $uploaded_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($uploaded_ext, $allowed_extensions, true)) {
                $errors[] = 'Invalid file extension. Allowed formats: .jpg, .jpeg, .png, .webp.';
            }

            // MIME type check
            $allowed_mime_types = ['image/jpeg', 'image/png', 'image/webp'];

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file_tmp);
            finfo_close($finfo);

            if (!in_array($mime_type, $allowed_mime_types, true)) {
                $errors[] = 'Invalid image MIME type: ' . sanitize($mime_type);
            }

            // Update file extension logic based on validated MIME type
            if ($mime_type === 'image/webp') {
                $ext = 'webp';
            } elseif ($mime_type === 'image/png') {
                $ext = 'png';
            } else {
                $ext = 'jpg';
            }

            if (empty($errors)) {
                $upload_dir = realpath(__DIR__ . '/../images');
                if (!$upload_dir || !is_dir($upload_dir)) {
                    $upload_dir = __DIR__ . '/../images';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                }

                $safe_filename = uniqid('prod_', true) . '.' . $ext;
                $destination   = rtrim($upload_dir, '/\\') . DIRECTORY_SEPARATOR . $safe_filename;

                if (move_uploaded_file($file_tmp, $destination)) {
                    $image_path = 'images/' . $safe_filename;
                } else {
                    $errors[] = 'Failed to move uploaded replacement image.';
                }
            }
        }
    }

    // Execute Update Query
    if (empty($errors)) {
        try {
            $update_stmt = $pdo->prepare("
                UPDATE products 
                SET category_id = :category_id, 
                    name = :name, 
                    description = :description, 
                    price = :price, 
                    stock = :stock, 
                    image = :image 
                WHERE id = :id
            ");
            $update_stmt->execute([
                ':category_id' => $category_id,
                ':name'        => $name,
                ':description' => $description,
                ':price'       => (float)$price,
                ':stock'       => (int)$stock,
                ':image'       => $image_path,
                ':id'          => $product_id,
            ]);

            set_flash_message('success', 'Product "' . sanitize($name) . '" updated successfully!');
            header("Location: " . BASE_URL . "/admin/index.php");
            exit;

        } catch (PDOException $e) {
            $errors[] = 'Database update error: ' . $e->getMessage();
        }
    }
}

$page_title = 'Edit Product #' . $product_id;
require_once __DIR__ . '/../includes/header.php';
$current_img_src = !empty($image_path) ? BASE_URL . '/' . ltrim($image_path, '/') : BASE_URL . '/images/placeholder.svg';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <!-- Breadcrumb Navigation -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/admin/index.php" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit #<?php echo $product_id; ?></li>
            </ol>
        </nav>

        <!-- Edit Form Card -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-5">
            <div class="card-header bg-dark text-white p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-pencil-square text-info fs-4"></i>
                        <div>
                            <h4 class="fw-bold mb-0 text-white">Edit Product</h4>
                            <small class="text-white-50">Modifying product ID #<?php echo $product_id; ?></small>
                        </div>
                    </div>
                    <span class="badge bg-secondary rounded-pill px-3 py-2">ID: #<?php echo $product_id; ?></span>
                </div>
            </div>

            <div class="card-body p-4 p-md-5">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger rounded-4 shadow-sm mb-4">
                        <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Please fix the following errors:</div>
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $err): ?>
                                <li><?php echo sanitize($err); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="<?php echo BASE_URL; ?>/admin/edit_product.php?id=<?php echo $product_id; ?>" method="POST" enctype="multipart/form-data" novalidate>
                    <!-- Product Name -->
                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold text-secondary small">Product Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo sanitize($name); ?>" required autofocus>
                    </div>

                    <!-- Category & Price Row -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="category_id" class="form-label fw-semibold text-secondary small">Category <span class="text-danger">*</span></label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">-- Choose Category --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo (int)$cat['id']; ?>" <?php echo ((int)$category_id === (int)$cat['id']) ? 'selected' : ''; ?>>
                                        <?php echo sanitize($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="price" class="form-label fw-semibold text-secondary small">Price (USD) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted border-end-0">$</span>
                                <input type="number" step="0.01" class="form-control border-start-0" id="price" name="price" value="<?php echo sanitize((string)$price); ?>" min="0" required>
                            </div>
                        </div>
                    </div>

                    <!-- Stock Quantity -->
                    <div class="mb-3">
                        <label for="stock" class="form-label fw-semibold text-secondary small">Stock Quantity <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="stock" name="stock" value="<?php echo sanitize((string)$stock); ?>" min="0" required>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label for="description" class="form-label fw-semibold text-secondary small">Full Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="4" required><?php echo sanitize($description); ?></textarea>
                    </div>

                    <!-- Current Image Preview & Replacement -->
                    <div class="mb-4 p-3 bg-light rounded-4 border">
                        <label class="form-label fw-semibold text-secondary small mb-2">Current Product Image</label>
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <img src="<?php echo sanitize($current_img_src); ?>" alt="Current Product Image" style="width: 80px; height: 80px; object-fit: contain; background: #ffffff; border-radius: 8px; padding: 6px; border: 1px solid #cbd5e1;">
                            <div class="small text-muted">
                                File: <code><?php echo sanitize($image_path); ?></code><br>
                                Leave the file field below empty to keep this existing photo.
                            </div>
                        </div>

                        <label for="image" class="form-label fw-semibold text-secondary small">Replace Image (Optional)</label>
                        <input type="file" class="form-control bg-white" id="image" name="image" accept=".jpg,.jpeg,.png,.webp">
                        <div class="form-text text-muted small">
                            Accepted: <strong>.jpg, .jpeg, .png, .webp</strong> (Max 5MB).
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <a href="<?php echo BASE_URL; ?>/admin/index.php" class="btn btn-outline-secondary rounded-pill px-4">
                            <i class="bi bi-arrow-left me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary rounded-pill px-5 py-2 fw-semibold shadow-sm">
                            <i class="bi bi-check2-circle me-1"></i> Update Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
