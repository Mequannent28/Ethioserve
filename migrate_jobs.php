<?php
require_once 'includes/db.php';

$queries = [
    // Job Categories
    "CREATE TABLE IF NOT EXISTS job_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        icon VARCHAR(50) DEFAULT 'fas fa-briefcase',
        color VARCHAR(30) DEFAULT '#1565C0',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // Companies / Employers
    "CREATE TABLE IF NOT EXISTS job_companies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        company_name VARCHAR(200) NOT NULL,
        logo_url TEXT,
        description TEXT,
        website VARCHAR(255),
        location VARCHAR(200),
        industry VARCHAR(100),
        size VARCHAR(50),
        verified TINYINT(1) DEFAULT 0,
        rating DECIMAL(3,2) DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // Job Listings
    "CREATE TABLE IF NOT EXISTS job_listings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT,
        posted_by INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        requirements TEXT,
        job_type ENUM('full_time','part_time','contract','internship','freelance','daily_labor') NOT NULL DEFAULT 'full_time',
        category_id INT,
        location VARCHAR(200),
        is_remote TINYINT(1) DEFAULT 0,
        salary_min DECIMAL(12,2),
        salary_max DECIMAL(12,2),
        salary_period ENUM('hour','day','week','month','project') DEFAULT 'month',
        currency VARCHAR(10) DEFAULT 'ETB',
        skills_required TEXT,
        experience_level ENUM('entry','mid','senior','any') DEFAULT 'any',
        education_level VARCHAR(100),
        deadline DATE,
        status ENUM('active','closed','draft') DEFAULT 'active',
        views INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (company_id) REFERENCES job_companies(id) ON DELETE SET NULL,
        FOREIGN KEY (posted_by) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (category_id) REFERENCES job_categories(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // Applicant Profiles
    "CREATE TABLE IF NOT EXISTS job_profiles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL UNIQUE,
        headline VARCHAR(255),
        bio TEXT,
        location VARCHAR(200),
        phone VARCHAR(30),
        skills TEXT,
        experience_years INT DEFAULT 0,
        education TEXT,
        portfolio_url VARCHAR(255),
        cv_url VARCHAR(255),
        profile_pic VARCHAR(255),
        availability ENUM('immediately','within_week','within_month','not_looking') DEFAULT 'immediately',
        expected_salary VARCHAR(100),
        linkedin_url VARCHAR(255),
        rating DECIMAL(3,2) DEFAULT 0.00,
        total_reviews INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // Work Experience
    "CREATE TABLE IF NOT EXISTS job_experience (
        id INT AUTO_INCREMENT PRIMARY KEY,
        profile_id INT NOT NULL,
        job_title VARCHAR(200),
        company VARCHAR(200),
        start_date DATE,
        end_date DATE,
        is_current TINYINT(1) DEFAULT 0,
        description TEXT,
        FOREIGN KEY (profile_id) REFERENCES job_profiles(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // Job Applications
    "CREATE TABLE IF NOT EXISTS job_applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        job_id INT NOT NULL,
        applicant_id INT NOT NULL,
        cover_letter TEXT,
        cv_url VARCHAR(255),
        status ENUM('pending','shortlisted','interviewed','hired','rejected') DEFAULT 'pending',
        interview_date DATETIME,
        notes TEXT,
        applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (job_id) REFERENCES job_listings(id) ON DELETE CASCADE,
        FOREIGN KEY (applicant_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_application (job_id, applicant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // Freelance Services
    "CREATE TABLE IF NOT EXISTS freelance_services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        provider_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        category VARCHAR(100),
        skills TEXT,
        price_type ENUM('fixed','hourly','negotiable') DEFAULT 'fixed',
        price DECIMAL(12,2),
        delivery_days INT DEFAULT 7,
        image_url TEXT,
        portfolio_images TEXT,
        status ENUM('active','paused','deleted') DEFAULT 'active',
        rating DECIMAL(3,2) DEFAULT 0.00,
        total_orders INT DEFAULT 0,
        views INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (provider_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // Reviews & Ratings
    "CREATE TABLE IF NOT EXISTS job_reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reviewer_id INT NOT NULL,
        reviewee_id INT NOT NULL,
        job_id INT,
        service_id INT,
        rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
        review TEXT,
        type ENUM('company_review','freelancer_review') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (reviewee_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // Saved / Bookmarked Jobs
    "CREATE TABLE IF NOT EXISTS job_saved (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        job_id INT NOT NULL,
        saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (job_id) REFERENCES job_listings(id) ON DELETE CASCADE,
        UNIQUE KEY unique_save (user_id, job_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // Job Notifications
    "CREATE TABLE IF NOT EXISTS job_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255),
        message TEXT,
        link VARCHAR(255),
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

// Seed categories
$categories = [
    ['Technology & IT', 'fas fa-laptop-code', '#1565C0'],
    ['Design & Creative', 'fas fa-palette', '#AD1457'],
    ['Marketing & Sales', 'fas fa-bullhorn', '#E65100'],
    ['Healthcare', 'fas fa-heartbeat', '#2E7D32'],
    ['Education & Training', 'fas fa-graduation-cap', '#6A1B9A'],
    ['Construction & Labor', 'fas fa-hard-hat', '#F57F17'],
    ['Finance & Accounting', 'fas fa-chart-line', '#00695C'],
    ['Legal & Admin', 'fas fa-balance-scale', '#4527A0'],
    ['Writing & Translation', 'fas fa-pen', '#0277BD'],
    ['Customer Service', 'fas fa-headset', '#558B2F'],
    ['Engineering', 'fas fa-cogs', '#455A64'],
    ['Other', 'fas fa-briefcase', '#78909C'],
];

echo "<h2>🚀 Running Jobs & Freelance Migration...</h2><hr>";
$success = true;
foreach ($queries as $i => $sql) {
    try {
        $pdo->exec($sql);
        echo "✅ Query " . ($i + 1) . " executed successfully<br>";
    } catch (Exception $e) {
        echo "❌ Query " . ($i + 1) . " failed: " . $e->getMessage() . "<br>";
        $success = false;
    }
}

// Seed categories if empty
try {
    $count = $pdo->query("SELECT COUNT(*) FROM job_categories")->fetchColumn();
    if ($count == 0) {
        $stmt = $pdo->prepare("INSERT INTO job_categories (name, icon, color) VALUES (?, ?, ?)");
        foreach ($categories as $cat) {
            $stmt->execute($cat);
        }
        echo "✅ Seeded " . count($categories) . " job categories<br>";
    } else {
        echo "ℹ️ Job categories already seeded ({$count} exist)<br>";
    }
} catch (Exception $e) {
    echo "❌ Category seeding failed: " . $e->getMessage() . "<br>";
}

echo "<hr>";
if ($success) {
    echo "<h3 style='color:green'>✅ Jobs & Freelance module migration complete!</h3>";
    echo "<a href='customer/jobs.php'>→ Go to Jobs & Freelance page</a>";
} else {
    echo "<h3 style='color:red'>⚠️ Migration completed with some errors. Check above.</h3>";
}
