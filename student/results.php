<?php
// student/results.php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';
checkRole('student');

$page_title = "My Results";
$student_id = $_SESSION['user_id'];

include '../includes/header.php';
?>

<div class="content-row" style="display: grid; grid-template-columns: 2fr 1fr; gap: 25px;">
    <!-- Results Table -->
    <div class="stat-card" style="padding: 35px;">
        <div class="card-title"
            style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
            <span><i class="fas fa-clipboard-list" style="color: var(--text-muted); margin-right: 10px;"></i> Exam
                History</span>

            <!-- Filters -->
            <form action="" method="GET" style="display: flex; gap: 10px; align-items: center;">
                <input type="text" name="search" placeholder="Search exam..."
                    value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
                    style="padding: 6px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.85rem;">

                <select name="type"
                    style="padding: 6px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.85rem; background: white;">
                    <option value="">All Status</option>
                    <option value="published" <?php echo ($_GET['type'] ?? '') == 'published' ? 'selected' : ''; ?>>
                        Published</option>
                    <option value="pending" <?php echo ($_GET['type'] ?? '') == 'pending' ? 'selected' : ''; ?>>Pending
                    </option>
                    <option value="not_attended" <?php echo ($_GET['type'] ?? '') == 'not_attended' ? 'selected' : ''; ?>>
                        Not Attended</option>
                </select>

                <button type="submit" class="btn btn-primary"
                    style="width: auto; padding: 6px 15px; font-size: 0.85rem;">Filter</button>
                <?php if (isset($_GET['search']) || isset($_GET['type'])): ?>
                    <a href="results.php"
                        style="font-size: 0.8rem; color: var(--text-muted); text-decoration: none;">Clear</a>
                <?php endif; ?>
            </form>
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
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $now = date('Y-m-d H:i:s');

                    // Filters
                    $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
                    $type = isset($_GET['type']) ? $_GET['type'] : '';

                    $where_clause = "s.student_id = $student_id";
                    if ($search) {
                        $where_clause .= " AND e.title LIKE '%$search%'";
                    }
                    if ($type == 'published') {
                        $where_clause .= " AND s.status = 'published'";
                    } elseif ($type == 'pending') {
                        $where_clause .= " AND s.status != 'published'";
                    }

                    // 1. Regular submissions (only if type is not 'not_attended')
                    if ($type != 'not_attended') {
                        $sql = "SELECT s.id, s.score, s.started_at, s.status, e.title, e.total_marks, 'attended' as attended_flag
                                FROM submissions s 
                                JOIN exams e ON s.exam_id = e.id 
                                WHERE $where_clause 
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
                            echo "</tr>";
                        }

                    }

                    // 2. Expired exams the student never attempted — show as Not Attended (if type is '' or 'not_attended')
                    if ($type == '' || $type == 'not_attended') {
                        $na_where = "e.status = 'active' AND e.end_date < '$now'";
                        if ($search) {
                            $na_where .= " AND e.title LIKE '%$search%'";
                        }

                        $not_attended_sql = "SELECT e.id, e.title, e.end_date
                                             FROM exams e
                                             WHERE $na_where
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
                            echo "</tr>";
                        }
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
            <p style="font-size: 0.85rem; color: var(--text-muted);">Overall accuracy across all your submitted
                assessments.
            </p>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('performanceChart').getContext('2d');

        <?php
        // --- Real performance analytics from student_answers ---
        
        // Total questions attempted across all submissions for this student (only for published results)
        $analytics_sql = "
            SELECT 
                SUM(CASE WHEN sa.is_correct = 1 THEN 1 ELSE 0 END) AS correct_count,
                SUM(CASE WHEN sa.is_correct = 0 AND (sa.selected_option IS NOT NULL OR (sa.submitted_code IS NOT NULL AND sa.submitted_code != '')) THEN 1 ELSE 0 END) AS incorrect_count,
                SUM(CASE WHEN sa.id IS NULL OR ((sa.selected_option IS NULL OR sa.selected_option = '') AND (sa.submitted_code IS NULL OR sa.submitted_code = '')) THEN 1 ELSE 0 END) AS unanswered_count,
                COUNT(q.id) AS total_questions
            FROM submissions s
            JOIN questions q ON s.exam_id = q.exam_id
            LEFT JOIN student_answers sa ON sa.submission_id = s.id AND sa.question_id = q.id
            WHERE s.student_id = $student_id AND s.status = 'published'
        ";
        $analytics_res = $conn->query($analytics_sql);
        $analytics = $analytics_res ? $analytics_res->fetch_assoc() : null;

        $correct_count = (int) ($analytics['correct_count'] ?? 0);
        $incorrect_count = (int) ($analytics['incorrect_count'] ?? 0);
        $unanswered_count = (int) ($analytics['unanswered_count'] ?? 0);
        $total_questions = (int) ($analytics['total_questions'] ?? 0);

        // Calculate if there's any published data
        $has_data = ($total_questions > 0);

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