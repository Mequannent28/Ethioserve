<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// This script simulates "Red Cloud ICT Solution" posting a job
// and prepares an environment for testing applications.

try {
    // 1. Get or Create a User for the Company (let's assume ID 1 or current)
    $user_id = $_SESSION['id'] ?? $_SESSION['user_id'] ?? 1; // Default to 1 if no session

    // 2. Ensure Company exists
    $stmt = $pdo->prepare("SELECT id FROM job_companies WHERE company_name = ?");
    $stmt->execute(['Red Cloud ICT Solution']);
    $company = $stmt->fetch();

    if (!$company) {
        $pdo->prepare("INSERT INTO job_companies (user_id, company_name, description, website, location, industry, size, verified) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([
                $user_id,
                'Red Cloud ICT Solution',
                'A leading ICT solutions provider in Ethiopia, specializing in cloud infrastructure, software development, and network security.',
                'https://redcloudict.com',
                'Addis Ababa, Bole',
                'Technology',
                '50-200 employees',
                1
            ]);
        $company_id = $pdo->lastInsertId();
        echo "✅ Created Company: Red Cloud ICT Solution<br>";
    } else {
        $company_id = $company['id'];
        echo "ℹ️ Company Red Cloud ICT Solution already exists.<br>";
    }

    // 3. Post a Job
    $job_title = "Senior Cloud Infrastructure Engineer";
    $stmt = $pdo->prepare("SELECT id FROM job_listings WHERE title = ? AND company_id = ?");
    $stmt->execute([$job_title, $company_id]);
    if (!$stmt->fetch()) {
        $pdo->prepare("INSERT INTO job_listings (company_id, posted_by, title, description, requirements, job_type, category_id, location, salary_min, salary_max, salary_period, skills_required, experience_level, status) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([
                $company_id,
                $user_id,
                $job_title,
                "We are looking for an experienced Cloud Infrastructure Engineer to design and manage our hybrid cloud environments. You will be responsible for ensuring 99.9% uptime and implementing security best practices.",
                " - 5+ years of experience in AWS/Azure\n- Strong knowledge of Linux/Unix\n- Experience with Terraform and Ansible\n- Bachelor's degree in CS or related field",
                'full_time',
                1, // Technology & IT
                'Addis Ababa / Remote',
                45000,
                75000,
                'month',
                'AWS, Linux, Terraform, Python, Security',
                'senior',
                'active'
            ]);
        echo "✅ Posted Job: $job_title<br>";
    } else {
        echo "ℹ️ Job '$job_title' already exists.<br>";
    }

    echo "<hr><h3>Next Steps:</h3>";
    echo "1. Go to <a href='customer/jobs.php'>Jobs Page</a> to see the new listing.<br>";
    echo "2. Log in as a different user to Apply.<br>";
    echo "3. Go to <a href='customer/employer_dashboard.php'>Employer Dashboard</a> to see the applicant's CV and info.";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
