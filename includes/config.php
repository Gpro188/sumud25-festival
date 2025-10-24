<?php
// Database configuration for SUMUD'25 Arts Festival
// Updated for InfinityFree hosting
define('DB_HOST', 'sql107.infinityfree.com');
define('DB_NAME', 'if0_40237012_sumud');
define('DB_USER', 'if0_40237012');
define('DB_PASS', '');

// Create PDO connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // Log error and show user-friendly message
    error_log("Database connection failed: " . $e->getMessage());
    die("Sorry, we're experiencing technical difficulties. Please try again later.");
}
?>