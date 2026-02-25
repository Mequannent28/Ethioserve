<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

echo "<h2>Seeding Employer Demo Account...</h2>";

try {
    // 1. Create or Update User
    $username = 'cloud_company';
    $password = password_hash('password', PASSWORD_DEFAULT);
    $email = 'hr@cloud-ict.com';
    $full_name = 'Red Cloud ICT Solutions';
    $role = 'employer';

    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user) {
        $user_id = $user['id'];
        $stmt = $pdo->prepare("UPDATE users SET password = ?, role = ?, email = ?, full_name = ? WHERE id = ?");
        $stmt->execute([$password, $role, $email, $full_name, $user_id]);
        echo "User '{$username}' updated.<br>";
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, full_name, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$username, $password, $email, $full_name, $role]);
        $user_id = $pdo->lastInsertId();
        echo "User '{$username}' created.<br>";
    }

    // 2. Create or Update Company
    $stmt = $pdo->prepare("SELECT id FROM job_companies WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $company = $stmt->fetch();

    if ($company) {
        $company_id = $company['id'];
        $stmt = $pdo->prepare("UPDATE job_companies SET company_name = ?, industry = ?, location = ?, verified = 1 WHERE id = ?");
        $stmt->execute([$full_name, 'Information Technology', 'Addis Ababa, Ethiopia', $company_id]);
        echo "Company details updated.<br>";
    } else {
        $stmt = $pdo->prepare("INSERT INTO job_companies (user_id, company_name, industry, location, verified) VALUES (?, ?, ?, ?, 1)");
        $stmt->execute([$user_id, $full_name, 'Information Technology', 'Addis Ababa, Ethiopia']);
        $company_id = $pdo->lastInsertId();
        echo "Company details created.<br>";
    }

    // 3. Add Some Sample Jobs if none exist
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM job_listings WHERE company_id = ?");
    $stmt->execute([$company_id]);
    if ($stmt->fetchColumn() == 0) {
        $jobs = [
            ['Senior Web Developer', 'We are looking for a senior PHP/Laravel expert...', '5+ years experience', 'full_time', 'Addis Ababa', 45000, 65000],
            ['Graphic Designer', 'Creative mind for brand identity...', 'Portfolio required', 'contract', 'Remote', 15000, 25000],
            ['Junior IT Admin', 'Support our team with infrastructure maintenance...', 'CCNA preferred', 'full_time', 'Bole, Addis Ababa', 12000, 18000]
        ];

        $stmt = $pdo->prepare("INSERT INTO job_listings (company_id, posted_by, title, description, requirements, job_type, location, salary_min, salary_max, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
        foreach ($jobs as $j) {
            $stmt->execute([$company_id, $user_id, $j[0], $j[1], $j[2], $j[3], $j[4], $j[5], $j[6]]);
        }
        echo "Sample jobs added.<br>";
    }

    echo "<h3 style='color:green;'>Success! Demo account is ready.</h3>";
    echo "<p>Username: <b>cloud_company</b><br>Password: <b>password</b></p>";
    echo "<a href='login.php'>Go to Login</a>";

} catch (Exception $e) {
    echo "<h3 style='color:red;'>Error: " . $e->getMessage() . "</h3>";
}
