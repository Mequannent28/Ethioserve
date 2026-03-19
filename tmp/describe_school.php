require_once __DIR__ . '/../includes/db.php';

$tables = ['sms_teachers', 'sms_student_profiles', 'sms_parents', 'sms_classes', 'sms_subjects'];
foreach ($tables as $t) {
    echo "\nTable: $t\n";
    try {
        $stmt = $pdo->query("DESCRIBE $t");
        while ($r = $stmt->fetch()) {
            echo $r['Field'] . ' (' . $r['Type'] . ') ' . ($r['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . "\n";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
