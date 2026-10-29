<?php
/**
 * Site Header & Navigation Bar
 * File: includes/header.php
 *
 * Initializes session, includes configuration/helpers, renders the Bootstrap 5 navbar,
 * displays flash messages, and opens the main container.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

$cart_count = get_cart_count();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? sanitize($page_title) . ' | TechStore' : 'TechStore - Premium Electronics'; ?></title>

    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Custom Styles -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/style.css">
    <link rel="icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/images/placeholder.svg">
    <style>
        body { font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
    </style>
</head>
<body class="d-flex flex-column min-vh-100 bg-light">

    <!-- Top Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="<?php echo BASE_URL; ?>/index.php">
                <i class="bi bi-cpu text-info fs-4"></i>
                <span class="text-white">TechStore</span>
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#storeNavbar" aria-controls="storeNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="storeNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link text-white-50" href="<?php echo BASE_URL; ?>/index.php">All Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white-50" href="<?php echo BASE_URL; ?>/index.php?category=1">Phones</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white-50" href="<?php echo BASE_URL; ?>/index.php?category=2">Tablets</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white-50" href="<?php echo BASE_URL; ?>/index.php?category=3">Accessories</a>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-2">
                    <!-- Cart Button with Dynamic Badge -->
                    <a href="<?php echo BASE_URL; ?>/cart.php" class="btn btn-outline-light position-relative rounded-pill px-3 py-2 d-inline-flex align-items-center gap-2">
                        <i class="bi bi-cart3"></i>
                        <span>Cart</span>
                        <span class="badge bg-primary rounded-pill"><?php echo $cart_count; ?></span>
                    </a>

                    <!-- User Account / Authentication Links -->
                    <?php if (is_logged_in()): ?>
                        <div class="dropdown">
                            <button class="btn btn-dark dropdown-toggle rounded-pill px-3 py-2 border border-secondary text-white" type="button" id="userMenuBtn" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle text-info me-1"></i>
                                <?php echo sanitize($_SESSION['user_name'] ?? 'Account'); ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3 mt-2" aria-labelledby="userMenuBtn">
                                <li>
                                    <div class="dropdown-header text-muted small">
                                        Signed in as<br>
                                        <strong class="text-dark"><?php echo sanitize($_SESSION['user_email'] ?? ''); ?></strong>
                                    </div>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo BASE_URL; ?>/cart.php">
                                        <i class="bi bi-bag-check me-2"></i> My Cart
                                    </a>
                                </li>
                                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/index.php"><i class="bi bi-speedometer2 me-2"></i> Admin Dashboard</a>
                                    </li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>/auth/logout.php">
                                        <i class="bi bi-box-arrow-right me-2"></i> Logout
                                    </a>
                                </li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <div class="d-flex gap-2">
                            <a href="<?php echo BASE_URL; ?>/auth/login.php" class="btn btn-outline-light rounded-pill px-3 py-2">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Login
                            </a>
                            <a href="<?php echo BASE_URL; ?>/auth/register.php" class="btn btn-primary rounded-pill px-3 py-2">
                                <i class="bi bi-person-plus me-1"></i> Register
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content Area -->
    <main>
        <?php if ($flash = get_flash_message()): ?>
            <div class="container mt-3">
                <div class="alert alert-<?php echo sanitize($flash['type']); ?> alert-dismissible fade show shadow-sm rounded-4 d-flex align-items-center" role="alert">
                    <i class="bi <?php echo ($flash['type'] === 'success') ? 'bi-check-circle-fill text-success' : 'bi-exclamation-triangle-fill text-danger'; ?> me-2 fs-5"></i>
                    <div><?php echo sanitize($flash['message']); ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>

        <div class="container py-4">
