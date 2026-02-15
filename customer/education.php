<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';
$flash = getFlashMessage();

// Check student grade if logged in
if (isLoggedIn() && !isset($_GET['grade'])) {
    $uid = $_SESSION['user_id'];
    try {
        $stmt = $pdo->prepare("SELECT grade FROM users WHERE id = ?");
        $stmt->execute([$uid]);
        $g = $stmt->fetchColumn();
        if ($g > 0) {
            header("Location: education.php?grade=" . $g);
            exit();
        }
    } catch (Exception $e) {
        // Column might not exist yet
    }
}

// Selected grade
$grade = isset($_GET['grade']) ? (int) $_GET['grade'] : 0;
$tab = sanitize($_GET['tab'] ?? 'textbooks');

// Ethiopian curriculum data
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

$subject_icons = [
    'Amharic' => 'language',
    'English' => 'book-open',
    'Mathematics' => 'calculator',
    'Environmental Science' => 'leaf',
    'Afan Oromo' => 'globe-africa',
    'Social Studies' => 'users',
    'Civics' => 'landmark',
    'General Science' => 'flask',
    'Biology' => 'dna',
    'Physics' => 'atom',
    'Chemistry' => 'vial',
    'Geography' => 'globe-americas',
    'History' => 'monument',
    'Economics' => 'chart-line',
    'ICT' => 'laptop-code'
];
$subject_colors = [
    'Amharic' => '#E65100',
    'English' => '#1565C0',
    'Mathematics' => '#2E7D32',
    'Environmental Science' => '#00897B',
    'Afan Oromo' => '#6A1B9A',
    'Social Studies' => '#AD1457',
    'Civics' => '#4527A0',
    'General Science' => '#00838F',
    'Biology' => '#1B5E20',
    'Physics' => '#0D47A1',
    'Chemistry' => '#B71C1C',
    'Geography' => '#E65100',
    'History' => '#5D4037',
    'Economics' => '#F57F17',
    'ICT' => '#263238'
];

// Video resources per subject
$video_links = [
    'Mathematics' => ['title' => 'Math Made Easy', 'id' => 'pTnEG_WGd2Q'],
    'English' => ['title' => 'English Grammar Basics', 'id' => 'Qf1Kj0EhKdE'],
    'Physics' => ['title' => 'Physics Fundamentals', 'id' => 'ZM8ECi_piNU'],
    'Chemistry' => ['title' => 'Chemistry Basics', 'id' => 'FSyAehMdpyI'],
    'Biology' => ['title' => 'Biology Introduction', 'id' => 'QnQe0xW_JY4'],
    'Amharic' => ['title' => 'Amharic Fidel', 'id' => 'wk3v4GkxbPo'],
];


include('../includes/header.php');
?>

<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
<style>
    :root {
        --edu-primary: #1565C0;
        --edu-dark: #1B5E20;
        /* Matching EthioServe Green */
        --edu-accent: #FFB300;
    }

    body {
        font-family: 'Outfit', sans-serif;
        background: #f8fafc;
    }

    .edu-hero {
        background: linear-gradient(135deg, #1B5E20 0%, #2E7D32 100%);
        padding: 80px 0 120px;
        color: #fff;
        text-align: center;
        border-bottom-left-radius: 60px;
        border-bottom-right-radius: 60px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        margin-bottom: -60px;
    }

    .edu-hero h1 {
        font-size: 3.5rem;
        font-weight: 800;
        margin-bottom: 20px;
    }

    .grade-scroll-wrap {
        background: #fff;
        border-radius: 24px;
        padding: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        margin-bottom: 40px;
    }

    .grade-scroll {
        display: flex;
        gap: 12px;
        overflow-x: auto;
        padding: 5px;
        scrollbar-width: thin;
        scrollbar-color: var(--edu-primary) transparent;
    }

    .grade-btn {
        min-width: 100px;
        padding: 15px;
        border-radius: 20px;
        background: #f1f5f9;
        text-decoration: none;
        color: #475569;
        font-weight: 700;
        transition: all 0.3s;
        text-align: center;
        border: 2px solid transparent;
        flex-shrink: 0;
    }

    .grade-btn:hover {
        background: #e2e8f0;
        color: var(--edu-primary);
        transform: translateY(-3px);
    }

    .grade-btn.active {
        background: var(--edu-primary);
        color: #fff;
        box-shadow: 0 8px 20px rgba(21, 101, 192, 0.2);
    }

    .tab-pills-custom {
        display: inline-flex;
        background: #fff;
        padding: 8px;
        border-radius: 50px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        margin-bottom: 40px;
    }

    .tab-pill-custom {
        padding: 12px 30px;
        border-radius: 50px;
        text-decoration: none;
        color: #64748b;
        font-weight: 600;
        transition: 0.3s;
    }

    .tab-pill-custom.active {
        background: var(--edu-primary);
        color: #fff;
    }

    .subject-card-new {
        background: #fff;
        border-radius: 24px;
        padding: 30px;
        border: 1px solid #f1f5f9;
        transition: all 0.3s;
        height: 100%;
        text-decoration: none;
        display: block;
        color: #1e293b;
    }

    .subject-card-new:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
        border-color: var(--edu-primary);
    }

    .subject-icon-box {
        width: 64px;
        height: 64px;
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        margin-bottom: 20px;
        color: #fff;
    }

    .btn-action-rounded {
        background: #f1f5f9;
        color: #475569;
        padding: 8px 20px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.85rem;
        text-decoration: none;
        transition: 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-action-rounded:hover {
        background: var(--edu-primary);
        color: #fff;
    }

    @media (max-width: 768px) {
        .edu-hero h1 {
            font-size: 2.2rem;
        }

        .edu-hero {
            padding: 60px 0 100px;
        }
    }
</style>

<div class="edu-hero">
    <div class="container position-relative" style="z-index:2;">
        <span class="badge px-4 py-2 rounded-pill mb-4 shadow-sm"
            style="background:rgba(255,255,255,0.2); backdrop-filter:blur(10px); color:#fff; border: 1px solid rgba(255,255,255,0.3);">
            <i class="fas fa-graduation-cap me-2"></i> Ethiopian New Curriculum
        </span>
        <h1 class="display-3 fw-bold text-white mb-3">Empowering <span style="color:var(--edu-accent);">Ethiopia</span>
            Through Education</h1>
        <p class="lead text-white-50 mb-0 mx-auto" style="max-width: 600px;">Access high-quality Grade 1-12 textbooks,
            teacher guides, and expert video lessons in one professional platform.</p>
    </div>
</div>

<div class="container" style="margin-top:-60px; position:relative; z-index:10;">

    <!-- Grade Selection Bar -->
    <div class="grade-scroll-wrap">
        <div class="grade-scroll">
            <a href="education.php" class="grade-btn <?php echo $grade === 0 ? 'active' : ''; ?>">
                <i class="fas fa-home me-2"></i> Overview
            </a>
            <?php for ($g = 1; $g <= 12; $g++): ?>
                <a href="<?php echo isLoggedIn() ? 'set_grade.php' : 'education.php'; ?>?grade=<?php echo $g; ?>&tab=<?php echo $tab; ?>"
                    class="grade-btn <?php echo $grade === $g ? 'active' : ''; ?>">
                    Grade <?php echo $g; ?>
                </a>
            <?php endfor; ?>
        </div>
    </div>

    <?php if ($grade === 0): ?>
        <!-- ALL GRADES OVERVIEW -->
        <div class="text-center mb-4">
            <h3 class="fw-bold">Select a Grade to Begin</h3>
            <p class="text-muted">Choose from Grade 1 to Grade 12 — access textbooks, teacher guides, and video lessons</p>
        </div>
        <div class="row g-4 mb-5">
            <?php for ($g = 1; $g <= 12; $g++):
                $colors = ['#1565C0', '#2E7D32', '#E65100', '#6A1B9A', '#00897B', '#AD1457', '#0D47A1', '#1B5E20', '#BF360C', '#4527A0', '#00695C', '#C62828'];
                $c = $colors[$g - 1];
                $scount = count($subjects_by_grade[$g]);
                $level = $g <= 4 ? 'Lower Primary' : ($g <= 6 ? 'Upper Primary' : ($g <= 8 ? 'Junior Secondary' : 'Senior Secondary'));
                ?>
                <div class="col-lg-3 col-md-4 col-6">
                    <a href="?grade=<?php echo $g; ?>" class="subject-card-new text-center">
                        <div class="subject-icon-box mx-auto shadow-sm" style="background:<?php echo $c; ?>;">
                            <?php echo $g; ?>
                        </div>
                        <h6 class="fw-bold mb-1">Grade <?php echo $g; ?></h6>
                        <p class="small text-muted mb-2"><?php echo $scount; ?> Subjects</p>
                        <span class="badge rounded-pill"
                            style="background:<?php echo $c; ?>15; color:<?php echo $c; ?>; font-size:.65rem; padding: 5px 12px;">
                            <?php echo $level; ?>
                        </span>
                    </a>
                </div>
            <?php endfor; ?>
        </div>

        <!-- LMS Banner -->
        <div class="card border-0 shadow-sm overflow-hidden rounded-4 mb-4"
            style="background:linear-gradient(135deg,#1e1b4b,#312e81,#4338ca);cursor:pointer;"
            onclick="window.location.href='lms.php'">
            <div class="card-body p-4 p-md-5 d-flex align-items-center flex-wrap gap-4">
                <div class="flex-shrink-0 text-center">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle"
                        style="width:70px;height:70px;background:rgba(255,255,255,0.15);backdrop-filter:blur(8px);">
                        <i class="fas fa-brain text-white" style="font-size:1.8rem;"></i>
                    </div>
                </div>
                <div class="flex-grow-1">
                    <h4 class="fw-bold text-white mb-1">
                        <i class="fas fa-flask me-2" style="color:#a5b4fc;"></i>Online LMS — Test Your Knowledge
                    </h4>
                    <p class="text-white-50 mb-0">
                        Take auto-graded chapter exams for every grade and subject. Get instant results, see correct
                        answers, and track your progress!
                    </p>
                </div>
                <div class="flex-shrink-0">
                    <a href="lms.php" class="btn btn-light fw-bold rounded-pill px-4 py-2" style="color:#4338ca;">
                        <i class="fas fa-play me-1"></i> Open LMS
                    </a>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- SPECIFIC GRADE VIEW -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h3 class="fw-bold mb-0"><i class="fas fa-graduation-cap me-2" style="color:var(--edu-primary);"></i>Grade
                <?php echo $grade; ?> —
                <?php echo $grade <= 4 ? 'Lower Primary' : ($grade <= 6 ? 'Upper Primary' : ($grade <= 8 ? 'Junior Secondary' : 'Senior Secondary')); ?>
            </h3>
            <span class="badge rounded-pill px-3 py-2"
                style="background:var(--edu-light);color:var(--edu-primary);font-weight:700;">
                <?php echo count($subjects_by_grade[$grade]); ?> Subjects
            </span>
        </div>

        <!-- Navigation Tabs -->
        <div class="text-center mb-4">
            <div class="tab-pills-custom">
                <a href="?grade=<?php echo $grade; ?>&tab=textbooks"
                    class="tab-pill-custom <?php echo $tab === 'textbooks' ? 'active' : ''; ?>">
                    <i class="fas fa-book me-2"></i>Student Textbooks
                </a>
                <a href="?grade=<?php echo $grade; ?>&tab=teachers"
                    class="tab-pill-custom <?php echo $tab === 'teachers' ? 'active' : ''; ?>">
                    <i class="fas fa-chalkboard-teacher me-2"></i>Teacher Guides
                </a>
                <a href="?grade=<?php echo $grade; ?>&tab=videos"
                    class="tab-pill-custom <?php echo $tab === 'videos' ? 'active' : ''; ?>">
                    <i class="fas fa-play-circle me-2"></i>Video Lessons
                </a>
                <a href="lms.php?grade=<?php echo $grade; ?>" class="tab-pill-custom bg-dark text-white shadow-sm ms-lg-2">
                    <i class="fas fa-brain me-2 text-warning"></i>Take Exam
                </a>
            </div>
        </div>

        <?php if ($tab === 'textbooks'): ?>
            <!-- STUDENT TEXTBOOKS -->
            <div class="row g-4 mb-5">
                <?php foreach ($subjects_by_grade[$grade] as $i => $subj):
                    $icon = $subject_icons[$subj] ?? 'book';
                    $color = $subject_colors[$subj] ?? '#1565C0';
                    // Load real data from database
                    $unit_count = 0;
                    $pages = 0;
                    $edition = '2023';
                    try {
                        $res_stmt = $pdo->prepare("SELECT pages, units, edition FROM education_resources WHERE grade=? AND subject=? AND type='textbook' AND status='active' LIMIT 1");
                        $res_stmt->execute([$grade, $subj]);
                        $res_data = $res_stmt->fetch();
                        if ($res_data) {
                            $unit_count = $res_data['units'] ?? 0;
                            $pages = $res_data['pages'] ?? 0;
                            $edition = $res_data['edition'] ?? '2023';
                        }
                    } catch (Exception $e) {
                    }
                    ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="subject-card-new h-100">
                            <div class="d-flex align-items-start gap-4 mb-4">
                                <div class="subject-icon-box shadow-sm mb-0"
                                    style="background:<?php echo $color; ?>; min-width: 64px;">
                                    <i class="fas fa-<?php echo $icon; ?>"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-1"><?php echo $subj; ?></h5>
                                    <p class="small text-muted mb-0">Grade <?php echo $grade; ?> Textbook</p>
                                </div>
                            </div>

                            <div class="d-flex gap-2 mb-4 flex-wrap">
                                <?php if ($unit_count > 0): ?>
                                    <span class="badge rounded-pill bg-light text-dark border py-2 px-3 fw-normal"
                                        style="font-size: 0.75rem;">
                                        <i class="fas fa-list-ul me-2 text-primary"></i><?php echo $unit_count; ?> Units
                                    </span>
                                <?php endif; ?>
                                <span class="badge rounded-pill bg-light text-dark border py-2 px-3 fw-normal"
                                    style="font-size: 0.75rem;">
                                    <i class="fas fa-calendar-check me-2 text-success"></i><?php echo $edition; ?> Edition
                                </span>
                            </div>

                            <div class="d-grid gap-2">
                                <a href="view_textbook.php?grade=<?php echo $grade; ?>&subject=<?php echo urlencode($subj); ?>&type=textbook"
                                    class="btn-action-rounded justify-content-center py-2"
                                    style="background:var(--edu-primary); color:#fff;">
                                    <i class="fas fa-book-reader me-2"></i> Open Textbook
                                </a>
                                <a href="lms.php?grade=<?php echo $grade; ?>&subject=<?php echo urlencode($subj); ?>"
                                    class="btn-action-rounded justify-content-center py-2">
                                    <i class="fas fa-tasks me-2"></i> Practice Quiz
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php elseif ($tab === 'teachers'): ?>
            <!-- TEACHER GUIDES -->
            <div class="row g-4 mb-5">
                <?php foreach ($subjects_by_grade[$grade] as $subj):
                    $icon = $subject_icons[$subj] ?? 'book';
                    $color = $subject_colors[$subj] ?? '#E65100';
                    ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="subject-card-new h-100" style="border-top: 4px solid <?php echo $color; ?>;">
                            <div class="d-flex align-items-start gap-4 mb-4">
                                <div class="subject-icon-box shadow-sm mb-0"
                                    style="background:<?php echo $color; ?>; min-width: 64px;">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-1"><?php echo $subj; ?></h5>
                                    <p class="small text-muted mb-0">Teacher's Guide — Grade <?php echo $grade; ?></p>
                                </div>
                            </div>

                            <ul class="list-unstyled small text-muted mb-4">
                                <li class="mb-2 d-flex gap-2 align-items-center"><i
                                        class="fas fa-check-circle text-success fs-6"></i> <span>Teaching strategies & plans</span>
                                </li>
                                <li class="mb-2 d-flex gap-2 align-items-center"><i
                                        class="fas fa-check-circle text-success fs-6"></i> <span>Answer keys for units</span></li>
                            </ul>

                            <a href="view_textbook.php?grade=<?php echo $grade; ?>&subject=<?php echo urlencode($subj); ?>&type=teacher_guide"
                                class="btn-action-rounded justify-content-center py-2 w-100"
                                style="background: #FFF3E0; color: #E65100;">
                                <i class="fas fa-chalkboard me-2"></i> Open instructor Guide
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php elseif ($tab === 'videos'): ?>
            <!-- VIDEO LESSONS -->
            <div class="row g-4 mb-5">
                <?php foreach ($subjects_by_grade[$grade] as $subj):
                    $color = $subject_colors[$subj] ?? '#1565C0';
                    $vid = $video_links[$subj] ?? null;
                    ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="subject-card-new p-0 overflow-hidden h-100">
                            <div class="video-thumb">
                                <?php if ($vid): ?>
                                    <iframe src="https://www.youtube.com/embed/<?php echo $vid['id']; ?>" allowfullscreen
                                        loading="lazy"></iframe>
                                <?php else: ?>
                                    <div class="d-flex align-items-center justify-content-center h-100 position-absolute top-0 start-0 w-100"
                                        style="background:linear-gradient(135deg,<?php echo $color; ?>,<?php echo $color; ?>cc);">
                                        <div class="text-center text-white">
                                            <i class="fas fa-play-circle fs-1 mb-2 d-block"></i>
                                            <span class="small fw-bold">Video Coming Soon</span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="p-4">
                                <h6 class="fw-bold mb-1 text-dark"><?php echo $subj; ?> Video Lessons</h6>
                                <p class="small text-muted mb-3">Grade <?php echo $grade; ?> — Official Curriculum</p>

                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge rounded-pill bg-light text-dark border py-2 px-3 fw-normal"
                                        style="font-size: 0.7rem;">
                                        <i class="fas fa-clock text-primary me-2"></i>HD Quality
                                    </span>
                                    <?php if ($vid): ?>
                                        <a href="https://www.youtube.com/watch?v=<?php echo $vid['id']; ?>" target="_blank"
                                            class="btn btn-sm btn-action-rounded">
                                            <i class="fas fa-play me-1"></i> Full Screen
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

</div>



<?php include('../includes/footer.php'); ?>