<?php
require_once 'includes/db.php';

// Check if database is initialized
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() == 0) {
        // Auto-import if database.sql exists
        $sql_file = __DIR__ . '/database.sql';
        if (file_exists($sql_file)) {
            $sql = file_get_contents($sql_file);
            if (!empty($sql)) {
                $pdo->exec($sql);
            }
        }
    }
} catch (Exception $e) {
    // Silent fail for health check to allow container to start
}

http_response_code(200);
echo "OK - EthioServe is running.";
?>