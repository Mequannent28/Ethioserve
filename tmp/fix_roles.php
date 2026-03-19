<?php
require_once __DIR__ . '/../includes/db.php';

try {
    echo "Updating users table role enum...\n";
    $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('admin','hotel','broker','transport','customer','restaurant','taxi','student','doctor','employer','dating','home_pro','teacher','school_admin','parent') DEFAULT 'customer'");
    
    echo "Updating school_admin1 role...\n";
    $pdo->prepare("UPDATE users SET role = 'school_admin' WHERE username = 'school_admin1'")->execute();
    
    echo "Updating teacher1_school role...\n";
    $pdo->prepare("UPDATE users SET role = 'teacher' WHERE username = 'teacher1_school'")->execute();

    echo "Updating teacher2_school role...\n";
    $pdo->prepare("UPDATE users SET role = 'teacher' WHERE username = 'teacher2_school'")->execute();

    echo "Updating parent1_school role...\n";
    $pdo->prepare("UPDATE users SET role = 'parent' WHERE username = 'parent1_school'")->execute();

    echo "Updating parent2_school role...\n";
    $pdo->prepare("UPDATE users SET role = 'parent' WHERE username = 'parent2_school'")->execute();

    echo "Finished fixing roles.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
