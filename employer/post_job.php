<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireRole('employer');

$user_id = getCurrentUserId();

// Get company details
$stmt = $pdo->prepare("SELECT id FROM job_companies WHERE user_id = ?");
$stmt->execute([$user_id]);
$company = $stmt->fetch();
$company_id = $company['id'];

$categories = $pdo->query("SELECT * FROM job_categories ORDER BY name ASC")->fetchAll();
$exams = $pdo->prepare("SELECT id, title FROM job_exams WHERE company_id = ? ORDER BY title ASC");
$exams->execute([$company_id]);
$available_exams = $exams->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_job'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO job_listings 
                (company_id, posted_by, exam_id, title, description, requirements, job_type, category_id, 
                 location, salary_min, salary_max, salary_period, skills_required, experience_level, deadline, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
            ");
            $stmt->execute([
                $company_id,
                $user_id,
                !empty($_POST['exam_id']) ? (int)$_POST['exam_id'] : null,
                sanitize($_POST['title']),
                $_POST['description'], // Allow rich text if any
                sanitize($_POST['requirements']),
                $_POST['job_type'],
                (int) $_POST['category_id'],
                sanitize($_POST['location']),
                (float) $_POST['salary_min'],
                (float) $_POST['salary_max'],
                $_POST['salary_period'],
                sanitize($_POST['skills_required']),
                $_POST['experience_level'],
                !empty($_POST['deadline']) ? $_POST['deadline'] : null
            ]);

            redirectWithMessage('jobs_management.php', 'success', 'Job posted successfully!');
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Job - EthioServe</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <!-- PDF and Word Parsing Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.6.0/mammoth.browser.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <style>
        .question-block { background: #fafafa; border: 1px solid #eaeaea; border-radius: 8px; padding: 20px; margin-bottom: 20px; position:relative;}
        .remove-q-btn { position: absolute; top: 10px; right: 10px; }
    </style>
</head>

<body class="bg-light">
    <div class="dashboard-wrapper d-flex">
        <?php include('../includes/sidebar_employer.php'); ?>

        <div class="main-content flex-grow-1 p-4">
            <div class="container-fluid" style="max-width: 900px;">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <a href="jobs_management.php" class="btn btn-white shadow-sm rounded-circle p-2"
                        style="width:40px; height:40px;">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h2 class="fw-bold mb-0">Post a New Job</h2>
                </div>

                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4 p-md-5">
                        <form method="POST">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="post_job" value="1">

                            <div class="mb-4">
                                <label class="form-label fw-bold">Job Title *</label>
                                <input type="text" name="title" class="form-control rounded-3 py-3" required
                                    placeholder="e.g. Senior PHP Developer">
                            </div>

                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Category *</label>
                                    <select name="category_id" class="form-select rounded-3 py-3" required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>">
                                                <?php echo htmlspecialchars($cat['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Job Type *</label>
                                    <select name="job_type" class="form-select rounded-3 py-3" required>
                                        <option value="full_time">Full Time</option>
                                        <option value="part_time">Part Time</option>
                                        <option value="contract">Contract</option>
                                        <option value="internship">Internship</option>
                                        <option value="freelance">Freelance</option>
                                        <option value="daily_labor">Daily Labor</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Location *</label>
                                    <input type="text" name="location" class="form-control rounded-3 py-3" required
                                        placeholder="e.g. Addis Ababa, Remote">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Experience Level</label>
                                    <select name="experience_level" class="form-select rounded-3 py-3">
                                        <option value="any">Any Experience</option>
                                        <option value="entry">Entry Level</option>
                                        <option value="mid">Mid Level</option>
                                        <option value="senior">Senior Level</option>
                                        <option value="lead">Lead</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">Job Description *</label>
                                <textarea name="description" class="form-control rounded-3" rows="8" required
                                    placeholder="Describe the role and responsibilities..."></textarea>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">Requirements / Qualifications</label>
                                <textarea name="requirements" class="form-control rounded-3" rows="5"
                                    placeholder="List skills, education, and experience needed..."></textarea>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">Skills Required (Comma separated)</label>
                                <input type="text" name="skills_required" class="form-control rounded-3 py-3"
                                    placeholder="PHP, MySQL, Laravel, Git">
                            </div>

                            <div class="row g-4 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Min Salary</label>
                                    <input type="number" name="salary_min" class="form-control rounded-3 py-3"
                                        placeholder="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Max Salary</label>
                                    <input type="number" name="salary_max" class="form-control rounded-3 py-3"
                                        placeholder="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Salary Period</label>
                                    <select name="salary_period" class="form-select rounded-3 py-3">
                                        <option value="month">Per Month</option>
                                        <option value="year">Per Year</option>
                                        <option value="hour">Per Hour</option>
                                        <option value="day">Per Day</option>
                                        <option value="project">Per Project</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">Application Deadline</label>
                                <input type="date" name="deadline" class="form-control rounded-3 py-3">
                            </div>

                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:40px; height:40px;">
                                            <i class="fas fa-file-signature"></i>
                                        </div>
                                        <div>
                                            <h5 class="fw-bold mb-0">Required Assessment <span class="badge bg-primary-subtle text-primary fw-normal ms-2" style="font-size: 0.7rem;">LMS</span></h5>
                                            <p class="text-muted small mb-0">Select or create an exam that applicants must complete</p>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#instantExamModal">
                                        <i class="fas fa-plus me-1"></i> Create New
                                    </button>
                                </div>
                                <select name="exam_id" id="exam_id_dropdown" class="form-select rounded-3 py-3 border-primary border-opacity-25">
                                    <option value="">No Exam Required (Direct Application)</option>
                                    <?php foreach ($available_exams as $exam): ?>
                                        <option value="<?php echo $exam['id']; ?>">
                                            <?php echo htmlspecialchars($exam['title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="mt-2 small text-primary">
                                    <i class="fas fa-info-circle me-1"></i> Candidates will be prompted to take this exam when applying.
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary-green rounded-pill py-3 fw-bold shadow-sm">
                                    <i class="fas fa-paper-plane me-2"></i>Publish Job Listing
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <p class="text-center text-muted small mt-4">By publishing, you agree to EthioServe's Recruitment Terms.
                </p>
            </div>
        </div>
    </div>
    <div class="modal" id="instantExamModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 bg-primary bg-opacity-10 py-3">
                    <h5 class="modal-title fw-bold text-primary"><i class="fas fa-magic me-2"></i>Instant Exam Creation (PDF/Mass Entry)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="examCreationStep1">
                        <div class="row mb-4">
                            <div class="col-md-8">
                                <label class="form-label fw-bold small">1. Exam Title (Required) *</label>
                                <input type="text" id="modalExamTitle" class="form-control" placeholder="e.g. Assessment for PHP Role">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">Duration (Mins)</label>
                                <input type="number" id="modalExamDuration" class="form-control" value="30">
                            </div>
                        </div>

                        <div class="card bg-warning bg-opacity-10 border-warning border-opacity-25 mb-4 rounded-3">
                            <div class="card-body p-3">
                                <h6 class="fw-bold text-warning-emphasis small mb-2"><i class="fas fa-file-import me-1"></i>Mass Import Options</h6>
                                <div class="row g-2">
                                    <div class="col-md-5">
                                        <input type="file" id="modalExamFile" class="form-control form-control-sm" accept=".pdf,.doc,.docx">
                                    </div>
                                    <div class="col-md-3">
                                        <button type="button" class="btn btn-sm btn-warning w-100" id="modalExtractBtn">
                                            <i class="fas fa-file-upload me-1"></i> Extract File
                                        </button>
                                    </div>
                                    <div class="col-md-4">
                                        <div id="modalUploadStatus" class="small mt-1 d-none"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small">2. Paste Questions Content here (or auto-fill from file)</label>
                            <textarea id="modalExamTextarea" class="form-control" rows="8" placeholder="Format:
1. Question Text
A) Option A
B) Option B
C) Option C
D) Option D
Answer: A"></textarea>
                            <div class="mt-2 d-flex justify-content-between">
                                <span class="text-muted small"><i class="fas fa-info-circle me-1"></i> Ensure 'Answer: Letter' is present for each.</span>
                                <button type="button" class="btn btn-sm btn-dark" id="modalParseQuestionsBtn">
                                    <i class="fas fa-wand-magic-sparkles me-1"></i> Parse & Review
                                </button>
                            </div>
                        </div>
                    </div>

                    <div id="examCreationStep2" class="d-none">
                        <h6 class="fw-bold mb-3"><i class="fas fa-check-double me-2 text-success"></i>Review Extracted Questions</h6>
                        <div id="modalReviewContainer"></div>
                        <div class="text-center mt-3">
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill me-2" onclick="document.getElementById('examCreationStep2').classList.add('d-none'); document.getElementById('examCreationStep1').classList.remove('d-none');">
                                <i class="fas fa-arrow-left me-1"></i> Edit Source Text
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" id="modalFinalSaveBtn" disabled>
                        <i class="fas fa-save me-2"></i>Link Exam to Job
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // PDF/Mammoth Logic for Modal
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

        document.getElementById('modalExtractBtn').addEventListener('click', async function() {
            const fileInput = document.getElementById('modalExamFile');
            const status = document.getElementById('modalUploadStatus');
            const textarea = document.getElementById('modalExamTextarea');
            if (!fileInput.files[0]) return alert("Select a file first.");

            const file = fileInput.files[0];
            status.classList.remove('d-none');
            status.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Extracting...';
            status.className = "small mt-1 text-primary";

            try {
                if (file.type === "application/pdf") {
                    const reader = new FileReader();
                    reader.onload = async function() {
                        const typedarray = new Uint8Array(this.result);
                        const pdf = await pdfjsLib.getDocument(typedarray).promise;
                        let fullText = "";
                        for (let i = 1; i <= pdf.numPages; i++) {
                            const page = await pdf.getPage(i);
                            const textContent = await page.getTextContent();
                            fullText += textContent.items.map(s => s.str).join(' ') + "\n";
                        }
                        textarea.value = fullText;
                        status.innerHTML = 'PDF extracted!';
                        status.className = "small mt-1 text-success";
                    };
                    reader.readAsArrayBuffer(file);
                } else if (file.name.endsWith('.docx')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        mammoth.extractRawText({arrayBuffer: e.target.result})
                            .then(res => { 
                                textarea.value = res.value; 
                                status.innerHTML = 'Word extracted!'; 
                                status.className = "small mt-1 text-success";
                            });
                    };
                    reader.readAsArrayBuffer(file);
                }
            } catch (err) { alert("Extraction failed."); }
        });

        let parsedQuestions = [];
        document.getElementById('modalParseQuestionsBtn').addEventListener('click', function() {
            const text = document.getElementById('modalExamTextarea').value;
            const lines = text.split('\n');
            parsedQuestions = [];
            let currentQ = null;

            lines.forEach(line => {
                line = line.trim();
                if(!line) return;
                const qMatch = line.match(/^(?:Question\s+)?(\d+)[.)]\s*(.*)/i);
                if (qMatch) {
                    if (currentQ) parsedQuestions.push(currentQ);
                    currentQ = { text: qMatch[2], opt_a: '', opt_b: '', opt_c: '', opt_d: '', correct: '' };
                    return;
                }
                if (currentQ) {
                    const optMatch = line.match(/^([A-D])[.)]\s*(.*)/i);
                    if (optMatch) { currentQ[`opt_${optMatch[1].toLowerCase()}`] = optMatch[2]; return; }
                    const ansMatch = line.match(/^(?:Answer|Correct|Ans):\s*([A-D])/i);
                    if (ansMatch) { currentQ.correct = ansMatch[1].toUpperCase(); return; }
                    if (currentQ.opt_a === '') currentQ.text += " " + line;
                }
            });
            if (currentQ) parsedQuestions.push(currentQ);

            if (parsedQuestions.length === 0) return alert("No questions detected.");

            // Show Review
            const container = document.getElementById('modalReviewContainer');
            container.innerHTML = parsedQuestions.map((q, i) => `
                <div class="question-block border rounded-3 p-3 mb-2 bg-white small shadow-sm">
                    <span class="badge bg-primary mb-2">#${i+1}</span>
                    <div class="fw-bold">${q.text}</div>
                    <div class="row g-2 mt-1">
                        <div class="col-6">A: ${q.opt_a}</div> <div class="col-6">B: ${q.opt_b}</div>
                        <div class="col-6">C: ${q.opt_c || '-'}</div> <div class="col-6">D: ${q.opt_d || '-'}</div>
                    </div>
                    <div class="mt-2 text-success fw-bold">Answer: ${q.correct}</div>
                </div>
            `).join('');

            document.getElementById('examCreationStep1').classList.add('d-none');
            document.getElementById('examCreationStep2').classList.remove('d-none');
            document.getElementById('modalFinalSaveBtn').disabled = false;
        });

        document.getElementById('modalFinalSaveBtn').addEventListener('click', async function() {
            const btn = this;
            const title = document.getElementById('modalExamTitle').value.trim();
            if (!title) return alert("Please enter an exam title.");

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';

            const formData = new FormData();
            formData.append('title', title);
            formData.append('duration_minutes', document.getElementById('modalExamDuration').value);
            parsedQuestions.forEach((q, i) => {
                formData.append(`questions[${i}][text]`, q.text);
                formData.append(`questions[${i}][opt_a]`, q.opt_a);
                formData.append(`questions[${i}][opt_b]`, q.opt_b);
                formData.append(`questions[${i}][opt_c]`, q.opt_c);
                formData.append(`questions[${i}][opt_d]`, q.opt_d);
                formData.append(`questions[${i}][correct]`, q.correct);
            });

            try {
                const res = await (await fetch('create_exam_api.php', { method: 'POST', body: formData })).json();
                if (res.success) {
                    const dropdown = document.getElementById('exam_id_dropdown');
                    const newOpt = new Option(res.title, res.exam_id, true, true);
                    dropdown.add(newOpt);
                    bootstrap.Modal.getInstance(document.getElementById('instantExamModal')).hide();
                    alert("Exam created and selected!");
                } else alert(res.message);
            } catch (err) { alert("Network error."); }
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save me-2"></i>Link Exam to Job';
        });
    </script>
</body>
</html>