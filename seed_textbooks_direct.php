<?php
/**
 * DIRECT PDF SEEDER
 * Uses direct PDF links from the government education bureau (anrseb.gov.et)
 * ensures textbooks display correctly in the PDF viewer.
 */
require_once 'includes/functions.php';
require_once 'includes/db.php';

echo "<pre>🚀 Starting Direct PDF Seeder...\n";

try {
    // Clear existing textbook resources to avoid duplicates or broken links
    $pdo->exec("DELETE FROM education_resources WHERE type = 'textbook'");
    echo "🗑️ Cleared existing textbook resources.\n";

    $base_url = "https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/";

    $seed_data = [
        // Grade 1
        ['grade' => 1, 'subject' => 'Amharic', 'file' => 'Grade%201_Amharic_Textbook.pdf'],
        ['grade' => 1, 'subject' => 'English', 'file' => 'Grade%201_English_Textbook.pdf'],
        ['grade' => 1, 'subject' => 'Mathematics', 'file' => 'Grade%201_Mathematics_Textbook.pdf'],
        ['grade' => 1, 'subject' => 'Environmental Science', 'file' => 'Grade%201_Environmental%20Science_Textbook.pdf'],

        // Grade 2
        ['grade' => 2, 'subject' => 'Amharic', 'file' => 'Grade%202_Amharic_Textbook.pdf'],
        ['grade' => 2, 'subject' => 'English', 'file' => 'Grade%202_English_Textbook.pdf'],
        ['grade' => 2, 'subject' => 'Mathematics', 'file' => 'Grade%202_Mathematics_Textbook.pdf'],
        ['grade' => 2, 'subject' => 'Environmental Science', 'file' => 'Grade%202_Environmental%20Science_Textbook.pdf'],

        // Grade 3
        ['grade' => 3, 'subject' => 'Amharic', 'file' => 'Grade%203_Amharic_Textbook.pdf'],
        ['grade' => 3, 'subject' => 'English', 'file' => 'Grade%203_English_Textbook.pdf'],
        ['grade' => 3, 'subject' => 'Mathematics', 'file' => 'Grade%203_Mathematics_Textbook.pdf'],
        ['grade' => 3, 'subject' => 'Environmental Science', 'file' => 'Grade%203_Environmental%20Science_Textbook.pdf'],

        // Grade 4
        ['grade' => 4, 'subject' => 'Amharic', 'file' => 'Grade%204_Amharic_Textbook.pdf'],
        ['grade' => 4, 'subject' => 'English', 'file' => 'Grade%204_English_Textbook.pdf'],
        ['grade' => 4, 'subject' => 'Mathematics', 'file' => 'Grade%204_Mathematics_Textbook.pdf'],
        ['grade' => 4, 'subject' => 'Environmental Science', 'file' => 'Grade%204_Environmental%20Science_Textbook.pdf'],

        // Grade 5
        ['grade' => 5, 'subject' => 'Amharic', 'file' => 'Grade%205_Amharic_Textbook.pdf'],
        ['grade' => 5, 'subject' => 'English', 'file' => 'Grade%205_English_Textbook.pdf'],
        ['grade' => 5, 'subject' => 'Mathematics', 'file' => 'Grade%205_Mathematics_Textbook.pdf'],
        ['grade' => 5, 'subject' => 'Environmental Science', 'file' => 'Grade%205_Environmental%20Science_Textbook.pdf'],
        ['grade' => 5, 'subject' => 'General Science', 'file' => 'Grade%205_Environmental%20Science_Textbook.pdf'], // Map to env science

        // Grade 6
        ['grade' => 6, 'subject' => 'Amharic', 'file' => 'Grade%206_Amharic_Textbook.pdf'],
        ['grade' => 6, 'subject' => 'English', 'file' => 'Grade%206_English_Textbook.pdf'],
        ['grade' => 6, 'subject' => 'Mathematics', 'file' => 'Grade%206_Mathematics_Textbook.pdf'],
        ['grade' => 6, 'subject' => 'Environmental Science', 'file' => 'Grade%206_Environmental%20Science_Textbook.pdf'],
        ['grade' => 6, 'subject' => 'General Science', 'file' => 'Grade%206_Environmental%20Science_Textbook.pdf'],

        // Grade 7
        ['grade' => 7, 'subject' => 'Amharic', 'file' => 'Grade%207_Amharic_Textbook.pdf'],
        ['grade' => 7, 'subject' => 'English', 'file' => 'Grade%207_English_Textbook.pdf'],
        ['grade' => 7, 'subject' => 'Mathematics', 'file' => 'Grade%207_Mathematics_Textbook.pdf'],
        ['grade' => 7, 'subject' => 'General Science', 'file' => 'Grade%207_General%20Science_Textbook.pdf'],
        ['grade' => 7, 'subject' => 'Social Studies', 'file' => 'Grade%207_Social%20Study_Textbook.pdf'],
        ['grade' => 7, 'subject' => 'Civics', 'file' => 'Grade%207_Citizenship%20Education_Textbook.pdf'],

        // Grade 8
        ['grade' => 8, 'subject' => 'Amharic', 'file' => 'Grade%208_Amharic_Textbook.pdf'],
        ['grade' => 8, 'subject' => 'English', 'file' => 'Grade%208_English_Textbook.pdf'],
        ['grade' => 8, 'subject' => 'Mathematics', 'file' => 'Grade%208_Mathematics_Textbook.pdf'],
        ['grade' => 8, 'subject' => 'General Science', 'file' => 'Grade%208_General%20Science_Textbook.pdf'],
        ['grade' => 8, 'subject' => 'Social Studies', 'file' => 'Grade%208_Social%20Study_Textbook.pdf'],
        ['grade' => 8, 'subject' => 'Civics', 'file' => 'Grade%208_Citizenship%20Education_Textbook.pdf'],

        // Grade 9
        ['grade' => 9, 'subject' => 'Amharic', 'file' => 'Grade%209_Amharic_Textbook.pdf'],
        ['grade' => 9, 'subject' => 'English', 'file' => 'Grade%209_English_Textbook.pdf'],
        ['grade' => 9, 'subject' => 'Mathematics', 'file' => 'Grade%209_Mathematics_Textbook.pdf'],
        ['grade' => 9, 'subject' => 'Biology', 'file' => 'Grade%209_Biology_Textbook.pdf'],
        ['grade' => 9, 'subject' => 'Physics', 'file' => 'Grade%209_Physics_Textbook.pdf'],
        ['grade' => 9, 'subject' => 'Chemistry', 'file' => 'Grade%209_Chemistry_Textbook.pdf'],

        // Grade 10
        ['grade' => 10, 'subject' => 'Amharic', 'file' => 'Grade%2010_Amharic_Textbook.pdf'],
        ['grade' => 10, 'subject' => 'English', 'file' => 'Grade%2010_English_Textbook.pdf'],
        ['grade' => 10, 'subject' => 'Mathematics', 'file' => 'Grade%2010_Mathematics_Textbook.pdf'],
        ['grade' => 10, 'subject' => 'Biology', 'file' => 'Grade%2010_Biology_Textbook.pdf'],
        ['grade' => 10, 'subject' => 'Physics', 'file' => 'Grade%2010_Physics_Textbook.pdf'],
        ['grade' => 10, 'subject' => 'Chemistry', 'file' => 'Grade%2010_Chemistry_Textbook.pdf'],

        // Grade 11
        ['grade' => 11, 'subject' => 'Amharic', 'file' => 'Grade%2011_Amharic_Textbook.pdf'],
        ['grade' => 11, 'subject' => 'English', 'file' => 'Grade%2011_English_Textbook.pdf'],
        ['grade' => 11, 'subject' => 'Mathematics', 'file' => 'Grade%2011_Mathematics_Textbook.pdf'],
        ['grade' => 11, 'subject' => 'Biology', 'file' => 'Grade%2011_Biology_Textbook.pdf'],
        ['grade' => 11, 'subject' => 'Physics', 'file' => 'Grade%2011_Physics_Textbook.pdf'],
        ['grade' => 11, 'subject' => 'Chemistry', 'file' => 'Grade%2011_Chemistry_Textbook.pdf'],

        // Grade 12
        ['grade' => 12, 'subject' => 'Amharic', 'file' => 'Grade%2012_Amharic_Textbook.pdf'],
        ['grade' => 12, 'subject' => 'English', 'file' => 'Grade%2012_English_Textbook.pdf'],
        ['grade' => 12, 'subject' => 'Mathematics', 'file' => 'Grade%2012_Mathematics_Textbook.pdf'],
        ['grade' => 12, 'subject' => 'Biology', 'file' => 'Grade%2012_Biology_Textbook.pdf'],
        ['grade' => 12, 'subject' => 'Physics', 'file' => 'Grade%2012_Physics_Textbook.pdf'],
        ['grade' => 12, 'subject' => 'Chemistry', 'file' => 'Grade%2012_Chemistry_Textbook.pdf'],
    ];

    $stmt = $pdo->prepare("INSERT INTO education_resources (grade, subject, type, title, description, file_url, status) VALUES (?, ?, 'textbook', ?, ?, ?, 'active')");

    $count = 0;
    foreach ($seed_data as $data) {
        $full_url = $base_url . "Grade" . $data['grade'] . "/" . $data['file'];
        $title = "Grade " . $data['grade'] . " " . $data['subject'] . " Student Textbook (Direct PDF)";
        $desc = "Official Ethiopian New Curriculum Grade " . $data['grade'] . " " . $data['subject'] . " Student Textbook. High-quality PDF for offline reading.";

        $stmt->execute([
            $data['grade'],
            $data['subject'],
            $title,
            $desc,
            $full_url
        ]);
        $count++;
    }

    echo "✅ Successfully seeded {$count} textbooks with direct PDF links.\n";
    echo "🎉 Done!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
echo "</pre>";
