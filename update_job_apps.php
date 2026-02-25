<?php
require_once 'includes/db.php';

$queries = [
    "ALTER TABLE job_applications ADD COLUMN IF NOT EXISTS applicant_name VARCHAR(150)",
    "ALTER TABLE job_applications ADD COLUMN IF NOT EXISTS applicant_email VARCHAR(150)",
    "ALTER TABLE job_applications ADD COLUMN IF NOT EXISTS applicant_phone VARCHAR(50)",
    "ALTER TABLE job_applications ADD COLUMN IF NOT EXISTS applicant_photo VARCHAR(255)",
    "ALTER TABLE job_applications ADD COLUMN IF NOT EXISTS portfolio_url VARCHAR(255)",
    "ALTER TABLE job_applications ADD COLUMN IF NOT EXISTS university VARCHAR(255)",
    "ALTER TABLE job_applications ADD COLUMN IF NOT EXISTS gpa DECIMAL(4,2)",
    "ALTER TABLE job_applications ADD COLUMN IF NOT EXISTS recommendation_url VARCHAR(255)",
    "ALTER TABLE job_applications ADD COLUMN IF NOT EXISTS certificates_url VARCHAR(255)"
];

echo "<h2>🛠️ Updating job_applications table...</h2><hr>";

foreach ($queries as $i => $sql) {
    try {
        $pdo->exec($sql);
        echo "✅ Step " . ($i + 1) . " executed successfully<br>";
    } catch (Exception $e) {
        // fail silently if column exists or other issues, as we want to continue
        echo "ℹ️ Step " . ($i + 1) . " skipped/notice: " . $e->getMessage() . "<br>";
    }
}

echo "<hr><h3 style='color:green'>Done!</h3>";
?>