<?php
/**
 * Force-create/reset School demo accounts on Render.
 * Visit: https://ethioserve-j88x.onrender.com/school/seed_users.php
 */
require_once '../includes/db.php';

$hash = password_hash('password', PASSWORD_DEFAULT);

echo "<style>body{font-family:monospace;background:#111;color:#0f0;padding:30px;font-size:14px;} .err{color:#f66;} .ok{color:#0f0;} h2{color:#ff0;border-bottom:1px solid #333;padding-bottom:10px;}</style>";
echo "<h2>🏫 EthioServe — School User Setup</h2>";

// First ensure all SMS tables exist
require_once '../includes/sms_migration.php';
migrateSMS($pdo);
echo "<span class='ok'>✅ SMS Tables verified</span><br><br>";

// Ensure classes exist
$classCount = $pdo->query("SELECT COUNT(*) FROM sms_classes")->fetchColumn();
if ($classCount == 0) {
    $pdo->exec("INSERT INTO sms_classes (class_name,section,capacity,room_number) VALUES ('Grade 10','A',40,'Room 101'),('Grade 11','B',35,'Room 205'),('Grade 12','A',30,'Room 301')");
    echo "<span class='ok'>✅ Classes seeded (3)</span><br>";
}

// Get or create class ID
$classId = $pdo->query("SELECT id FROM sms_classes LIMIT 1")->fetchColumn() ?: 1;

function upsertUser($pdo, $username, $email, $fullName, $role, $hash) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username=?");
    $stmt->execute([$username]);
    $existing = $stmt->fetchColumn();
    if ($existing) {
        // Force update password and role
        $pdo->prepare("UPDATE users SET password=?, role=?, full_name=? WHERE id=?")->execute([$hash, $role, $fullName, $existing]);
        echo "<span class='ok'>🔄 $username — reset (ID: $existing)</span><br>";
        return $existing;
    } else {
        $pdo->prepare("INSERT INTO users (username,email,password,full_name,role,created_at) VALUES (?,?,?,?,?,NOW())")->execute([$username, $email, $hash, $fullName, $role]);
        $id = $pdo->lastInsertId();
        echo "<span class='ok'>✅ $username — created (ID: $id)</span><br>";
        return $id;
    }
}

// teacher1
$teacherId = upsertUser($pdo, 'teacher1', 'teacher1@ethioserve.com', 'Abebe Bikila', 'teacher', $hash);
try {
    $pdo->prepare("INSERT IGNORE INTO sms_teachers (user_id,employee_id,specialization) VALUES (?,'TCH001','Mathematics')")->execute([$teacherId]);
} catch(Exception $e) {}

// student1
$studentId = upsertUser($pdo, 'student1', 'student1@ethioserve.com', 'Dawit Kebede', 'student', $hash);
try {
    $pdo->prepare("INSERT INTO sms_student_profiles (user_id,class_id,student_id_number,gender) VALUES (?,?,'STU-001','Male') ON DUPLICATE KEY UPDATE class_id=?")->execute([$studentId, $classId, $classId]);
} catch(Exception $e) {
    echo "<span class='err'>⚠ Student profile: " . $e->getMessage() . "</span><br>";
}

// parent1
$parentId = upsertUser($pdo, 'parent1', 'parent1@ethioserve.com', 'Kebede Michael', 'parent', $hash);
try {
    $pdo->prepare("INSERT IGNORE INTO sms_parents (user_id,occupation) VALUES (?,'Self-Employed')")->execute([$parentId]);
} catch(Exception $e) {}

// school_admin1
upsertUser($pdo, 'school_admin1', 'school_admin1@ethioserve.com', 'School Administrator', 'school_admin', $hash);

// admin (super admin)
try {
    $adminId = upsertUser($pdo, 'admin', 'admin@ethioserve.com', 'Super Admin', 'admin', $hash);
} catch(Exception $e) {
    echo "<span class='err'>⚠ admin: " . $e->getMessage() . "</span><br>";
}

echo "<br><hr style='border-color:#333'>";
echo "<h2>🎉 Done! All accounts ready.</h2>";
echo "<table style='border-collapse:collapse;width:100%;'>";
echo "<tr style='color:#ff0'><th style='text-align:left;padding:6px 16px;'>Username</th><th style='text-align:left;padding:6px 16px;'>Password</th><th style='text-align:left;padding:6px 16px;'>Role</th></tr>";
$rows = [
    ['teacher1', 'password', 'Teacher'],
    ['student1', 'password', 'Student'],
    ['parent1', 'password', 'Parent'],
    ['school_admin1', 'password', 'School Admin'],
    ['admin', 'password', 'Super Admin'],
];
foreach ($rows as $r) {
    echo "<tr><td style='padding:4px 16px;'>{$r[0]}</td><td style='padding:4px 16px;color:#0af;'>{$r[1]}</td><td style='padding:4px 16px;color:#fa0;'>{$r[2]}</td></tr>";
}
echo "</table>";
echo "<br><a href='../school/login.php' style='display:inline-block;margin-top:20px;padding:12px 30px;background:#1B5E20;color:white;text-decoration:none;border-radius:8px;font-weight:bold;font-size:16px;'>→ Go to School Login</a>";
