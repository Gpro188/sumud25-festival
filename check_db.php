<?php
// Check database structure
include '../includes/config.php';

try {
    // Check if team_leaders table exists and its structure
    $stmt = $pdo->query("DESCRIBE team_leaders");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>team_leaders table structure:</h2>";
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . ($column['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . $column['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if is_active column exists
    $hasIsActive = false;
    foreach ($columns as $column) {
        if ($column['Field'] == 'is_active') {
            $hasIsActive = true;
            break;
        }
    }
    
    if ($hasIsActive) {
        echo "<p style='color: green; font-weight: bold;'>✓ is_active column exists</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>✗ is_active column is missing</p>";
        echo "<p>You need to add the is_active column to the team_leaders table.</p>";
        echo "<p>Run this SQL command:</p>";
        echo "<pre>ALTER TABLE team_leaders ADD COLUMN is_active BOOLEAN DEFAULT TRUE;</pre>";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>