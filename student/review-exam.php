<?php
// student/review-exam.php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';
checkRole('student');

$submission_id = isset($_GET['submission_id']) ? (int) $_GET['submission_id'] : 0;
$student_id = $_SESSION['user_id'];

if (!$submission_id) {
    header("Location: results.php");
    exit();
}

// Fetch Submission and Exam details
$sql = "SELECT s.*, e.title, e.total_marks, e.type as exam_type 
        FROM submissions s 
        JOIN exams e ON s.exam_id = e.id 
        WHERE s.id = $submission_id AND s.student_id = $student_id AND s.status = 'published'";
$sub_res = $conn->query($sql);

if ($sub_res->num_rows == 0) {
    header("Location: results.php");
    exit();
}

$submission = $sub_res->fetch_assoc();
$percentage = ($submission['total_marks'] > 0) ? round(($submission['score'] / $submission['total_marks']) * 100, 2) : 0;

$page_title = "Review: " . $submission['title'];
include '../includes/header.php';
?>

<style>
    .review-header {
        background: var(--card-bg);
        padding: 25px 35px;
        border-radius: 16px;
        margin-bottom: 25px;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--border-color);
    }

    .q-card {
        background: var(--card-bg);
        padding: 30px;
        border-radius: 12px;
        margin-bottom: 20px;
        position: relative;
        border: 1px solid var(--border-color);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
    }

    .q-status-bar {
        position: absolute;
        left: 0;
        top: 20px;
        bottom: 20px;
        width: 6px;
        border-radius: 0 4px 4px 0;
    }

    .q-status-correct {
        background-color: #10b981;
    }

    .q-status-incorrect {
        background-color: #ef4444;
    }

    .q-status-unattempted {
        background-color: #94a3b8;
    }

    .option-row {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-top: 12px;
        padding: 8px 12px;
        border-radius: 8px;
        transition: background 0.2s;
    }

    .option-dot {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        border: 2px solid #cbd5e1;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .option-dot.selected {
        border-color: var(--primary-color);
    }

    .option-dot.selected::after {
        content: '';
        width: 10px;
        height: 10px;
        background: var(--primary-color);
        border-radius: 50%;
    }

    .correct-indicator {
        margin-left: auto;
        font-size: 0.8rem;
        font-weight: 600;
        color: #10b981;
    }

    .legend {
        display: flex;
        gap: 20px;
        justify-content: center;
        margin-bottom: 25px;
        font-size: 0.9rem;
        font-weight: 500;
        color: var(--text-muted);
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
    }
</style>

<div class="review-header">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="font-size: 1.5rem; color: var(--text-main); margin: 0;">Exam Performance Detail</h2>
        <div style="text-align: right;">
            <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">Overall Percentage: <span
                    style="font-weight: 700; color: var(--text-main);">
                    <?php echo $percentage; ?>% (
                    <?php echo $submission['score']; ?>/
                    <?php echo $submission['total_marks']; ?>)
                </span></p>
        </div>
    </div>
    <div
        style="display: flex; justify-content: space-between; border-top: 1px solid var(--border-color); pt: 15px; margin-top: 15px; padding-top: 15px;">
        <p style="margin: 0; color: var(--text-muted);">
            <?php echo htmlspecialchars($submission['title']); ?>
        </p>
        <p style="margin: 0; color: var(--text-muted);">Section Score: <span
                style="font-weight: 600; color: var(--text-main);">
                <?php echo $submission['score']; ?>/
                <?php echo $submission['total_marks']; ?>
            </span></p>
    </div>
</div>

<div class="legend">
    <div class="legend-item">
        <div class="dot q-status-correct"></div> Correct
    </div>
    <div class="legend-item">
        <div class="dot q-status-incorrect"></div> Incorrect
    </div>
    <div class="legend-item">
        <div class="dot q-status-unattempted"></div> Unattempted
    </div>
</div>

<div class="questions-list">
    <?php
    // Fetch all questions for this exam
    $q_sql = "SELECT q.*, sa.selected_option, sa.is_correct, sa.marks_obtained, sa.submitted_code,
                     sa.passed_test_cases, sa.total_test_cases, sa.evaluation_status
              FROM questions q
              LEFT JOIN student_answers sa ON sa.question_id = q.id AND sa.submission_id = $submission_id
              WHERE q.exam_id = {$submission['exam_id']}
              ORDER BY q.id ASC";
    $q_res = $conn->query($q_sql);
    $q_num = 1;
    $total_q = $q_res->num_rows;

    while ($q = $q_res->fetch_assoc()) {
        $attempted = ($q['selected_option'] !== null || $q['submitted_code'] !== null);
        $is_correct = ($q['is_correct'] == 1);

        $status_class = "q-status-unattempted";
        if ($attempted) {
            $status_class = $is_correct ? "q-status-correct" : "q-status-incorrect";
        }
        ?>
        <div class="q-card">
            <div class="q-status-bar <?php echo $status_class; ?>"></div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                <span style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted);">Q
                    <?php echo $q_num; ?> of
                    <?php echo $total_q; ?>
                </span>
                <span style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted);">
                    <?php echo $q['marks_obtained'] ?? 0; ?> /
                    <?php echo $q['marks']; ?> Marks
                </span>
            </div>

            <p style="font-size: 1.1rem; color: var(--text-main); font-weight: 500; line-height: 1.6; margin-bottom: 25px;">
                <?php echo nl2br(htmlspecialchars($q['question_text'])); ?>
            </p>

            <?php if ($q['type'] == 'MCQ'): ?>
                <div class="options-container">
                    <?php
                    $opts = ['A' => $q['option_a'], 'B' => $q['option_b'], 'C' => $q['option_c'], 'D' => $q['option_d']];
                    foreach ($opts as $key => $val):
                        if (empty($val))
                            continue;
                        $is_selected = ($q['selected_option'] == $key);
                        $is_right = ($q['correct_option'] == $key);
                        ?>
                        <div class="option-row"
                            style="<?php echo ($is_selected && !$is_right) ? 'background: rgba(239, 68, 68, 0.1);' : ($is_right ? 'background: rgba(16, 185, 129, 0.1);' : ''); ?>">
                            <div class="option-dot <?php echo $is_selected ? 'selected' : ''; ?>"></div>
                            <span
                                style="color: var(--text-main); <?php echo ($is_selected || $is_right) ? 'font-weight: 600;' : ''; ?>">
                                <?php echo htmlspecialchars($val); ?>
                            </span>
                            <?php if ($is_right): ?>
                                <span class="correct-indicator"><i class="fas fa-check-circle"></i> Correct Answer</span>
                            <?php elseif ($is_selected): ?>
                                <span class="correct-indicator" style="color: #ef4444;"><i class="fas fa-times-circle"></i> Your
                                    Answer</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: // Coding Question ?>
                <div style="margin-top: 15px;">
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; background: #f1f5f9; padding: 12px 20px; border-radius: 10px;">
                        <div>
                            <span style="font-size: 0.85rem; color: var(--text-muted); font-weight: 500;">Test Cases:</span>
                            <span style="margin-left:8px; font-weight:700; color: var(--text-main);">
                                Passed <?php echo $q['passed_test_cases'] ?? 0; ?> of <?php echo $q['total_test_cases'] ?? 0; ?>
                            </span>
                        </div>
                        <?php if ($q['evaluation_status']): ?>
                            <?php
                            $status_color = '#ef4444'; // Wrong Answer
                            $status_bg = 'rgba(239, 68, 68, 0.1)';
                            if ($q['evaluation_status'] == 'Accepted') {
                                $status_color = '#10b981';
                                $status_bg = 'rgba(16, 185, 129, 0.1)';
                            } elseif ($q['evaluation_status'] == 'Partial') {
                                $status_color = '#f59e0b';
                                $status_bg = 'rgba(245, 158, 11, 0.1)';
                            }
                            ?>
                            <span
                                style="background:<?php echo $status_bg; ?>; color:<?php echo $status_color; ?>; padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.5px;">
                                <?php echo $q['evaluation_status']; ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <label
                        style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 8px;">Your
                        Submission:</label>
                    <pre
                        style="background: var(--bg-color); padding: 15px; border-radius: 8px; border: 1px solid var(--border-color); font-family: monospace; font-size: 0.9rem; overflow-x: auto; color: var(--text-main);"><?php echo htmlspecialchars($q['submitted_code'] ?: '// No code submitted'); ?></pre>
                </div>
            <?php endif; ?>
        </div>
        <?php
        $q_num++;
    }
    ?>
</div>

<div style="margin-bottom: 40px; text-align: center;">
    <a href="results.php" class="btn"
        style="background: var(--card-bg); color: var(--text-main); text-decoration: none; padding: 12px 30px; border-radius: 8px; font-weight: 600; border: 1px solid var(--border-color);">
        <i class="fas fa-arrow-left" style="margin-right: 8px;"></i> Back to Results
    </a>
</div>

<?php include '../includes/footer.php'; ?>