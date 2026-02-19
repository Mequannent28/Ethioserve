<?php
require_once 'includes/db.php';

echo "<h2>Database Status Check</h2>";

try {
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<h3>Found " . count($tables) . " tables:</h3>";
    echo "<ul>";
    foreach ($tables as $t) {
        $count = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        echo "<li><b>$t</b>: $count rows</li>";
    }
    echo "</ul>";

    if (!in_array('hotels', $tables)) {
        echo "<h3 style='color: red;'>CRITICAL: 'hotels' table is MISSING!</h3>";
        echo "<p><a href='repair_db.php'>Click here to fix it</a></p>";
    } else {
        echo "<h3 style='color: green;'>SUCCESS: 'hotels' table is present.</h3>";
    }

} catch (Exception $e) {
    echo "<h3 style='color: red;'>Connection Error: " . $e->getMessage() . "</h3>";
}
