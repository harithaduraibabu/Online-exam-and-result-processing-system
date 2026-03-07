<?php
// student/learning-space.php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';
checkRole('student');

$page_title = "Learning Space";
$exam_id = isset($_GET['view_exam']) ? (int) $_GET['view_exam'] : 0;

include '../includes/header.php';
?>

<div class="content-row">
    <?php if ($exam_id): ?>
        <!-- Viewing Specific Exam Material -->
        <?php
        $exam = $conn->query("SELECT * FROM exams WHERE id = $exam_id AND is_material_published = 1")->fetch_assoc();
        if (!$exam):
            echo "<div class='alert alert-danger'>Material not found or access denied.</div>";
        else:
            ?>
            <div style="margin-bottom: 25px;">
                <a href="learning-space.php" style="color: var(--primary-color); text-decoration: none; font-size: 0.9rem;">
                    <i class="fas fa-arrow-left"></i> Back to Learning Space
                </a>
                <h2 style="margin-top: 15px; font-weight: 700;">
                    <?php echo htmlspecialchars($exam['title']); ?> - Solutions
                </h2>
                <p style="color: var(--text-muted);">
                    <?php echo $exam['type']; ?> Assessment Review
                </p>
            </div>

            <div class="stat-card">
                <div class="card-title">Questions & Correct Answers</div>
                <div style="display: flex; flex-direction: column; gap: 30px; margin-top: 20px;">
                    <?php
                    $questions = $conn->query("SELECT * FROM questions WHERE exam_id = $exam_id ORDER BY id ASC");
                    $q_no = 1;
                    while ($q = $questions->fetch_assoc()):
                        ?>
                        <div style="padding: 20px; border: 1px solid #e2e8f0; border-radius: 12px; background: #fff;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                                <span style="font-weight: 600; color: var(--primary-color);">Question
                                    <?php echo $q_no++; ?>
                                </span>
                                <span style="font-size: 0.8rem; background: #f1f5f9; padding: 4px 10px; border-radius: 20px;">
                                    <?php echo $q['marks']; ?> Marks
                                </span>
                            </div>
                            <p style="font-weight: 500; margin-bottom: 20px; font-size: 1.1rem;">
                                <?php echo nl2br(htmlspecialchars($q['question_text'])); ?>
                            </p>

                            <?php if ($q['type'] == 'MCQ'): ?>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                    <?php foreach (['a', 'b', 'c', 'd'] as $opt):
                                        $is_correct = strtoupper($opt) == $q['correct_option'];
                                        $style = $is_correct ? 'border: 2px solid #10b981; background: #f0fdf4;' : 'border: 1px solid #e2e8f0; background: #f8fafc;';
                                        $icon = $is_correct ? '<i class="fas fa-check-circle" style="color: #10b981;"></i>' : '';
                                        ?>
                                        <div
                                            style="padding: 12px 15px; border-radius: 8px; font-size: 0.95rem; display: flex; justify-content: space-between; align-items: center; <?php echo $style; ?>">
                                            <span><strong>
                                                    <?php echo strtoupper($opt); ?>)
                                                </strong>
                                                <?php echo htmlspecialchars($q['option_' . $opt]); ?>
                                            </span>
                                            <?php echo $icon; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div style="margin-top: 15px; font-size: 0.9rem; color: #059669; font-weight: 600;">
                                    Correct Answer: Option
                                    <?php echo $q['correct_option']; ?>
                                </div>
                            <?php else: ?>
                                <div
                                    style="background: #1e293b; color: #f8fafc; padding: 20px; border-radius: 8px; font-family: monospace; white-space: pre-wrap; font-size: 0.9rem;">
                                    <?php echo htmlspecialchars($q['coding_template']); ?>
                                </div>
                                <div style="margin-top: 15px;">
                                    <h5 style="margin-bottom: 10px;">Reference Solution / Logic</h5>
                                    <p style="font-size: 0.9rem; color: var(--text-muted);">Compare your implementation with the
                                        expected test cases below.</p>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 10px;">
                                        <div style="padding: 10px; background: #f1f5f9; border-radius: 6px;">
                                            <small style="color: var(--text-muted);">Input 1:</small>
                                            <div style="font-family: monospace;">
                                                <?php echo htmlspecialchars($q['test_case_1_input']); ?>
                                            </div>
                                        </div>
                                        <div style="padding: 10px; background: #f1f5f9; border-radius: 6px;">
                                            <small style="color: var(--text-muted);">Output 1:</small>
                                            <div style="font-family: monospace;">
                                                <?php echo htmlspecialchars($q['test_case_1_output']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <!-- Main Learning Space View -->
        <div style="margin-bottom: 30px;">
            <h2 style="font-weight: 700;">Learning Space</h2>
            <p style="color: var(--text-muted);">Access answers and study materials for completed assessments.</p>
        </div>

        <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));">
            <!-- Section: Published Assessments -->
            <div class="stat-card">
                <h3 style="margin-bottom: 20px; font-weight: 600;">Assessment Materials</h3>
                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <?php
                    $materials = $conn->query("SELECT * FROM exams WHERE is_material_published = 1 ORDER BY end_date DESC");
                    if ($materials->num_rows > 0):
                        while ($row = $materials->fetch_assoc()):
                            ?>
                            <div
                                style="padding: 18px; border: 1px solid #e2e8f0; border-radius: 12px; background: #f8fafc; display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <h4 style="font-weight: 600;">
                                        <?php echo htmlspecialchars($row['title']); ?>
                                    </h4>
                                    <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">
                                        <?php echo $row['type']; ?> • Released:
                                        <?php echo date('d M, Y', strtotime($row['end_date'])); ?>
                                    </p>
                                </div>
                                <a href="?view_exam=<?php echo $row['id']; ?>" class="btn btn-primary"
                                    style="padding: 8px 16px; font-size: 0.85rem; text-decoration: none; width: auto;">View
                                    Solutions</a>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 40px 0;">
                            <i class="fas fa-book-reader"
                                style="font-size: 3rem; color: var(--bg-color); margin-bottom: 15px;"></i>
                            <p style="color: var(--text-muted);">No materials published yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Section: Other Resources -->
            <div class="stat-card">
                <h3 style="margin-bottom: 20px; font-weight: 600;">General Resources</h3>
                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <?php
                    $resources = $conn->query("SELECT * FROM resources ORDER BY created_at DESC");
                    if ($resources->num_rows > 0):
                        while ($res = $resources->fetch_assoc()):
                            $icon_class = $res['type'] == 'video' ? 'fa-video' : 'fa-file-pdf';
                            $bg_class = $res['type'] == 'video' ? 'e0e7ff' : 'fee2e2';
                            $text_class = $res['type'] == 'video' ? '4338ca' : 'ef4444';
                            ?>
                            <a href="../<?php echo $res['file_path']; ?>" target="_blank"
                                style="text-decoration: none; color: inherit; display: block;">
                                <div
                                    style="padding: 15px; border: 1px solid #e2e8f0; border-radius: 12px; display: flex; align-items: center; gap: 15px; transition: 0.2s; cursor: pointer;">
                                    <div
                                        style="width: 45px; height: 45px; background: #<?php echo $bg_class; ?>; color: #<?php echo $text_class; ?>; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                                        <i class="fas <?php echo $icon_class; ?>"></i>
                                    </div>
                                    <div>
                                        <h5 style="font-weight: 600;"><?php echo htmlspecialchars($res['title']); ?></h5>
                                        <p style="font-size: 0.75rem; color: var(--text-muted); text-transform: capitalize;">
                                            <?php echo $res['type']; ?> Resource</p>
                                    </div>
                                </div>
                            </a>
                        <?php endwhile;
                    else: ?>
                        <div style="text-align: center; padding: 20px;">
                            <p style="font-size: 0.85rem; color: var(--text-muted);">No additional resources available yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>