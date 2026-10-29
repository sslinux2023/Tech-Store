<?php
/**
 * User Login Page
 * File: auth/login.php
 *
 * Validates credentials using PDO prepared statements and password_verify().
 * Prevents session fixation via session_regenerate_id().
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$error = '';
$email = '';
$redirect = isset($_GET['redirect']) ? trim($_GET['redirect']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $redirect = trim($_POST['redirect'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Please enter both your email address and password.';
    } else {
        try {
            // Prepared statement to look up user securely
            $stmt = $pdo->prepare("SELECT id, name, email, password, role FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Prevent session fixation attack
                session_regenerate_id(true);

                // Set user session variables
                $_SESSION['user_id']    = (int)$user['id'];
                $_SESSION['user_name']  = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role']  = $user['role'];

                set_flash_message('success', 'Welcome back, ' . sanitize($user['name']) . '!');

                if (!empty($redirect) && strpos($redirect, 'http') === false) {
                    header('Location: ' . BASE_URL . '/' . ltrim($redirect, '/'));
                } else {
                    header('Location: ' . BASE_URL . '/index.php');
                }
                exit;
            } else {
                $error = 'Invalid email address or password.';
            }
        } catch (PDOException $e) {
            $error = 'Database authentication error occurred.';
        }
    }
}

$page_title = 'Sign In';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center py-4">
    <div class="col-md-7 col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="bg-dark text-white p-4 text-center">
                <i class="bi bi-person-circle display-5 text-info mb-2"></i>
                <h3 class="fw-bold mb-1">Welcome Back</h3>
                <p class="text-white-50 small mb-0">Sign in to your TechStore account</p>
            </div>

            <div class="card-body p-4 p-md-5">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger rounded-3 py-2 small d-flex align-items-center mb-4" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-6"></i>
                        <div><?php echo sanitize($error); ?></div>
                    </div>
                <?php endif; ?>

                <form action="<?php echo BASE_URL; ?>/auth/login.php" method="POST" novalidate>
                    <input type="hidden" name="redirect" value="<?php echo sanitize($redirect); ?>">

                    <!-- Email -->
                    <div class="mb-3">
                        <label for="email" class="form-label small fw-semibold text-secondary">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted border-end-0">
                                <i class="bi bi-envelope"></i>
                            </span>
                            <input type="email" class="form-control border-start-0" id="email" name="email" value="<?php echo sanitize($email); ?>" placeholder="name@example.com" required autofocus>
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="mb-4">
                        <label for="password" class="form-label small fw-semibold text-secondary">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted border-end-0">
                                <i class="bi bi-lock"></i>
                            </span>
                            <input type="password" class="form-control border-start-0" id="password" name="password" placeholder="••••••••" required>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill py-3 fw-bold shadow-sm">
                        Sign In
                    </button>
                </form>

                <div class="text-center mt-4 pt-3 border-top">
                    <p class="text-muted small mb-0">
                        Don't have an account yet? 
                        <a href="<?php echo BASE_URL; ?>/auth/register.php" class="text-primary fw-bold text-decoration-none">Create Account</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>