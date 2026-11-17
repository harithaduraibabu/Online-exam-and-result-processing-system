<?php
// admin/manage-questions.php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';
checkRole('admin');

$exam_id = isset($_GET['exam_id']) ? (int) $_GET['exam_id'] : 0;
if (!$exam_id) {
    header("Location: manage-exams.php");
    exit();
}

// Fetch Exam Info
$exam = $conn->query("SELECT * FROM exams WHERE id = $exam_id")->fetch_assoc();
$page_title = "Questions: " . $exam['title'];
$success = "";
$error = "";

if (isset($_GET['msg']) && $_GET['msg'] == 'deleted') {
    $success = "Question removed successfully!";
}

// Handle Add Question
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_question'])) {
    $question_text = mysqli_real_escape_string($conn, $_POST['question_text']);
    $type = $exam['type'];
    $marks = (int) $_POST['marks'];

    if ($type == 'MCQ') {
        $opt_a = mysqli_real_escape_string($conn, $_POST['option_a']);
        $opt_b = mysqli_real_escape_string($conn, $_POST['option_b']);
        $opt_c = mysqli_real_escape_string($conn, $_POST['option_c']);
        $opt_d = mysqli_real_escape_string($conn, $_POST['option_d']);
        $correct = $_POST['correct_option'];

        $sql = "INSERT INTO questions (exam_id, question_text, type, option_a, option_b, option_c, option_d, correct_option, marks) 
                VALUES (?, ?, 'MCQ', ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issssssi", $exam_id, $question_text, $opt_a, $opt_b, $opt_c, $opt_d, $correct, $marks);
    } else {
        $template = mysqli_real_escape_string($conn, $_POST['coding_template']);
        $tc1_in = mysqli_real_escape_string($conn, $_POST['tc1_in'] ?? '');
        $tc1_out = mysqli_real_escape_string($conn, $_POST['tc1_out'] ?? '');
        $tc2_in = mysqli_real_escape_string($conn, $_POST['tc2_in'] ?? '');
        $tc2_out = mysqli_real_escape_string($conn, $_POST['tc2_out'] ?? '');
        $tc3_in = mysqli_real_escape_string($conn, $_POST['tc3_in'] ?? '');
        $tc3_out = mysqli_real_escape_string($conn, $_POST['tc3_out'] ?? '');

        $sql = "INSERT INTO questions (exam_id, question_text, type, coding_template, 
                test_case_1_input, test_case_1_output, test_case_2_input, test_case_2_output, 
                test_case_3_input, test_case_3_output, marks) 
                VALUES (?, ?, 'Coding', ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issssssssi", $exam_id, $question_text, $template, $tc1_in, $tc1_out, $tc2_in, $tc2_out, $tc3_in, $tc3_out, $marks);
    }

    if ($stmt->execute()) {
        updateExamTotalMarks($conn, $exam_id);
        $success = "Question added successfully!";
    } else {
        $error = "Error adding question: " . $conn->error;
    }
}

// Handle Edit Question
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_question'])) {
    $q_id = (int) $_POST['q_id'];
    $question_text = mysqli_real_escape_string($conn, $_POST['question_text']);
    $marks = (int) $_POST['marks'];

    if ($exam['type'] == 'MCQ') {
        $opt_a = mysqli_real_escape_string($conn, $_POST['option_a']);
        $opt_b = mysqli_real_escape_string($conn, $_POST['option_b']);
        $opt_c = mysqli_real_escape_string($conn, $_POST['option_c']);
        $opt_d = mysqli_real_escape_string($conn, $_POST['option_d']);
        $correct = $_POST['correct_option'];

        $sql = "UPDATE questions SET question_text = ?, option_a = ?, option_b = ?, option_c = ?, option_d = ?, correct_option = ?, marks = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssii", $question_text, $opt_a, $opt_b, $opt_c, $opt_d, $correct, $marks, $q_id);
    } else {
        $template = mysqli_real_escape_string($conn, $_POST['coding_template']);
        $tc1_in = mysqli_real_escape_string($conn, $_POST['tc1_in'] ?? '');
        $tc1_out = mysqli_real_escape_string($conn, $_POST['tc1_out'] ?? '');
        $tc2_in = mysqli_real_escape_string($conn, $_POST['tc2_in'] ?? '');
        $tc2_out = mysqli_real_escape_string($conn, $_POST['tc2_out'] ?? '');
        $tc3_in = mysqli_real_escape_string($conn, $_POST['tc3_in'] ?? '');
        $tc3_out = mysqli_real_escape_string($conn, $_POST['tc3_out'] ?? '');

        $sql = "UPDATE questions SET question_text = ?, coding_template = ?, test_case_1_input = ?, test_case_1_output = ?, 
                test_case_2_input = ?, test_case_2_output = ?, test_case_3_input = ?, test_case_3_output = ?, marks = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssii", $question_text, $template, $tc1_in, $tc1_out, $tc2_in, $tc2_out, $tc3_in, $tc3_out, $marks, $q_id);
    }

    if ($stmt->execute()) {
        updateExamTotalMarks($conn, $exam_id);
        $success = "Question updated successfully!";
    } else {
        $error = "Error updating question: " . $conn->error;
    }
}

// Handle Bulk Upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_upload'])) {
    $raw_text = $_POST['bulk_text'];
    $default_marks = (int) $_POST['bulk_marks'] ?: 1;
    $new_duration = (int) $_POST['bulk_duration'];

    // Update duration if provided
    if ($new_duration > 0) {
        $conn->query("UPDATE exams SET duration = $new_duration WHERE id = $exam_id");
    }

    // Handle File Upload
    if (!empty($_FILES['bulk_file']['name'])) {
        $file_tmp = $_FILES['bulk_file']['tmp_name'];
        $file_info = pathinfo($_FILES['bulk_file']['name']);
        $file_ext = strtolower($file_info['extension']);

        if ($file_ext === 'txt') {
            $raw_text = file_get_contents($file_tmp);
        } elseif ($file_ext === 'docx' && class_exists('ZipArchive')) {
            $zip = new ZipArchive;
            if ($zip->open($file_tmp) === TRUE) {
                $xml = $zip->getFromName('word/document.xml');
                $zip->close();
                if ($xml) {
                    $raw_text = strip_tags($xml, '<w:p>');
                    $raw_text = preg_replace('/<w:p[^>]*>/', "\n", $raw_text);
                    $raw_text = strip_tags($raw_text);
                }
            }
        }
    }

    if (!empty(trim($raw_text))) {
        // Pre-process text to standardize the "ANSWER :" spacing
        $raw_text = preg_replace('/ANSWER\s*:\s*/i', 'ANSWER : ', $raw_text);

        // Split based on the "ANSWER : X" string as the most reliable end-of-question marker
        $ans_split = '/(?<=ANSWER\s:\s[A-D])\s*/i';
        $questions_blocks = preg_split($ans_split, $raw_text);

        // Fallback for numbered lists
        if (count($questions_blocks) <= 1) {
            $split_pattern = '/\n\s*(\d+\.|\?|(?=\b[a-d][\)\.]))/i';
            $questions_blocks = preg_split($split_pattern, $raw_text);
        }

        $count = 0;
        foreach ($questions_blocks as $block) {
            if (empty(trim($block)))
                continue;

            $lines = explode("\n", trim($block));
            $q_text = "";
            $options = ['a' => '', 'b' => '', 'c' => '', 'd' => ''];
            $correct = '';

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line))
                    continue;

                if (preg_match('/^[a-d][\)\.]\s*(.*)/i', $line, $matches)) {
                    $options[strtolower($line[0])] = $matches[1];
                } elseif (preg_match('/ANSWER\s*:\s*([A-D])/i', $line, $matches)) {
                    $correct = strtoupper($matches[1]);
                } else {
                    if (empty($options['a']) && empty($options['b']) && empty($options['c']) && empty($options['d']) && empty($correct)) {
                        $q_text .= (empty($q_text) ? "" : " ") . $line;
                    }
                }
            }

            if (!empty($q_text) && !empty($correct)) {
                $sql = "INSERT INTO questions (exam_id, question_text, type, option_a, option_b, option_c, option_d, correct_option, marks) 
                        VALUES (?, ?, 'MCQ', ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("issssssi", $exam_id, $q_text, $options['a'], $options['b'], $options['c'], $options['d'], $correct, $default_marks);
                if ($stmt->execute())
                    $count++;
            }
        }
        updateExamTotalMarks($conn, $exam_id);
        $success = "$count questions uploaded successfully!";
        $exam = $conn->query("SELECT * FROM exams WHERE id = $exam_id")->fetch_assoc();
    }
}

// Handle Delete Question
if (isset($_GET['delete'])) {
    $q_id = (int) $_GET['delete'];
    $delete_query = "DELETE FROM questions WHERE id = $q_id";
    if ($conn->query($delete_query)) {
        updateExamTotalMarks($conn, $exam_id);
        header("Location: manage-questions.php?exam_id=$exam_id&msg=deleted");
    } else {
        $error = "Error deleting question: " . $conn->error;
        // If redirect fails, we'll see the error on the page
    }
    exit();
}

include '../includes/header.php';

// If editing, fetch the question
$edit_q = null;
if (isset($_GET['edit'])) {
    $edit_id = (int) $_GET['edit'];
    $edit_q = $conn->query("SELECT * FROM questions WHERE id = $edit_id")->fetch_assoc();
}
?>

<div class="content-row">
    <!-- Form Section -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
        <!-- Add/Edit Question Form -->
        <div class="stat-card">
            <div class="card-title">
                <?php echo $edit_q ? "Edit" : "Add New"; ?> <?php echo $exam['type']; ?> Question
            </div>

            <?php if ($success)
                echo "<div class='alert' style='background: #ecfdf5; color: #065f46; margin-bottom: 20px;'>$success</div>"; ?>
            <?php if ($error)
                echo "<div class='alert alert-danger' style='margin-bottom: 20px;'>$error</div>"; ?>

            <form action="" method="POST">
                <?php if ($edit_q): ?>
                    <input type="hidden" name="q_id" value="<?php echo $edit_q['id']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Question Text</label>
                    <textarea name="question_text" class="form-control" rows="3"
                        required><?php echo $edit_q ? htmlspecialchars($edit_q['question_text']) : ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label>Marks</label>
                    <input type="number" name="marks" class="form-control"
                        value="<?php echo $edit_q ? $edit_q['marks'] : '1'; ?>" required>
                </div>

                <?php if ($exam['type'] == 'MCQ'): ?>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Option A</label>
                            <input type="text" name="option_a" class="form-control"
                                value="<?php echo $edit_q ? htmlspecialchars($edit_q['option_a']) : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Option B</label>
                            <input type="text" name="option_b" class="form-control"
                                value="<?php echo $edit_q ? htmlspecialchars($edit_q['option_b']) : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Option C</label>
                            <input type="text" name="option_c" class="form-control"
                                value="<?php echo $edit_q ? htmlspecialchars($edit_q['option_c']) : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Option D</label>
                            <input type="text" name="option_d" class="form-control"
                                value="<?php echo $edit_q ? htmlspecialchars($edit_q['option_d']) : ''; ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Correct Option</label>
                        <select name="correct_option" class="form-control" required>
                            <?php foreach (['A', 'B', 'C', 'D'] as $opt): ?>
                                <option value="<?php echo $opt; ?>" <?php echo ($edit_q && $edit_q['correct_option'] == $opt) ? 'selected' : ''; ?>>Option <?php echo $opt; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php else: ?>
                    <div class="form-group">
                        <label>Default Language Template</label>
                        <select id="lang-preset" class="form-control" onchange="applyLangTemplate()">
                            <option value="">— Pick a language to auto-fill template —</option>
                            <option value="java">Java</option>
                            <option value="python">Python</option>
                            <option value="c">C</option>
                            <option value="cpp">C++</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Coding Template <small style="color:var(--text-muted);font-weight:400;">(editable —
                                auto-filled when you pick a language above)</small></label>
                        <textarea id="coding-template-area" name="coding_template" class="form-control" rows="8"
                            style="font-family:monospace;font-size:0.9rem;"><?php echo $edit_q ? htmlspecialchars($edit_q['coding_template']) : ''; ?></textarea>
                    </div>
                    <!-- Test Cases -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group"><label>TC 1 Input</label><input type="text" name="tc1_in"
                                class="form-control"
                                value="<?php echo $edit_q ? htmlspecialchars($edit_q['test_case_1_input']) : ''; ?>"></div>
                        <div class="form-group"><label>TC 1 Output</label><input type="text" name="tc1_out"
                                class="form-control"
                                value="<?php echo $edit_q ? htmlspecialchars($edit_q['test_case_1_output']) : ''; ?>"></div>
                        <div class="form-group"><label>TC 2 Input</label><input type="text" name="tc2_in"
                                class="form-control"
                                value="<?php echo $edit_q ? htmlspecialchars($edit_q['test_case_2_input']) : ''; ?>"></div>
                        <div class="form-group"><label>TC 2 Output</label><input type="text" name="tc2_out"
                                class="form-control"
                                value="<?php echo $edit_q ? htmlspecialchars($edit_q['test_case_2_output']) : ''; ?>"></div>
                        <div class="form-group"><label>TC 3 Input</label><input type="text" name="tc3_in"
                                class="form-control"
                                value="<?php echo $edit_q ? htmlspecialchars($edit_q['test_case_3_input']) : ''; ?>"></div>
                        <div class="form-group"><label>TC 3 Output</label><input type="text" name="tc3_out"
                                class="form-control"
                                value="<?php echo $edit_q ? htmlspecialchars($edit_q['test_case_3_output']) : ''; ?>"></div>
                    </div>
                <?php endif; ?>

                <button type="submit" name="<?php echo $edit_q ? 'edit_question' : 'add_question'; ?>"
                    class="btn btn-primary">
                    <?php echo $edit_q ? "Update Question" : "Add Question"; ?>
                </button>
                <?php if ($edit_q): ?>
                    <a href="manage-questions.php?exam_id=<?php echo $exam_id; ?>" class="btn"
                        style="background: var(--bg-color); color: var(--text-color); margin-left:10px;">Cancel</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Bulk Upload Section (Only for MCQ) -->
        <?php if ($exam['type'] == 'MCQ'): ?>
            <div class="stat-card">
                <div class="card-title">Bulk Upload MCQs</div>
                <form action="" method="POST" enctype="multipart/form-data">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                        <div class="form-group">
                            <label>Default Marks</label>
                            <input type="number" name="bulk_marks" class="form-control" value="1" placeholder="Marks per Q">
                        </div>
                        <div class="form-group">
                            <label>Exam Duration (Min)</label>
                            <input type="number" name="bulk_duration" class="form-control"
                                value="<?php echo $exam['duration']; ?>" placeholder="Update duration">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Upload Document (Optional)</label>
                        <input type="file" name="bulk_file" class="form-control" accept=".txt,.docx" style="padding: 8px;">
                        <small style="color: var(--text-muted);">Supports .txt and .docx</small>
                    </div>

                    <div class="form-group">
                        <label>Or Paste Content</label>
                        <textarea name="bulk_text" class="form-control" rows="8"
                            placeholder="Paste your document content here if not uploading..."></textarea>
                    </div>

                    <button type="submit" name="bulk_upload" class="btn btn-primary"
                        style="background: #8b5cf6; width: 100%; font-weight: bold;">
                        <i class="fas fa-upload" style="margin-right: 8px;"></i> Upload Questions
                    </button>

                    <div
                        style="margin-top: 15px; font-size: 0.75rem; color: var(--text-muted); padding: 10px; background: #f8fafc; border-radius: 8px;">
                        <strong>Format Helper:</strong><br>
                        Question text?<br>
                        a) Option 1 <br>
                        b) Option 2 <br>
                        ANSWER : A
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="stat-card"
                style="display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center;">
                <i class="fas fa-code"
                    style="font-size: 3rem; color: var(--primary-color); opacity: 0.2; margin-bottom: 15px;"></i>
                <p>Bulk upload is available for MCQ exams.<br>For coding questions, please use the manual editor.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Questions List -->
    <div class="stat-card">
        <div class="card-title">Added Questions</div>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="text-align: left; border-bottom: 2px solid var(--bg-color);">
                    <th style="padding: 15px 0;">Question</th>
                    <th>Type</th>
                    <th>Marks</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $questions = $conn->query("SELECT * FROM questions WHERE exam_id = $exam_id ORDER BY id ASC");
                while ($row = $questions->fetch_assoc()) {
                    $preview = strlen($row['question_text']) > 80 ? substr($row['question_text'], 0, 80) . "..." : $row['question_text'];
                    echo "<tr style='border-bottom: 1px solid var(--bg-color);'>";
                    echo "<td style='padding: 15px 0;'>$preview</td>";
                    echo "<td>{$row['type']}</td>";
                    echo "<td>{$row['marks']}</td>";
                    echo "<td>
                            <a href='?exam_id=$exam_id&edit={$row['id']}' 
                               style='color: var(--primary-color); text-decoration: none; margin-right: 20px;' 
                               title='Edit'>
                               <i class='fas fa-edit'></i>
                            </a>
                            <button type='button'
                               class='btn-icon delete-question'
                               data-id='{$row['id']}'
                               style='color: var(--danger); background: none; border: none; cursor: pointer; padding: 0;' 
                               title='Delete'>
                               <i class='fas fa-trash' style='pointer-events: none;'></i>
                            </button>
                          </td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // --- Language template presets ---
    const LANG_TEMPLATES = {
        java: `// You are using Java`,
        python: `# You are using Python`,
        c: `// You are using C`,
        cpp: `// You are using C++`
    };

    function applyLangTemplate() {
        const lang = document.getElementById('lang-preset').value;
        const area = document.getElementById('coding-template-area');
        if (lang && LANG_TEMPLATES[lang]) {
            area.value = LANG_TEMPLATES[lang];
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.body.addEventListener('click', function (e) {
            if (e.target.closest('.delete-question')) {
                const btn = e.target.closest('.delete-question');
                const qId = btn.getAttribute('data-id');
                if (confirm('Are you sure you want to delete this question?')) {
                    window.location.href = `manage-questions.php?exam_id=<?php echo $exam_id; ?>&delete=${qId}`;
                }
            }
        });
    });
</script>

<?php include '../includes/footer.php'; ?>