<?php
/**
 * Helper Functions
 * File: includes/functions.php
 *
 * Implements sanitization, authentication checking, cart counting,
 * price formatting, and flash messaging utilities.
 */

/**
 * Sanitize text output using htmlspecialchars to protect against Cross-Site Scripting (XSS).
 *
 * @param string|null $data
 * @return string
 */
function sanitize(?string $data): string {
    if ($data === null) {
        return '';
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Check if a user is currently authenticated via session.
 *
 * @return bool
 */
function is_logged_in(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Set a one-time flash notification message in the session.
 *
 * @param string $type Bootstrap alert type: 'success', 'danger', 'warning', 'info'
 * @param string $message Alert message text
 */
function set_flash_message(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type'    => $type,
        'message' => $message,
    ];
}

/**
 * Retrieve and clear the current flash notification from the session.
 *
 * @return array|null Array with keys ['type', 'message'] or null
 */
function get_flash_message(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Calculate the total item count in the shopping cart.
 * Accurately supports both scalar quantities ($_SESSION['cart'][$id] = $qty)
 * and array items ($_SESSION['cart'][$id]['quantity']).
 *
 * @return int
 */
function get_cart_count(): int {
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        return 0;
    }

    $count = 0;
    foreach ($_SESSION['cart'] as $item) {
        if (is_array($item)) {
            $count += (int)($item['quantity'] ?? 0);
        } else {
            $count += (int)$item;
        }
    }
    return $count;
}

/**
 * Format numeric amount into standard USD currency string ($XX.XX).
 *
 * @param float|int|string $amount
 * @return string
 */
function format_price($amount): string {
    return '$' . number_format((float)$amount, 2);
}
