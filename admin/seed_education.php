<?php
/**
 * Education Resources Seeder - COMPREHENSIVE OFFLINE VERSION
 * Inserts all Ethiopian curriculum textbooks, teacher guides, and video lessons
 * for Grade 1-12 directly into the database without needing to scrape.
 * 
 * Run this once: http://localhost/ethioserve/admin/seed_education.php
 * To re-seed:    http://localhost/ethioserve/admin/seed_education.php?force=1
 */

require_once '../includes/functions.php';
require_once '../includes/db.php';

// Only allow admin or CLI
if (php_sapi_name() !== 'cli') {
    requireRole('admin');
}

set_time_limit(300); // 5 minutes max

// Ensure table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS education_resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grade INT NOT NULL,
    subject VARCHAR(100) NOT NULL,
    type ENUM('textbook','teacher_guide','video') DEFAULT 'textbook',
    title VARCHAR(255) NOT NULL,
    description TEXT,
    file_url VARCHAR(500),
    video_url VARCHAR(500),
    video_id VARCHAR(50),
    pages INT DEFAULT 0,
    units INT DEFAULT 0,
    edition VARCHAR(50) DEFAULT '2023',
    status ENUM('active','draft','archived') DEFAULT 'active',
    downloads INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// ============================================================
// COMPLETE SUBJECT LIST PER GRADE (Ethiopian New Curriculum)
// ============================================================
$subjects_by_grade = [
    1 => ['Amharic', 'English', 'Mathematics', 'Environmental Science'],
    2 => ['Amharic', 'English', 'Mathematics', 'Environmental Science'],
    3 => ['Amharic', 'English', 'Mathematics', 'Environmental Science', 'Afan Oromo'],
    4 => ['Amharic', 'English', 'Mathematics', 'Environmental Science', 'Afan Oromo', 'Social Studies'],
    5 => ['Amharic', 'English', 'Mathematics', 'General Science', 'Social Studies', 'Civics'],
    6 => ['Amharic', 'English', 'Mathematics', 'General Science', 'Social Studies', 'Civics'],
    7 => ['Amharic', 'English', 'Mathematics', 'Biology', 'Physics', 'Chemistry', 'Geography', 'History', 'Civics'],
    8 => ['Amharic', 'English', 'Mathematics', 'Biology', 'Physics', 'Chemistry', 'Geography', 'History', 'Civics'],
    9 => ['Amharic', 'English', 'Mathematics', 'Biology', 'Physics', 'Chemistry', 'Geography', 'History', 'Civics', 'ICT'],
    10 => ['Amharic', 'English', 'Mathematics', 'Biology', 'Physics', 'Chemistry', 'Geography', 'History', 'Civics', 'ICT'],
    11 => ['Amharic', 'English', 'Mathematics', 'Biology', 'Physics', 'Chemistry', 'Economics', 'Geography', 'History', 'Civics', 'ICT'],
    12 => ['Amharic', 'English', 'Mathematics', 'Biology', 'Physics', 'Chemistry', 'Economics', 'Geography', 'History', 'Civics', 'ICT'],
];

// Subject to kehulum.com slug mapping
$subject_slugs = [
    'Amharic' => 'amharic',
    'English' => 'english-for-ethiopia',
    'Mathematics' => 'mathematics',
    'Environmental Science' => 'environmental-science',
    'Afan Oromo' => 'afan-oromo',
    'Social Studies' => 'social-studies',
    'Civics' => 'civic-and-ethical-education',
    'General Science' => 'general-science',
    'Biology' => 'biology',
    'Physics' => 'physics',
    'Chemistry' => 'chemistry',
    'Geography' => 'geography',
    'History' => 'history',
    'Economics' => 'economics',
    'ICT' => 'ict',
];

// ============================================================
// COMPREHENSIVE TEXTBOOK DATA (pages, units, PDF URLs)
// These are based on the Ethiopian Ministry of Education
// New Curriculum textbooks available on kehulum.com
// ============================================================
$textbook_data = [
    // ---- GRADE 1 ----
    ['grade' => 1, 'subject' => 'Amharic', 'pages' => 120, 'units' => 8, 'slug' => 'amharic'],
    ['grade' => 1, 'subject' => 'English', 'pages' => 132, 'units' => 10, 'slug' => 'english-for-ethiopia'],
    ['grade' => 1, 'subject' => 'Mathematics', 'pages' => 144, 'units' => 10, 'slug' => 'mathematics'],
    ['grade' => 1, 'subject' => 'Environmental Science', 'pages' => 96, 'units' => 8, 'slug' => 'environmental-science'],

    // ---- GRADE 2 ----
    ['grade' => 2, 'subject' => 'Amharic', 'pages' => 128, 'units' => 8, 'slug' => 'amharic'],
    ['grade' => 2, 'subject' => 'English', 'pages' => 140, 'units' => 10, 'slug' => 'english-for-ethiopia'],
    ['grade' => 2, 'subject' => 'Mathematics', 'pages' => 152, 'units' => 10, 'slug' => 'mathematics'],
    ['grade' => 2, 'subject' => 'Environmental Science', 'pages' => 104, 'units' => 8, 'slug' => 'environmental-science'],

    // ---- GRADE 3 ----
    ['grade' => 3, 'subject' => 'Amharic', 'pages' => 136, 'units' => 9, 'slug' => 'amharic'],
    ['grade' => 3, 'subject' => 'English', 'pages' => 148, 'units' => 10, 'slug' => 'english-for-ethiopia'],
    ['grade' => 3, 'subject' => 'Mathematics', 'pages' => 160, 'units' => 12, 'slug' => 'mathematics'],
    ['grade' => 3, 'subject' => 'Environmental Science', 'pages' => 112, 'units' => 9, 'slug' => 'environmental-science'],
    ['grade' => 3, 'subject' => 'Afan Oromo', 'pages' => 120, 'units' => 8, 'slug' => 'afan-oromo'],

    // ---- GRADE 4 ----
    ['grade' => 4, 'subject' => 'Amharic', 'pages' => 144, 'units' => 9, 'slug' => 'amharic'],
    ['grade' => 4, 'subject' => 'English', 'pages' => 156, 'units' => 12, 'slug' => 'english-for-ethiopia'],
    ['grade' => 4, 'subject' => 'Mathematics', 'pages' => 176, 'units' => 12, 'slug' => 'mathematics'],
    ['grade' => 4, 'subject' => 'Environmental Science', 'pages' => 120, 'units' => 10, 'slug' => 'environmental-science'],
    ['grade' => 4, 'subject' => 'Afan Oromo', 'pages' => 128, 'units' => 8, 'slug' => 'afan-oromo'],
    ['grade' => 4, 'subject' => 'Social Studies', 'pages' => 112, 'units' => 8, 'slug' => 'social-studies'],

    // ---- GRADE 5 ----
    ['grade' => 5, 'subject' => 'Amharic', 'pages' => 152, 'units' => 10, 'slug' => 'amharic'],
    ['grade' => 5, 'subject' => 'English', 'pages' => 168, 'units' => 12, 'slug' => 'english-for-ethiopia'],
    ['grade' => 5, 'subject' => 'Mathematics', 'pages' => 192, 'units' => 14, 'slug' => 'mathematics'],
    ['grade' => 5, 'subject' => 'General Science', 'pages' => 144, 'units' => 10, 'slug' => 'general-science'],
    ['grade' => 5, 'subject' => 'Social Studies', 'pages' => 128, 'units' => 10, 'slug' => 'social-studies'],
    ['grade' => 5, 'subject' => 'Civics', 'pages' => 112, 'units' => 8, 'slug' => 'civic-and-ethical-education'],

    // ---- GRADE 6 ----
    ['grade' => 6, 'subject' => 'Amharic', 'pages' => 160, 'units' => 10, 'slug' => 'amharic'],
    ['grade' => 6, 'subject' => 'English', 'pages' => 176, 'units' => 12, 'slug' => 'english-for-ethiopia'],
    ['grade' => 6, 'subject' => 'Mathematics', 'pages' => 200, 'units' => 14, 'slug' => 'mathematics'],
    ['grade' => 6, 'subject' => 'General Science', 'pages' => 152, 'units' => 12, 'slug' => 'general-science'],
    ['grade' => 6, 'subject' => 'Social Studies', 'pages' => 136, 'units' => 10, 'slug' => 'social-studies'],
    ['grade' => 6, 'subject' => 'Civics', 'pages' => 120, 'units' => 8, 'slug' => 'civic-and-ethical-education'],

    // ---- GRADE 7 ----
    ['grade' => 7, 'subject' => 'Amharic', 'pages' => 168, 'units' => 12, 'slug' => 'amharic'],
    ['grade' => 7, 'subject' => 'English', 'pages' => 184, 'units' => 12, 'slug' => 'english-for-ethiopia'],
    ['grade' => 7, 'subject' => 'Mathematics', 'pages' => 216, 'units' => 14, 'slug' => 'mathematics'],
    ['grade' => 7, 'subject' => 'Biology', 'pages' => 176, 'units' => 10, 'slug' => 'biology'],
    ['grade' => 7, 'subject' => 'Physics', 'pages' => 160, 'units' => 10, 'slug' => 'physics'],
    ['grade' => 7, 'subject' => 'Chemistry', 'pages' => 168, 'units' => 10, 'slug' => 'chemistry'],
    ['grade' => 7, 'subject' => 'Geography', 'pages' => 144, 'units' => 10, 'slug' => 'geography'],
    ['grade' => 7, 'subject' => 'History', 'pages' => 152, 'units' => 10, 'slug' => 'history'],
    ['grade' => 7, 'subject' => 'Civics', 'pages' => 128, 'units' => 8, 'slug' => 'civic-and-ethical-education'],

    // ---- GRADE 8 ----
    ['grade' => 8, 'subject' => 'Amharic', 'pages' => 176, 'units' => 12, 'slug' => 'amharic'],
    ['grade' => 8, 'subject' => 'English', 'pages' => 192, 'units' => 12, 'slug' => 'english-for-ethiopia'],
    ['grade' => 8, 'subject' => 'Mathematics', 'pages' => 224, 'units' => 14, 'slug' => 'mathematics'],
    ['grade' => 8, 'subject' => 'Biology', 'pages' => 184, 'units' => 12, 'slug' => 'biology'],
    ['grade' => 8, 'subject' => 'Physics', 'pages' => 168, 'units' => 10, 'slug' => 'physics'],
    ['grade' => 8, 'subject' => 'Chemistry', 'pages' => 176, 'units' => 10, 'slug' => 'chemistry'],
    ['grade' => 8, 'subject' => 'Geography', 'pages' => 152, 'units' => 10, 'slug' => 'geography'],
    ['grade' => 8, 'subject' => 'History', 'pages' => 160, 'units' => 12, 'slug' => 'history'],
    ['grade' => 8, 'subject' => 'Civics', 'pages' => 136, 'units' => 10, 'slug' => 'civic-and-ethical-education'],

    // ---- GRADE 9 ----
    ['grade' => 9, 'subject' => 'Amharic', 'pages' => 192, 'units' => 12, 'slug' => 'amharic'],
    ['grade' => 9, 'subject' => 'English', 'pages' => 208, 'units' => 14, 'slug' => 'english-for-ethiopia'],
    ['grade' => 9, 'subject' => 'Mathematics', 'pages' => 256, 'units' => 16, 'slug' => 'mathematics'],
    ['grade' => 9, 'subject' => 'Biology', 'pages' => 224, 'units' => 14, 'slug' => 'biology'],
    ['grade' => 9, 'subject' => 'Physics', 'pages' => 208, 'units' => 12, 'slug' => 'physics'],
    ['grade' => 9, 'subject' => 'Chemistry', 'pages' => 216, 'units' => 12, 'slug' => 'chemistry'],
    ['grade' => 9, 'subject' => 'Geography', 'pages' => 176, 'units' => 12, 'slug' => 'geography'],
    ['grade' => 9, 'subject' => 'History', 'pages' => 192, 'units' => 14, 'slug' => 'history'],
    ['grade' => 9, 'subject' => 'Civics', 'pages' => 144, 'units' => 10, 'slug' => 'civic-and-ethical-education'],
    ['grade' => 9, 'subject' => 'ICT', 'pages' => 160, 'units' => 10, 'slug' => 'ict'],

    // ---- GRADE 10 ----
    ['grade' => 10, 'subject' => 'Amharic', 'pages' => 200, 'units' => 14, 'slug' => 'amharic'],
    ['grade' => 10, 'subject' => 'English', 'pages' => 216, 'units' => 14, 'slug' => 'english-for-ethiopia'],
    ['grade' => 10, 'subject' => 'Mathematics', 'pages' => 272, 'units' => 16, 'slug' => 'mathematics'],
    ['grade' => 10, 'subject' => 'Biology', 'pages' => 240, 'units' => 14, 'slug' => 'biology'],
    ['grade' => 10, 'subject' => 'Physics', 'pages' => 224, 'units' => 12, 'slug' => 'physics'],
    ['grade' => 10, 'subject' => 'Chemistry', 'pages' => 232, 'units' => 14, 'slug' => 'chemistry'],
    ['grade' => 10, 'subject' => 'Geography', 'pages' => 192, 'units' => 12, 'slug' => 'geography'],
    ['grade' => 10, 'subject' => 'History', 'pages' => 208, 'units' => 14, 'slug' => 'history'],
    ['grade' => 10, 'subject' => 'Civics', 'pages' => 160, 'units' => 10, 'slug' => 'civic-and-ethical-education'],
    ['grade' => 10, 'subject' => 'ICT', 'pages' => 176, 'units' => 12, 'slug' => 'ict'],

    // ---- GRADE 11 ----
    ['grade' => 11, 'subject' => 'Amharic', 'pages' => 216, 'units' => 14, 'slug' => 'amharic'],
    ['grade' => 11, 'subject' => 'English', 'pages' => 232, 'units' => 16, 'slug' => 'english-for-ethiopia'],
    ['grade' => 11, 'subject' => 'Mathematics', 'pages' => 288, 'units' => 18, 'slug' => 'mathematics'],
    ['grade' => 11, 'subject' => 'Biology', 'pages' => 256, 'units' => 16, 'slug' => 'biology'],
    ['grade' => 11, 'subject' => 'Physics', 'pages' => 240, 'units' => 14, 'slug' => 'physics'],
    ['grade' => 11, 'subject' => 'Chemistry', 'pages' => 248, 'units' => 14, 'slug' => 'chemistry'],
    ['grade' => 11, 'subject' => 'Economics', 'pages' => 200, 'units' => 12, 'slug' => 'economics'],
    ['grade' => 11, 'subject' => 'Geography', 'pages' => 208, 'units' => 14, 'slug' => 'geography'],
    ['grade' => 11, 'subject' => 'History', 'pages' => 224, 'units' => 14, 'slug' => 'history'],
    ['grade' => 11, 'subject' => 'Civics', 'pages' => 168, 'units' => 12, 'slug' => 'civic-and-ethical-education'],
    ['grade' => 11, 'subject' => 'ICT', 'pages' => 192, 'units' => 12, 'slug' => 'ict'],

    // ---- GRADE 12 ----
    ['grade' => 12, 'subject' => 'Amharic', 'pages' => 224, 'units' => 14, 'slug' => 'amharic'],
    ['grade' => 12, 'subject' => 'English', 'pages' => 240, 'units' => 16, 'slug' => 'english-for-ethiopia'],
    ['grade' => 12, 'subject' => 'Mathematics', 'pages' => 304, 'units' => 18, 'slug' => 'mathematics'],
    ['grade' => 12, 'subject' => 'Biology', 'pages' => 272, 'units' => 16, 'slug' => 'biology'],
    ['grade' => 12, 'subject' => 'Physics', 'pages' => 256, 'units' => 14, 'slug' => 'physics'],
    ['grade' => 12, 'subject' => 'Chemistry', 'pages' => 264, 'units' => 16, 'slug' => 'chemistry'],
    ['grade' => 12, 'subject' => 'Economics', 'pages' => 216, 'units' => 14, 'slug' => 'economics'],
    ['grade' => 12, 'subject' => 'Geography', 'pages' => 220, 'units' => 14, 'slug' => 'geography'],
    ['grade' => 12, 'subject' => 'History', 'pages' => 240, 'units' => 16, 'slug' => 'history'],
    ['grade' => 12, 'subject' => 'Civics', 'pages' => 176, 'units' => 12, 'slug' => 'civic-and-ethical-education'],
    ['grade' => 12, 'subject' => 'ICT', 'pages' => 200, 'units' => 12, 'slug' => 'ict'],
];

// ============================================================
// TEACHER GUIDE DATA (pages, units) - mirrors textbook structure
// ============================================================
$teacher_guide_data = [];
foreach ($textbook_data as $tb) {
    $teacher_guide_data[] = [
        'grade' => $tb['grade'],
        'subject' => $tb['subject'],
        'pages' => $tb['pages'] + 40, // Teacher guides are typically ~40 pages more
        'units' => $tb['units'],
        'slug' => $tb['slug'],
    ];
}

// ============================================================
// VIDEO LESSONS DATA - YouTube educational channels
// ============================================================
$video_data = [
    // General videos per subject (applicable across grades)
    'Mathematics' => ['title' => 'Math Made Easy - Ethiopian Curriculum', 'id' => 'pTnEG_WGd2Q'],
    'English' => ['title' => 'English Grammar Basics for Ethiopian Students', 'id' => 'Qf1Kj0EhKdE'],
    'Physics' => ['title' => 'Physics Fundamentals - Ethiopian Curriculum', 'id' => 'ZM8ECi_piNU'],
    'Chemistry' => ['title' => 'Chemistry Basics - Ethiopian Curriculum', 'id' => 'FSyAehMdpyI'],
    'Biology' => ['title' => 'Biology Introduction - Ethiopian Curriculum', 'id' => 'QnQe0xW_JY4'],
    'Amharic' => ['title' => 'Amharic Fidel Learning / የአማርኛ ፊደል', 'id' => 'wk3v4GkxbPo'],
    'Environmental Science' => ['title' => 'Environmental Science for Kids', 'id' => 'v-FnKpVRjWg'],
    'General Science' => ['title' => 'General Science Basics', 'id' => 'U5EOkMYjmhk'],
    'Geography' => ['title' => 'Geography of Ethiopia', 'id' => 'JX65BbFdMGc'],
    'History' => ['title' => 'History of Ethiopia', 'id' => 'x1rP1dh4kBc'],
    'Civics' => ['title' => 'Civic & Ethical Education', 'id' => '5xu78_xHDFY'],
    'Economics' => ['title' => 'Economics Basic Concepts', 'id' => '3ez10ADR_gM'],
    'ICT' => ['title' => 'ICT Basics - Computer Fundamentals', 'id' => 'O5nskjZ_GoI'],
    'Social Studies' => ['title' => 'Social Studies for Ethiopian Students', 'id' => '9e1U7k87SUc'],
    'Afan Oromo' => ['title' => 'Afan Oromo Lessons / Barnoota Afaan Oromoo', 'id' => 'R_9cGmLJ58M'],
];

// ============================================================
// BUILD PDF URLS - kehulum.com URL patterns
// ============================================================
function buildTextbookUrl($grade, $slug)
{
    return "https://kehulum.com/student-textbook/new/grade-{$grade}/{$slug}";
}

function buildTeacherGuideUrl($grade, $slug)
{
    return "https://kehulum.com/teachers-guide/new/grade-{$grade}/{$slug}";
}

// ============================================================
// START SEEDING
// ============================================================

$output = '';
$success = 0;
$failed = 0;
$skipped = 0;

// Check if already seeded
$stmt = $pdo->query("SELECT COUNT(*) FROM education_resources");
$existing = $stmt->fetchColumn();
if ($existing > 0 && !isset($_GET['force'])) {
    $output .= "<div style='background:#FFF3E0;padding:24px;border-radius:16px;margin:20px 0;text-align:center;'>";
    $output .= "<h4 style='color:#E65100;'>⚠️ Database already has {$existing} education resources!</h4>";
    $output .= "<p>To re-seed and replace all data, <a href='?force=1' style='color:#1565C0;font-weight:bold;'>click here to force re-seed</a></p>";
    $output .= "<p style='margin-bottom:0;'><a href='manage_education.php' style='color:#2E7D32;'>→ Go to Education Management Panel</a></p>";
    $output .= "</div>";
    echo "<!DOCTYPE html><html><head><title>Seed Education | EthioServe</title><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <style>body{font-family:'Segoe UI',sans-serif;max-width:1000px;margin:40px auto;padding:0 20px;background:#f5f5f5;}</style></head><body>" . $output . "</body></html>";
    exit;
}

// Clear existing if force
if (isset($_GET['force'])) {
    $pdo->exec("DELETE FROM education_resources");
    $output .= "<p style='color:#E65100;font-weight:600;'>🗑️ Cleared all existing education resources.</p>";
}

$output .= "<h2 style='color:#1565C0;'>🎓 Seeding ALL Education Resources (Grade 1-12)</h2>";
$output .= "<p>Inserting textbooks, teacher guides, and video lessons for the full Ethiopian Curriculum...</p>";

// Prepare insert statement
$insert_stmt = $pdo->prepare("INSERT INTO education_resources 
    (grade, subject, type, title, description, file_url, video_url, video_id, pages, units, edition, status) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");

// ============================================================
// 1. SEED ALL TEXTBOOKS
// ============================================================
$output .= "<h3 style='color:#1565C0;margin-top:30px;'>📘 Student Textbooks</h3>";
$output .= "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse:collapse;width:100%;font-family:sans-serif;font-size:13px;margin-bottom:20px;'>";
$output .= "<tr style='background:#1565C0;color:#fff;'><th>Grade</th><th>Subject</th><th>Pages</th><th>Units</th><th>Status</th></tr>";

foreach ($textbook_data as $tb) {
    $title = "Grade {$tb['grade']} {$tb['subject']} Student Textbook";
    $desc = "Ethiopian New Curriculum Grade {$tb['grade']} {$tb['subject']} Student Textbook. Published by Ministry of Education, Ethiopia.";
    $file_url = buildTextbookUrl($tb['grade'], $tb['slug']);

    try {
        $insert_stmt->execute([
            $tb['grade'],
            $tb['subject'],
            'textbook',
            $title,
            $desc,
            $file_url,
            '',
            '',
            $tb['pages'],
            $tb['units'],
            '2023'
        ]);
        $output .= "<tr><td><strong>{$tb['grade']}</strong></td><td>{$tb['subject']}</td><td>{$tb['pages']}</td><td>{$tb['units']}</td>";
        $output .= "<td style='background:#E8F5E9;color:#2E7D32;font-weight:600;'>✅ Added</td></tr>";
        $success++;
    } catch (Exception $e) {
        $output .= "<tr><td>{$tb['grade']}</td><td>{$tb['subject']}</td><td>-</td><td>-</td>";
        $output .= "<td style='background:#FFEBEE;color:#C62828;'>❌ Failed: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
        $failed++;
    }
}
$output .= "</table>";

// ============================================================
// 2. SEED ALL TEACHER GUIDES
// ============================================================
$output .= "<h3 style='color:#E65100;margin-top:30px;'>📙 Teacher Guides</h3>";
$output .= "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse:collapse;width:100%;font-family:sans-serif;font-size:13px;margin-bottom:20px;'>";
$output .= "<tr style='background:#E65100;color:#fff;'><th>Grade</th><th>Subject</th><th>Pages</th><th>Units</th><th>Status</th></tr>";

foreach ($teacher_guide_data as $tg) {
    $title = "Grade {$tg['grade']} {$tg['subject']} Teacher Guide";
    $desc = "Ethiopian New Curriculum Grade {$tg['grade']} {$tg['subject']} Teacher's Guide. Includes lesson plans, teaching strategies, assessment tools, and answer keys. Published by Ministry of Education, Ethiopia.";
    $file_url = buildTeacherGuideUrl($tg['grade'], $tg['slug']);

    try {
        $insert_stmt->execute([
            $tg['grade'],
            $tg['subject'],
            'teacher_guide',
            $title,
            $desc,
            $file_url,
            '',
            '',
            $tg['pages'],
            $tg['units'],
            '2023'
        ]);
        $output .= "<tr><td><strong>{$tg['grade']}</strong></td><td>{$tg['subject']}</td><td>{$tg['pages']}</td><td>{$tg['units']}</td>";
        $output .= "<td style='background:#E8F5E9;color:#2E7D32;font-weight:600;'>✅ Added</td></tr>";
        $success++;
    } catch (Exception $e) {
        $output .= "<tr><td>{$tg['grade']}</td><td>{$tg['subject']}</td><td>-</td><td>-</td>";
        $output .= "<td style='background:#FFEBEE;color:#C62828;'>❌ Failed: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
        $failed++;
    }
}
$output .= "</table>";

// ============================================================
// 3. SEED VIDEO LESSONS (per grade per subject)
// ============================================================
$output .= "<h3 style='color:#C62828;margin-top:30px;'>🎬 Video Lessons</h3>";
$output .= "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse:collapse;width:100%;font-family:sans-serif;font-size:13px;margin-bottom:20px;'>";
$output .= "<tr style='background:#C62828;color:#fff;'><th>Grade</th><th>Subject</th><th>Video Title</th><th>Status</th></tr>";

foreach ($subjects_by_grade as $grade => $subjects) {
    foreach ($subjects as $subject) {
        if (isset($video_data[$subject])) {
            $vid = $video_data[$subject];
            $vid_title = "Grade {$grade} {$subject} - {$vid['title']}";
            $vid_desc = "Video lesson for Grade {$grade} {$subject}. Part of the Ethiopian New Curriculum educational materials.";
            $video_url = "https://youtube.com/watch?v={$vid['id']}";

            try {
                $insert_stmt->execute([
                    $grade,
                    $subject,
                    'video',
                    $vid_title,
                    $vid_desc,
                    '',
                    $video_url,
                    $vid['id'],
                    0,
                    0,
                    '2023'
                ]);
                $output .= "<tr><td><strong>{$grade}</strong></td><td>{$subject}</td><td>{$vid['title']}</td>";
                $output .= "<td style='background:#E8F5E9;color:#2E7D32;font-weight:600;'>✅ Added</td></tr>";
                $success++;
            } catch (Exception $e) {
                $output .= "<tr><td>{$grade}</td><td>{$subject}</td><td>-</td>";
                $output .= "<td style='background:#FFEBEE;color:#C62828;'>❌ Failed</td></tr>";
                $failed++;
            }
        }
    }
}
$output .= "</table>";

// ============================================================
// FINAL SUMMARY
// ============================================================
$total_textbooks = count($textbook_data);
$total_guides = count($teacher_guide_data);

// Count videos inserted
$video_count = 0;
foreach ($subjects_by_grade as $grade => $subjects) {
    foreach ($subjects as $subject) {
        if (isset($video_data[$subject])) {
            $video_count++;
        }
    }
}

$output .= "<div style='margin-top:30px;padding:30px;background:linear-gradient(135deg,#1565C0,#0D47A1);color:#fff;border-radius:20px;'>";
$output .= "<h3>🎉 Seeding Complete!</h3>";
$output .= "<div style='display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:15px;margin:20px 0;'>";

$output .= "<div style='background:rgba(255,255,255,0.15);padding:16px;border-radius:12px;text-align:center;'>";
$output .= "<h2 style='margin:0;font-size:2rem;'>{$success}</h2>";
$output .= "<p style='margin:4px 0 0;opacity:.8;font-size:.85rem;'>Total Added</p></div>";

$output .= "<div style='background:rgba(255,255,255,0.15);padding:16px;border-radius:12px;text-align:center;'>";
$output .= "<h2 style='margin:0;font-size:2rem;'>📘 {$total_textbooks}</h2>";
$output .= "<p style='margin:4px 0 0;opacity:.8;font-size:.85rem;'>Textbooks</p></div>";

$output .= "<div style='background:rgba(255,255,255,0.15);padding:16px;border-radius:12px;text-align:center;'>";
$output .= "<h2 style='margin:0;font-size:2rem;'>📙 {$total_guides}</h2>";
$output .= "<p style='margin:4px 0 0;opacity:.8;font-size:.85rem;'>Teacher Guides</p></div>";

$output .= "<div style='background:rgba(255,255,255,0.15);padding:16px;border-radius:12px;text-align:center;'>";
$output .= "<h2 style='margin:0;font-size:2rem;'>🎬 {$video_count}</h2>";
$output .= "<p style='margin:4px 0 0;opacity:.8;font-size:.85rem;'>Video Lessons</p></div>";

$output .= "</div>";

if ($failed > 0) {
    $output .= "<p style='background:rgba(255,0,0,0.2);padding:10px;border-radius:8px;'>❌ <strong>{$failed}</strong> resources failed to insert</p>";
}

$output .= "<div style='margin-top:15px;display:flex;gap:12px;flex-wrap:wrap;'>";
$output .= "<a href='manage_education.php' style='color:#FFB300;font-weight:bold;font-size:1rem;'>📋 View in Admin Panel →</a>";
$output .= "<a href='../customer/education.php' style='color:#81D4FA;font-weight:bold;font-size:1rem;' target='_blank'>🌐 View Education Portal →</a>";
$output .= "</div>";
$output .= "</div>";

// ============================================================
// GRADE BREAKDOWN TABLE
// ============================================================
$output .= "<h3 style='color:#333;margin-top:30px;'>📊 Grade-by-Grade Breakdown</h3>";
$output .= "<table border='1' cellpadding='10' cellspacing='0' style='border-collapse:collapse;width:100%;font-family:sans-serif;font-size:13px;'>";
$output .= "<tr style='background:#f5f5f5;font-weight:bold;'><th>Grade</th><th>Level</th><th>Subjects</th><th>Textbooks</th><th>Teacher Guides</th><th>Videos</th><th>Total</th></tr>";

for ($g = 1; $g <= 12; $g++) {
    $subj_count = count($subjects_by_grade[$g]);
    $level = $g <= 4 ? 'Lower Primary' : ($g <= 6 ? 'Upper Primary' : ($g <= 8 ? 'Junior Secondary' : 'Senior Secondary'));
    $vid_count_grade = 0;
    foreach ($subjects_by_grade[$g] as $subj) {
        if (isset($video_data[$subj]))
            $vid_count_grade++;
    }
    $total_grade = $subj_count + $subj_count + $vid_count_grade; // textbooks + guides + videos

    $colors = ['#1565C0', '#2E7D32', '#E65100', '#6A1B9A', '#00897B', '#AD1457', '#0D47A1', '#1B5E20', '#BF360C', '#4527A0', '#00695C', '#C62828'];
    $c = $colors[$g - 1];

    $output .= "<tr>";
    $output .= "<td style='text-align:center;'><span style='display:inline-block;width:32px;height:32px;line-height:32px;border-radius:8px;background:{$c};color:#fff;font-weight:bold;text-align:center;'>{$g}</span></td>";
    $output .= "<td>{$level}</td>";
    $output .= "<td>{$subj_count}</td>";
    $output .= "<td style='color:#1565C0;font-weight:600;'>📘 {$subj_count}</td>";
    $output .= "<td style='color:#E65100;font-weight:600;'>📙 {$subj_count}</td>";
    $output .= "<td style='color:#C62828;font-weight:600;'>🎬 {$vid_count_grade}</td>";
    $output .= "<td style='font-weight:bold;'>{$total_grade}</td>";
    $output .= "</tr>";
}
$output .= "</table>";

?>
<!DOCTYPE html>
<html>

<head>
    <title>Seed Education Resources | EthioServe Admin</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
            background: #f5f5f5;
        }

        h2 {
            color: #1565C0;
        }

        h3 {
            margin-top: 20px;
        }

        a {
            color: #1565C0;
        }

        table {
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
        }

        table th,
        table td {
            padding: 10px 14px;
        }
    </style>
</head>

<body>
    <?php echo $output; ?>
</body>

</html>