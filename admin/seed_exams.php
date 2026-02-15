<?php
/**
 * LMS Exam Seeder — Seeds comprehensive exam questions for Grades 1-12
 * Run via: http://localhost/ethioserve/admin/seed_exams.php
 * Force re-seed: http://localhost/ethioserve/admin/seed_exams.php?force=1
 */
require_once '../includes/functions.php';
require_once '../includes/db.php';

set_time_limit(300);

// Create tables
$pdo->exec("CREATE TABLE IF NOT EXISTS lms_exams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grade INT NOT NULL,
    subject VARCHAR(100) NOT NULL,
    chapter INT NOT NULL DEFAULT 1,
    chapter_title VARCHAR(255) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    duration_minutes INT DEFAULT 30,
    pass_percentage INT DEFAULT 50,
    total_questions INT DEFAULT 10,
    difficulty ENUM('easy','medium','hard') DEFAULT 'medium',
    status ENUM('active','draft','archived') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS lms_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    question_text TEXT NOT NULL,
    option_a VARCHAR(500) NOT NULL,
    option_b VARCHAR(500) NOT NULL,
    option_c VARCHAR(500) NOT NULL,
    option_d VARCHAR(500) NOT NULL,
    correct_answer CHAR(1) NOT NULL,
    explanation TEXT,
    points INT DEFAULT 1,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (exam_id) REFERENCES lms_exams(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS lms_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    exam_id INT NOT NULL,
    score DECIMAL(5,2) DEFAULT 0,
    total_points INT DEFAULT 0,
    earned_points INT DEFAULT 0,
    status ENUM('in_progress','completed','abandoned') DEFAULT 'in_progress',
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    time_spent_seconds INT DEFAULT 0,
    FOREIGN KEY (exam_id) REFERENCES lms_exams(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS lms_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attempt_id INT NOT NULL,
    question_id INT NOT NULL,
    selected_answer CHAR(1),
    is_correct TINYINT(1) DEFAULT 0,
    FOREIGN KEY (attempt_id) REFERENCES lms_attempts(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES lms_questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Check existing count
$existing = $pdo->query("SELECT COUNT(*) FROM lms_exams")->fetchColumn();
$force = isset($_GET['force']);

if ($existing > 0 && !$force) {
    echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Exam Seeder</title>
    <link href='https://fonts.googleapis.com/css2?family=Outfit:wght@400;700;900&display=swap' rel='stylesheet'>
    <style>body{font-family:Outfit,sans-serif;background:#0a0f1a;color:#fff;padding:40px;text-align:center;}
    a{color:#6366f1;text-decoration:none;padding:12px 24px;border:1px solid #6366f1;border-radius:50px;display:inline-block;margin:10px;}
    a:hover{background:#6366f1;color:#fff;}</style></head><body>
    <h1>📚 $existing exams already exist</h1>
    <p>Use <code>?force=1</code> to clear and re-seed all exams</p>
    <a href='?force=1'>🔄 Force Re-seed</a>
    <a href='../customer/lms.php'>📖 View LMS</a>
    </body></html>";
    exit;
}

if ($force) {
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
    $pdo->exec("TRUNCATE TABLE lms_answers");
    $pdo->exec("TRUNCATE TABLE lms_attempts");
    $pdo->exec("TRUNCATE TABLE lms_questions");
    $pdo->exec("TRUNCATE TABLE lms_exams");
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
}

// ============================================================================
// COMPREHENSIVE EXAM DATA — Questions for each grade/subject/chapter
// ============================================================================

$exam_data = [];

// =========================================================================
// MATHEMATICS — All Grades
// =========================================================================
$math_questions = [
    1 => [ // Grade 1
        [
            'chapter' => 1,
            'title' => 'Numbers 1-20',
            'difficulty' => 'easy',
            'questions' => [
                ['What comes after 5?', '4', '6', '7', '3', 'B', 'After 5 comes 6 in counting order.'],
                ['How many fingers do you have on one hand?', '4', '10', '5', '3', 'C', 'One hand has 5 fingers.'],
                ['What is 2 + 3?', '4', '6', '5', '7', 'C', '2 + 3 = 5'],
                ['Which number is bigger: 8 or 3?', '3', '8', 'They are equal', 'Neither', 'B', '8 is bigger than 3.'],
                ['What is 10 - 4?', '5', '7', '4', '6', 'D', '10 - 4 = 6'],
            ]
        ],
        [
            'chapter' => 2,
            'title' => 'Addition and Subtraction',
            'difficulty' => 'easy',
            'questions' => [
                ['What is 1 + 1?', '3', '1', '2', '0', 'C', '1 + 1 = 2'],
                ['What is 7 - 3?', '4', '5', '3', '2', 'A', '7 - 3 = 4'],
                ['What is 4 + 5?', '8', '10', '9', '7', 'C', '4 + 5 = 9'],
                ['If you have 6 apples and eat 2, how many are left?', '3', '4', '5', '8', 'B', '6 - 2 = 4 apples remain.'],
                ['What is 3 + 3 + 3?', '6', '12', '9', '10', 'C', '3 + 3 + 3 = 9'],
            ]
        ],
    ],
    2 => [
        [
            'chapter' => 1,
            'title' => 'Numbers up to 100',
            'difficulty' => 'easy',
            'questions' => [
                ['What is 15 + 10?', '20', '25', '30', '35', 'B', '15 + 10 = 25'],
                ['Which number comes after 49?', '48', '40', '50', '59', 'C', 'After 49 comes 50.'],
                ['What is the value of 5 tens?', '5', '50', '500', '55', 'B', '5 tens = 50'],
                ['Count by 2s: 2, 4, 6, __?', '7', '10', '8', '9', 'C', 'Counting by 2: 2, 4, 6, 8'],
                ['What is 50 - 20?', '20', '40', '30', '25', 'C', '50 - 20 = 30'],
            ]
        ],
    ],
    3 => [
        [
            'chapter' => 1,
            'title' => 'Multiplication Basics',
            'difficulty' => 'easy',
            'questions' => [
                ['What is 3 × 4?', '7', '12', '10', '14', 'B', '3 × 4 = 12'],
                ['What is 5 × 2?', '10', '7', '25', '52', 'A', '5 × 2 = 10'],
                ['What is 6 × 3?', '9', '63', '18', '15', 'C', '6 × 3 = 18'],
                ['If each bag has 4 oranges, how many in 3 bags?', '7', '12', '15', '43', 'B', '3 bags × 4 = 12 oranges'],
                ['What is 7 × 1?', '8', '6', '1', '7', 'D', 'Any number × 1 = itself. So 7 × 1 = 7'],
            ]
        ],
    ],
    4 => [
        [
            'chapter' => 1,
            'title' => 'Long Division',
            'difficulty' => 'medium',
            'questions' => [
                ['What is 24 ÷ 6?', '3', '4', '5', '6', 'B', '24 ÷ 6 = 4'],
                ['What is 36 ÷ 9?', '3', '5', '4', '6', 'C', '36 ÷ 9 = 4'],
                ['What is the remainder of 17 ÷ 5?', '1', '3', '2', '4', 'C', '17 ÷ 5 = 3 remainder 2'],
                ['What is 100 ÷ 10?', '1', '100', '10', '1000', 'C', '100 ÷ 10 = 10'],
                ['What is 45 ÷ 5?', '7', '8', '10', '9', 'D', '45 ÷ 5 = 9'],
            ]
        ],
    ],
    5 => [
        [
            'chapter' => 1,
            'title' => 'Fractions',
            'difficulty' => 'medium',
            'questions' => [
                ['What is 1/2 + 1/2?', '2/4', '1', '1/4', '2/2', 'B', '1/2 + 1/2 = 1 (or 2/2 = 1)'],
                ['Which fraction is larger: 1/3 or 1/2?', '1/3', '1/2', 'They are equal', 'Cannot tell', 'B', '1/2 is larger because dividing into 2 parts gives bigger pieces than 3 parts.'],
                ['Simplify 4/8', '2/4', '1/2', '4/8', '1/4', 'B', '4/8 = 1/2 after dividing both by 4.'],
                ['What is 3/4 of 20?', '10', '12', '15', '18', 'C', '3/4 × 20 = 60/4 = 15'],
                ['Convert 1/4 to a percentage', '40%', '50%', '25%', '75%', 'C', '1/4 = 0.25 = 25%'],
            ]
        ],
    ],
    6 => [
        [
            'chapter' => 1,
            'title' => 'Decimals and Percentages',
            'difficulty' => 'medium',
            'questions' => [
                ['What is 0.5 + 0.25?', '0.30', '0.75', '0.52', '0.70', 'B', '0.5 + 0.25 = 0.75'],
                ['Convert 75% to a decimal', '7.5', '0.075', '0.75', '75.0', 'C', '75% = 75/100 = 0.75'],
                ['What is 10% of 200?', '10', '20', '2', '100', 'B', '10% × 200 = 20'],
                ['What is 3.14 rounded to the nearest whole number?', '3', '4', '3.1', '3.2', 'A', '3.14 rounds down to 3.'],
                ['Which is greater: 0.6 or 0.55?', '0.55', '0.6', 'They are equal', 'Cannot tell', 'B', '0.6 = 0.60, which is greater than 0.55.'],
            ]
        ],
    ],
    7 => [
        [
            'chapter' => 1,
            'title' => 'Algebra - Linear Equations',
            'difficulty' => 'medium',
            'questions' => [
                ['Solve: x + 5 = 12', 'x = 5', 'x = 17', 'x = 7', 'x = 6', 'C', 'x = 12 - 5 = 7'],
                ['Solve: 2x = 10', 'x = 20', 'x = 5', 'x = 8', 'x = 12', 'B', 'x = 10/2 = 5'],
                ['What is the value of 3(x+2) when x=4?', '14', '18', '12', '10', 'B', '3(4+2) = 3×6 = 18'],
                ['Simplify: 4x + 3x', '12x', '7x', '43x', '7x²', 'B', '4x + 3x = 7x (combine like terms)'],
                ['If 3x - 6 = 9, what is x?', '1', '3', '5', '15', 'C', '3x = 15, x = 5'],
            ]
        ],
    ],
    8 => [
        [
            'chapter' => 1,
            'title' => 'Geometry - Shapes & Angles',
            'difficulty' => 'medium',
            'questions' => [
                ['How many degrees in a full circle?', '180°', '90°', '360°', '270°', 'C', 'A full circle has 360 degrees.'],
                ['What is the sum of angles in a triangle?', '360°', '90°', '270°', '180°', 'D', 'The sum of interior angles in a triangle is always 180°.'],
                ['A right angle measures how many degrees?', '45°', '90°', '180°', '60°', 'B', 'A right angle is exactly 90 degrees.'],
                ['How many sides does a hexagon have?', '5', '7', '6', '8', 'C', 'A hexagon has 6 sides.'],
                ['What is the area of a rectangle with length 8 and width 5?', '13', '40', '26', '45', 'B', 'Area = length × width = 8 × 5 = 40'],
            ]
        ],
    ],
    9 => [
        [
            'chapter' => 1,
            'title' => 'Quadratic Equations',
            'difficulty' => 'hard',
            'questions' => [
                ['What is the standard form of a quadratic equation?', 'y = mx + b', 'ax² + bx + c = 0', 'a/b = c/d', 'y = kx', 'B', 'The standard form is ax² + bx + c = 0 where a ≠ 0.'],
                ['Solve: x² = 25', 'x = 5', 'x = -5', 'x = 5 or x = -5', 'x = 25', 'C', '√25 = ±5, so x = 5 or x = -5.'],
                ['What is the discriminant of ax² + bx + c?', 'a² - 4bc', 'b² - 4ac', '4ac - b²', 'b² + 4ac', 'B', 'The discriminant is b² - 4ac.'],
                ['If discriminant > 0, how many real solutions?', '0', '1', '2', '3', 'C', 'If b² - 4ac > 0, there are 2 distinct real solutions.'],
                ['Factor: x² - 9', '(x-3)(x-3)', '(x+3)(x-3)', '(x+9)(x-1)', '(x-3)(x+9)', 'B', 'x² - 9 is a difference of squares: (x+3)(x-3)'],
            ]
        ],
    ],
    10 => [
        [
            'chapter' => 1,
            'title' => 'Trigonometry',
            'difficulty' => 'hard',
            'questions' => [
                ['What is sin(30°)?', '1', '√3/2', '1/2', '√2/2', 'C', 'sin(30°) = 1/2 is a standard trigonometric value.'],
                ['What is the Pythagorean theorem?', 'a + b = c', 'a² + b² = c²', 'a × b = c', 'a/b = c', 'B', 'For a right triangle: a² + b² = c² where c is the hypotenuse.'],
                ['cos(0°) equals?', '0', '-1', '1', '1/2', 'C', 'cos(0°) = 1'],
                ['In a right triangle, the longest side is called?', 'Base', 'Height', 'Hypotenuse', 'Perpendicular', 'C', 'The hypotenuse is the longest side, opposite the right angle.'],
                ['What is tan(45°)?', '0', '√2', '1', '1/2', 'C', 'tan(45°) = sin(45°)/cos(45°) = 1'],
            ]
        ],
    ],
    11 => [
        [
            'chapter' => 1,
            'title' => 'Calculus - Limits & Derivatives',
            'difficulty' => 'hard',
            'questions' => [
                ['What is the derivative of x²?', 'x', '2x', 'x²', '2x²', 'B', 'Using the power rule: d/dx(x²) = 2x.'],
                ['What is the limit of 1/x as x → ∞?', '∞', '1', '0', '-1', 'C', 'As x gets larger, 1/x approaches 0.'],
                ['Derivative of a constant is?', '1', 'The constant itself', '∞', '0', 'D', 'The derivative of any constant is 0.'],
                ['What is d/dx(3x³)?', '3x²', '9x²', '9x³', 'x³', 'B', 'd/dx(3x³) = 3·3x² = 9x²'],
                ['What does the integral of v(t) represent?', 'Acceleration', 'Velocity', 'Displacement', 'Force', 'C', 'The integral of velocity gives displacement.'],
            ]
        ],
    ],
    12 => [
        [
            'chapter' => 1,
            'title' => 'Probability & Statistics',
            'difficulty' => 'hard',
            'questions' => [
                ['A fair coin is flipped. P(heads) is?', '1', '1/3', '0', '1/2', 'D', 'A fair coin has equal probability: P(H) = 1/2.'],
                ['The mean of 2, 4, 6 is?', '3', '4', '12', '6', 'B', 'Mean = (2+4+6)/3 = 12/3 = 4'],
                ['What is the mode of: 3, 5, 3, 7, 3, 9?', '3', '5', '7', '9', 'A', 'Mode is the most frequent value. 3 appears 3 times.'],
                ['Standard deviation measures what?', 'Central tendency', 'Spread of data', 'Probability', 'Frequency', 'B', 'Standard deviation measures how spread out data is from the mean.'],
                ['Rolling a die, P(even number) is?', '1/6', '1/3', '1/2', '2/3', 'C', 'Even numbers: 2,4,6 → 3 out of 6 = 1/2'],
            ]
        ],
    ],
];

// =========================================================================
// ENGLISH — All Grades
// =========================================================================
$english_questions = [
    1 => [
        [
            'chapter' => 1,
            'title' => 'Alphabet & Phonics',
            'difficulty' => 'easy',
            'questions' => [
                ['How many letters are in the English alphabet?', '24', '25', '26', '27', 'C', 'The English alphabet has 26 letters (A-Z).'],
                ['Which is a vowel?', 'B', 'C', 'A', 'D', 'C', 'A, E, I, O, U are vowels. A is a vowel.'],
                ['What sound does "C" make in "cat"?', 'S', 'K', 'Ch', 'Sh', 'B', '"C" makes a hard "K" sound in "cat".'],
                ['Which word rhymes with "bat"?', 'Ball', 'Cat', 'Dog', 'Sun', 'B', '"Bat" and "cat" both end with "-at".'],
                ['Complete: The ___ is red.', 'car', 'run', 'big', 'happy', 'A', '"The car is red." is a complete sentence with a noun.'],
            ]
        ],
    ],
    5 => [
        [
            'chapter' => 1,
            'title' => 'Parts of Speech',
            'difficulty' => 'medium',
            'questions' => [
                ['Which is a noun?', 'Run', 'Beautiful', 'Table', 'Quickly', 'C', 'A noun is a person, place, or thing. "Table" is a thing.'],
                ['Identify the verb: "She sings beautifully."', 'She', 'sings', 'beautifully', 'None', 'B', '"Sings" is the action word (verb) in the sentence.'],
                ['What is an adjective?', 'A doing word', 'A naming word', 'A describing word', 'A joining word', 'C', 'An adjective describes a noun (e.g., big, red, happy).'],
                ['"He ran quickly." — "quickly" is a(n)?', 'Noun', 'Verb', 'Adjective', 'Adverb', 'D', 'An adverb modifies a verb. "Quickly" tells how he ran.'],
                ['Which is a conjunction?', 'and', 'big', 'run', 'the', 'A', '"And" joins words or sentences. It is a conjunction.'],
            ]
        ],
    ],
    9 => [
        [
            'chapter' => 1,
            'title' => 'Essay Writing & Grammar',
            'difficulty' => 'medium',
            'questions' => [
                ['What is a thesis statement?', 'The first sentence', 'The conclusion', 'The main argument of an essay', 'A quote', 'C', 'A thesis statement clearly states the main argument.'],
                ['Which sentence is in passive voice?', 'The cat caught the mouse.', 'She wrote a letter.', 'The letter was written by her.', 'They play football.', 'C', '"Was written by her" = passive. The subject receives the action.'],
                ['"Their, There, They\'re" — Which means "belonging to them"?', 'There', 'Their', "They're", 'All of them', 'B', '"Their" is possessive (belonging to them).'],
                ['A paragraph should have a?', 'Title', 'Topic sentence', 'Bibliography', 'Footnote', 'B', 'Every paragraph needs a topic sentence that states the main idea.'],
                ['What does "revise" mean in writing?', 'Delete everything', 'Read once', 'Review and improve', 'Submit', 'C', 'Revising means reviewing and improving your writing.'],
            ]
        ],
    ],
];

// =========================================================================
// SCIENCE SUBJECTS
// =========================================================================
$science_questions = [
    'Environmental Science' => [
        1 => [
            [
                'chapter' => 1,
                'title' => 'Living & Non-Living Things',
                'difficulty' => 'easy',
                'questions' => [
                    ['Which is a living thing?', 'Rock', 'Water', 'Tree', 'Chair', 'C', 'Trees grow, breathe, and reproduce — they are living things.'],
                    ['What do plants need to grow?', 'Darkness', 'Water and sunlight', 'Ice', 'Rocks', 'B', 'Plants need water, sunlight, soil, and air to grow.'],
                    ['Which animal lives in water?', 'Cat', 'Fish', 'Dog', 'Hen', 'B', 'Fish live in water — they breathe through gills.'],
                    ['What season is hottest?', 'Winter', 'Spring', 'Summer', 'Autumn', 'C', 'Summer is the hottest season of the year.'],
                    ['Trees give us?', 'Fire', 'Oxygen', 'Salt', 'Metal', 'B', 'Trees produce oxygen through photosynthesis.'],
                ]
            ],
        ],
    ],
    'Biology' => [
        7 => [
            [
                'chapter' => 1,
                'title' => 'Cell Biology',
                'difficulty' => 'medium',
                'questions' => [
                    ['What is the basic unit of life?', 'Atom', 'Molecule', 'Cell', 'Organ', 'C', 'The cell is the basic structural and functional unit of life.'],
                    ['Which organelle is the "powerhouse" of the cell?', 'Nucleus', 'Ribosome', 'Mitochondria', 'Golgi body', 'C', 'Mitochondria produce energy (ATP) for the cell.'],
                    ['Plant cells have ___ that animal cells don\'t.', 'Nucleus', 'Cell wall', 'Cytoplasm', 'Ribosomes', 'B', 'Plant cells have a rigid cell wall; animal cells do not.'],
                    ['DNA is found in the?', 'Cell wall', 'Nucleus', 'Cytoplasm', 'Membrane', 'B', 'DNA is stored in the nucleus of the cell.'],
                    ['Which organelle makes proteins?', 'Lysosome', 'Ribosome', 'Vacuole', 'Nucleus', 'B', 'Ribosomes are responsible for protein synthesis.'],
                ]
            ],
        ],
        9 => [
            [
                'chapter' => 1,
                'title' => 'Genetics & Heredity',
                'difficulty' => 'hard',
                'questions' => [
                    ['Who is the father of genetics?', 'Darwin', 'Mendel', 'Watson', 'Linnaeus', 'B', 'Gregor Mendel is known as the father of genetics.'],
                    ['DNA stands for?', 'Deoxyribose Nucleic Acid', 'Deoxyribonucleic Acid', 'Dinitro Nucleotide Acid', 'Dinucleic Acid', 'B', 'DNA = Deoxyribonucleic Acid'],
                    ['How many chromosomes do humans have?', '23', '44', '46', '48', 'C', 'Humans have 46 chromosomes (23 pairs).'],
                    ['A dominant trait is represented by?', 'Lowercase letter', 'Capital letter', 'Number', 'Symbol', 'B', 'Dominant alleles are shown with capital letters (e.g., B).'],
                    ['Genotype refers to?', 'Physical appearance', 'Genetic makeup', 'Blood type only', 'Eye color only', 'B', 'Genotype is the genetic composition (e.g., Bb, BB).'],
                ]
            ],
        ],
    ],
    'Physics' => [
        7 => [
            [
                'chapter' => 1,
                'title' => 'Force & Motion',
                'difficulty' => 'medium',
                'questions' => [
                    ['What is the SI unit of force?', 'Watt', 'Joule', 'Newton', 'Pascal', 'C', 'The SI unit of force is the Newton (N).'],
                    ['Speed is measured in?', 'kg', 'm/s', 'liters', 'm²', 'B', 'Speed = distance/time, measured in meters per second (m/s).'],
                    ['Gravity pulls objects towards?', 'Sky', 'Sideways', 'Earth\'s center', 'Space', 'C', 'Gravity pulls everything towards the center of Earth.'],
                    ['Newton\'s first law is about?', 'Force', 'Inertia', 'Energy', 'Gravity', 'B', 'Newton\'s first law (law of inertia) states objects stay at rest unless acted upon.'],
                    ['Which has more inertia?', 'Feather', 'Truck', 'Paper', 'Balloon', 'B', 'More massive objects have more inertia. A truck is most massive.'],
                ]
            ],
        ],
        9 => [
            [
                'chapter' => 1,
                'title' => 'Electricity & Magnetism',
                'difficulty' => 'hard',
                'questions' => [
                    ['Ohm\'s law states V = ?', 'I/R', 'I × R', 'R/I', 'I + R', 'B', 'Ohm\'s Law: Voltage = Current × Resistance (V = IR)'],
                    ['The SI unit of electric current is?', 'Volt', 'Ohm', 'Ampere', 'Watt', 'C', 'Current is measured in Amperes (A).'],
                    ['Like poles of a magnet will?', 'Attract', 'Repel', 'Do nothing', 'Stick together', 'B', 'Like poles (N-N or S-S) repel each other.'],
                    ['What material is a good conductor?', 'Wood', 'Rubber', 'Copper', 'Glass', 'C', 'Copper is an excellent electrical conductor.'],
                    ['Power (P) = ?', 'V × I', 'V + I', 'V / I', 'V - I', 'A', 'Electrical power P = Voltage × Current'],
                ]
            ],
        ],
    ],
    'Chemistry' => [
        7 => [
            [
                'chapter' => 1,
                'title' => 'Matter & Its Properties',
                'difficulty' => 'medium',
                'questions' => [
                    ['What are the 3 states of matter?', 'Hot, cold, warm', 'Solid, liquid, gas', 'Earth, water, fire', 'Hard, soft, flexible', 'B', 'Matter exists as solid, liquid, or gas.'],
                    ['Water boils at what temperature?', '50°C', '100°C', '200°C', '0°C', 'B', 'Water boils at 100°C (212°F) at sea level.'],
                    ['The chemical formula for water is?', 'CO2', 'NaCl', 'H2O', 'O2', 'C', 'Water is H2O — 2 hydrogen atoms and 1 oxygen atom.'],
                    ['An atom consists of?', 'Only protons', 'Protons, neutrons, electrons', 'Only electrons', 'Only neutrons', 'B', 'Atoms have protons and neutrons in the nucleus, with electrons orbiting.'],
                    ['Which is a chemical change?', 'Cutting paper', 'Melting ice', 'Burning wood', 'Breaking glass', 'C', 'Burning creates new substances — it is a chemical change.'],
                ]
            ],
        ],
        9 => [
            [
                'chapter' => 1,
                'title' => 'The Periodic Table',
                'difficulty' => 'hard',
                'questions' => [
                    ['Who created the periodic table?', 'Newton', 'Einstein', 'Mendeleev', 'Bohr', 'C', 'Dmitri Mendeleev created the first periodic table in 1869.'],
                    ['How many elements are in the periodic table?', '100', '118', '92', '108', 'B', 'There are currently 118 confirmed elements.'],
                    ['Elements in the same column are called?', 'Periods', 'Groups', 'Rows', 'Series', 'B', 'Vertical columns are called groups or families.'],
                    ['What is the atomic number of Carbon?', '12', '14', '6', '8', 'C', 'Carbon has atomic number 6 (6 protons).'],
                    ['Noble gases are in group?', '1', '7', '17', '18', 'D', 'Noble gases (He, Ne, Ar, etc.) are in Group 18.'],
                ]
            ],
        ],
    ],
    'General Science' => [
        5 => [
            [
                'chapter' => 1,
                'title' => 'The Human Body',
                'difficulty' => 'medium',
                'questions' => [
                    ['How many bones does an adult human have?', '106', '206', '306', '150', 'B', 'An adult human skeleton has 206 bones.'],
                    ['Which organ pumps blood?', 'Brain', 'Lungs', 'Heart', 'Kidney', 'C', 'The heart pumps blood throughout the body.'],
                    ['The largest organ in the human body is?', 'Liver', 'Brain', 'Heart', 'Skin', 'D', 'Skin is the largest organ by surface area.'],
                    ['Blood is filtered by the?', 'Heart', 'Lungs', 'Kidneys', 'Stomach', 'C', 'Kidneys filter blood and produce urine.'],
                    ['Oxygen is carried by?', 'White blood cells', 'Red blood cells', 'Platelets', 'Plasma', 'B', 'Red blood cells contain hemoglobin which carries oxygen.'],
                ]
            ],
        ],
    ],
];

// =========================================================================
// SOCIAL SUBJECTS
// =========================================================================
$social_questions = [
    'History' => [
        7 => [
            [
                'chapter' => 1,
                'title' => 'Ancient Ethiopian History',
                'difficulty' => 'medium',
                'questions' => [
                    ['The Aksumite Kingdom was located in?', 'West Africa', 'Northern Ethiopia', 'Southern Africa', 'Central Asia', 'B', 'The Aksumite Kingdom was in northern Ethiopia and Eritrea.'],
                    ['The Aksumite obelisks are found in?', 'Lalibela', 'Axum', 'Gondar', 'Harar', 'B', 'The famous obelisks (stelae) are in Axum.'],
                    ['King Ezana was known for?', 'Building pyramids', 'Converting to Christianity', 'Inventing writing', 'Exploring space', 'B', 'King Ezana made Christianity the state religion of Aksum.'],
                    ['Ethiopia\'s ancient script is called?', 'Arabic', 'Latin', 'Ge\'ez', 'Greek', 'C', 'Ge\'ez is the ancient Ethiopian script, still used in the church.'],
                    ['The rock-hewn churches of Lalibela were built by?', 'King Lalibela', 'King Menelik', 'King Tewodros', 'Emperor Haile Selassie', 'A', 'King Lalibela ordered the construction of the 11 rock-hewn churches.'],
                ]
            ],
        ],
        9 => [
            [
                'chapter' => 1,
                'title' => 'Modern Ethiopian History',
                'difficulty' => 'medium',
                'questions' => [
                    ['The Battle of Adwa was fought in?', '1886', '1896', '1906', '1916', 'B', 'The Battle of Adwa was fought on March 1, 1896.'],
                    ['Ethiopia defeated which country at Adwa?', 'Britain', 'France', 'Italy', 'Germany', 'C', 'Ethiopia defeated Italy at the Battle of Adwa.'],
                    ['Who led Ethiopia at the Battle of Adwa?', 'Haile Selassie', 'Menelik II', 'Tewodros II', 'Yohannes IV', 'B', 'Emperor Menelik II led Ethiopia to victory at Adwa.'],
                    ['Ethiopia was the only African country that was never?', 'Visited', 'Colonized', 'Traded with', 'Named', 'B', 'Ethiopia was never colonized (briefly occupied by Italy 1936-41).'],
                    ['The Ethiopian calendar has how many months?', '12', '14', '13', '10', 'C', 'The Ethiopian calendar has 13 months (12 months of 30 days + Pagume).'],
                ]
            ],
        ],
    ],
    'Geography' => [
        7 => [
            [
                'chapter' => 1,
                'title' => 'Ethiopian Geography',
                'difficulty' => 'medium',
                'questions' => [
                    ['What is the capital of Ethiopia?', 'Adama', 'Bahir Dar', 'Addis Ababa', 'Hawassa', 'C', 'Addis Ababa is the capital and largest city of Ethiopia.'],
                    ['The highest mountain in Ethiopia is?', 'Mount Kenya', 'Ras Dashen', 'Kilimanjaro', 'Mount Entoto', 'B', 'Ras Dashen (4,550m) is Ethiopia\'s highest peak.'],
                    ['Ethiopia is located in which continent?', 'Asia', 'Europe', 'Africa', 'South America', 'C', 'Ethiopia is in East Africa, in the Horn of Africa.'],
                    ['The Blue Nile originates from?', 'Lake Victoria', 'Lake Tana', 'Red Sea', 'Lake Turkana', 'B', 'The Blue Nile originates from Lake Tana in northern Ethiopia.'],
                    ['How many regional states does Ethiopia have?', '9', '10', '11', '12', 'C', 'Ethiopia currently has 11 regional states and 2 chartered cities.'],
                ]
            ],
        ],
    ],
    'Civics' => [
        5 => [
            [
                'chapter' => 1,
                'title' => 'Rights & Responsibilities',
                'difficulty' => 'easy',
                'questions' => [
                    ['Every child has the right to?', 'Drive a car', 'Education', 'Work in a factory', 'Vote', 'B', 'Every child has the right to free education.'],
                    ['Being a good citizen means?', 'Breaking rules', 'Respecting others', 'Being selfish', 'Ignoring laws', 'B', 'Good citizens respect others and follow rules.'],
                    ['Who makes laws in Ethiopia?', 'Teachers', 'Parliament', 'Students', 'Police', 'B', 'The Parliament (House of Peoples\' Representatives) makes laws.'],
                    ['The Ethiopian flag colors are?', 'Red, blue, white', 'Green, yellow, red', 'Black, red, gold', 'Blue, white, green', 'B', 'The Ethiopian flag is green, yellow, and red.'],
                    ['Democracy means?', 'Rule by one person', 'Rule by the military', 'Rule by the people', 'No rules at all', 'C', 'Democracy = government by the people.'],
                ]
            ],
        ],
    ],
    'Economics' => [
        9 => [
            [
                'chapter' => 1,
                'title' => 'Basic Economic Concepts',
                'difficulty' => 'medium',
                'questions' => [
                    ['What does "scarcity" mean?', 'Having too much', 'Limited resources with unlimited wants', 'Free goods', 'No demand', 'B', 'Scarcity means resources are limited relative to our unlimited wants.'],
                    ['The term "GDP" stands for?', 'General Domestic Price', 'Gross Domestic Product', 'Government Development Plan', 'General Development Policy', 'B', 'GDP = Gross Domestic Product, the total value of goods produced.'],
                    ['Supply and demand determine?', 'Weight', 'Temperature', 'Price', 'Distance', 'C', 'The interaction of supply and demand determines market price.'],
                    ['What is inflation?', 'Decrease in prices', 'Increase in general price level', 'Stable prices', 'Free goods', 'B', 'Inflation is a sustained increase in the general price level.'],
                    ['Ethiopia\'s currency is called?', 'Dollar', 'Birr', 'Shilling', 'Pound', 'B', 'The Ethiopian currency is the Birr (ETB).'],
                ]
            ],
        ],
    ],
    'ICT' => [
        9 => [
            [
                'chapter' => 1,
                'title' => 'Computer Basics',
                'difficulty' => 'medium',
                'questions' => [
                    ['CPU stands for?', 'Central Processing Unit', 'Computer Personal Unit', 'Central Program Utility', 'Core Processing Unit', 'A', 'CPU = Central Processing Unit, the brain of the computer.'],
                    ['Which is an input device?', 'Monitor', 'Printer', 'Keyboard', 'Speaker', 'C', 'A keyboard is used to input data into the computer.'],
                    ['1 kilobyte (KB) = ?', '100 bytes', '1000 bytes', '1024 bytes', '500 bytes', 'C', '1 KB = 1024 bytes (2^10 bytes).'],
                    ['RAM stands for?', 'Read Access Memory', 'Random Access Memory', 'Run Active Memory', 'Readable Active Mode', 'B', 'RAM = Random Access Memory, temporary fast storage.'],
                    ['Which is an operating system?', 'Microsoft Word', 'Google Chrome', 'Windows', 'PowerPoint', 'C', 'Windows is an operating system that manages computer hardware.'],
                ]
            ],
        ],
    ],
    'Amharic' => [
        1 => [
            [
                'chapter' => 1,
                'title' => 'ፊደላት (Letters)',
                'difficulty' => 'easy',
                'questions' => [
                    ['The Amharic alphabet is called?', 'Arabic', 'Latin', 'Fidel (ፊደል)', 'Greek', 'C', 'The Amharic writing system is called Fidel (ፊደል).'],
                    ['How many base characters (ፊደሎች) are in the Amharic alphabet?', '26', '33', '30', '28', 'B', 'There are 33 base characters with 7 forms each.'],
                    ['Which is the first letter of the Amharic alphabet?', 'ሀ', 'ለ', 'ሠ', 'በ', 'A', 'ሀ (Ha) is the first letter of the Amharic alphabet.'],
                    ['"እናት" means?', 'Father', 'Mother', 'Brother', 'Sister', 'B', 'እናት means mother in Amharic.'],
                    ['"ውሃ" means?', 'Food', 'Fire', 'Water', 'Air', 'C', 'ውሃ means water in Amharic.'],
                ]
            ],
        ],
    ],
];

// =========================================================================
// SEEDING LOGIC
// =========================================================================
$stats = ['exams' => 0, 'questions' => 0, 'errors' => 0];

function seedExams($pdo, $subject, $grade_data, &$stats)
{
    foreach ($grade_data as $grade => $chapters) {
        foreach ($chapters as $ch) {
            try {
                $exam_stmt = $pdo->prepare("INSERT INTO lms_exams 
                    (grade, subject, chapter, chapter_title, title, description, duration_minutes, pass_percentage, total_questions, difficulty) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                $title = "Grade {$grade} {$subject} — Chapter {$ch['chapter']}: {$ch['title']}";
                $desc = "Test your knowledge of {$ch['title']} from Grade {$grade} {$subject}.";
                $q_count = count($ch['questions']);
                $duration = max(10, $q_count * 3);

                $exam_stmt->execute([
                    $grade,
                    $subject,
                    $ch['chapter'],
                    $ch['title'],
                    $title,
                    $desc,
                    $duration,
                    50,
                    $q_count,
                    $ch['difficulty']
                ]);
                $exam_id = $pdo->lastInsertId();
                $stats['exams']++;

                // Insert questions
                $q_stmt = $pdo->prepare("INSERT INTO lms_questions 
                    (exam_id, question_text, option_a, option_b, option_c, option_d, correct_answer, explanation, sort_order) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

                foreach ($ch['questions'] as $idx => $q) {
                    $q_stmt->execute([$exam_id, $q[0], $q[1], $q[2], $q[3], $q[4], $q[5], $q[6] ?? '', $idx + 1]);
                    $stats['questions']++;
                }
            } catch (Exception $e) {
                $stats['errors']++;
            }
        }
    }
}

// Seed Mathematics
seedExams($pdo, 'Mathematics', $math_questions, $stats);

// Seed English (fill remaining grades with grade 1 and 9 data patterns)
$english_all = [];
for ($g = 1; $g <= 12; $g++) {
    if (isset($english_questions[$g])) {
        $english_all[$g] = $english_questions[$g];
    } elseif ($g <= 4) {
        $english_all[$g] = $english_questions[1]; // use grade 1 pattern
    } elseif ($g <= 8) {
        $english_all[$g] = $english_questions[5];
    } else {
        $english_all[$g] = $english_questions[9];
    }
}
seedExams($pdo, 'English', $english_all, $stats);

// Seed Science subjects
foreach ($science_questions as $subject => $grade_data) {
    seedExams($pdo, $subject, $grade_data, $stats);
}

// Seed Social subjects
foreach ($social_questions as $subject => $grade_data) {
    seedExams($pdo, $subject, $grade_data, $stats);
}

// Done - output report
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>LMS Exam Seeder — Results</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: #0a0f1a;
            color: #e5e7eb;
            margin: 0;
            padding: 40px 20px;
        }

        .container {
            max-width: 700px;
            margin: 0 auto;
        }

        .hero {
            text-align: center;
            margin-bottom: 40px;
        }

        .hero h1 {
            color: #fff;
            font-weight: 900;
            font-size: 2rem;
            margin-bottom: 8px;
        }

        .hero p {
            color: #6b7280;
            font-size: .9rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #1a2332;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 24px;
            text-align: center;
        }

        .stat-val {
            font-size: 2.5rem;
            font-weight: 900;
            color: #fff;
            line-height: 1;
        }

        .stat-label {
            color: #6b7280;
            font-size: .75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 6px;
        }

        .stat-val.success {
            color: #10b981;
        }

        .stat-val.accent {
            color: #6366f1;
        }

        .stat-val.danger {
            color: #ef4444;
        }

        .actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 28px;
            border-radius: 50px;
            font-weight: 700;
            font-size: .85rem;
            text-decoration: none;
            transition: .3s;
        }

        .btn-accent {
            background: #6366f1;
            color: #fff;
        }

        .btn-accent:hover {
            background: #4f46e5;
        }

        .btn-outline {
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #fff;
        }

        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.1);
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="hero">
            <h1>✅ LMS Exam Seeder Complete!</h1>
            <p>
                <?php echo $force ? 'Database cleared and re-seeded successfully' : 'Exams seeded successfully'; ?>
            </p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-val accent">
                    <?php echo $stats['exams']; ?>
                </div>
                <div class="stat-label">Exams Created</div>
            </div>
            <div class="stat-card">
                <div class="stat-val success">
                    <?php echo $stats['questions']; ?>
                </div>
                <div class="stat-label">Questions Added</div>
            </div>
            <div class="stat-card">
                <div class="stat-val danger">
                    <?php echo $stats['errors']; ?>
                </div>
                <div class="stat-label">Errors</div>
            </div>
        </div>

        <div class="actions">
            <a href="../customer/lms.php" class="btn btn-accent"><i class="fas fa-graduation-cap"></i> View LMS</a>
            <a href="?force=1" class="btn btn-outline"><i class="fas fa-redo"></i> Re-seed</a>
            <a href="../customer/education.php" class="btn btn-outline"><i class="fas fa-book"></i> Education Portal</a>
        </div>
    </div>
</body>

</html>