<?php
require_once 'includes/fpdf.php';

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'EthioServe Sample Exam Questions', 0, 1, 'C');
$pdf->Ln(10);

$questions = [
    [
        'q' => '1. What does PHP stand for?',
        'a' => 'A) Personal Hypertext Processor',
        'b' => 'B) PHP: Hypertext Preprocessor',
        'c' => 'C) Private Home Page',
        'd' => 'D) Professional HTML Processor',
        'ans' => 'Answer: B'
    ],
    [
        'q' => '2. Which superglobal is used to collect data after submitting an HTML form with method="post"?',
        'a' => 'A) $_GET',
        'b' => 'B) $_REQUEST',
        'c' => 'C) $_POST',
        'd' => 'D) $_SERVER',
        'ans' => 'Answer: C'
    ],
    [
        'q' => '3. How do you start a session in PHP?',
        'a' => 'A) session_start();',
        'b' => 'B) start_session();',
        'c' => 'C) $_SESSION->start();',
        'd' => 'D) session();',
        'ans' => 'Answer: A'
    ],
    [
        'q' => '4. What is the correct way to connect to a MySQL database using PDO?',
        'a' => 'A) new PDO("mysql:host=localhost;dbname=test", "user", "pass");',
        'b' => 'B) mysql_connect("localhost", "user", "pass");',
        'c' => 'C) mysqli_connect("localhost", "user", "pass");',
        'd' => 'D) pdo_connect("localhost", "user", "pass");',
        'ans' => 'Answer: A'
    ],
    [
        'q' => '5. Which function is used to check if a variable is set and is not NULL?',
        'a' => 'A) is_null()',
        'b' => 'B) isset()',
        'c' => 'C) empty()',
        'd' => 'D) check()',
        'ans' => 'Answer: B'
    ],
    [
        'q' => '6. Which operator is used for concatenation in PHP?',
        'a' => 'A) +',
        'b' => 'B) *',
        'c' => 'C) .',
        'd' => 'D) &',
        'ans' => 'Answer: C'
    ],
    [
        'q' => '7. What is the default file extension for PHP files?',
        'a' => 'A) .html',
        'b' => 'B) .xml',
        'c' => 'C) .php',
        'd' => 'D) .ph',
        'ans' => 'Answer: C'
    ],
    [
        'q' => '8. Which loop is specifically used to iterate through arrays in PHP?',
        'a' => 'A) for',
        'b' => 'B) while',
        'c' => 'C) foreach',
        'd' => 'D) do-while',
        'ans' => 'Answer: C'
    ],
    [
        'q' => '9. How do you write a comment in PHP?',
        'a' => 'A) // comment',
        'b' => 'B) <!-- comment -->',
        'c' => 'C) /* comment */',
        'd' => 'D) Both A and C',
        'ans' => 'Answer: D'
    ],
    [
        'q' => '10. What is the result of echo 5 + "5"; in PHP?',
        'a' => 'A) 55',
        'b' => 'B) 10',
        'c' => 'C) Error',
        'd' => 'D) 5',
        'ans' => 'Answer: B'
    ]
];

$pdf->SetFont('Arial', '', 12);
foreach ($questions as $q) {
    if ($pdf->GetY() > 250) $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->MultiCell(0, 8, $q['q']);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 7, $q['a'], 0, 1);
    $pdf->Cell(0, 7, $q['b'], 0, 1);
    $pdf->Cell(0, 7, $q['c'], 0, 1);
    $pdf->Cell(0, 7, $q['d'], 0, 1);
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->Cell(0, 7, $q['ans'], 0, 1);
    $pdf->Ln(5);
}

$pdf->Output('F', 'EthioServe_Sample_Exam.pdf');
echo "PDF Generated successfully: EthioServe_Sample_Exam.pdf";
