<?php
require_once 'includes/db.php';

// Check what role teacher1 actually has
$stmt = $pdo->query("SELECT id, username, email, role, password FROM users WHERE username IN ('teacher1','student1','parent1','admin') ORDER BY username");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>User Roles in Database</h2><table border='1' cellpadding='8'>";
echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Password Hash OK?</th></tr>";
foreach ($users as $u) {
    $pwOk = password_verify('password', $u['password']) ? '✅ YES' : '❌ NO';
    echo "<tr>
        <td>{$u['id']}</td>
        <td><b>{$u['username']}</b></td>
        <td>{$u['email']}</td>
        <td style='color:red; font-weight:bold'>{$u['role']}</td>
        <td>{$pwOk}</td>
    </tr>";
}
echo "</table>";

echo "<h3>Fix: Update roles to correct values</h3>";

// Auto-fix if teacher1 has wrong role
$pdo->exec("UPDATE users SET role = 'teacher' WHERE username = 'teacher1' AND role != 'teacher'");
$pdo->exec("UPDATE users SET role = 'student' WHERE username = 'student1' AND role != 'student'");
$pdo->exec("UPDATE users SET role = 'parent'  WHERE username = 'parent1'  AND role != 'parent'");

// Re-check
$stmt2 = $pdo->query("SELECT username, role FROM users WHERE username IN ('teacher1','student1','parent1','admin')");
echo "<h3>After Fix:</h3><table border='1' cellpadding='8'><tr><th>Username</th><th>Role</th></tr>";
foreach ($stmt2->fetchAll() as $r) {
    echo "<tr><td>{$r['username']}</td><td style='color:green; font-weight:bold'>{$r['role']}</td></tr>";
}
echo "</table>";

echo "<br><a href='school/login.php' style='padding:10px 20px; background:#1B5E20; color:#fff; border-radius:8px; text-decoration:none;'>→ Go to School Login</a>";
?>
