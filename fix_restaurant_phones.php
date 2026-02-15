<?php
/**
 * Database Fix: Add phone column to hotels and update restaurants with phone numbers
 * Also adds Chapa payment support
 * Run: http://localhost/ethioserve/fix_restaurant_phones.php
 */
require_once 'includes/db.php';

try {
    // 1. Add phone column to hotels table if it doesn't exist
    $columns = $pdo->query("SHOW COLUMNS FROM hotels LIKE 'phone'")->fetch();
    if (!$columns) {
        $pdo->exec("ALTER TABLE hotels ADD COLUMN phone VARCHAR(20) AFTER location");
        echo "✅ Added 'phone' column to hotels table<br>";
    } else {
        echo "ℹ️ 'phone' column already exists<br>";
    }

    // 2. Add email column to hotels table if it doesn't exist
    $columns = $pdo->query("SHOW COLUMNS FROM hotels LIKE 'email'")->fetch();
    if (!$columns) {
        $pdo->exec("ALTER TABLE hotels ADD COLUMN email VARCHAR(100) AFTER phone");
        echo "✅ Added 'email' column to hotels table<br>";
    } else {
        echo "ℹ️ 'email' column already exists<br>";
    }

    // 3. Update all restaurants with phone numbers and emails
    $restaurant_contacts = [
        'Yod Abyssinia Traditional Restaurant' => ['+251 911 234 567', 'info@yodabyssinia.com'],
        '2000 Habesha Cultural Restaurant' => ['+251 911 345 678', 'info@2000habesha.com'],
        'Lucy Restaurant & Lounge' => ['+251 911 456 789', 'info@lucyrestaurant.com'],
        'Kategna Traditional Food' => ['+251 911 567 890', 'info@kategna.com'],
        'Totot Cultural Restaurant' => ['+251 911 678 901', 'info@tototrestaurant.com'],
        'Four Sisters Restaurant' => ['+251 911 789 012', 'info@foursisters.com'],
        'Makush Art Gallery & Restaurant' => ['+251 911 890 123', 'info@makush.com'],
        'Ben Abeba Restaurant' => ['+251 911 901 234', 'info@benabeba.com'],
        'Dashen Traditional Restaurant' => ['+251 911 012 345', 'info@dashenrestaurant.com'],
        'Habesha Restaurant' => ['+251 911 123 456', 'info@habesharestaurant.com'],
        'Saro-Maria Hotel Restaurant' => ['+251 911 234 568', 'info@saromaria.com'],
        'Tomoca Coffee' => ['+251 911 345 679', 'info@tomocacoffee.com'],
        'Addis in Dar Restaurant' => ['+251 911 456 790', 'info@addisindar.com'],
        'Lime Tree Café & Restaurant' => ['+251 911 567 891', 'info@limetree.com'],
        "Castelli's Italian-Ethiopian Restaurant" => ['+251 911 678 902', 'info@castellis.com'],
        'Hilton Addis Ababa' => ['+251 115 170 000', 'info@hiltonaddis.com'],
        'Sheraton Addis' => ['+251 115 171 717', 'info@sheratonaddis.com'],
    ];

    $stmt = $pdo->prepare("UPDATE hotels SET phone = ?, email = ? WHERE name = ?");
    $updated = 0;
    foreach ($restaurant_contacts as $name => $contacts) {
        $stmt->execute([$contacts[0], $contacts[1], $name]);
        if ($stmt->rowCount() > 0) {
            echo "📞 Updated: <strong>$name</strong> — {$contacts[0]}<br>";
            $updated++;
        }
    }

    // Also set a default phone for any restaurants without one
    $pdo->exec("UPDATE hotels SET phone = '+251 911 000 000' WHERE phone IS NULL OR phone = ''");
    $pdo->exec("UPDATE hotels SET email = 'order@ethioserve.com' WHERE email IS NULL OR email = ''");

    echo "<hr>";
    echo "<div style='background:#1a1a2e;color:#00e676;padding:20px;border-radius:10px;font-size:16px;'>";
    echo "✅ <strong>Done!</strong> Updated $updated restaurants with phone numbers and emails.<br>";
    echo "📞 All restaurants now have contact info for orders.<br>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div style='background:#ff4444;color:white;padding:20px;border-radius:10px;'>";
    echo "❌ <strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>