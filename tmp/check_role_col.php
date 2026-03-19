<?php
require_once 'c:/xampp/htdocs/Ethioserve-main/includes/db.php';
$stmt = $pdo->query("DESCRIBE users");
while($r = $stmt->fetch()) {
    if($r['Field'] == 'role') {
        print_r($r);
    }
}
