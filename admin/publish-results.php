<?php
// admin/publish-results.php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';
checkRole('admin');

$page_title = "Publish Results";
$success = "";

// Handle Publish Action
if (isset($_GET['publish_exam'])) {
    $exam_id = (int) $_GET['publish_exam'];

    // Auto-calculate MCQ scores first
    $subs = $conn->query("SELECT s.id, s.exam_id FROM submissions s WHERE s.exam_id = $exam_id AND s.status IN ('pending', 'evaluated')");
    while ($s = $subs->fetch_assoc()) {
        $sub_id = $s['id']; // Fix: Define sub_id correctly inside the loop

        // 1. Re-calculate MCQ scores to ensure accuracy
        $conn->query("UPDATE student_answers sa 
                     JOIN questions q ON sa.question_id = q.id 
                     SET sa.is_correct = (CASE WHEN sa.selected_option = q.correct_option THEN 1 ELSE 0 END),
                         sa.marks_obtained = (CASE WHEN sa.selected_option = q.correct_option THEN q.marks ELSE 0 END)
                     WHERE sa.submission_id = $sub_id AND q.type = 'MCQ'");

        // 2. Sum all marks (MCQ + Coding)
        $score_row = $conn->query("SELECT SUM(marks_obtained) as total FROM student_answers WHERE submission_id = $sub_id")->fetch_assoc();
        $total_score = $score_row['total'] ?? 0;

        $conn->query("UPDATE submissions SET score = $total_score, status = 'published' WHERE id = $sub_id");
    }

    $success = "Results published and scores calculated for the selected exam!";
}

// Handle Individual Publish Action
if (isset($_GET['publish_submission'])) {
    $sub_id = (int) $_GET['publish_submission'];

    // Fetch submission to verify it exists and is actionable
    $check = $conn->query("SELECT id FROM submissions WHERE id = $sub_id AND status IN ('pending', 'evaluated')");
    if ($check->num_rows > 0) {
        // 1. Re-calculate MCQ scores to ensure accuracy
        $conn->query("UPDATE student_answers sa 
                     JOIN questions q ON sa.question_id = q.id 
                     SET sa.is_correct = (CASE WHEN sa.selected_option = q.correct_option THEN 1 ELSE 0 END),
                         sa.marks_obtained = (CASE WHEN sa.selected_option = q.correct_option THEN q.marks ELSE 0 END)
                     WHERE sa.submission_id = $sub_id AND q.type = 'MCQ'");

        // 2. Sum all marks (MCQ + Coding)
        $score_row = $conn->query("SELECT SUM(marks_obtained) as total FROM student_answers WHERE submission_id = $sub_id")->fetch_assoc();
        $total_score = $score_row['total'] ?? 0;

        $conn->query("UPDATE submissions SET score = $total_score, status = 'published' WHERE id = $sub_id");
        $success = "Student result published successfully!";
    }
}

include '../includes/header.php';
?>

<div class="content-row" style="display: flex; flex-direction: column; gap: 25px;">

    <!-- ── PENDING RESULTS ── -->
    <div class="stat-card" style="padding: 35px;">
        <div class="card-title" style="margin-bottom: 25px;">
            <span><i class="fas fa-hourglass-half" style="color: var(--warning); margin-right: 10px;"></i> Pending
                Results — Awaiting Publish</span>
        </div>
        <?php if ($success)
            echo "<div class='alert' style='background:#ecfdf5;color:#065f46;margin-bottom:20px;'>$success</div>"; ?>

        <?php
        $pending_exams = $conn->query("
            SELECT e.id, e.title, e.total_marks, COUNT(s.id) as sub_count
            FROM exams e
            JOIN submissions s ON e.id = s.exam_id
            WHERE s.status IN ('pending', 'evaluated')
            GROUP BY e.id
        ");
        if ($pending_exams->num_rows > 0):
            while ($exam = $pending_exams->fetch_assoc()):
                ?>
                <div style="margin-bottom: 30px; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden;">
                    <div
                        style="background: #f8fafc; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h4 style="font-weight: 600; margin: 0;"><?php echo htmlspecialchars($exam['title']); ?></h4>
                            <span style="font-size: 0.8rem; color: var(--text-muted);"><?php echo $exam['sub_count']; ?>
                                submission(s) pending</span>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <a href="?publish_exam=<?php echo $exam['id']; ?>" class="btn btn-primary"
                                style="padding:6px 15px;font-size:0.8rem;width:auto;"
                                onclick="return confirm('Publish all results for this exam?')">
                                <i class="fas fa-paper-plane" style="margin-right:5px;"></i> Publish All
                            </a>
                        </div>
                    </div>
                    <!-- Per-student score preview -->
                    <table style="width:100%;border-collapse:collapse;">
                        <thead>
                            <tr style="background:#f1f5f9;text-align:left;">
                                <th style="padding:10px 15px;font-size:0.8rem;color:var(--text-muted);">Student</th>
                                <th style="padding:10px 15px;font-size:0.8rem;color:var(--text-muted);">Answers Saved</th>
                                <th style="padding:10px 15px;font-size:0.8rem;color:var(--text-muted);">Score Preview</th>
                                <th style="padding:10px 15px;font-size:0.8rem;color:var(--text-muted);">Submitted At</th>
                                <th style="padding:10px 15px;font-size:0.8rem;color:var(--text-muted);">Status</th>
                                <th style="padding:10px 15px;font-size:0.8rem;color:var(--text-muted);">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $students = $conn->query("
                    SELECT s.id as sub_id, s.started_at, s.status, u.full_name,
                           COUNT(sa.id) as answers_saved,
                           SUM(sa.is_correct) as total_correct,
                           SUM(sa.marks_obtained) as current_score
                    FROM submissions s
                    JOIN users u ON s.student_id = u.id
                    LEFT JOIN student_answers sa ON sa.submission_id = s.id
                    LEFT JOIN questions q ON q.id = sa.question_id
                    WHERE s.exam_id = {$exam['id']} AND s.status IN ('pending', 'evaluated')
                    GROUP BY s.id
                    ORDER BY u.full_name ASC
                ");
                            while ($stu = $students->fetch_assoc()):
                                ?>
                                <tr style="border-top:1px solid #f1f5f9;">
                                    <td style="padding:12px 15px;font-weight:500;">
                                        <?php echo htmlspecialchars($stu['full_name']); ?>
                                    </td>
                                    <td style="padding:12px 15px;"><?php echo $stu['answers_saved']; ?> answers</td>
                                    <td style="padding:12px 15px;color:var(--secondary-color);font-weight:600;">
                                        <?php echo (float) ($stu['current_score'] ?? 0); ?> marks
                                        <small
                                            style="display:block;font-size:0.7rem;color:var(--text-muted);font-weight:400;">(<?php echo $stu['total_correct']; ?>
                                            correct)</small>
                                    </td>
                                    <td style="padding:12px 15px;font-size:0.85rem;color:var(--text-muted);">
                                        <?php echo date('d M Y, h:i A', strtotime($stu['started_at'])); ?>
                                    </td>
                                    <td style="padding:12px 15px;">
                                        <span
                                            style="background:<?php echo $stu['status'] == 'evaluated' ? '#dcfce7' : '#fef3c7'; ?>;color:<?php echo $stu['status'] == 'evaluated' ? '#166534' : '#92400e'; ?>;padding:3px 10px;border-radius:20px;font-size:0.75rem;font-weight:600;"><?php echo ucfirst($stu['status'] ?? 'pending'); ?></span>
                                    </td>
                                    <td style="padding:12px 15px;">
                                        <a href="?publish_submission=<?php echo $stu['sub_id']; ?>" class="btn btn-primary"
                                            style="padding: 4px 10px; font-size: 0.7rem; width: auto; background: var(--secondary-color);"
                                            onclick="return confirm('Publish results for this student?')">
                                            Publish
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endwhile; else: ?>
            <p style="text-align:center;color:var(--text-muted);padding:30px 0;">No pending results to publish.</p>
        <?php endif; ?>
    </div>

    <!-- ── PUBLISHED RESULTS ── -->
    <div class="stat-card" style="padding: 35px;">
        <div class="card-title" style="margin-bottom: 25px;">
            <span><i class="fas fa-check-circle" style="color: var(--secondary-color); margin-right: 10px;"></i>
                Published Results — Student Scores</span>
        </div>
        <?php
        $published_exams = $conn->query("
            SELECT DISTINCT e.id, e.title,
                   (SELECT COALESCE(SUM(q.marks),0) FROM questions q WHERE q.exam_id = e.id) AS total_marks
            FROM exams e
            JOIN submissions s ON e.id = s.exam_id
            WHERE s.status = 'published'
            ORDER BY e.title ASC
        ");
        if ($published_exams->num_rows > 0):
            while ($exam = $published_exams->fetch_assoc()):
                ?>
                <div style="margin-bottom: 30px; border: 1px solid #d1fae5; border-radius: 12px; overflow: hidden;">
                    <div
                        style="background: #ecfdf5; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h4 style="font-weight: 600; margin: 0; color: #065f46;">
                                <?php echo htmlspecialchars($exam['title']); ?>
                            </h4>
                            <span style="font-size: 0.8rem; color: #047857;">Total Marks:
                                <?php echo $exam['total_marks']; ?></span>
                        </div>
                        <a href="exam-results-report.php?exam_id=<?php echo $exam['id']; ?>" target="_blank" class="btn"
                            style="background: white; color: #059669; border: 1px solid #10b981; font-size: 0.75rem; padding: 6px 12px; border-radius: 6px; display: flex; align-items: center; gap: 6px; text-decoration: none; width: auto;">
                            <i class="fas fa-download"></i> Download Results
                        </a>
                    </div>
                    <table style="width:100%;border-collapse:collapse;">
                        <thead>
                            <tr style="background:#f8fafc;text-align:left;">
                                <th style="padding:10px 15px;font-size:0.8rem;color:var(--text-muted);">#</th>
                                <th style="padding:10px 15px;font-size:0.8rem;color:var(--text-muted);">Student Name</th>
                                <th style="padding:10px 15px;font-size:0.8rem;color:var(--text-muted);">Score</th>
                                <th style="padding:10px 15px;font-size:0.8rem;color:var(--text-muted);">Percentage</th>
                                <th style="padding:10px 15px;font-size:0.8rem;color:var(--text-muted);">Correct / Wrong</th>
                                <th style="padding:10px 15px;font-size:0.8rem;color:var(--text-muted);">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $rank = 0;
                            $scores = $conn->query("
                    SELECT s.score, s.started_at, u.full_name,
                           (SELECT COALESCE(SUM(q2.marks),0) FROM questions q2 WHERE q2.exam_id = s.exam_id) AS total_marks,
                           SUM(CASE WHEN sa.is_correct = 1 THEN 1 ELSE 0 END) as correct_count,
                           SUM(CASE WHEN sa.is_correct = 0 AND sa.selected_option IS NOT NULL THEN 1 ELSE 0 END) as wrong_count
                    FROM submissions s
                    JOIN users u ON s.student_id = u.id
                    LEFT JOIN student_answers sa ON sa.submission_id = s.id
                    WHERE s.exam_id = {$exam['id']} AND s.status = 'published'
                    GROUP BY s.id
                    ORDER BY s.score DESC
                ");
                            while ($row = $scores->fetch_assoc()):
                                $rank++;
                                $total_marks = $row['total_marks'];
                                $pct = $total_marks > 0 ? round(($row['score'] / $total_marks) * 100) : 0;
                                $pct_color = $pct >= 75 ? '#10b981' : ($pct >= 40 ? '#f59e0b' : '#ef4444');
                                $medal = $rank;
                                ?>
                                <tr style="border-top:1px solid #f1f5f9;">
                                    <td style="padding:12px 15px;font-size:0.9rem;"><?php echo $medal; ?></td>
                                    <td style="padding:12px 15px;font-weight:600;">
                                        <?php echo htmlspecialchars($row['full_name']); ?>
                                    </td>
                                    <td style="padding:12px 15px;font-weight:700;color:var(--primary-color);">
                                        <?php echo $row['score']; ?> / <?php echo $total_marks; ?>
                                    </td>
                                    <td style="padding:12px 15px;">
                                        <span style="font-weight:700;color:<?php echo $pct_color; ?>;"><?php echo $pct; ?>%</span>
                                    </td>
                                    <td style="padding:12px 15px;">
                                        <span style="color:#10b981;font-weight:600;"><?php echo $row['correct_count']; ?> ✓</span>
                                        &nbsp;
                                        <span style="color:#ef4444;font-weight:600;"><?php echo $row['wrong_count']; ?> ✗</span>
                                    </td>
                                    <td style="padding:12px 15px;font-size:0.82rem;color:var(--text-muted);">
                                        <?php echo date('d M Y', strtotime($row['started_at'])); ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endwhile; else: ?>
            <p style="text-align:center;color:var(--text-muted);padding:30px 0;">No published results yet.</p>
        <?php endif; ?>
    </div>

    <!-- ── NOT ATTENDED ── -->
    <div class="stat-card" style="padding: 35px;">
        <div class="card-title" style="margin-bottom: 25px;">
            <span><i class="fas fa-user-times" style="color: #ef4444; margin-right: 10px;"></i>
                Not Attended — Students Who Missed Exams</span>
        </div>
        <?php
        $now = date('Y-m-d H:i:s');
        $expired_exams = $conn->query("
            SELECT DISTINCT e.id, e.title, e.end_date
            FROM exams e
            WHERE e.status = 'active' AND e.end_date < '$now'
            ORDER BY e.end_date DESC
        ");

        if ($expired_exams && $expired_exams->num_rows > 0):
            while ($exam = $expired_exams->fetch_assoc()):
                // Find students who didn't submit
                $not_attended = $conn->query("
                    SELECT u.full_name, u.email, u.register_number
                    FROM users u
                    WHERE u.role = 'student'
                    AND u.id NOT IN (
                        SELECT student_id FROM submissions WHERE exam_id = {$exam['id']}
                    )
                    ORDER BY u.full_name ASC
                ");
                if ($not_attended->num_rows == 0)
                    continue;
                ?>
                <div style="margin-bottom: 30px; border: 1px solid rgba(239,68,68,0.2); border-radius: 12px; overflow: hidden;">
                    <div
                        style="background: rgba(239,68,68,0.05); padding: 15px 20px; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h4 style="font-weight: 600; margin: 0; color: var(--text-main);">
                                <?php echo htmlspecialchars($exam['title']); ?>
                            </h4>
                            <span style="font-size: 0.8rem; color: #ef4444;">Ended:
                                <?php echo date('d M Y, h:i A', strtotime($exam['end_date'])); ?></span>
                        </div>
                        <span
                            style="background: rgba(239,68,68,0.1); color: #ef4444; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 700;">
                            <?php echo $not_attended->num_rows; ?> Not Attended
                        </span>
                    </div>
                    <table style="width:100%;border-collapse:collapse;">
                        <thead>
                            <tr style="background:var(--bg-color);text-align:left;">
                                <th style="padding:10px 15px;font-size:0.8rem;color:var(--text-muted);">Student Name</th>
                                <th style="padding:10px 15px;font-size:0.8rem;color:var(--text-muted);">Register No.</th>
                                <th style="padding:10px 15px;font-size:0.8rem;color:var(--text-muted);">Email</th>
                                <th style="padding:10px 15px;font-size:0.8rem;color:var(--text-muted);">Remark</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($stu = $not_attended->fetch_assoc()): ?>
                                <tr style="border-top:1px solid var(--border-color);">
                                    <td style="padding:12px 15px;font-weight:600;color:var(--text-main);">
                                        <?php echo htmlspecialchars($stu['full_name']); ?>
                                    </td>
                                    <td style="padding:12px 15px;color:var(--text-muted);">
                                        <?php echo htmlspecialchars($stu['register_number'] ?? '—'); ?>
                                    </td>
                                    <td style="padding:12px 15px;color:var(--text-muted);font-size:0.85rem;">
                                        <?php echo htmlspecialchars($stu['email']); ?>
                                    </td>
                                    <td style="padding:12px 15px;">
                                        <span
                                            style="background:rgba(239,68,68,0.1);color:#ef4444;padding:3px 10px;border-radius:20px;font-size:0.75rem;font-weight:700;">Not
                                            Attended</span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endwhile; else: ?>
            <p style="text-align:center;color:var(--text-muted);padding:30px 0;">No expired exams with unattended students.
            </p>
        <?php endif; ?>
    </div>

</div>

<?php include '../includes/footer.php'; ?>