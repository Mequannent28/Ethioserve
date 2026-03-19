<?php
require_once 'includes/db.php';
$tables = ['sms_attendance'];
foreach ($tables as $table) {
    echo "--- Table: $table ---\n";
    $stmt = $pdo->query("DESCRIBE $table");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    echo "\n";
}
?>
