<?php
require_once 'includes/db.php';

// Prepare directory
$dir = 'uploads/education';
if (!file_exists($dir)) {
    mkdir($dir, 0777, true);
}

// Create a dummy PDF if it doesn't exist
$dummy_pdf = $dir . '/placeholder.pdf';
if (!file_exists($dummy_pdf)) {
    // A more realistic minimal PDF header
    $content = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R >>\nendobj\n4 0 obj\n<< /Length 50 >>\nstream\nBT /F1 24 Tf 100 700 Td (Placeholder Ethiopian Textbook) Tj ET\nendstream\nendobj\nxref\n0 5\n0000000000 65535 f\n0000000009 00000 n\n0000000062 00000 n\n0000000121 00000 n\n0000000216 00000 n\ntrailer\n<< /Size 5 /Root 1 0 R >>\nstartxref\n315\n%%EOF";
    file_put_contents($dummy_pdf, $content);
}

$resources = [
    [
        'title' => 'Grade 1 Amharic Student Textbook',
        'grade' => 1,
        'subject' => 'Amharic',
        'type' => 'textbook',
        'edition' => '2023',
        'pages' => 180,
        'file_path' => 'placeholder.pdf'
    ],
    [
        'title' => 'Grade 1 English Student Textbook',
        'grade' => 1,
        'subject' => 'English',
        'type' => 'textbook',
        'edition' => '2023',
        'pages' => 150,
        'file_path' => 'placeholder.pdf'
    ],
    [
        'title' => 'Grade 1 Mathematics Student Textbook',
        'grade' => 1,
        'subject' => 'Mathematics',
        'type' => 'textbook',
        'edition' => '2023',
        'pages' => 200,
        'file_path' => 'placeholder.pdf'
    ],
    [
        'title' => 'Grade 7 Biology Teacher Guide',
        'grade' => 7,
        'subject' => 'Biology',
        'type' => 'teacher_guide',
        'edition' => '2022',
        'pages' => 220,
        'file_path' => 'placeholder.pdf'
    ]
];

try {
    // Clear existing to avoid duplicates during testing
    $pdo->exec("TRUNCATE TABLE education_resources");

    $stmt = $pdo->prepare("INSERT INTO education_resources (title, grade, subject, type, edition, pages, file_path, status, views, downloads, created_at) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, 'active', 0, 0, NOW())");

    foreach ($resources as $res) {
        $stmt->execute([
            $res['title'],
            $res['grade'],
            $res['subject'],
            $res['type'],
            $res['edition'],
            $res['pages'],
            $res['file_path']
        ]);
    }

    echo "Education resources seeded successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
