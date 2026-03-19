<?php
require_once 'c:/xampp/htdocs/Ethioserve-main/includes/db.php';
try {
    echo "Brokers table:\n";
    $stmt = $pdo->query('DESCRIBE brokers');
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
    echo "\nReferrals table:\n";
    $stmt = $pdo->query('DESCRIBE referrals');
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
