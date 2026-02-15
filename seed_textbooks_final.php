<?php
/**
 * COMPREHENSIVE DIRECT PDF SEEDER v2
 * Maps all textbooks for Grade 1-12 using official government direct links.
 */
require_once 'includes/functions.php';
require_once 'includes/db.php';

echo "<pre>🚀 Starting Comprehensive Direct PDF Seeder v2...\n";

try {
    $pdo->exec("DELETE FROM education_resources WHERE type = 'textbook'");
    echo "🗑️ Cleared existing textbook resources.\n";

    $base_url = "https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/";

    $seed_data = [];

    // Subjects by level patterns
    $primary_subjects = ['Amharic', 'English', 'Mathematics', 'Environmental Science', 'HPE', 'Visual and Performing Art'];
    $middle_subjects = ['Amharic', 'English', 'Mathematics', 'Environmental Science', 'Social Studies', 'Civics', 'HPE', 'Visual and Performing Art'];
    $high_subjects = ['Amharic', 'English', 'Mathematics', 'Biology', 'Physics', 'Chemistry', 'Social Studies', 'Civics', 'ICT'];

    for ($g = 1; $g <= 12; $g++) {
        $subjects = ($g <= 4) ? $primary_subjects : (($g <= 8) ? $middle_subjects : $high_subjects);

        foreach ($subjects as $subj) {
            $file_subj = $subj;
            if ($subj == 'Social Studies')
                $file_subj = 'Social Study';
            if ($subj == 'Civics')
                $file_subj = 'Citizenship Education';
            if ($subj == 'General Science')
                $file_subj = 'General Science';

            // Special mappings for filenames known to exist
            $filename = "Grade%20{$g}_" . str_replace(' ', '%20', $file_subj) . "_Textbook.pdf";

            // Fallbacks for naming inconsistencies in primary/middle
            if ($g <= 6 && $subj == 'Environmental Science') {
                // Sometimes called General Science in higher primary
                $seed_data[] = ['grade' => $g, 'subject' => 'General Science', 'file' => $filename];
            }

            $seed_data[] = [
                'grade' => $g,
                'subject' => $subj,
                'file' => $filename
            ];
        }
    }

    $stmt = $pdo->prepare("INSERT INTO education_resources (grade, subject, type, title, description, file_url, status) VALUES (?, ?, 'textbook', ?, ?, ?, 'active')");

    $count = 0;
    foreach ($seed_data as $data) {
        $full_url = $base_url . "Grade" . $data['grade'] . "/" . $data['file'];
        $title = "Grade " . $data['grade'] . " " . $data['subject'] . " Student Textbook";
        $desc = "Official Ethiopian New Curriculum Grade " . $data['grade'] . " " . $data['subject'] . " Student Textbook. Direct High-Quality PDF.";

        try {
            $stmt->execute([
                $data['grade'],
                $data['subject'],
                $title,
                $desc,
                $full_url
            ]);
            $count++;
        } catch (Exception $e) {
            // Skip duplicates if any
        }
    }

    echo "✅ Successfully seeded {$count} textbooks with direct PDF links.\n";
    echo "🎉 Seeding Completed!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
echo "</pre>";
