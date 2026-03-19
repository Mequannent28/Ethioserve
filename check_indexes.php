<?php
require_once 'includes/db.php';
$table = 'sms_attendance';
echo "--- Indexes for $table ---\n";
$stmt = $pdo->query("SHOW INDEX FROM $table");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}
?>
