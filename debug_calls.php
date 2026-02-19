<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

echo "<h2>Video Call Debug</h2>";
echo "Current User ID: " . (isLoggedIn() ? getCurrentUserId() : "Not Logged In") . "<br>";
echo "Current Time: " . date('Y-m-d H:i:s') . "<br><br>";

try {
    $stmt = $pdo->query("SELECT * FROM video_calls ORDER BY id DESC LIMIT 5");
    $calls = $stmt->fetchAll();

    echo "<table border='1'>
    <tr>
        <th>ID</th>
        <th>Caller</th>
        <th>Receiver</th>
        <th>Status</th>
        <th>Created At</th>
    </tr>";
    foreach ($calls as $c) {
        echo "<tr>
            <td>{$c['id']}</td>
            <td>{$c['caller_id']}</td>
            <td>{$c['receiver_id']}</td>
            <td>{$c['status']}</td>
            <td>{$c['created_at']}</td>
        </tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>