<?php
/**
 * Migration Script v2.0
 * Purpose: Update production database on Render to support Tax Features.
 */
require_once 'includes/functions.php';
require_once 'includes/db.php';

echo "<h2>EthioServe Database Migration Center</h2>";
echo "<div style='font-family:monospace; background:#f4f4f4; padding:20px; border-radius:10px;'>";

try {
    echo "Checking 'menu_items' table structure...<br>";

    // Check if column exists
    $check = $pdo->query("SHOW COLUMNS FROM menu_items LIKE 'tax_rate'");
    if ($check->rowCount() == 0) {
        echo "→ Adding 'tax_rate' column... ";
        $pdo->exec("ALTER TABLE menu_items ADD COLUMN tax_rate DECIMAL(5,2) DEFAULT 15.00 AFTER price");
        echo "<span style='color:green'>SUCCESS</span><br>";
    } else {
        echo "→ Column 'tax_rate' already exists.<br>";
    }

    echo "Updating existing items to baseline 15% tax... ";
    $count = $pdo->exec("UPDATE menu_items SET tax_rate = 15.00 WHERE tax_rate IS NULL OR tax_rate = 0.00");
    echo "<span style='color:green'>Updated $count items.</span><br>";

    echo "<br><h3 style='color:green'>Migration Complete!</h3>";
    echo "<p>Your Render instance is now updated with the Tax Feature.</p>";

} catch (Exception $e) {
    echo "<br><h3 style='color:red'>Migration Failed!</h3>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div>";
echo "<br><a href='index.php'>Return to Dashboard</a>";
?>