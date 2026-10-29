<?php
/**
 * Site Footer
 * File: includes/footer.php
 *
 * Closes the page container and main wrapper, renders the footer section,
 * and includes the Bootstrap 5 JavaScript bundle.
 */
?>
        </div><!-- /.container py-4 -->
    </main>

    <!-- Footer Section -->
    <footer class="footer-tech mt-auto bg-dark text-white-50 py-5 border-top border-dark">
        <div class="container">
            <div class="row g-4">
                <!-- Brand Info -->
                <div class="col-lg-4 col-md-6">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-cpu text-info fs-4"></i>
                        <h5 class="text-white fw-bold mb-0">TechStore</h5>
                    </div>
                    <p class="small text-secondary mb-3">
                        Your trusted electronics store for high-performance phones, tablets, and mobile accessories.
                    </p>
                    <div class="d-flex gap-3 text-secondary small">
                        <span><i class="bi bi-shield-check text-info me-1"></i> Genuine Devices</span>
                        <span><i class="bi bi-truck text-info me-1"></i> Fast Shipping</span>
                    </div>
                </div>

                <!-- Categories -->
                <div class="col-lg-2 col-md-6 col-6">
                    <h6 class="text-white fw-bold mb-3">Categories</h6>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="<?php echo BASE_URL; ?>/index.php" class="text-white-50 text-decoration-none">All Products</a></li>
                        <li class="mb-2"><a href="<?php echo BASE_URL; ?>/index.php?category=1" class="text-white-50 text-decoration-none">Phones</a></li>
                        <li class="mb-2"><a href="<?php echo BASE_URL; ?>/index.php?category=2" class="text-white-50 text-decoration-none">Tablets</a></li>
                        <li class="mb-2"><a href="<?php echo BASE_URL; ?>/index.php?category=3" class="text-white-50 text-decoration-none">Accessories</a></li>
                    </ul>
                </div>

                <!-- Customer Account -->
                <div class="col-lg-2 col-md-6 col-6">
                    <h6 class="text-white fw-bold mb-3">Account</h6>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="<?php echo BASE_URL; ?>/cart.php" class="text-white-50 text-decoration-none">Shopping Cart</a></li>
                        <li class="mb-2"><a href="<?php echo BASE_URL; ?>/checkout.php" class="text-white-50 text-decoration-none">Checkout</a></li>
                        <?php if (is_logged_in()): ?>
                            <li class="mb-2"><a href="<?php echo BASE_URL; ?>/auth/logout.php" class="text-white-50 text-decoration-none">Logout</a></li>
                        <?php else: ?>
                            <li class="mb-2"><a href="<?php echo BASE_URL; ?>/auth/login.php" class="text-white-50 text-decoration-none">Sign In</a></li>
                            <li class="mb-2"><a href="<?php echo BASE_URL; ?>/auth/register.php" class="text-white-50 text-decoration-none">Create Account</a></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Newsletter / Info -->
                <div class="col-lg-4 col-md-6">
                    <h6 class="text-white fw-bold mb-3">Newsletter</h6>
                    <p class="small text-secondary mb-3">Get the latest updates on new arrivals and special tech discounts.</p>
                    <div class="input-group input-group-sm">
                        <input type="email" class="form-control bg-dark border-secondary text-white" placeholder="Enter your email" aria-label="Email">
                        <button class="btn btn-primary" type="button">Subscribe</button>
                    </div>
                </div>
            </div>

            <div class="border-top border-secondary pt-4 mt-4 d-flex flex-column flex-md-row justify-content-between align-items-center small text-secondary">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> TechStore Electronics. Built with PHP, PDO & Bootstrap 5 on XAMPP.</p>
                <div>
                    <i class="bi bi-shield-lock-fill text-success me-1"></i> Secure 256-bit SSL Protected
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
