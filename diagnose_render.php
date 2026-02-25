<?php
require_once 'includes/functions.php';
require_once 'includes/db.php';

echo "<h2>EthioServe Render Diagnosis & Repair</h2>";

if (isset($_GET['action']) && $_GET['action'] === 'import') {
    echo "<h3>Attempting Forced Import...</h3>";
    try {
        $sql = file_get_contents('database.sql');
        if ($sql) {
            $pdo->exec($sql);
            echo "<p style='color:green'>SUCCESS: database.sql imported via PHP.</p>";
        } else {
            echo "<p style='color:red'>ERROR: database.sql not found or empty!</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red'>Import Failed: " . $e->getMessage() . "</p>";
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'reset_pass') {
    echo "<h3>Resetting All User Passwords to 'password'...</h3>";
    try {
        $hash = password_hash('password', PASSWORD_DEFAULT);
        $count = $pdo->exec("UPDATE users SET password = '$hash'");
        echo "<p style='color:green'>SUCCESS: Reset $count users.</p>";
    } catch (Exception $e) {
        echo "<p style='color:red'>Reset Failed: " . $e->getMessage() . "</p>";
    }
}

try {
    // 1. Check Tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<h3>Tables Found: " . count($tables) . "</h3>";
    echo "<ul>";
    foreach ($tables as $t)
        echo "<li>$t</li>";
    echo "</ul>";

    // 2. Check Users
    if (in_array('users', $tables)) {
        $user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        echo "<p>Total Users in DB: <strong>$user_count</strong></p>";

        $stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE username = 'dawit_dating'");
        $stmt->execute();
        $user = $stmt->fetch();

        if ($user) {
            echo "<p style='color:green'>User 'dawit_dating' EXISTS. ID: {$user['id']}, Role: {$user['role']}</p>";
        } else {
            echo "<p style='color:red'>User 'dawit_dating' NOT FOUND in database!</p>";

            // Show some users
            echo "<h4>Some Users in DB:</h4>";
            $users = $pdo->query("SELECT username, role FROM users LIMIT 10")->fetchAll();
            foreach ($users as $u)
                echo "{$u['username']} ({$u['role']})<br>";
        }
    } else {
        echo "<p style='color:red'>ERROR: 'users' table is MISSING!</p>";
    }

} catch (Exception $e) {
    echo "<p style='color:red'>Diagnosis Failed: " . $e->getMessage() . "</p>";
}
?>

<hr>
<div style="background:#f9f9f9; padding:20px; border-radius:10px; border:1px solid #ddd;">
    <h4>Repair Actions:</h4>
    <p>If you see 0 users or missing tables, run this:</p>
    <a href="?action=import" style="padding:10px 20px; background:#007bff; color:white; text-decoration:none; border-radius:5px; font-weight:bold;">Forced Database Import</a>
    
    <p style="margin-top:20px;">If login fails but user exists, run this:</p>
    <a href="?action=reset_pass" style="padding:10px 20px; background:#ffc107; color:black; text-decoration:none; border-radius:5px; font-weight:bold;">Reset All Passwords to "password"</a>
</div>

<br>
<a href="login.php" style="display:inline-block; margin-top:20px; color:#007bff;">← Back to Login Page</a>