<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: " . BASE_URL . "/index.php");
    exit;
}

// Fetch available categories for dropdown
try {
    $cat_stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
    $categories = $cat_stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

$errors = [];
$name        = '';
$description = '';
$price       = '';
$stock       = 10;
$category_id = '';

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

    // File Upload Validation & Processing
    $image_path = 'images/placeholder.svg';

    if (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'A product image is required.';
    } elseif ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'File upload error occurred. Error code: ' . (int)$_FILES['image']['error'];
    } else {
        $file_tmp  = $_FILES['image']['tmp_name'];
        $file_name = $_FILES['image']['name'];
        $file_size = $_FILES['image']['size'];

        // Maximum allowed file size: 5MB
        if ($file_size > 5 * 1024 * 1024) {
            $errors[] = 'File is too large. Maximum allowed size is 5MB.';
        }

        // Whitelist allowed file extensions
        $uploaded_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($uploaded_ext, $allowed_extensions, true)) {
            $errors[] = 'Invalid file extension. Allowed formats: .jpg, .jpeg, .png, .webp.';
        }

        // Validate MIME type securely using finfo_file on the temporary uploaded file
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

        // If validation passed, store securely
        if (empty($errors)) {
            $upload_dir = realpath(__DIR__ . '/../images');
            if (!$upload_dir || !is_dir($upload_dir)) {
                $upload_dir = __DIR__ . '/../images';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
            }

            // Generate unique, collision-proof sanitized filename
            $safe_filename = uniqid('prod_', true) . '.' . $ext;
            $destination   = rtrim($upload_dir, '/\\') . DIRECTORY_SEPARATOR . $safe_filename;

            if (move_uploaded_file($file_tmp, $destination)) {
                $image_path = 'images/' . $safe_filename;
            } else {
                $errors[] = 'Failed to move uploaded file to target images directory.';
            }
        }
    }

    // Insert into Database
    if (empty($errors)) {
        try {
            $insert_stmt = $pdo->prepare("
                INSERT INTO products (category_id, name, description, price, stock, image) 
                VALUES (:category_id, :name, :description, :price, :stock, :image)
            ");
            $insert_stmt->execute([
                ':category_id' => $category_id,
                ':name'        => $name,
                ':description' => $description,
                ':price'       => (float)$price,
                ':stock'       => (int)$stock,
                ':image'       => $image_path,
            ]);

            set_flash_message('success', 'Product "' . sanitize($name) . '" created successfully!');
            header("Location: " . BASE_URL . "/admin/index.php");
            exit;

        } catch (PDOException $e) {
            $errors[] = 'Database insert error: ' . $e->getMessage();
        }
    }
}

$page_title = 'Add New Product';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <!-- Breadcrumb Navigation -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/admin/index.php" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Add Product</li>
            </ol>
        </nav>

        <!-- Form Card -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-5">
            <div class="card-header bg-dark text-white p-4">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-plus-circle-fill text-info fs-4"></i>
                    <div>
                        <h4 class="fw-bold mb-0 text-white">Add New Product</h4>
                        <small class="text-white-50">Populate catalog specifications and upload device photo</small>
                    </div>
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

                <form action="<?php echo BASE_URL; ?>/admin/add_product.php" method="POST" enctype="multipart/form-data" novalidate>
                    <!-- Product Name -->
                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold text-secondary small">Product Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo sanitize($name); ?>" placeholder="e.g. UltraPhone 16 Pro Max" required autofocus>
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
                                <input type="number" step="0.01" class="form-control border-start-0" id="price" name="price" value="<?php echo sanitize((string)$price); ?>" placeholder="999.99" min="0" required>
                            </div>
                        </div>
                    </div>

                    <!-- Stock Quantity -->
                    <div class="mb-3">
                        <label for="stock" class="form-label fw-semibold text-secondary small">Stock Quantity <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="stock" name="stock" value="<?php echo sanitize((string)$stock); ?>" placeholder="25" min="0" required>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label for="description" class="form-label fw-semibold text-secondary small">Full Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="4" placeholder="Enter hardware specifications, dimensions, battery life, and display features..." required><?php echo sanitize($description); ?></textarea>
                    </div>

                    <!-- Image Upload -->
                    <div class="mb-4">
                        <label for="image" class="form-label fw-semibold text-secondary small">Product Image <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="image" name="image" accept=".jpg,.jpeg,.png,.webp" required>
                        <div class="form-text text-muted small">
                            Accepted formats: <strong>.jpg, .jpeg, .png, .webp</strong> (Max: 5MB). File will be cryptographically renamed.
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <a href="<?php echo BASE_URL; ?>/admin/index.php" class="btn btn-outline-secondary rounded-pill px-4">
                            <i class="bi bi-arrow-left me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary rounded-pill px-5 py-2 fw-semibold shadow-sm">
                            <i class="bi bi-check2-circle me-1"></i> Save Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
