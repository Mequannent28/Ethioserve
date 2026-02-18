<?php
require_once 'includes/db.php';

$is_cli = php_sapi_name() === 'cli';
$br = $is_cli ? "\n" : "<br>";

if (!$is_cli)
    echo "<h2>Database Force Initializer</h2>";

try {
    // Check if tables already exist
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (count($tables) > 10) {
        if ($is_cli) {
            echo "  → Database already contains " . count($tables) . " tables. Skipping initialization.$br";
            return;
        } else {
            echo "Database already initialized with " . count($tables) . " tables.$br";
        }
    }

    $sqlFile = __DIR__ . '/database.sql';
    if (!file_exists($sqlFile)) {
        die("Error: database.sql not found.");
    }

    if (!$is_cli)
        echo "Reading database.sql...$br";
    $sql = file_get_contents($sqlFile);

    if (!$is_cli)
        echo "Executing queries... this may take a moment.$br";

    // Disable foreign key checks for import
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // Execute the entire file contents
    $pdo->exec($sql);

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    // Fetch confirmed tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!$is_cli) {
        echo "<h3 style='color: green;'>Success! Database has been re-initialized.</h3>";
        echo "<b>Tables present:</b><br>";
        echo "<ul><li>" . implode("</li><li>", $tables) . "</li></ul>";
        echo "<a href='customer/index.php'>Go to Website</a>";
    } else {
        echo "  → Success! Database initialized with " . count($tables) . " tables.$br";
    }

} catch (Exception $e) {
    if (!$is_cli) {
        echo "<h3 style='color: red;'>Init Failed: " . $e->getMessage() . "</h3>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    } else {
        echo "  → Init Failed: " . $e->getMessage() . $br;
    }
}
