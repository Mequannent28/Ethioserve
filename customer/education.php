<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';
$flash = getFlashMessage();

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
        --edu-dark: #0D47A1;
        --edu-light: #E3F2FD;
        --edu-gold: #FFB300;
    }

    body {
        font-family: 'Outfit', sans-serif;
        background: #f0f4f8;
    }

    .edu-hero {
        background: linear-gradient(135deg, rgba(13, 71, 161, 0.9), rgba(21, 101, 192, 0.8)), url('https://images.unsplash.com/photo-1503676260728-1c00da094a0b?auto=format&fit=crop&w=1200&q=80');
        background-size: cover;
        background-position: center;
        padding: 60px 0 100px;
        color: #fff;
        text-align: center;
        position: relative;
    }

    .edu-hero::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 60px;
        background: #f0f4f8;
        border-radius: 50px 50px 0 0;
    }

    .edu-hero h1 {
        font-size: 2.8rem;
        font-weight: 800;
        letter-spacing: -1px;
    }

    .edu-hero h1 span {
        color: var(--edu-gold);
    }

    .grade-scroll {
        display: flex;
        gap: 10px;
        overflow-x: auto;
        padding: 10px 0 20px;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    .grade-scroll::-webkit-scrollbar {
        display: none;
    }

    .grade-btn {
        min-width: 90px;
        padding: 14px 10px;
        border-radius: 16px;
        border: 2px solid #e0e0e0;
        background: #fff;
        text-align: center;
        text-decoration: none;
        color: #333;
        font-weight: 700;
        transition: all .3s;
        flex-shrink: 0;
    }

    .grade-btn:hover {
        border-color: var(--edu-primary);
        color: var(--edu-primary);
        transform: translateY(-3px);
    }

    .grade-btn.active {
        background: var(--edu-primary);
        color: #fff;
        border-color: var(--edu-primary);
        box-shadow: 0 8px 25px rgba(21, 101, 192, 0.3);
    }

    .grade-btn small {
        display: block;
        font-size: .65rem;
        font-weight: 400;
        opacity: .7;
        margin-top: 2px;
    }

    .tab-pills {
        display: flex;
        gap: 8px;
        background: #fff;
        padding: 6px;
        border-radius: 50px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
        display: inline-flex;
    }

    .tab-pill {
        padding: 10px 25px;
        border-radius: 50px;
        font-weight: 700;
        font-size: .85rem;
        text-decoration: none;
        color: #888;
        transition: .3s;
    }

    .tab-pill.active {
        background: var(--edu-primary);
        color: #fff;
        box-shadow: 0 4px 15px rgba(21, 101, 192, 0.3);
    }

    .tab-pill:hover {
        color: var(--edu-primary);
    }

    .subject-card {
        background: #fff;
        border-radius: 18px;
        padding: 24px;
        border: none;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
        transition: all .35s;
        height: 100%;
        cursor: pointer;
        text-decoration: none;
        display: block;
        color: #333;
    }

    .subject-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.1);
        color: #333;
    }

    .subject-icon {
        width: 55px;
        height: 55px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        color: #fff;
        margin-bottom: 15px;
    }

    .subject-card h6 {
        font-weight: 700;
        margin-bottom: 4px;
    }

    .subject-card p {
        font-size: .78rem;
        color: #999;
        margin: 0;
    }

    .btn-download {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 18px;
        border-radius: 10px;
        font-size: .8rem;
        font-weight: 600;
        border: none;
        transition: .3s;
        text-decoration: none;
    }

    .btn-dl-student {
        background: var(--edu-light);
        color: var(--edu-primary);
    }

    .btn-dl-student:hover {
        background: var(--edu-primary);
        color: #fff;
    }

    .btn-dl-teacher {
        background: #FFF3E0;
        color: #E65100;
    }

    .btn-dl-teacher:hover {
        background: #E65100;
        color: #fff;
    }

    .video-card {
        background: #fff;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        transition: .35s;
    }

    .video-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.1);
    }

    .video-thumb {
        position: relative;
        padding-top: 56.25%;
        background: #000;
    }

    .video-thumb iframe {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border: 0;
    }

    .empty-edu {
        padding: 80px 20px;
        text-align: center;
    }

    .empty-edu i {
        font-size: 5rem;
        color: #ccc;
        margin-bottom: 20px;
    }

    /* PDF Viewer Modal */
    .pdf-viewer-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.85);
        z-index: 9999;
        animation: fadeInOverlay .3s ease;
    }

    .pdf-viewer-overlay.active {
        display: flex;
        flex-direction: column;
    }

    @keyframes fadeInOverlay {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    .pdf-viewer-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 24px;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(10px);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .pdf-viewer-header h5 {
        color: #fff;
        margin: 0;
        font-size: 1rem;
        font-weight: 600;
    }

    .pdf-viewer-header .badge {
        font-size: .7rem;
    }

    .pdf-viewer-close {
        background: rgba(255, 255, 255, 0.15);
        border: none;
        color: #fff;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        font-size: 1.2rem;
        cursor: pointer;
        transition: .3s;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .pdf-viewer-close:hover {
        background: rgba(255, 255, 255, 0.3);
    }

    .pdf-viewer-body {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        overflow: hidden;
    }

    .pdf-viewer-body iframe {
        width: 100%;
        height: 100%;
        border: none;
        background: #fff;
    }

    .pdf-coming-soon {
        text-align: center;
        color: #fff;
        padding: 60px 30px;
    }

    .pdf-coming-soon i {
        font-size: 5rem;
        color: rgba(255, 255, 255, 0.3);
        margin-bottom: 20px;
    }

    .pdf-coming-soon h4 {
        font-weight: 700;
        margin-bottom: 10px;
    }

    .pdf-coming-soon p {
        color: rgba(255, 255, 255, 0.6);
        max-width: 400px;
        margin: 0 auto;
    }

    @media(max-width:768px) {
        .edu-hero h1 {
            font-size: 1.8rem;
        }

        .grade-btn {
            min-width: 70px;
            padding: 10px 8px;
            font-size: .85rem;
        }
    }
</style>

<!-- Hero -->
<div class="edu-hero">
    <div class="position-relative" style="z-index:2;">
        <span class="badge px-3 py-2 rounded-pill mb-3 fw-bold"
            style="background:rgba(255,179,0,0.2);color:var(--edu-gold);">📚 Ethiopian Curriculum</span>
        <h1>Learn <span>Smarter</span></h1>
        <p class="lead opacity-80 mb-0">Grade 1-12 Textbooks, Teacher Guides & Video Lessons</p>
    </div>
</div>

<div class="container" style="margin-top:-40px;position:relative;z-index:10;">

    <!-- Grade Selector -->
    <div class="text-center mb-4">
        <div class="grade-scroll justify-content-center flex-wrap">
            <a href="education.php" class="grade-btn <?php echo $grade === 0 ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i><small>All Grades</small>
            </a>
            <?php for ($g = 1; $g <= 12; $g++): ?>
                <a href="?grade=<?php echo $g; ?>&tab=<?php echo $tab; ?>"
                    class="grade-btn <?php echo $grade === $g ? 'active' : ''; ?>">
                    <?php echo $g; ?><small>Grade
                        <?php echo $g; ?>
                    </small>
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
                    <a href="?grade=<?php echo $g; ?>" class="subject-card text-center">
                        <div class="subject-icon mx-auto"
                            style="background:<?php echo $c; ?>;width:65px;height:65px;font-size:1.5rem;">
                            <?php echo $g; ?>
                        </div>
                        <h6>Grade
                            <?php echo $g; ?>
                        </h6>
                        <p>
                            <?php echo $scount; ?> Subjects
                        </p>
                        <span class="badge rounded-pill mt-2"
                            style="background:<?php echo $c; ?>15;color:<?php echo $c; ?>;font-size:.7rem;">
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

        <!-- Tab Pills -->
        <div class="text-center mb-4">
            <div class="tab-pills">
                <a href="?grade=<?php echo $grade; ?>&tab=textbooks"
                    class="tab-pill <?php echo $tab === 'textbooks' ? 'active' : ''; ?>"><i class="fas fa-book me-1"></i>
                    Textbooks</a>
                <a href="?grade=<?php echo $grade; ?>&tab=teachers"
                    class="tab-pill <?php echo $tab === 'teachers' ? 'active' : ''; ?>"><i
                        class="fas fa-chalkboard-teacher me-1"></i> Teacher Guide</a>
                <a href="?grade=<?php echo $grade; ?>&tab=videos"
                    class="tab-pill <?php echo $tab === 'videos' ? 'active' : ''; ?>"><i
                        class="fas fa-play-circle me-1"></i>
                    Videos</a>
                <a href="lms.php?grade=<?php echo $grade; ?>" class="tab-pill"
                    style="background:linear-gradient(135deg,#6366f1,#818cf8);color:#fff;"><i class="fas fa-brain me-1"></i>
                    Take Exam</a>
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
                        <div class="subject-card">
                            <div class="d-flex align-items-start gap-3 mb-3">
                                <div class="subject-icon" style="background:<?php echo $color; ?>;">
                                    <i class="fas fa-<?php echo $icon; ?>"></i>
                                </div>
                                <div>
                                    <h6>
                                        <?php echo $subj; ?>
                                    </h6>
                                    <p>Grade
                                        <?php echo $grade; ?> Student Textbook
                                    </p>
                                </div>
                            </div>
                            <div class="d-flex gap-2 mb-3 flex-wrap">
                                <?php if ($unit_count > 0): ?>
                                    <span class="badge rounded-pill"
                                        style="background:<?php echo $color; ?>15;color:<?php echo $color; ?>;font-size:.7rem;">
                                        <i class="fas fa-list me-1"></i>
                                        <?php echo $unit_count; ?> Units
                                    </span>
                                <?php endif; ?>
                                <?php if ($pages > 0): ?>
                                    <span class="badge rounded-pill" style="background:#f5f5f5;color:#666;font-size:.7rem;">
                                        <i class="fas fa-file me-1"></i>
                                        <?php echo $pages; ?> Pages
                                    </span>
                                <?php endif; ?>
                                <span class="badge rounded-pill" style="background:#E8F5E9;color:#2E7D32;font-size:.7rem;">
                                    <i class="fas fa-check me-1"></i><?php echo $edition; ?> Edition
                                </span>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="view_textbook.php?grade=<?php echo $grade; ?>&subject=<?php echo urlencode($subj); ?>&type=textbook"
                                    class="btn-download btn-dl-student flex-grow-1 justify-content-center">
                                    <i class="fas fa-book-reader"></i> View Textbook
                                </a>
                                <a href="lms.php?grade=<?php echo $grade; ?>&subject=<?php echo urlencode($subj); ?>"
                                    class="btn-download justify-content-center"
                                    style="background:linear-gradient(135deg,#6366f1,#818cf8);color:#fff;border:none;flex:0 0 auto;padding:8px 14px;">
                                    <i class="fas fa-brain"></i> Exam
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
                        <div class="subject-card" style="border-left:4px solid <?php echo $color; ?>;">
                            <div class="d-flex align-items-start gap-3 mb-3">
                                <div class="subject-icon" style="background:<?php echo $color; ?>;">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                </div>
                                <div>
                                    <h6>
                                        <?php echo $subj; ?> — Teacher Guide
                                    </h6>
                                    <p>Grade
                                        <?php echo $grade; ?> Instructor Manual
                                    </p>
                                </div>
                            </div>
                            <ul class="list-unstyled small text-muted mb-3">
                                <li class="mb-1"><i class="fas fa-check-circle text-success me-2"></i>Lesson plans & objectives</li>
                                <li class="mb-1"><i class="fas fa-check-circle text-success me-2"></i>Teaching strategies</li>
                                <li class="mb-1"><i class="fas fa-check-circle text-success me-2"></i>Assessment rubrics</li>
                                <li><i class="fas fa-check-circle text-success me-2"></i>Answer keys included</li>
                            </ul>
                            <div class="d-flex gap-2">
                                <a href="view_textbook.php?grade=<?php echo $grade; ?>&subject=<?php echo urlencode($subj); ?>&type=teacher_guide"
                                    class="btn-download btn-dl-teacher flex-grow-1 justify-content-center">
                                    <i class="fas fa-book-reader"></i> View Teacher Guide
                                </a>
                            </div>
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
                        <div class="video-card">
                            <div class="video-thumb">
                                <?php if ($vid): ?>
                                    <iframe src="https://www.youtube.com/embed/<?php echo $vid['id']; ?>" allowfullscreen
                                        loading="lazy"></iframe>
                                <?php else: ?>
                                    <div class="d-flex align-items-center justify-content-center h-100 position-absolute top-0 start-0 w-100"
                                        style="background:linear-gradient(135deg,<?php echo $color; ?>,<?php echo $color; ?>cc);">
                                        <div class="text-center text-white">
                                            <i class="fas fa-play-circle fs-1 mb-2 d-block"></i>
                                            <span class="small">Coming Soon</span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="p-3">
                                <h6 class="fw-bold mb-1">
                                    <?php echo $subj; ?> — Grade
                                    <?php echo $grade; ?>
                                </h6>
                                <p class="text-muted small mb-2">
                                    <?php echo $vid ? $vid['title'] : 'Video lesson coming soon'; ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge rounded-pill"
                                        style="background:<?php echo $color; ?>15;color:<?php echo $color; ?>;">
                                        <i class="fas fa-<?php echo $subject_icons[$subj] ?? 'book'; ?> me-1"></i>
                                        <?php echo $subj; ?>
                                    </span>
                                    <?php if ($vid): ?>
                                        <a href="https://www.youtube.com/watch?v=<?php echo $vid['id']; ?>" target="_blank"
                                            class="btn btn-sm btn-outline-primary rounded-pill px-3" style="font-size:.75rem;">
                                            <i class="fas fa-external-link-alt me-1"></i>Watch
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