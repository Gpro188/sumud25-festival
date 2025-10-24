<?php
// Test file to verify setup
echo "<h2>SUMUD'25 Festival Setup Test</h2>";

// Test 1: Check if config.php exists and is readable
echo "<h3>1. Configuration File Check</h3>";
if (file_exists('includes/config.php')) {
    echo "<p style='color: green;'>✓ config.php found</p>";
    
    // Try to include config and test database connection
    try {
        include 'includes/config.php';
        echo "<p style='color: green;'>✓ config.php loaded successfully</p>";
        
        // Test database connection
        if (isset($pdo)) {
            echo "<p style='color: green;'>✓ Database connection established</p>";
            
            // Test a simple query
            try {
                $stmt = $pdo->query("SHOW TABLES");
                $tables = $stmt->fetchAll();
                echo "<p style='color: green;'>✓ Database query successful</p>";
                echo "<p>Found " . count($tables) . " tables in database</p>";
            } catch (Exception $e) {
                echo "<p style='color: red;'>✗ Database query failed: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p style='color: red;'>✗ Database connection not established</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Failed to load config.php: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>✗ config.php not found</p>";
}

// Test 2: Check required directories
echo "<h3>2. Directory Structure Check</h3>";
$required_dirs = ['admin', 'assets', 'assets/gallery', 'css', 'includes', 'js'];
foreach ($required_dirs as $dir) {
    if (is_dir($dir)) {
        echo "<p style='color: green;'>✓ $dir directory exists</p>";
    } else {
        echo "<p style='color: red;'>✗ $dir directory missing</p>";
    }
}

// Test 3: Check required files
echo "<h3>3. Required Files Check</h3>";
$required_files = [
    'includes/config.php',
    'includes/database.sql',
    'index.php',
    'results.php',
    'admin/login.php',
    'admin/dashboard.php'
];

foreach ($required_files as $file) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✓ $file exists</p>";
    } else {
        echo "<p style='color: red;'>✗ $file missing</p>";
    }
}

echo "<h3>Setup Verification Complete</h3>";
echo "<p>If all checks are green, your files are ready for InfinityFree hosting.</p>";
?>