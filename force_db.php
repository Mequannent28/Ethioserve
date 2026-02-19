<?php
require_once 'includes/db.php';

$is_cli = php_sapi_name() === 'cli';
$br = $is_cli ? "\n" : "<br>";

if (!$is_cli)
    echo "<h2 style='font-family: sans-serif; color: #1a5c37;'>EthioServe Database Initializer</h2>";

try {
    // 1. Check if database is already populated
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // If we have more than a handful of tables, assume it's already done
    if (count($tables) > 15 && !isset($_GET['force'])) {
        $msg = "  ✓ Database already contains " . count($tables) . " tables. Skipping initialization.$br";
        echo $is_cli ? $msg : "<div style='color: green; font-weight: bold;'>$msg</div>";
        return;
    }

    $sqlFile = __DIR__ . '/database.sql';
    if (!file_exists($sqlFile)) {
        $error = "  ✗ FATAL ERROR: database.sql not found at $sqlFile$br";
        echo $is_cli ? $error : "<div style='color: red;'>$error</div>";
        exit(1);
    }

    $msg = "  → Reading database.sql (" . round(filesize($sqlFile) / 1024, 2) . " KB)...$br";
    echo $is_cli ? $msg : "<div>$msg</div>";

    // Disable foreign key checks for clean import
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // Read file line by line to handle large files and multi-statements
    $handle = fopen($sqlFile, "r");
    $query = "";
    $count = 0;
    $success_count = 0;

    if ($handle) {
        while (($line = fgets($handle)) !== false) {
            $trimmedLine = trim($line);
            // Skip comments and empty lines
            if ($trimmedLine == "" || strpos($trimmedLine, "--") === 0 || strpos($trimmedLine, "/*") === 0) {
                continue;
            }

            $query .= $line;
            // End of query detection: search for semicolon NOT inside quotes (minimal detection)
            // Simpler: just check if the trimmed line ends with a semicolon
            if (substr($trimmedLine, -1) == ";") {
                try {
                    $pdo->exec($query);
                    $success_count++;
                } catch (Exception $e) {
                    // Log error but continue
                }
                $query = "";
                $count++;
            }
        }
        fclose($handle);
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    // Verify results
    $stmt = $pdo->query("SHOW TABLES");
    $final_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!$is_cli) {
        echo "<h3 style='color: green;'>Success! Database has been re-initialized.</h3>";
        echo "<p>Executed $count batches. Current tables: <b>" . count($final_tables) . "</b></p>";
        echo "<ul><li>" . implode("</li><li>", $final_tables) . "</li></ul>";
        echo "<a href='customer/index.php' style='display: inline-block; background: #1a5c37; color: white; padding: 10px 20px; border-radius: 20px; text-decoration: none; margin-top: 20px;'>Go to Community Hub</a>";
    } else {
        echo "  ✓ Success! Database initialized with " . count($final_tables) . " tables ($success_count/$count queries successful).$br";
    }

} catch (Exception $e) {
    $error_msg = "  ✗ FATAL INIT ERROR: " . $e->getMessage() . $br;
    if (!$is_cli) {
        echo "<div style='color: red; padding: 20px; border: 2px solid red; border-radius: 10px; background: #fff5f5;'>";
        echo "<h3>$error_msg</h3>";
        echo "<pre style='font-size: 0.7rem;'>" . $e->getTraceAsString() . "</pre>";
        echo "</div>";
    } else {
        echo $error_msg;
        exit(1);
    }
}
