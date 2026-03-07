<?php
// student/index.php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';
checkRole('student');

$page_title = "Student Dashboard";
$student_id = $_SESSION['user_id'];

// Stats
$user_data = $conn->query("SELECT * FROM users WHERE id = $student_id")->fetch_assoc();
$exams_taken = $conn->query("SELECT COUNT(*) as total FROM submissions WHERE student_id = $student_id")->fetch_assoc()['total'];
$published_results = $conn->query("SELECT COUNT(*) as total FROM submissions WHERE student_id = $student_id AND status = 'published'")->fetch_assoc()['total'];

// Get Active Assessments (Dynamic) - Exclude already submitted exams
$active_exams = $conn->query("
    SELECT e.*, 
    (SELECT COUNT(*) FROM questions q WHERE q.exam_id = e.id) as question_count
    FROM exams e 
    LEFT JOIN submissions s ON e.id = s.exam_id AND s.student_id = $student_id AND s.status IS NOT NULL
    WHERE e.status = 'active' AND s.id IS NULL 
    AND (e.end_date IS NULL OR e.end_date >= NOW()) 
    ORDER BY e.start_date ASC
    LIMIT 4
");

// Get Recent Results
$recent_results = $conn->query("
    SELECT s.id, s.score, s.started_at, e.title, e.total_marks
    FROM submissions s
    JOIN exams e ON s.exam_id = e.id
    WHERE s.student_id = $student_id AND s.status = 'published'
    ORDER BY s.finished_at DESC
    LIMIT 3
");

include '../includes/header.php';
?>

<!-- Profile Header Banner -->
<div class="stat-card" style="padding: 0; overflow: hidden; margin-bottom: 30px; border-radius: 16px;">
    <div
        style="height: 160px; background: <?php echo $user_data['cover_gradient'] ?? 'linear-gradient(135deg, #6366f1 0%, #a855f7 100%)'; ?>; position: relative;">
        <!-- No overlay image needed as gradient is chosen by user -->
    </div>
    <div style="padding: 0 40px 30px; margin-top: -60px; position: relative; z-index: 5;">
        <div style="display: flex; align-items: flex-end; gap: 25px; flex-wrap: wrap;">
            <div
                style="width: 120px; height: 120px; border-radius: 50%; border: 5px solid white; background: white; overflow: hidden; box-shadow: var(--shadow-md);">
                <?php if (!empty($user_data['profile_image'])): ?>
                    <img src="../<?php echo $user_data['profile_image']; ?>"
                        style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                    <div
                        style="width: 100%; height: 100%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: #94a3b8; font-size: 3rem;">
                        <i class="fas fa-user"></i>
                    </div>
                <?php endif; ?>
            </div>
            <div style="padding-bottom: 10px; flex-grow: 1;">
                <h2 style="font-size: 1.8rem; font-weight: 800; color: var(--text-main); margin-bottom: 4px;">
                    <?php echo htmlspecialchars($user_data['full_name']); ?>
                </h2>
                <p style="color: #ffffff; font-weight: 600; margin-bottom: 15px;">
                    <?php echo htmlspecialchars($user_data['email']); ?>
                </p>

                <div
                    style="display: flex; gap: 30px; flex-wrap: wrap; border-top: 1px solid #f1f5f9; padding-top: 15px;">
                    <div style="display: flex; flex-direction: column;">
                        <span
                            style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">Register
                            Number</span>
                        <span
                            style="font-size: 0.95rem; font-weight: 700; color: var(--text-main);"><?php echo htmlspecialchars($user_data['register_number'] ?? 'Not Set'); ?></span>
                    </div>
                    <div style="display: flex; flex-direction: column;">
                        <span
                            style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">Degree</span>
                        <span
                            style="font-size: 0.95rem; font-weight: 700; color: var(--text-main);"><?php echo htmlspecialchars($user_data['degree'] ?? 'Not Set'); ?></span>
                    </div>
                    <div style="display: flex; flex-direction: column;">
                        <span
                            style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">Batch</span>
                        <span
                            style="font-size: 0.95rem; font-weight: 700; color: var(--text-main);"><?php echo htmlspecialchars($user_data['batch'] ?? 'Not Set'); ?></span>
                    </div>
                    <div style="display: flex; flex-direction: column;">
                        <span
                            style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">College</span>
                        <span
                            style="font-size: 0.95rem; font-weight: 700; color: var(--text-main);"><?php echo htmlspecialchars($user_data['college'] ?? 'Not Set'); ?></span>
                    </div>
                </div>
            </div>
            <div style="padding-bottom: 15px;">
                <a href="profile.php" class="btn"
                    style="background: white; color: #6366f1; border: 1px solid #e0e7ff; box-shadow: var(--shadow-sm); padding: 10px 20px; border-radius: 10px; font-weight: 600; text-decoration: none;">Edit
                    Profile</a>
            </div>
        </div>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-title"><i class="fas fa-check-circle"
                style="color: var(--secondary-color); margin-right: 8px;"></i> Exams Completed</div>
        <div class="stat-value"><?php echo $exams_taken; ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-title"><i class="fas fa-chart-line" style="color: var(--accent-color); margin-right: 8px;"></i>
            Results Published</div>
        <div class="stat-value"><?php echo $published_results; ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-title"><i class="fas fa-calendar-alt" style="color: var(--warning); margin-right: 8px;"></i>
            Pending Assessments</div>
        <div class="stat-value"><?php echo $active_exams->num_rows; ?></div>
    </div>
</div>

<!-- ── RECENT RESULTS ── -->
<h3
    style="margin-bottom: 25px; font-weight: 700; color: var(--text-main); font-size: 1.5rem; letter-spacing: -0.025em; margin-top: 40px;">
    Recent Results</h3>
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); margin-bottom: 40px;">
    <?php if ($recent_results->num_rows > 0): ?>
        <?php while ($res = $recent_results->fetch_assoc()):
            $pct = ($res['total_marks'] > 0) ? round(($res['score'] / $res['total_marks']) * 100) : 0;
            $status_color = ($pct >= 40) ? 'var(--secondary-color)' : '#ef4444';
            ?>
            <div class="stat-card" style="padding: 25px; border-left: 5px solid <?php echo $status_color; ?>;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                    <div>
                        <h4 style="font-weight: 700; font-size: 1.1rem; margin: 0; color: var(--text-main);">
                            <?php echo htmlspecialchars($res['title']); ?>
                        </h4>
                        <span
                            style="font-size: 0.8rem; color: var(--text-muted);"><?php echo date('d M, Y', strtotime($res['started_at'])); ?></span>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-weight: 700; font-size: 1.2rem; color: <?php echo $status_color; ?>;">
                            <?php echo $pct; ?>%
                        </div>
                        <span
                            style="font-size: 0.7rem; font-weight: 600; color: var(--text-muted);"><?php echo $res['score']; ?>
                            / <?php echo $res['total_marks']; ?></span>
                    </div>
                </div>
                <a href="review-exam.php?submission_id=<?php echo $res['id']; ?>" class="btn"
                    style="width: 100%; background: var(--bg-color); color: var(--primary-color); text-decoration: none; text-align: center; padding: 10px; font-size: 0.9rem; font-weight: 600; border: 1px solid var(--border-color); display: block;">
                    View Detailed Review
                </a>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="stat-card"
            style="grid-column: 1 / -1; padding: 30px; text-align: center; color: var(--text-muted); font-style: italic;">
            No published results yet. Complete an exam to see your performance here!
        </div>
    <?php endif; ?>
</div>

<h3
    style="margin-bottom: 25px; font-weight: 700; color: var(--text-main); font-size: 1.5rem; letter-spacing: -0.025em;">
    Active Assessments</h3>
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));">
    <?php if ($active_exams->num_rows > 0): ?>
        <?php while ($exam = $active_exams->fetch_assoc()):
            $current_time = date('Y-m-d H:i:s');
            $is_available = ($exam['start_date'] === null || $current_time >= $exam['start_date']);
            ?>
            <div class="stat-card"
                style="display: flex; flex-direction: column; justify-content: space-between; padding: 35px;">
                <div>
                    <div style="display: flex; gap: 20px; align-items: flex-start; margin-bottom: 25px;">
                        <div
                            style="width: 48px; height: 48px; border-radius: 14px; background: <?php echo $exam['type'] == 'Coding' ? 'rgba(99, 102, 241, 0.1)' : 'rgba(16, 185, 129, 0.1)'; ?>; display: flex; align-items: center; justify-content: center; color: <?php echo $exam['type'] == 'Coding' ? 'var(--primary-color)' : 'var(--secondary-color)'; ?>; flex-shrink: 0; font-size: 1.5rem;">
                            <i class="<?php echo $exam['type'] == 'Coding' ? 'fas fa-code' : 'fas fa-list-ul'; ?>"></i>
                        </div>
                        <div>
                            <h4
                                style="font-weight: 700; font-size: 1.25rem; margin-bottom: 4px; color: var(--text-main); line-height: 1.3;">
                                <?php echo htmlspecialchars($exam['title']); ?>
                            </h4>
                            <span
                                style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: <?php echo $exam['type'] == 'Coding' ? 'var(--primary-color)' : 'var(--secondary-color)'; ?>; background: <?php echo $exam['type'] == 'Coding' ? 'rgba(99, 102, 241, 0.1)' : 'rgba(16, 185, 129, 0.1)'; ?>; padding: 4px 10px; border-radius: 20px; display: inline-block; margin-top: 5px;">
                                <?php echo $exam['type']; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div
                    style="margin-bottom: 25px; background: var(--bg-color); padding: 20px; border-radius: 12px; border: 1px solid var(--border-color); transition: var(--transition);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <div
                            style="display: flex; align-items: center; gap: 8px; color: var(--text-muted); font-size: 0.85rem; font-weight: 500;">
                            <i class="far fa-clock"></i> Duration
                        </div>
                        <div style="font-weight: 700; color: var(--text-main);"><?php echo $exam['duration']; ?> Min</div>
                    </div>
                    <?php if (!$is_available): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                            <div
                                style="display: flex; align-items: center; gap: 8px; color: var(--text-muted); font-size: 0.85rem; font-weight: 500;">
                                <i class="far fa-calendar"></i> Starts
                            </div>
                            <div style="font-weight: 600; color: var(--warning); font-size: 0.85rem;">
                                <?php echo date('d M, h:i A', strtotime($exam['start_date'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <div
                            style="display: flex; align-items: center; gap: 8px; color: var(--text-muted); font-size: 0.85rem; font-weight: 500;">
                            <i class="fas fa-question-circle"></i> No. of Questions
                        </div>
                        <div style="font-weight: 700; color: var(--text-main);"><?php echo $exam['question_count']; ?></div>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div
                            style="display: flex; align-items: center; gap: 8px; color: var(--text-muted); font-size: 0.85rem; font-weight: 500;">
                            <i class="fas fa-bullseye"></i> Total Marks
                        </div>
                        <div style="font-weight: 700; color: var(--text-main);"><?php echo $exam['total_marks']; ?></div>
                    </div>
                </div>

                <?php if ($is_available): ?>
                    <a href="take-exam.php?id=<?php echo $exam['id']; ?>" class="btn btn-primary"
                        style="width: 100%; text-decoration: none; text-align: center; padding: 14px; font-size: 1.05rem; display: flex; align-items: center; justify-content: center; gap: 10px; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);">
                        Start Assessment <i class="fas fa-arrow-right" style="font-size: 0.9rem;"></i>
                    </a>
                <?php else: ?>
                    <button class="btn"
                        style="width: 100%; background: var(--bg-color); color: var(--warning); cursor: not-allowed; border: 1px solid rgba(245, 158, 11, 0.2); background: rgba(245, 158, 11, 0.05); padding: 14px; font-size: 1.05rem; display: flex; align-items: center; justify-content: center; gap: 10px;"
                        disabled>
                        <i class="fas fa-lock"></i> Opening Soon
                    </button>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="stat-card"
            style="grid-column: 1 / -1; text-align: center; padding: 60px 40px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
            <div
                style="width: 80px; height: 80px; background: #f1f5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; color: var(--text-muted); padding: 25px;">
                <i class="fas fa-coffee" style="font-size: 2rem;"></i>
            </div>
            <h4 style="font-size: 1.25rem; font-weight: 700; color: var(--text-main); margin-bottom: 10px;">You're all
                caught up!</h4>
            <p style="color: var(--text-muted); max-width: 400px; line-height: 1.6;">There are no pending assessments
                available. Take a break or review your past performance.</p>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>