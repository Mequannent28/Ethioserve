<?php
require_once 'includes/db.php';

echo "<h2>🔧 Finalizing Demo Data Fixes...</h2><hr>";

try {
    // 1. Fix health_providers table structure
    echo "Checking health_providers columns... ";
    $res = $pdo->query("DESC health_providers")->fetchAll();
    $has_user_id = false;
    foreach ($res as $row) {
        if ($row['Field'] === 'user_id') {
            $has_user_id = true;
            break;
        }
    }

    if (!$has_user_id) {
        $pdo->exec("ALTER TABLE health_providers ADD COLUMN user_id INT AFTER id, ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL");
        echo "✅ Added user_id column.<br>";
    } else {
        echo "ℹ️ user_id column already exists.<br>";
    }

    // 2. Link dr_dawit user to a health_provider entry
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'dr_dawit'");
    $stmt->execute();
    $dr_user_id = $stmt->fetchColumn();

    if ($dr_user_id) {
        // Check if a provider exists for this user
        $stmt = $pdo->prepare("SELECT id FROM health_providers WHERE user_id = ?");
        $stmt->execute([$dr_user_id]);
        $provider_id = $stmt->fetchColumn();

        if (!$provider_id) {
            // Check if "Dr. Dawit Telemed" entry exists without user_id
            $stmt = $pdo->prepare("SELECT id FROM health_providers WHERE name LIKE '%Dawit%' AND user_id IS NULL");
            $stmt->execute();
            $existing_id = $stmt->fetchColumn();

            if ($existing_id) {
                $pdo->prepare("UPDATE health_providers SET user_id = ? WHERE id = ?")->execute([$dr_user_id, $existing_id]);
                echo "✅ Linked existing provider 'ID $existing_id' to user 'dr_dawit'.<br>";
            } else {
                // Create new provider entry
                $pdo->prepare("INSERT INTO health_providers (user_id, name, type, bio, location, phone, rating, is_available) VALUES (?, ?, 'doctor', ?, ?, ?, 4.8, 1)")
                    ->execute([
                        $dr_user_id,
                        'Dr. Dawit Telemed',
                        'Expert in telemedicine and general health consultations.',
                        'Bole, Addis Ababa',
                        '0931313131'
                    ]);
                echo "✅ Created new health_provider entry for 'dr_dawit'.<br>";
            }
        } else {
            echo "ℹ️ user 'dr_dawit' already has a provider entry (ID $provider_id).<br>";
        }
    } else {
        echo "❌ User 'dr_dawit' NOT found. Please run setup_demo_accounts.php first.<br>";
    }

    // 3. Ensure cloud_company has a company entry
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'cloud_company'");
    $stmt->execute();
    $cc_user_id = $stmt->fetchColumn();

    if ($cc_user_id) {
        $stmt = $pdo->prepare("SELECT id FROM job_companies WHERE user_id = ?");
        $stmt->execute([$cc_user_id]);
        if (!$stmt->fetch()) {
            $pdo->prepare("INSERT INTO job_companies (user_id, company_name, location, industry, verified) VALUES (?, ?, 'Addis Ababa', 'Technology', 1)")
                ->execute([$cc_user_id, 'Red Cloud ICT Solution']);
            echo "✅ Created job_company for 'cloud_company'.<br>";
        } else {
            echo "ℹ️ Company for 'cloud_company' already exists.<br>";
        }
    }

    echo "<hr><h3 style='color:green;'>🎉 Database data fixed!</h3>";
    echo "<a href='login.php'>Go to Login</a>";

} catch (Exception $e) {
    echo "<h3 style='color:red;'>❌ Error: " . $e->getMessage() . "</h3>";
}
