<?php
require_once 'c:/xampp/htdocs/Ethioserve-main/includes/db.php';

function createUser($pdo, $username, $email, $fullName, $role) {
    $password = password_hash('password', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, role, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$username, $email, $password, $fullName, $role]);
    return $pdo->lastInsertId();
}

try {
    $pdo->beginTransaction();

    // 1. Create Classes if none exist
    $stmt = $pdo->query("SELECT COUNT(*) FROM sms_classes");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO sms_classes (class_name, section, capacity, room_number, created_at) VALUES ('Grade 10', 'A', 40, 'Room 101', NOW())");
        $classId1 = $pdo->lastInsertId();
        $pdo->exec("INSERT INTO sms_classes (class_name, section, capacity, room_number, created_at) VALUES ('Grade 11', 'B', 35, 'Room 205', NOW())");
        $classId2 = $pdo->lastInsertId();
    } else {
        $classId1 = $pdo->query("SELECT id FROM sms_classes LIMIT 1")->fetchColumn();
        $classId2 = $pdo->query("SELECT id FROM sms_classes LIMIT 1 OFFSET 1")->fetchColumn() ?: $classId1;
    }

    // 2. Create School Admin
    $adminId = createUser($pdo, 'school_admin1', 'school_admin1@ethioserve.com', 'School Administrator', 'school_admin');
    echo "Created School Admin ID: $adminId\n";

    // 3. Create Teachers
    $t1Id = createUser($pdo, 'teacher1_school', 'teacher1@ethioserve.com', 'Abebe Bikila', 'teacher');
    $pdo->prepare("INSERT INTO sms_teachers (user_id, employee_id, specialization) VALUES (?, 'TCH001', 'Mathematics')")->execute([$t1Id]);
    
    $t2Id = createUser($pdo, 'teacher2_school', 'teacher2@ethioserve.com', 'Muluwork Tesfaye', 'teacher');
    $pdo->prepare("INSERT INTO sms_teachers (user_id, employee_id, specialization) VALUES (?, 'TCH002', 'Biology')")->execute([$t2Id]);
    echo "Created Teachers.\n";

    // 4. Create Parents
    $p1Id = createUser($pdo, 'parent1_school', 'parent1@ethioserve.com', 'Kebede Michael', 'parent');
    $pdo->prepare("INSERT INTO sms_parents (user_id, occupation) VALUES (?, 'Self-Employed')")->execute([$p1Id]);
    $parentTableId1 = $pdo->lastInsertId();

    $p2Id = createUser($pdo, 'parent2_school', 'parent2@ethioserve.com', 'Almaz Ayana', 'parent');
    $pdo->prepare("INSERT INTO sms_parents (user_id, occupation) VALUES (?, 'Nurse')")->execute([$p2Id]);
    $parentTableId2 = $pdo->lastInsertId();
    echo "Created Parents.\n";

    // 5. Create Students
    $s1Id = createUser($pdo, 'student1_school', 'student1_school@ethioserve.com', 'Dawit Kebede', 'student');
    $pdo->prepare("INSERT INTO sms_student_profiles (user_id, parent_id, class_id, student_id_number, gender) VALUES (?, ?, ?, 'STU001', 'Male')")->execute([$s1Id, $parentTableId1, $classId1]);

    $s2Id = createUser($pdo, 'student2_school', 'student2_school@ethioserve.com', 'Selam Almaz', 'student');
    $pdo->prepare("INSERT INTO sms_student_profiles (user_id, parent_id, class_id, student_id_number, gender) VALUES (?, ?, ?, 'STU002', 'Female')")->execute([$s2Id, $parentTableId2, $classId2]);
    echo "Created Students.\n";

    $pdo->commit();
    echo "\nAll users created successfully!\n";
    echo "Passwords for all: password\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}
