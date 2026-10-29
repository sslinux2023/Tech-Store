<?php
/**
 * User Registration Page
 * File: auth/register.php
 *
 * Validates registration input, checks email uniqueness, hashes passwords
 * securely with Bcrypt, and logs the user in immediately before redirecting to homepage.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirect if already authenticated
if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$errors = [];
$name   = '';
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name             = trim($_POST['name'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Field validation
    if (empty($name)) {
        $errors[] = 'Full name is required.';
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }

    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }

    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }

    // Check if email already exists in users table
    if (empty($errors)) {
        try {
            $check_stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $check_stmt->execute([':email' => $email]);
            if ($check_stmt->fetch()) {
                $errors[] = 'An account with this email address already exists.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error checking account uniqueness.';
        }
    }

    // Insert new user if no errors
    if (empty($errors)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'customer';

            $insert_stmt = $pdo->prepare("
                INSERT INTO users (name, email, password, role) 
                VALUES (:name, :email, :password, :role)
            ");
            $insert_stmt->execute([
                ':name'     => $name,
                ':email'    => $email,
                ':password' => $hashed_password,
                ':role'     => $role,
            ]);

            $new_user_id = (int)$pdo->lastInsertId();

            // Log user in immediately
            $_SESSION['user_id']    = $new_user_id;
            $_SESSION['user_name']  = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role']  = $role;

            set_flash_message('success', 'Registration successful! Welcome to TechStore, ' . sanitize($name) . '.');
            header('Location: ' . BASE_URL . '/index.php');
            exit;

        } catch (PDOException $e) {
          $errors[] = 'Registration failed due to a system error. Please try again.';        }
    }
}

$page_title = 'Create an Account';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center py-4">
    <div class="col-md-7 col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="bg-dark text-white p-4 text-center">
                <i class="bi bi-person-plus-fill display-5 text-info mb-2"></i>
                <h3 class="fw-bold mb-1">Create Account</h3>
                <p class="text-white-50 small mb-0">Join TechStore for faster checkout and order tracking</p>
            </div>

            <div class="card-body p-4 p-md-5">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger rounded-3 py-2 small mb-4">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo sanitize($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="<?php echo BASE_URL; ?>/auth/register.php" method="POST" novalidate>
                    <!-- Name -->
                    <div class="mb-3">
                        <label for="name" class="form-label small fw-semibold text-secondary">Full Name</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted border-end-0">
                                <i class="bi bi-person"></i>
                            </span>
                            <input type="text" class="form-control border-start-0" id="name" name="name" value="<?php echo sanitize($name); ?>" placeholder="John Doe" required autofocus>
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="mb-3">
                        <label for="email" class="form-label small fw-semibold text-secondary">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted border-end-0">
                                <i class="bi bi-envelope"></i>
                            </span>
                            <input type="email" class="form-control border-start-0" id="email" name="email" value="<?php echo sanitize($email); ?>" placeholder="john@example.com" required>
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="mb-3">
                        <label for="password" class="form-label small fw-semibold text-secondary">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted border-end-0">
                                <i class="bi bi-lock"></i>
                            </span>
                            <input type="password" class="form-control border-start-0" id="password" name="password" placeholder="At least 6 characters" required>
                        </div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label small fw-semibold text-secondary">Confirm Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted border-end-0">
                                <i class="bi bi-shield-lock"></i>
                            </span>
                            <input type="password" class="form-control border-start-0" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill py-3 fw-bold shadow-sm">
                        Register Account
                    </button>
                </form>

                <div class="text-center mt-4 pt-3 border-top">
                    <p class="text-muted small mb-0">
                        Already have an account? 
                        <a href="<?php echo BASE_URL; ?>/auth/login.php" class="text-primary fw-bold text-decoration-none">Sign In</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
