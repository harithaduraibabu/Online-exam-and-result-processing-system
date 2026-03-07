<?php
// student/take-exam.php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';
checkRole('student');

$exam_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$exam_id)
    header("Location: exams.php");

// Fetch Exam and Questions
$exam = $conn->query("SELECT * FROM exams WHERE id = $exam_id AND status = 'active'")->fetch_assoc();
if (!$exam)
    header("Location: exams.php");

$questions_res = $conn->query("SELECT * FROM questions WHERE exam_id = $exam_id ORDER BY id ASC");
$questions = [];
while ($q = $questions_res->fetch_assoc())
    $questions[] = $q;

// Initialize Submission if not exists
$student_id = $_SESSION['user_id'];
$check_sub = $conn->query("SELECT id, started_at, status FROM submissions WHERE student_id = $student_id AND exam_id = $exam_id");
if ($check_sub->num_rows == 0) {
    $conn->query("INSERT INTO submissions (student_id, exam_id, started_at) VALUES ($student_id, $exam_id, NOW())");
    $submission_id = $conn->insert_id;
    $started_at = time();
} else {
    $sub_row = $check_sub->fetch_assoc();
    if ($sub_row['status'] !== 'pending' && $sub_row['status'] !== null) {
        // Exam already submitted
        header("Location: index.php");
        exit;
    }
    $submission_id = $sub_row['id'];
    $started_at = strtotime($sub_row['started_at']);
}

// Calculate time left
$duration_seconds = $exam['duration'] * 60;
$elapsed_seconds = max(0, time() - $started_at);
$time_left = max(0, $duration_seconds - $elapsed_seconds);

$page_title = $exam['title'];

// Get Theme Data
$u_id = $_SESSION['user_id'];
$theme_data = ['theme_color' => '#4F46E5', 'dark_mode' => 0];
$theme_res = $conn->query("SELECT theme_color, dark_mode FROM users WHERE id = $u_id");
if ($theme_res && $theme_res->num_rows > 0) {
    $theme_data = $theme_res->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en" class="<?php echo $theme_data['dark_mode'] ? 'dark-mode' : ''; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo $page_title; ?>
    </title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root {
            --primary-color:
                <?php echo $theme_data['theme_color']; ?>
            ;
            <?php
            $hex = str_replace('#', '', $theme_data['theme_color']);
            if (strlen($hex) == 3)
                $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
            $r = max(0, hexdec(substr($hex, 0, 2)) - 30);
            $g = max(0, hexdec(substr($hex, 2, 2)) - 30);
            $b = max(0, hexdec(substr($hex, 4, 2)) - 30);
            $hover = sprintf("#%02x%02x%02x", $r, $g, $b);
            ?>
            --primary-hover:
                <?php echo $hover; ?>
            ;
        }

        /* Dark Mode Overrides */
        .dark-mode {
            --bg-color: #0f172a;
            --card-bg: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: #334155;
        }

        body {
            background: var(--bg-color);
            color: var(--text-main);
            margin: 0;
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
        }

        .exam-header {
            background: var(--card-bg);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            z-index: 10;
        }

        .exam-container {
            display: flex;
            flex: 1;
            overflow: hidden;
        }

        .sidebar {
            width: 300px;
            background: var(--card-bg);
            border-right: 1px solid var(--border-color);
            padding: 25px;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }

        .question-panel {
            flex: 1;
            background: var(--bg-color);
            padding: 40px;
            overflow-y: auto;
            border-right: 1px solid var(--border-color);
        }

        .workspace-panel {
            flex: 1.2;
            background: var(--card-bg);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .workspace-scrollable {
            flex: 1;
            overflow-y: auto;
            padding: 30px;
        }

        .workspace-footer {
            padding: 20px 30px;
            background: var(--card-bg);
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .btn-nav {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: var(--bg-color);
            border: 1px solid var(--border-color);
            cursor: pointer;
            font-weight: 600;
            transition: 0.2s;
        }

        .btn-nav.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .btn-nav.answered {
            border-color: var(--primary-color);
            color: var(--primary-color);
            background: rgba(79, 70, 229, 0.05);
        }

        .code-editor {
            width: 100%;
            height: 400px;
            background: #1e293b;
            color: #e2e8f0;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 20px;
            font-family: 'Fira Code', 'Cascadia Code', monospace;
            font-size: 0.95rem;
            line-height: 1.6;
            resize: none;
            outline: none;
            tab-size: 4;
        }

        .code-editor:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .code-editor-header {
            background: #0f172a;
            padding: 10px 20px;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
            border: 1px solid #334155;
            border-bottom: none;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #94a3b8;
            font-size: 0.85rem;
        }

        .hidden {
            display: none !important;
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        /* Coding Exam Specific Split-Pane Layout */
        .coding-mode .sidebar {
            display: none !important;
        }

        .coding-mode .question-panel {
            flex: 1;
            padding: 35px;
            font-size: 1.05rem;
            line-height: 1.7;
        }

        .coding-mode .workspace-panel {
            flex: 1.5;
            background: #0f172a;
            /* Darker background for IDE feel */
        }

        .coding-mode .code-editor {
            height: calc(100vh - 260px);
            /* Fill available vertical space */
            font-size: 1rem;
            border-radius: 0 0 12px 12px;
            border-top: none;
        }

        .coding-mode .code-area {
            max-width: 100% !important;
            /* Allow code area to fill the panel */
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .coding-mode .workspace-scrollable {
            display: flex;
            flex-direction: column;
            padding: 20px 30px 10px 30px;
        }

        .coding-mode .question-content {
            /* Markdown styles for coding question description */
        }

        .coding-mode .question-content h3 {
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <header class="exam-header">
        <div style="display: flex; align-items: center; gap: 15px;">
            <div
                style="width: 40px; height: 40px; background: var(--primary-color); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white;">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div>
                <h2 style="font-size: 1.1rem; margin: 0; font-weight: 700;">
                    <?php echo htmlspecialchars($exam['title']); ?>
                </h2>
                <span
                    style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">Student
                    Portal</span>
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 30px;">
            <div
                style="background: rgba(239, 68, 68, 0.05); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.1); padding: 8px 18px; border-radius: 12px; display: flex; align-items: center; gap: 10px; font-weight: 700; font-family: monospace; font-size: 1.1rem;">
                <i class="fas fa-clock"></i>
                <span id="time-left">--:--</span>
            </div>
            <button class="btn btn-primary" onclick="finishExamPrompt()"
                style="padding: 10px 25px; border-radius: 12px; font-weight: 700; background: var(--secondary-color); border-color: var(--secondary-color);">Finish
                Test</button>
        </div>
    </header>

    <div class="exam-container <?php echo $exam['type'] == 'Coding' ? 'coding-mode' : ''; ?>">
        <!-- 1. Left Sidebar: Navigation -->
        <div class="sidebar">
            <h3
                style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); margin-bottom: 20px;">
                Question Navigation</h3>
            <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; margin-bottom: 30px;">
                <?php foreach ($questions as $index => $q): ?>
                    <button class="btn-nav <?php echo $index === 0 ? 'active' : ''; ?>" id="nav-btn-<?php echo $index; ?>"
                        onclick="goToQuestion(<?php echo $index; ?>)">
                        <?php echo $index + 1; ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <div
                style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border-color); display: flex; flex-direction: column; gap: 8px; width: 100%;">
                <div
                    style="display: flex; align-items: center; justify-content: center; flex-direction: column; gap: 4px;">
                    <div class="btn-nav answered"
                        style="width: 12px; height: 12px; margin: 0; min-height: 12px; border-radius: 3px;"></div>
                    <span
                        style="font-size: 0.65rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Done</span>
                </div>
            </div>
        </div>

        <!-- Iterator Wrapper for Middle & Right panels per question to cleanly isolate logic but keep them visually split -->
        <div style="flex: 1; display: flex; overflow: hidden; position: relative;">
            <?php foreach ($questions as $index => $q): ?>
                <div class="question-workspace-wrapper <?php echo $index > 0 ? 'hidden' : ''; ?>"
                    id="q-wrapper-<?php echo $index; ?>" style="display: flex; width: 100%; height: 100%;">

                    <!-- 2. Middle Panel: Question Description -->
                    <div class="question-panel">
                        <div class="question-header">
                            <span style="font-weight: 700; color: var(--primary-color);">Question <?php echo $index + 1; ?>
                                / <?php echo count($questions); ?></span>
                            <span
                                style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted); background: #e2e8f0; padding: 4px 10px; border-radius: 20px;">
                                Marks: <?php echo $q['marks']; ?>
                            </span>
                        </div>
                        <div class="question-content">
                            <?php if ($q['type'] !== 'Coding'): ?>
                                <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 20px; color: var(--text-main);">
                                    Multiple Choice Question
                                </h3>
                            <?php endif; ?>
                            <div style="white-space: pre-wrap; margin-bottom: 30px;"><?php
                            $cleaned_q_text = str_replace(['\r\n', '\n', '\r'], "\n", $q['question_text']);
                            // Simple markdown-like header conversion
                            $cleaned_q_text = preg_replace('/^###\s+(.*)$/m', '<h3 style="font-size:1.4rem;font-weight:700;color:var(--text-main);">$1</h3>', htmlspecialchars($cleaned_q_text));
                            echo $cleaned_q_text;
                            ?></div>
                        </div>
                    </div>

                    <!-- 3. Right Panel: Workspace Area -->
                    <div class="workspace-panel" id="q-<?php echo $index; ?>" data-id="<?php echo $q['id']; ?>"
                        data-type="<?php echo $q['type']; ?>">

                        <div class="workspace-scrollable">
                            <?php if ($q['type'] == 'MCQ'): ?>
                                <h4 style="margin-bottom: 20px; color: var(--text-main); font-weight: 600;">Select Options</h4>
                                <div class="options-container" style="max-width: 600px;">
                                    <?php foreach (['A', 'B', 'C', 'D'] as $opt): ?>
                                        <label
                                            style="display: flex; align-items: flex-start; gap: 15px; padding: 18px 24px; background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 12px; margin-bottom: 12px; cursor: pointer; transition: 0.2s; box-shadow: 0 1px 2px rgba(0,0,0,0.02);"
                                            onmouseover="this.style.borderColor='var(--primary-color)'"
                                            onmouseout="this.style.borderColor='var(--border-color)'">
                                            <input type="radio" name="ans-<?php echo $index; ?>" value="<?php echo $opt; ?>"
                                                onchange="saveMCQ(<?php echo $index; ?>, '<?php echo $opt; ?>')"
                                                style="margin-top: 4px; transform: scale(1.2);">
                                            <span style="font-size: 1.05rem; line-height: 1.4; color: var(--text-main);">
                                                <?php echo htmlspecialchars($q['option_' . strtolower($opt)]); ?>
                                            </span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="code-area" style="max-width: 900px;">
                                    <div class="code-editor-header">
                                        <span style="font-weight: 600; display: flex; align-items: center; gap: 8px;">
                                            <i class="fas fa-terminal"></i> Solution Editor
                                        </span>
                                        <select id="lang-select-<?php echo $index; ?>"
                                            style="background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.2); border-radius: 6px; padding: 4px 10px; font-size: 0.8rem; outline: none;"
                                            onchange="updateEditorPlaceholder(<?php echo $index; ?>)">
                                            <option value="62" data-lang="java" style="background:#1e293b; color:white;">Java
                                                (11)</option>
                                            <option value="71" data-lang="python" style="background:#1e293b; color:white;">
                                                Python (3.8)</option>
                                            <option value="50" data-lang="c" style="background:#1e293b; color:white;">C (GCC
                                                9.2)</option>
                                            <option value="54" data-lang="cpp" style="background:#1e293b; color:white;">C++ (GCC
                                                9.2)</option>
                                        </select>
                                    </div>
                                    <textarea id="code-editor-<?php echo $index; ?>" class="code-editor" spellcheck="false"
                                        oninput="autoSave(<?php echo $index; ?>)"><?php
                                           $cleaned_template = str_replace(['\r\n', '\n', '\r'], "\n", $q['coding_template']);
                                           echo htmlspecialchars($cleaned_template);
                                           ?></textarea>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Fixed Footer per Question Workspace -->
                        <div class="workspace-footer">
                            <div style="display: flex; gap: 10px;">
                                <?php if ($q['type'] == 'Coding'): ?>
                                    <button class="btn"
                                        style="background: white; border: 1px solid #cbd5e1; color: var(--text-main); font-weight: 600; padding: 10px 20px;"
                                        onclick="document.getElementById('code-editor-<?php echo $index; ?>').value=''">
                                        Clear
                                    </button>
                                <?php endif; ?>
                            </div>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <div id="save-status-<?php echo $index; ?>"
                                    style="font-size: 0.8rem; color: var(--text-muted); font-style: italic; margin-right: 10px;">
                                </div>
                                <button class="btn" disabled
                                    style="background: #f1f5f9; color: #94a3b8; border: 1px solid #e2e8f0; font-weight: 600; padding: 10px 20px;"
                                    id="btn-prev-<?php echo $index; ?>" onclick="prevQuestion()">Prev</button>
                                <button class="btn btn-primary" style="padding: 10px 24px; font-weight: 600;"
                                    id="btn-next-<?php echo $index; ?>"
                                    onclick="<?php echo ($index == count($questions) - 1) ? 'finishExamPrompt()' : 'nextQuestion()'; ?>">
                                    <?php echo ($index == count($questions) - 1) ? 'Submit Assessment' : 'Next Question'; ?>
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Confirmation Modal Overlay -->
    <div id="finish-modal" class="modal-overlay hidden">
        <div class="auth-card" style="max-width: 450px; background: white; padding: 40px; border-radius: 20px;">
            <h2 style="margin-bottom: 15px;">Finish Test?</h2>
            <p style="color: var(--text-muted); margin-bottom: 25px;">Are you sure you want to end the exam? You cannot
                go back once submitted.</p>
            <div id="finish-timer"
                style="font-size: 2rem; font-weight: 700; color: var(--danger); margin-bottom: 20px;">10</div>
            <div style="display: flex; gap: 15px;">
                <button class="btn" onclick="closeFinishModal()" style="background: #e2e8f0;">Cancel</button>
                <button id="confirm-finish" class="btn btn-primary" onclick="submitExam()" disabled
                    style="background: #94a3b8; cursor: not-allowed;">Confirm Finish</button>
            </div>
        </div>
    </div>

    <script>
        let currentIdx = 0;
        const totalQuestions = <?php echo count($questions); ?>;
        let timeLeft = <?php echo $time_left; ?>;
        const submissionId = <?php echo $submission_id; ?>;

        window.testCases = <?php
        $case_map = [];
        foreach ($questions as $idx => $q) {
            $cases = [];
            if ($q['type'] == 'Coding') {
                if (!empty($q['test_case_1_input']) || !empty($q['test_case_1_output'])) {
                    $cases[] = ['input' => $q['test_case_1_input'], 'expected' => $q['test_case_1_output']];
                }
                if (!empty($q['test_case_2_input']) || !empty($q['test_case_2_output'])) {
                    $cases[] = ['input' => $q['test_case_2_input'], 'expected' => $q['test_case_2_output']];
                }
                if (!empty($q['test_case_3_input']) || !empty($q['test_case_3_output'])) {
                    $cases[] = ['input' => $q['test_case_3_input'], 'expected' => $q['test_case_3_output']];
                }
            }
            $case_map[$idx] = $cases;
        }
        echo json_encode($case_map);
        ?>;

        // Timer Logic
        const timerInterval = setInterval(() => {
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                submitExam();
                return;
            }
            timeLeft--;
            const mins = Math.floor(timeLeft / 60);
            const secs = timeLeft % 60;
            const display = document.getElementById('time-left');
            if (display) display.innerText = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }, 1000);

        // Navigation Logic
        function goToQuestion(idx) {
            document.querySelectorAll('.question-workspace-wrapper').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.btn-nav').forEach(el => el.classList.remove('active'));

            document.getElementById(`q-wrapper-${idx}`).classList.remove('hidden');
            document.getElementById(`nav-btn-${idx}`).classList.add('active');

            currentIdx = idx;

            // Adjust prev/next buttons
            document.querySelectorAll(`[id^="btn-prev-"]`).forEach(btn => {
                btn.disabled = currentIdx === 0;
                if (currentIdx === 0) {
                    btn.style.background = '#f1f5f9';
                    btn.style.color = '#94a3b8';
                } else {
                    btn.style.background = 'white';
                    btn.style.color = 'var(--text-main)';
                }
            });
        }

        // Initial setup
        goToQuestion(0);

        function nextQuestion() {
            if (currentIdx < totalQuestions - 1) goToQuestion(currentIdx + 1);
        }

        function prevQuestion() {
            if (currentIdx > 0) goToQuestion(currentIdx - 1);
        }

        // Code Editor Behavior
        document.addEventListener('keydown', function (e) {
            if (e.target && e.target.classList.contains('code-editor')) {
                const el = e.target;
                const start = el.selectionStart;
                const end = el.selectionEnd;
                const val = el.value;

                if (e.key === 'Tab') {
                    e.preventDefault();
                    el.value = val.substring(0, start) + "    " + val.substring(end);
                    el.selectionStart = el.selectionEnd = start + 4;
                    return;
                }

                const pairs = { '(': ')', '{': '}', '[': ']', '"': '"', "'": "'" };
                if (pairs[e.key]) {
                    e.preventDefault();
                    el.value = val.substring(0, start) + e.key + pairs[e.key] + val.substring(end);
                    el.selectionStart = el.selectionEnd = start + 1;
                }

                if (e.key === 'Backspace' && start === end && start > 0) {
                    if (pairs[val.charAt(start - 1)] === val.charAt(start)) {
                        e.preventDefault();
                        el.value = val.substring(0, start - 1) + val.substring(end + 1);
                        el.selectionStart = el.selectionEnd = start - 1;
                    }
                }
            }
        });

        function updateEditorPlaceholder(idx) {
            const select = document.getElementById(`lang-select-${idx}`);
            const lang = select.options[select.selectedIndex].getAttribute('data-lang');
            const editor = document.getElementById(`code-editor-${idx}`);
            let template = "";
            if (lang === 'java') template = "// Java Template\npublic class Main {\n    public static void main(String[] args) {\n    }\n}";
            else if (lang === 'python') template = "# Python Template\n";
            else if (lang === 'c') template = "#include <stdio.h>\nint main() { return 0; }";
            else if (lang === 'cpp') template = "#include <iostream>\nusing namespace std;\nint main() { return 0; }";
            editor.value = template;
        }

        function markAnswered(idx) {
            const btn = document.getElementById(`nav-btn-${idx}`);
            if (btn) btn.classList.add('answered');
        }

        async function saveMCQ(idx, val) {
            markAnswered(idx);
            const qId = document.getElementById(`q-${idx}`).getAttribute('data-id');
            const status = document.getElementById(`save-status-${idx}`);
            if (status) status.innerText = "Saving...";

            try {
                const res = await fetch('../api/save-progress.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ submission_id: submissionId, question_id: qId, type: 'MCQ', answer: val })
                });
                if (res.ok && status) status.innerText = "Saved";
            } catch (e) {
                if (status) status.innerText = "Error saving";
            }
        }

        let saveTimeout;
        function autoSave(idx) {
            const status = document.getElementById(`save-status-${idx}`);
            if (status) status.innerText = "Typing...";
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(() => saveCode(idx), 2000);
        }

        async function saveCode(idx) {
            const editor = document.getElementById(`code-editor-${idx}`);
            const select = document.getElementById(`lang-select-${idx}`);
            const qId = document.getElementById(`q-${idx}`).getAttribute('data-id');
            const status = document.getElementById(`save-status-${idx}`);

            if (!editor) return;
            if (status) status.innerText = "Saving...";

            try {
                markAnswered(idx);
                const res = await fetch('../api/save-progress.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        submission_id: submissionId,
                        question_id: qId,
                        type: 'Coding',
                        answer: editor.value,
                        lang: select.options[select.selectedIndex].text
                    })
                });
                if (res.ok && status) status.innerText = "All changes saved";
            } catch (e) {
                if (status) status.innerText = "Error saving";
            }
        }

        async function submitExam() {
            // Save current question before redirect
            const workspace = document.getElementById(`q-${currentIdx}`);
            if (workspace) {
                const type = workspace.getAttribute('data-type');
                if (type === 'Coding') {
                    await saveCode(currentIdx);
                } else {
                    const selected = document.querySelector(`input[name="ans-${currentIdx}"]:checked`);
                    if (selected) await saveMCQ(currentIdx, selected.value);
                }
            }
            window.location.href = '../api/submit-exam.php?submission_id=' + submissionId;
        }

        function finishExamPrompt() {
            document.getElementById('finish-modal').classList.remove('hidden');
            let sec = 5; // Reduced for faster feedback
            const confirmBtn = document.getElementById('confirm-finish');
            const timerDisplay = document.getElementById('finish-timer');

            const countdown = setInterval(() => {
                sec--;
                if (timerDisplay) timerDisplay.innerText = sec;
                if (sec <= 0) {
                    clearInterval(countdown);
                    if (confirmBtn) {
                        confirmBtn.disabled = false;
                        confirmBtn.style.background = 'var(--primary-color)';
                        confirmBtn.style.cursor = 'pointer';
                    }
                }
            }, 1000);
        }

        function closeFinishModal() {
            document.getElementById('finish-modal').classList.add('hidden');
        }
    </script>
</body>

</html>