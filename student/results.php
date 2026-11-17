<?php
// student/results.php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';
checkRole('student');

$page_title = "My Results";
$student_id = $_SESSION['user_id'];

include '../includes/header.php';

// Activity for Heatmap (Last 30 days)
$activity_res = $conn->query("
    SELECT DATE(finished_at) as date, COUNT(*) as count 
    FROM submissions 
    WHERE student_id = $student_id 
    AND finished_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(finished_at)
");
$activity_data = [];
while ($row = $activity_res->fetch_assoc()) {
    $activity_data[$row['date']] = $row['count'];
}
?>

<!-- Activity Heatmap -->
<div class="stat-card" style="margin-bottom: 25px; padding: 25px;">
    <h3 style="margin-bottom: 20px; font-weight: 700; color: var(--text-main); font-size: 1.1rem; display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-fire" style="color: #ef4444;"></i> Activity Heatmap (Last 30 Days)
    </h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(20px, 1fr)); gap: 6px; overflow-x: auto; padding-bottom: 10px;">
        <?php
        for ($i = 30; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days"));
            $count = isset($activity_data[$d]) ? $activity_data[$d] : 0;
            $color = '#f1f5f9'; // Default empty
            if ($count > 0) $color = '#dcfce7'; // Level 1
            if ($count > 1) $color = '#86efac'; // Level 2
            if ($count > 2) $color = '#22c55e'; // Level 3
            
            echo "<div title='$d: $count exams' style='aspect-ratio: 1; background: $color; border-radius: 4px; border: 1px solid rgba(0,0,0,0.05);'></div>";
        }
        ?>
    </div>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px; font-size: 0.75rem; color: var(--text-muted);">
        <span><?php echo date('M d', strtotime("-30 days")); ?></span>
        <div style="display: flex; align-items: center; gap: 5px;">
            <span>Less</span>
            <div style="width: 10px; height: 10px; background: #f1f5f9; border-radius: 2px;"></div>
            <div style="width: 10px; height: 10px; background: #dcfce7; border-radius: 2px;"></div>
            <div style="width: 10px; height: 10px; background: #86efac; border-radius: 2px;"></div>
            <div style="width: 10px; height: 10px; background: #22c55e; border-radius: 2px;"></div>
            <span>More</span>
        </div>
        <span>Today</span>
    </div>
</div>

<div class="content-row" style="display: grid; grid-template-columns: 2fr 1fr; gap: 25px;">
    <!-- Results Table -->
    <div class="stat-card" style="padding: 35px;">
        <div class="card-title" style="margin-bottom: 20px;">
            <span><i class="fas fa-clipboard-list" style="color: var(--text-muted); margin-right: 10px;"></i> Exam
                History</span>
        </div>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; min-width: 500px;">
                <thead>
                    <tr
                        style="text-align: left; border-bottom: 2px solid var(--border-color); color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em;">
                        <th style="padding: 15px 10px;">Exam Title</th>
                        <th style="padding: 15px 10px;">Score</th>
                        <th style="padding: 15px 10px;">Date</th>
                        <th style="padding: 15px 10px;">Status</th>
                        <th style="padding: 15px 10px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $now = date('Y-m-d H:i:s');

                    // 1. Regular submissions
                    $sql = "SELECT s.id, s.score, s.started_at, s.status, e.title, e.total_marks, 'attended' as attended_flag
                            FROM submissions s 
                            JOIN exams e ON s.exam_id = e.id 
                            WHERE s.student_id = $student_id 
                            ORDER BY s.started_at DESC";
                    $res = $conn->query($sql);
                    while ($row = $res->fetch_assoc()) {
                        $is_published = ($row['status'] == 'published');
                        $score_display = $is_published ? "{$row['score']} / {$row['total_marks']}" : "-";

                        $status_bg = $is_published ? 'rgba(16, 185, 129, 0.1)' : 'rgba(245, 158, 11, 0.1)';
                        $status_color = $is_published ? 'var(--secondary-color)' : 'var(--warning)';
                        $status_label = $is_published ? 'Published' : 'Pending';

                        $title_display = $is_published
                            ? "<a href='review-exam.php?submission_id={$row['id']}' style='color: var(--primary-color); text-decoration: none;' title='Click to Review'>{$row['title']}</a>"
                            : $row['title'];

                        echo "<tr style='border-bottom: 1px solid var(--border-color); transition: var(--transition);'>";
                        echo "<td style='padding: 16px 10px; font-weight: 600; color: var(--text-main);'>$title_display</td>";
                        echo "<td style='padding: 16px 10px; font-weight: 700; color: var(--text-main); font-size: 1.05rem;'>$score_display</td>";
                        echo "<td style='padding: 16px 10px; color: var(--text-muted); font-size: 0.95rem;'>" . date('d M, Y', strtotime($row['started_at'])) . "</td>";
                        echo "<td style='padding: 16px 10px;'><span style='color: $status_color; background: $status_bg; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; letter-spacing: 0.05em; font-weight:600;'>$status_label</span></td>";
                        echo "<td style='padding: 16px 10px;'>";
                        if ($row['status'] == 'published' || $row['status'] == 'evaluated') {
                            echo "<a href='certificate.php?submission_id={$row['id']}' target='_blank' class='btn' style='background: #fef3c7; color: #92400e; text-decoration: none; padding: 6px 12px; font-size: 0.8rem; font-weight: 600; border: 1px solid #fde68a; border-radius: 6px; display: inline-flex; align-items: center; gap: 5px;'><i class='fas fa-certificate'></i> Certificate</a>";
                        } else {
                            echo "<span style='color: var(--text-muted); font-size: 0.8rem;'>Not Available</span>";
                        }
                        echo "</td>";
                        echo "</tr>";
                    }

                    // 2. Expired exams the student never attempted — show as Not Attended
                    $not_attended_sql = "SELECT e.id, e.title, e.end_date
                                         FROM exams e
                                         WHERE e.status = 'active'
                                         AND e.end_date < '$now'
                                         AND e.id NOT IN (
                                             SELECT exam_id FROM submissions WHERE student_id = $student_id
                                         )";
                    $na_res = $conn->query($not_attended_sql);
                    while ($row = $na_res->fetch_assoc()) {
                        echo "<tr style='border-bottom: 1px solid var(--border-color); opacity: 0.7;'>";
                        echo "<td style='padding: 16px 10px; font-weight: 600; color: var(--text-main);'>" . htmlspecialchars($row['title']) . "</td>";
                        echo "<td style='padding: 16px 10px; color: var(--text-muted);'>—</td>";
                        echo "<td style='padding: 16px 10px; color: var(--text-muted); font-size: 0.9rem;'>Ended " . date('d M, Y', strtotime($row['end_date'])) . "</td>";
                        echo "<td style='padding: 16px 10px;'><span style='color: #ef4444; background: rgba(239,68,68,0.1); padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; letter-spacing: 0.05em; font-weight:600;'>Not Attended</span></td>";
                        echo "<td style='padding: 16px 10px; color: var(--text-muted); font-size: 0.8rem;'>—</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Analytics (Pie Chart) -->
    <div class="stat-card" style="padding: 35px; display: flex; flex-direction: column;">
        <div class="card-title" style="margin-bottom: 25px;">
            <span><i class="fas fa-chart-pie" style="color: var(--warning); margin-right: 10px;"></i> Performance
                Analytics</span>
        </div>
        <div style="height: 300px; display: flex; align-items: center; justify-content: center;">
            <canvas id="performanceChart"></canvas>
        </div>
        <div style="margin-top: 20px; text-align: center;">
            <p style="font-size: 0.85rem; color: var(--text-muted);">MCQ accuracy across all your submitted assessments.
            </p>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('performanceChart').getContext('2d');

        <?php
        // --- Real performance analytics from student_answers ---
        
        // Total MCQ questions attempted across all submissions for this student (only for published results)
        $analytics_sql = "
            SELECT 
                SUM(CASE WHEN q.type = 'MCQ' AND sa.is_correct = 1 THEN 1 ELSE 0 END) AS correct_count,
                SUM(CASE WHEN q.type = 'MCQ' AND sa.selected_option IS NOT NULL AND sa.is_correct = 0 THEN 1 ELSE 0 END) AS incorrect_count,
                SUM(CASE WHEN q.type = 'MCQ' AND (sa.selected_option IS NULL OR sa.selected_option = '') THEN 1 ELSE 0 END) AS unanswered_count,
                COUNT(CASE WHEN q.type = 'MCQ' THEN 1 END) AS total_mcq
            FROM submissions s
            JOIN exams e ON s.exam_id = e.id
            JOIN questions q ON q.exam_id = e.id
            LEFT JOIN student_answers sa ON sa.submission_id = s.id AND sa.question_id = q.id
            WHERE s.student_id = $student_id AND s.status = 'published'
        ";
        $analytics_res = $conn->query($analytics_sql);
        $analytics = $analytics_res ? $analytics_res->fetch_assoc() : null;

        $correct_count = (int) ($analytics['correct_count'] ?? 0);
        $incorrect_count = (int) ($analytics['incorrect_count'] ?? 0);
        $unanswered_count = (int) ($analytics['unanswered_count'] ?? 0);
        $total_mcq = (int) ($analytics['total_mcq'] ?? 0);

        // Calculate if there's any published data
        $has_data = ($total_mcq > 0);

        // If no published MCQ data yet, we'll handle it in the JS
        if (!$has_data) {
            $correct_count = 0;
            $incorrect_count = 0;
            $unanswered_count = 1; // Placeholder for chart
        }
        ?>

        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Correct', 'Incorrect', 'Unanswered'],
                datasets: [{
                    data: [<?php echo $correct_count; ?>, <?php echo $incorrect_count; ?>, <?php echo $unanswered_count; ?>],
                    backgroundColor: ['#10b981', '#ef4444', '#94a3b8'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                return ' ' + ctx.label + ': ' + ctx.raw + ' question(s)';
                            }
                        }
                    }
                }
            }
        });
    });
</script>

<?php include '../includes/footer.php'; ?>