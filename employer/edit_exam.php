<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Check if user is logged in and is an employer
requireRole('employer');

$user_id = getCurrentUserId();
$stmt = $pdo->prepare("SELECT * FROM job_companies WHERE user_id = ?");
$stmt->execute([$user_id]);
$company = $stmt->fetch();

if (!$company || !isset($_GET['id'])) {
    header("Location: lms.php");
    exit();
}

$company_id = $company['id'];
$exam_id = (int)$_GET['id'];

// Get Exam Details
$stmt = $pdo->prepare("SELECT * FROM job_exams WHERE id = ? AND company_id = ?");
$stmt->execute([$exam_id, $company_id]);
$exam = $stmt->fetch();

if (!$exam) {
    header("Location: lms.php");
    exit();
}

// Get Questions
$stmt = $pdo->prepare("SELECT * FROM job_questions WHERE exam_id = ?");
$stmt->execute([$exam_id]);
$questions = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_exam'])) {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $duration = (int)$_POST['duration_minutes'];
    
    // Update Exam
    $stmt = $pdo->prepare("UPDATE job_exams SET title = ?, description = ?, duration_minutes = ? WHERE id = ?");
    $stmt->execute([$title, $description, $duration, $exam_id]);
    
    // Update Questions (Basic approach: Delete old and insert new. In real life, maybe better sync)
    $stmt = $pdo->prepare("DELETE FROM job_questions WHERE exam_id = ?");
    $stmt->execute([$exam_id]);
    
    if (isset($_POST['questions']) && is_array($_POST['questions'])) {
        $stmt_q = $pdo->prepare("INSERT INTO job_questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($_POST['questions'] as $q) {
            if (!empty($q['text']) && !empty($q['opt_a']) && !empty($q['opt_b']) && !empty($q['correct'])) {
                $stmt_q->execute([
                    $exam_id, 
                    sanitize($q['text']),
                    sanitize($q['opt_a']),
                    sanitize($q['opt_b']),
                    sanitize($q['opt_c']),
                    sanitize($q['opt_d']),
                    strtoupper(sanitize($q['correct']))
                ]);
            }
        }
    }
    
    $_SESSION['success_message'] = "Exam updated successfully!";
    header("Location: lms.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Exam - EthioServe</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <!-- PDF and Word Parsing Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.6.0/mammoth.browser.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f4f7f6; }
        .main-content { padding: 40px; }
        .card-custom { border: none; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .question-block { background: #fafafa; border: 1px solid #eaeaea; border-radius: 8px; padding: 20px; margin-bottom: 20px; position:relative;}
        .remove-q-btn { position: absolute; top: 10px; right: 10px; }
    </style>
</head>
<body>
    <div class="dashboard-wrapper d-flex">
        <?php include('../includes/sidebar_employer.php'); ?>

        <div class="main-content flex-grow-1">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold"><i class="fas fa-edit me-2 text-primary"></i>Edit Exam</h2>
                <a href="lms.php" class="btn btn-outline-secondary rounded-pill px-4"><i class="fas fa-arrow-left me-2"></i>Back to LMS</a>
            </div>

            <div class="card card-custom">
                <div class="card-body p-4">
                    <form method="POST" action="">
                        <input type="hidden" name="update_exam" value="1">
                        
                        <div class="row mb-4">
                            <div class="col-md-8">
                                <label class="form-label fw-bold">Exam Title *</label>
                                <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($exam['title']); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Duration (Minutes) *</label>
                                <input type="number" name="duration_minutes" class="form-control" value="<?php echo htmlspecialchars($exam['duration_minutes']); ?>" min="5" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Description / Instructions</label>
                            <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($exam['description']); ?></textarea>
                        </div>

                        <hr class="my-4">

                        <h5 class="fw-bold mb-3 d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-list-ol me-2 text-primary"></i>Questions</span>
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-success rounded-start-pill px-3" id="addQuestionBtn"><i class="fas fa-plus me-1"></i> Add Question</button>
                                <button type="button" class="btn btn-sm btn-outline-primary px-3" onclick="document.getElementById('bulkPasteArea').classList.toggle('d-none')"><i class="fas fa-paste me-1"></i> Bulk Paste</button>
                                <button type="button" class="btn btn-sm btn-warning rounded-end-pill px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#importModal"><i class="fas fa-file-import me-1"></i> Smart Import</button>
                            </div>
                        </h5>

                        <div id="bulkPasteArea" class="d-none mb-4 bg-light p-4 rounded-4 border border-primary border-opacity-10">
                            <h6 class="fw-bold mb-2"><i class="fas fa-magic me-2 text-primary"></i>Mass Questions Entry (Paste 30+ Questions)</h6>
                            <p class="text-muted small">Format: "1. Question... A) Choice... Answer: A"</p>
                            <textarea id="bulkTextarea" class="form-control mb-2" rows="10" placeholder="1. What is PHP?
A. Tool
B. Language
Answer: B"></textarea>
                            <button type="button" class="btn btn-primary btn-sm rounded-pill px-4" id="processBulkBtn">
                                <i class="fas fa-cog me-1"></i> Generate Questions Below
                            </button>
                        </div>

                        <div id="questionsContainer">
                            <?php if (empty($questions)): ?>
                                <!-- Empty state block -->
                            <?php else: ?>
                                <?php foreach ($questions as $index => $q): ?>
                                <div class="question-block" data-index="<?php echo $index; ?>">
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-q-btn" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-primary">Question <?php echo $index + 1; ?></label>
                                        <textarea name="questions[<?php echo $index; ?>][text]" class="form-control" rows="2" required><?php echo htmlspecialchars($q['question_text']); ?></textarea>
                                    </div>
                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6">
                                            <div class="input-group">
                                                <span class="input-group-text">A</span>
                                                <input type="text" name="questions[<?php echo $index; ?>][opt_a]" class="form-control" value="<?php echo htmlspecialchars($q['option_a']); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="input-group">
                                                <span class="input-group-text">B</span>
                                                <input type="text" name="questions[<?php echo $index; ?>][opt_b]" class="form-control" value="<?php echo htmlspecialchars($q['option_b']); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="input-group">
                                                <span class="input-group-text">C</span>
                                                <input type="text" name="questions[<?php echo $index; ?>][opt_c]" class="form-control" value="<?php echo htmlspecialchars($q['option_c']); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="input-group">
                                                <span class="input-group-text">D</span>
                                                <input type="text" name="questions[<?php echo $index; ?>][opt_d]" class="form-control" value="<?php echo htmlspecialchars($q['option_d']); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="form-label fw-bold">Correct Option *</label>
                                        <select name="questions[<?php echo $index; ?>][correct]" class="form-select w-auto" required>
                                            <option value="">Select correct option...</option>
                                            <option value="A" <?php echo $q['correct_option'] == 'A' ? 'selected' : ''; ?>>Option A</option>
                                            <option value="B" <?php echo $q['correct_option'] == 'B' ? 'selected' : ''; ?>>Option B</option>
                                            <option value="C" <?php echo $q['correct_option'] == 'C' ? 'selected' : ''; ?>>Option C</option>
                                            <option value="D" <?php echo $q['correct_option'] == 'D' ? 'selected' : ''; ?>>Option D</option>
                                        </select>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-primary px-5 py-2 fw-bold shadow-sm"><i class="fas fa-save me-2"></i>Update Exam</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Smart Import Modal -->
    <div class="modal" id="importModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 bg-warning bg-opacity-10 py-3">
                    <h5 class="modal-title fw-bold text-warning-emphasis"><i class="fas fa-magic me-2"></i>Smart AI Import</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">Upload a PDF/Word file or paste text from your document. Our system will try to automatically detect questions, options, and correct answers.</p>
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold small">1. Upload Document (Optional)</label>
                        <div class="input-group">
                            <input type="file" id="importFile" class="form-control rounded-start-pill" accept=".pdf,.doc,.docx">
                            <button class="btn btn-warning rounded-end-pill px-4" type="button" id="uploadParseBtn"><i class="fas fa-file-upload me-1"></i> Extract Text</button>
                        </div>
                        <div id="uploadStatus" class="mt-1 small text-success d-none"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">2. Paste Text Content (Supports Word/PDF Extraction)</label>
                        <textarea id="importTextarea" class="form-control rounded-4" rows="10" placeholder="Example Format:
1. What is the capital of Ethiopia?
A) Addis Ababa
B) Gondar
C) Mekelle
D) Dire Dawa
Answer: A"></textarea>
                    </div>

                    <div class="alert alert-info border-0 rounded-3 small">
                        <i class="fas fa-lightbulb me-2"></i><strong>Tip:</strong> Ensure each question is followed by options (A, B, C, D) and an "Answer: X" mark for best results.
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning rounded-pill px-4 fw-bold shadow-sm" id="processImportBtn"><i class="fas fa-wand-magic-sparkles me-2"></i>Parse & Add Questions</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let qIndex = <?php echo count($questions); ?>;
        
        function addQuestionBlock(data = null) {
            const container = document.getElementById('questionsContainer');
            const index = qIndex;
            
            const q = data || { text: '', opt_a: '', opt_b: '', opt_c: '', opt_d: '', correct: '' };
            
            const htm = `
                <div class="question-block" data-index="${index}">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-q-btn" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-primary">Question ${index + 1}</label>
                        <textarea name="questions[${index}][text]" class="form-control" rows="2" required placeholder="Enter question text...">${q.text}</textarea>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text">A</span>
                                <input type="text" name="questions[${index}][opt_a]" class="form-control" value="${q.opt_a}" required placeholder="Option A">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text">B</span>
                                <input type="text" name="questions[${index}][opt_b]" class="form-control" value="${q.opt_b}" required placeholder="Option B">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text">C</span>
                                <input type="text" name="questions[${index}][opt_c]" class="form-control" value="${q.opt_c}" placeholder="Option C (Optional)">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text">D</span>
                                <input type="text" name="questions[${index}][opt_d]" class="form-control" value="${q.opt_d}" placeholder="Option D (Optional)">
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="form-label fw-bold">Correct Option *</label>
                        <select name="questions[${index}][correct]" class="form-select w-auto" required>
                            <option value="">Select correct option...</option>
                            <option value="A" ${q.correct === 'A' ? 'selected' : ''}>Option A</option>
                            <option value="B" ${q.correct === 'B' ? 'selected' : ''}>Option B</option>
                            <option value="C" ${q.correct === 'C' ? 'selected' : ''}>Option C</option>
                            <option value="D" ${q.correct === 'D' ? 'selected' : ''}>Option D</option>
                        </select>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', htm);
            qIndex++;
        }

        // Helper to remove initial empty question if it exists and is untouched
        function removeInitialIfEmpty() {
            const container = document.getElementById('questionsContainer');
            if (container.children.length === 0) return;
            
            // For Edit page, we only remove if there's exactly one block and it's empty
            if (container.children.length === 1) {
                const firstBlock = container.firstElementChild;
                const text = firstBlock.querySelector('textarea').value.trim();
                const optA = firstBlock.querySelector('input[name*="[opt_a]"]').value.trim();
                if (!text && !optA) {
                    firstBlock.remove();
                }
            }
        }

        function parseQuestions(text) {
            const lines = text.split(/\r?\n/);
            const questions = [];
            let currentQ = null;

            lines.forEach(line => {
                line = line.trim();
                if (!line) return;

                const qMatch = line.match(/^(?:(?:\(|Question\s+)?(\d+)[.)\]:]\s*|([?¿]))\s*(.*)/i);
                if (qMatch) {
                    if (currentQ) questions.push(currentQ);
                    currentQ = { text: qMatch[3] || line, opt_a: '', opt_b: '', opt_c: '', opt_d: '', correct: '' };
                    return;
                }

                if (currentQ) {
                    const optMatch = line.match(/^(?:\(|\[)?([A-D])[.)\]\-:]\s*(.*)/i);
                    if (optMatch) {
                        const letter = optMatch[1].toUpperCase();
                        currentQ[`opt_${letter.toLowerCase()}`] = optMatch[2];
                        return;
                    }

                    const ansMatch = line.match(/^(?:(?:Answer|Ans|Correct|Key|Result)\s*(?:is|:|=)?\s*|\[)([A-D])(?:\])?/i);
                    if (ansMatch) {
                        currentQ.correct = ansMatch[1].toUpperCase();
                        return;
                    }

                    if (!currentQ.opt_a) {
                        currentQ.text += " " + line;
                    }
                } else if (!currentQ && line.length > 10) {
                    currentQ = { text: line, opt_a: '', opt_b: '', opt_c: '', opt_d: '', correct: '' };
                }
            });

            if (currentQ) questions.push(currentQ);
            return questions;
        }

        document.getElementById('addQuestionBtn').addEventListener('click', () => addQuestionBlock());

        document.getElementById('processBulkBtn').addEventListener('click', function() {
            const text = document.getElementById('bulkTextarea').value;
            if (!text.trim()) return alert("Paste some text first.");

            const questions = parseQuestions(text);
            if (questions.length === 0) return alert("No questions detected. Please verify the format.");

            removeInitialIfEmpty();
            questions.forEach(q => addQuestionBlock(q));
            
            document.getElementById('bulkPasteArea').classList.add('d-none');
            alert(`Succesfully generated ${questions.length} questions! Scroll down to review and save.`);
        });

        // --- Smart Import Logic ---
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

        // Handle File Extraction
        document.getElementById('uploadParseBtn').addEventListener('click', async function() {
            const fileInput = document.getElementById('importFile');
            const status = document.getElementById('uploadStatus');
            const textarea = document.getElementById('importTextarea');
            
            if (!fileInput.files[0]) {
                alert("Please select a file first.");
                return;
            }

            const file = fileInput.files[0];
            status.classList.remove('d-none');
            status.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Extracting text...';
            status.className = "mt-1 small text-primary";

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
                        status.innerHTML = '<i class="fas fa-check me-1"></i> PDF text extracted!';
                        status.className = "mt-1 small text-success";
                    };
                    reader.readAsArrayBuffer(file);
                } else if (file.name.endsWith('.docx')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        mammoth.extractRawText({arrayBuffer: e.target.result})
                            .then(function(result) {
                                textarea.value = result.value;
                                status.innerHTML = '<i class="fas fa-check me-1"></i> Word text extracted!';
                                status.className = "mt-1 small text-success";
                            })
                            .catch(function(err) {
                                console.log(err);
                                alert("Error parsing Word file.");
                            });
                    };
                    reader.readAsArrayBuffer(file);
                } else {
                    alert("Unsupported file format. Please use PDF or DOCX.");
                }
            } catch (err) {
                console.error(err);
                alert("An error occurred during extraction.");
            }
        });

        // Handle Parsing in Modal
        document.getElementById('processImportBtn').addEventListener('click', function() {
            const text = document.getElementById('importTextarea').value;
            if (!text.trim()) return alert("Please paste some text or extract from a file.");

            const questions = parseQuestions(text);
            if (questions.length === 0) return alert("No questions detected. Please check the format.");

            removeInitialIfEmpty();
            questions.forEach(q => addQuestionBlock(q));

            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('importModal')).hide();
            alert(`Successfully imported ${questions.length} questions!`);
        });
    </script>
</body>
</html>
