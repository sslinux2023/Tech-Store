<?php
/**
 * Database Configuration & PDO Connection
 * File: config/db.php
 *
 * Connects to MySQL using PDO with prepared statement emulation disabled
 * and exception-based error reporting. Defines the absolute base URL.
 */

// Define Base URL for consistent links and redirects across all directories
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/myproject');
}

// Database Credentials (Standard XAMPP default settings)
$db_host = '127.0.0.1';
$db_name = 'electronics_store';
$db_user = 'root';
$db_pass = '';
$db_port = 3306;
$charset = 'utf8mb4';

$dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset={$charset}";

$pdo_options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
} catch (PDOException $e) {
    die(
        '<div style="font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif; max-width: 650px; margin: 50px auto; padding: 24px; border: 1px solid #fecaca; background: #fff5f5; border-radius: 12px; color: #991b1b; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);">' .
        '<h2 style="margin-top: 0; color: #dc2626;">Database Connection Error</h2>' .
        '<p>Unable to connect to the database <strong>' . htmlspecialchars($db_name) . '</strong>.</p>' .
        '<p><strong>Quick Checklist:</strong></p>' .
        '<ol style="line-height: 1.6;">' .
        '<li>Verify that <strong>MySQL</strong> is running in your XAMPP Control Panel.</li>' .
        '<li>Open <a href="http://localhost/phpmyadmin" target="_blank" style="color: #2563eb; font-weight: bold;">phpMyAdmin</a> and ensure <code>electronics_store</code> is imported.</li>' .
        '</ol>' .
        '<p style="font-size: 13px; color: #64748b; margin-bottom: 0;">Error detail: ' . htmlspecialchars($e->getMessage()) . '</p>' .
        '</div>'
    );
}
