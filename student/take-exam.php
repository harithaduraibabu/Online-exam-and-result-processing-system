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
    if ($sub_row['status'] !== null) {
        // Exam already submitted
        header("Location: index.php");
        exit;
    }
    $submission_id = $sub_row['id'];
    $started_at = strtotime($sub_row['started_at']);
}

// Fetch existing answers to load state (MCQ and Coding)
$existing_answers_res = $conn->query("SELECT * FROM student_answers WHERE submission_id = $submission_id");
$existing_answers = [];
while ($ans = $existing_answers_res->fetch_assoc()) {
    $existing_answers[$ans['question_id']] = $ans;
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
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            z-index: 10;
            border-bottom: 1px solid var(--border-color);
        }

        .exam-body {
            display: flex;
            flex: 1;
            overflow: hidden;
            background: var(--bg-color);
        }

        /* 1. Left Panel: Navigation Palette */
        .nav-panel {
            width: 80px;
            background: var(--bg-color);
            border-right: 1px solid var(--border-color);
            padding: 15px 5px;
            display: flex;
            flex-direction: column;
            align-items: center;
            overflow-y: auto;
            flex-shrink: 0;
        }

        .btn-nav {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            background: var(--card-bg);
            color: var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-weight: 700;
            font-size: 0.95rem;
            margin-bottom: 12px;
            transition: all 0.2s;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .btn-nav:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .btn-nav.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            box-shadow: 0 4px 6px rgba(79, 70, 229, 0.3);
            transform: scale(1.05);
        }

        .btn-nav.marked {
            background: #f59e0b; /* Orange-500 */
            color: white;
            border-color: #f59e0b;
        }

        .btn-nav.answered {
            background: var(--secondary-color);
            color: white;
            border-color: var(--secondary-color);
        }

        /* 2. Middle Panel: Question Content */
        .question-panel {
            flex: 1;
            max-width: 500px;
            background: var(--card-bg);
            padding: 0;
            overflow-y: auto;
            border-right: 1px solid var(--border-color);
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.02);
            position: relative;
            z-index: 5;
            display: flex;
            flex-direction: column;
        }

        .question-header {
            padding: 20px 30px;
            border-bottom: 1px solid var(--border-color);
            background: var(--bg-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .question-content {
            padding: 30px;
            flex: 1;
            font-size: 1.05rem;
            line-height: 1.7;
            color: var(--text-main);
        }

        /* 3. Right Panel: Workspace / Answer Area */
        .workspace-panel {
            flex: 2;
            background: var(--bg-color);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            position: relative;
        }

        .workspace-scrollable {
            flex: 1;
            padding: 25px;
            overflow-y: auto;
        }

        .workspace-footer {
            padding: 15px 25px;
            background: var(--card-bg);
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 -4px 15px rgba(0, 0, 0, 0.03);
        }

        .code-editor-header {
            background: #1e293b;
            color: #94a3b8;
            padding: 10px 20px;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
            border-bottom: 1px solid #334155;
        }

        .code-editor {
            font-family: 'Consolas', 'Monaco', monospace;
            background: #0f172a;
            color: #e2e8f0;
            padding: 20px;
            border-bottom-left-radius: 8px;
            border-bottom-right-radius: 8px;
            width: 100%;
            height: clamp(300px, 50vh, 600px);
            resize: vertical;
            font-size: 0.95rem;
            line-height: 1.6;
            border: 1px solid #1e293b;
            border-top: none;
            outline: none;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .code-editor:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 1px var(--primary-color);
        }

        /* Terminal Styling */
        .execution-result {
            margin-top: 25px;
            background: var(--card-bg);
            border-radius: 8px;
            border: 1px solid var(--border-color);
            overflow: hidden;
            display: none;
        }

        .result-header {
            background: var(--bg-color);
            padding: 12px 20px;
            border-bottom: 1px solid var(--border-color);
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text-main);
        }

        .result-body {
            padding: 20px;
            font-family: 'Consolas', monospace;
            font-size: 0.9rem;
            white-space: pre-wrap;
            min-height: 80px;
            background: #f8fafc;
        }

        .timer-box {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--card-bg);
            padding: 6px 16px;
            border-radius: 20px;
            border: 1px solid var(--border-color);
        }

        .timer-box i {
            color: var(--primary-color);
        }

        .timer {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-main);
            font-variant-numeric: tabular-nums;
        }

        .hidden {
            display: none !important;
        }

        .testcase-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
            margin-top: 15px;
        }

        .testcase-table th {
            background: var(--border-color);
            padding: 10px;
            text-align: left;
            color: var(--text-main);
            font-weight: 600;
        }

        .testcase-table td {
            padding: 10px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-muted);
        }

        .testcase-table tr:last-child td {
            border-bottom: none;
        }

        .status-success {
            color: #16a34a;
            font-weight: 600;
        }

        .status-fail {
            color: #dc2626;
            font-weight: 600;
        }

        /* Modal Overlay */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
    </style>
</head>

<body>
    <div class="exam-header">
        <div style="display: flex; align-items: center; gap: 20px;">
            <div
                style="width: 36px; height: 36px; background: var(--primary-color); color: white; display: flex; align-items: center; justify-content: center; border-radius: 8px; font-weight: 700;">
                EP</div>
            <h2 style="font-weight: 700; font-size: 1.1rem; color: var(--text-main); margin: 0;">
                <?php echo htmlspecialchars($exam['title']); ?>
            </h2>
        </div>
        <div style="display: flex; gap: 20px; align-items: center;">
            <div class="timer-box">
                <i class="far fa-clock"></i>
                <div class="timer" id="time-left">--:--</div>
            </div>
            <button class="btn btn-primary" onclick="finishExamPrompt()"
                style="padding: 8px 24px; font-size: 0.9rem; border-radius: 20px; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.2);">Submit
                Test</button>
        </div>
    </div>

    <div class="exam-body">

        <!-- 1. Left Nav Palette -->
        <div class="nav-panel">
            <?php foreach ($questions as $index => $q): 
                $q_id = $q['id'];
                $is_answered = isset($existing_answers[$q_id]) && ($existing_answers[$q_id]['selected_option'] !== null || $existing_answers[$q_id]['submitted_code'] !== null);
                $is_marked = isset($existing_answers[$q_id]) && $existing_answers[$q_id]['is_marked'];
                
                $classes = [];
                if ($index == 0) $classes[] = 'active';
                if ($is_marked) $classes[] = 'marked';
                else if ($is_answered) $classes[] = 'answered';
                ?>
                <div class="btn-nav <?php echo implode(' ', $classes); ?>" id="nav-btn-<?php echo $index; ?>"
                    onclick="goToQuestion(<?php echo $index; ?>)" title="Question <?php echo $index + 1; ?>">
                    <?php echo $index + 1; ?>
                </div>
            <?php endforeach; ?>

            <div
                style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border-color); display: flex; flex-direction: column; gap: 8px; width: 100%;">
                <div
                    style="display: flex; align-items: center; justify-content: center; flex-direction: column; gap: 4px;">
                    <div class="btn-nav answered"
                        style="width: 12px; height: 12px; margin: 0; min-height: 12px; border-radius: 3px;"></div>
                    <span
                        style="font-size: 0.65rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Done</span>
                </div>
                <div
                    style="display: flex; align-items: center; justify-content: center; flex-direction: column; gap: 4px;">
                    <div class="btn-nav marked"
                        style="width: 12px; height: 12px; margin: 0; min-height: 12px; border-radius: 3px;"></div>
                    <span
                        style="font-size: 0.65rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Review</span>
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
                            <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 20px; color: var(--text-main);">
                                <?php echo $q['type'] == 'Coding' ? 'Programming Question' : 'Multiple Choice Question'; ?>
                            </h3>
                            <div style="white-space: pre-wrap; margin-bottom: 30px;"><?php
                            $cleaned_q_text = str_replace(['\r\n', '\n', '\r'], "\n", $q['question_text']);
                            echo htmlspecialchars($cleaned_q_text);
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
                                    <textarea id="code-editor-<?php echo $index; ?>" class="code-editor" spellcheck="false"><?php
                                       $cleaned_template = str_replace(['\r\n', '\n', '\r'], "\n", $q['coding_template']);
                                       echo htmlspecialchars($cleaned_template);
                                       ?></textarea>

                                    <!-- Advanced Results UI -->
                                    <div id="result-<?php echo $index; ?>" style="display: none; margin-top: 25px;">
                                        <div
                                            style="background: var(--card-bg); border-radius: 8px; border: 1px solid var(--border-color); margin-bottom: 15px; overflow: hidden;">
                                            <div
                                                style="padding: 12px 20px; font-weight: 700; font-size: 0.95rem; border-bottom: 1px solid var(--border-color); color: var(--text-main);">
                                                Result</div>
                                            <div style="padding: 15px 20px; background: var(--bg-color); font-weight: 600; color: var(--text-main);"
                                                id="overall-result-<?php echo $index; ?>"></div>
                                        </div>

                                        <div
                                            style="background: var(--card-bg); border-radius: 8px; border: 1px solid var(--border-color); margin-bottom: 15px; overflow: hidden;">
                                            <div
                                                style="padding: 12px 20px; font-weight: 700; font-size: 0.95rem; border-bottom: 1px solid var(--border-color); color: var(--text-main);">
                                                Compiler Message</div>
                                            <div style="padding: 15px 20px; background: var(--bg-color); font-family: monospace; font-size: 0.85rem; color: var(--text-main);"
                                                id="compiler-msg-<?php echo $index; ?>"></div>
                                        </div>

                                        <div id="testcases-container-<?php echo $index; ?>"
                                            style="background: var(--card-bg); border-radius: 8px; border: 1px solid var(--border-color); overflow: hidden;">
                                            <div
                                                style="padding: 12px 20px; font-weight: 700; font-size: 0.95rem; border-bottom: 1px solid var(--border-color); color: var(--text-main);">
                                                Sample Testcases</div>

                                            <div style="padding: 15px 20px;" id="testcases-list-<?php echo $index; ?>">
                                                <!-- Detailed Testcases Injected by JS -->
                                            </div>

                                            <div style="overflow-x: auto;">
                                                <table class="testcase-table" id="testcase-table-<?php echo $index; ?>"
                                                    style="margin-top:0;">
                                                    <thead>
                                                        <tr>
                                                            <th>Test Case</th>
                                                            <th>Result</th>
                                                            <th>Status</th>
                                                            <th>Time(Ms)</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <!-- Injected by JS -->
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Fixed Footer per Question Workspace -->
                        <div class="workspace-footer">
                            <div style="display: flex; gap: 10px;">
                                <button class="btn <?php echo $is_marked ? 'btn-warning' : ''; ?>" 
                                    id="btn-mark-<?php echo $index; ?>"
                                    style="background: <?php echo $is_marked ? '#f59e0b' : 'white'; ?>; border: 1px solid #f59e0b; color: <?php echo $is_marked ? 'white' : '#f59e0b'; ?>; font-weight: 600; padding: 10px 20px;"
                                    onclick="toggleMark(<?php echo $index; ?>)">
                                    <i class="<?php echo $is_marked ? 'fas' : 'far'; ?> fa-bookmark"></i> 
                                    <?php echo $is_marked ? 'Marked' : 'Mark for Review'; ?>
                                </button>
                                <?php if ($q['type'] == 'Coding'): ?>
                                    <button class="btn"
                                        style="background: white; border: 1px solid #cbd5e1; color: var(--text-main); font-weight: 600; padding: 10px 20px;"
                                        onclick="document.getElementById('code-editor-<?php echo $index; ?>').value=''">
                                        Clear
                                    </button>
                                    <button class="btn"
                                        style="background: white; border: 1px solid #cbd5e1; color: var(--text-main); font-weight: 600; padding: 10px 20px;"
                                        onclick="compileCode(<?php echo $index; ?>)">
                                        Compile & Run
                                    </button>
                                    <button class="btn btn-primary" style="font-weight: 600; padding: 10px 20px;"
                                        onclick="submitCode(<?php echo $index; ?>)">
                                        Submit Code
                                    </button>
                                <?php endif; ?>
                            </div>
                            <div style="display: flex; gap: 10px;">
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
            document.getElementById('time-left').innerText = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }, 1000);

        // Navigation Logic
        function goToQuestion(idx) {
            document.querySelectorAll('.question-workspace-wrapper').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.btn-nav').forEach(el => el.classList.remove('active'));

            document.getElementById(`q-wrapper-${idx}`).classList.remove('hidden');
            document.getElementById(`nav-btn-${idx}`).classList.add('active');

            currentIdx = idx;

            // Adjust prev/next buttons for the currently active wrapper
            document.querySelectorAll(`[id^="btn-prev-"]`).forEach(btn => {
                btn.disabled = currentIdx === 0;
                if (currentIdx === 0) {
                    btn.classList.remove('btn-primary');
                    btn.style.background = '#f1f5f9';
                    btn.style.color = '#94a3b8';
                    btn.style.borderColor = '#e2e8f0';
                } else {
                    btn.classList.add('btn');
                    btn.style.background = 'white';
                    btn.style.color = 'var(--text-main)';
                    btn.style.borderColor = '#cbd5e1';
                }
            });

            document.querySelectorAll(`[id^="btn-next-"]`).forEach(btn => {
                if (currentIdx === totalQuestions - 1) {
                    btn.innerText = 'Submit Assessment';
                    btn.onclick = finishExamPrompt;
                    btn.style.background = 'var(--secondary-color)';
                    btn.style.borderColor = 'var(--secondary-color)';
                } else {
                    btn.innerText = 'Next Question';
                    btn.onclick = nextQuestion;
                    btn.style.background = 'var(--primary-color)';
                    btn.style.borderColor = 'var(--primary-color)';
                }
            });
        }

        // Initial setup for the first question
        goToQuestion(0);

        function nextQuestion() {
            if (currentIdx < totalQuestions - 1) goToQuestion(currentIdx + 1);
        }

        function prevQuestion() {
            if (currentIdx > 0) goToQuestion(currentIdx - 1);
        }

        // Code Editor Auto-Close Brackets & Quotes Logic
        document.addEventListener('keydown', function (e) {
            if (e.target && e.target.classList.contains('code-editor')) {
                const el = e.target;
                const start = el.selectionStart;
                const end = el.selectionEnd;
                const val = el.value;

                // Tab handling (insert 4 spaces)
                if (e.key === 'Tab') {
                    e.preventDefault();
                    el.value = val.substring(0, start) + "    " + val.substring(end);
                    el.selectionStart = el.selectionEnd = start + 4;
                    return;
                }

                // Auto-close pairs
                const pairs = {
                    '(': ')',
                    '{': '}',
                    '[': ']',
                    '"': '"',
                    "'": "'"
                };

                if (pairs[e.key]) {
                    e.preventDefault();
                    const closingChar = pairs[e.key];
                    el.value = val.substring(0, start) + e.key + closingChar + val.substring(end);
                    el.selectionStart = el.selectionEnd = start + 1;
                }

                // Handle backspace when between empty pairs
                if (e.key === 'Backspace' && start === end && start > 0) {
                    const prevChar = val.charAt(start - 1);
                    const nextChar = val.charAt(start);
                    if (pairs[prevChar] === nextChar) {
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
            if (lang === 'java') {
                template = "// You are using Java\npublic class Main {\n    public static void main(String[] args) {\n        // Write your code here\n    }\n}";
            } else if (lang === 'python') {
                template = "# You are using Python\n# Write your code here\n";
            } else if (lang === 'c') {
                template = "// You are using C\n#include <stdio.h>\n\nint main() {\n    // Write your code here\n    return 0;\n}";
            } else if (lang === 'cpp') {
                template = "// You are using C++\n#include <iostream>\nusing namespace std;\n\nint main() {\n    // Write your code here\n    return 0;\n}";
            }
            editor.value = template;
        }

        // AJAX Save Progress
        const isMarked = <?php 
            $marked_arr = [];
            foreach ($questions as $index => $q) {
                $marked_arr[$index] = (isset($existing_answers[$q['id']]) && $existing_answers[$q['id']]['is_marked']) ? 1 : 0;
            }
            echo json_encode($marked_arr);
        ?>;

        function toggleMark(idx) {
            isMarked[idx] = isMarked[idx] ? 0 : 1;
            const btn = document.getElementById(`btn-mark-${idx}`);
            const navBtn = document.getElementById(`nav-btn-${idx}`);
            const qId = document.getElementById(`q-${idx}`).getAttribute('data-id');
            const type = document.getElementById(`q-${idx}`).getAttribute('data-type');
            
            if (isMarked[idx]) {
                btn.style.background = '#f59e0b';
                btn.style.color = 'white';
                btn.innerHTML = '<i class="fas fa-bookmark"></i> Marked';
                navBtn.classList.add('marked');
            } else {
                btn.style.background = 'white';
                btn.style.color = '#f59e0b';
                btn.innerHTML = '<i class="far fa-bookmark"></i> Mark for Review';
                navBtn.classList.remove('marked');
            }

            // Save state via AJAX
            // We need to send the current answer too, or just the mark status
            // For now, let's just send what we have
            const answer = (type === 'MCQ') ? (document.querySelector(`input[name="ans-${idx}"]:checked`)?.value || "") : document.getElementById(`code-editor-${idx}`).value;
            
            fetch('../api/save-progress.php', {
                method: 'POST',
                body: JSON.stringify({ 
                    submission_id: submissionId, 
                    question_id: qId, 
                    type: type, 
                    answer: answer,
                    is_marked: isMarked[idx]
                })
            });
        }

        function markAnswered(idx) {
            if (!isMarked[idx]) {
                document.getElementById(`nav-btn-${idx}`).classList.add('answered');
            }
        }

        async function saveMCQ(idx, val) {
            markAnswered(idx);
            const qId = document.getElementById(`q-${idx}`).getAttribute('data-id');
            await fetch('../api/save-progress.php', {
                method: 'POST',
                body: JSON.stringify({ 
                    submission_id: submissionId, 
                    question_id: qId, 
                    type: 'MCQ', 
                    answer: val,
                    is_marked: isMarked[idx]
                })
            });
        }

        async function compileCode(idx) {
            const editor = document.getElementById(`code-editor-${idx}`);
            const langSelect = document.getElementById(`lang-select-${idx}`);
            const resultBox = document.getElementById(`result-${idx}`);
            const overallResult = document.getElementById(`overall-result-${idx}`);
            const compilerMsg = document.getElementById(`compiler-msg-${idx}`);
            const tbody = document.querySelector(`#testcase-table-${idx} tbody`);
            const listContainer = document.getElementById(`testcases-list-${idx}`);

            resultBox.style.display = 'block';
            overallResult.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Running compilation...';
            compilerMsg.innerText = 'Compiling...';
            compilerMsg.style.color = 'var(--text-muted)';

            // For Compile & Run we focus on the first Sample TestCase
            document.getElementById(`testcase-table-${idx}`).style.display = 'none';
            listContainer.style.display = 'block';
            listContainer.innerHTML = '<div style="color:var(--text-muted);"><i class="fas fa-circle-notch fa-spin"></i> Executing against sample testcase...</div>';

            const cases = window.testCases[idx];
            if (!cases || cases.length === 0) return;

            const sampleCase = cases[0]; // Just run the first one for "Compile & Run"

            try {
                const response = await fetch('../api/compile.php', {
                    method: 'POST',
                    body: JSON.stringify({
                        source_code: editor.value,
                        language_id: langSelect.value,
                        stdin: sampleCase.input,
                        expected_output: sampleCase.expected
                    })
                });

                if (!response.ok) throw new Error('Network response was not ok');
                const data = await response.json();

                if (data.compile_output) {
                    compilerMsg.innerText = data.compile_output;
                    compilerMsg.style.color = '#dc2626';
                    overallResult.innerText = "Compilation Error";
                    overallResult.style.color = "#dc2626";
                    listContainer.innerHTML = '';
                    return;
                } else if (data.stderr) {
                    compilerMsg.innerText = data.stderr;
                    compilerMsg.style.color = '#dc2626';
                    overallResult.innerText = "Runtime Error";
                    overallResult.style.color = "#dc2626";
                    listContainer.innerHTML = '';
                    return;
                }

                compilerMsg.innerText = "Compilation successful";
                compilerMsg.style.color = '#16a34a';

                const isSuccess = data.status && data.status.id === 3;
                if (isSuccess) {
                    overallResult.innerHTML = '1/1 Sample testcase passed';
                    overallResult.style.color = '#166534';
                } else {
                    overallResult.innerHTML = '0/1 Sample testcase passed';
                    overallResult.style.color = '#9a3412';
                }

                // Build the Sample Testcase UI block
                const statusStr = isSuccess ? 'Passed' : 'Failed';
                const statusColor = isSuccess ? '#16a34a' : '#dc2626';
                const actualOutput = data.stdout ? data.stdout : '';

                listContainer.innerHTML = `
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; margin-bottom: 15px;">
                        <div style="font-weight: 700; color: var(--text-main); margin-bottom: 15px;">Testcase 1 - <span style="color: ${statusColor}">${statusStr}</span></div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div>
                                <div style="font-weight: 600; font-size: 0.85rem; margin-bottom: 8px;">Expected Output</div>
                                <div style="background: white; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; font-family: monospace; font-size: 0.9rem; min-height: 50px;">${sampleCase.expected.replace(/\\n/g, '<br>')}</div>
                            </div>
                            <div>
                                <div style="font-weight: 600; font-size: 0.85rem; margin-bottom: 8px;">Your Output</div>
                                <div style="background: white; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; font-family: monospace; font-size: 0.9rem; min-height: 50px;">${actualOutput.replace(/\\n/g, '<br>')}</div>
                            </div>
                        </div>
                    </div>
                `;

            } catch (err) {
                compilerMsg.innerText = "Connection Error: Could not reach compiler. Please check your internet.";
                compilerMsg.style.color = "#dc2626";
                overallResult.innerText = "System Error";
                listContainer.innerHTML = '';
                console.error(err);
            }
        }

        async function submitCode(idx) {
            const editor = document.getElementById(`code-editor-${idx}`);
            const langSelect = document.getElementById(`lang-select-${idx}`);
            const resultBox = document.getElementById(`result-${idx}`);
            const overallResult = document.getElementById(`overall-result-${idx}`);
            const compilerMsg = document.getElementById(`compiler-msg-${idx}`);
            const tbody = document.querySelector(`#testcase-table-${idx} tbody`);
            const listContainer = document.getElementById(`testcases-list-${idx}`);

            resultBox.style.display = 'block';
            overallResult.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Running hidden testcases...';
            compilerMsg.innerText = 'Compiling...';
            compilerMsg.style.color = 'var(--text-muted)';

            document.getElementById(`testcase-table-${idx}`).style.display = 'table';
            listContainer.style.display = 'none';
            tbody.innerHTML = '';

            const cases = window.testCases[idx];
            if (!cases || cases.length === 0) return;

            let passedCount = 0;
            let runFailed = false;

            try {
                for (let i = 0; i < cases.length; i++) {
                    const testCase = cases[i];

                    const response = await fetch('../api/compile.php', {
                        method: 'POST',
                        body: JSON.stringify({
                            source_code: editor.value,
                            language_id: langSelect.value,
                            stdin: testCase.input,
                            expected_output: testCase.expected
                        })
                    });

                    if (!response.ok) throw new Error('Network response was not ok');
                    const data = await response.json();

                    let statusText = "Failed";
                    let statusClass = "status-fail";
                    let icon = '<i class="fas fa-times-circle" style="color: #dc2626; font-size: 1.1rem;"></i>';
                    let time = "-";

                    if (data.compile_output) {
                        compilerMsg.innerText = data.compile_output;
                        compilerMsg.style.color = '#dc2626';
                        runFailed = true;
                        break;
                    } else if (data.stderr) {
                        compilerMsg.innerText = data.stderr;
                        compilerMsg.style.color = '#dc2626';
                        runFailed = true;
                        break;
                    } else if (data.status && data.status.id === 3) {
                        // Accepted
                        statusText = "Success";
                        statusClass = "status-success";
                        icon = '<i class="fas fa-check-circle" style="color: #16a34a; font-size: 1.1rem;"></i>';
                        passedCount++;
                        time = (parseFloat(data.time) * 1000).toFixed(0) || "-";
                        compilerMsg.innerText = "Compilation successful";
                        compilerMsg.style.color = '#16a34a';
                    } else {
                        // Wrong Answer or other status
                        statusText = data.status ? data.status.description : "Failed";
                        compilerMsg.innerText = "Compilation successful";
                        compilerMsg.style.color = '#16a34a';
                        time = data.time ? (parseFloat(data.time) * 1000).toFixed(0) : "-";
                    }

                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${i + 1}</td>
                        <td>${icon}</td>
                        <td class="${statusClass}">${statusText}</td>
                        <td>${time}</td>
                    `;
                    tbody.appendChild(tr);
                }

                if (runFailed) {
                    overallResult.innerText = "Compilation Error";
                    overallResult.style.color = "#dc2626";
                } else {
                    overallResult.innerText = `${passedCount}/${cases.length} Testcases Passed`;
                    if (passedCount === cases.length) {
                        overallResult.style.backgroundColor = '#f0fdf4';
                        overallResult.style.color = '#166534';
                    } else {
                        overallResult.style.backgroundColor = '#fff7ed';
                        overallResult.style.color = '#9a3412';
                    }
                }

            } catch (err) {
                compilerMsg.innerText = "Connection Error: Could not reach compiler. Please check your internet.";
                compilerMsg.style.color = "#dc2626";
                overallResult.innerText = "System Error";
                console.error(err);
            }

            markAnswered(idx);
            // Save code progress
            const qId = document.getElementById(`q-${idx}`).getAttribute('data-id');
            await fetch('../api/save-progress.php', {
                method: 'POST',
                body: JSON.stringify({
                    submission_id: submissionId,
                    question_id: qId,
                    type: 'Coding',
                    answer: editor.value,
                    lang: langSelect.options[langSelect.selectedIndex].text,
                    is_marked: isMarked[idx]
                })
            });
        }

        function finishExamPrompt() {
            document.getElementById('finish-modal').classList.remove('hidden');
            let sec = 10;
            const confirmBtn = document.getElementById('confirm-finish');
            const timerDisplay = document.getElementById('finish-timer');

            const countdown = setInterval(() => {
                sec--;
                timerDisplay.innerText = sec;
                if (sec <= 0) {
                    clearInterval(countdown);
                    confirmBtn.disabled = false;
                    confirmBtn.style.background = 'var(--primary-color)';
                    confirmBtn.style.cursor = 'pointer';
                }
            }, 1000);
        }

        function closeFinishModal() {
            document.getElementById('finish-modal').classList.add('hidden');
        }

        async function submitExam() {
            window.location.href = '../api/submit-exam.php?submission_id=' + submissionId;
        }
    </script>
</body>

</html>