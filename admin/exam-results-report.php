<?php
// admin/exam-results-report.php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';
checkRole('admin');

if (!isset($_GET['exam_id'])) {
    die("Exam ID required");
}

$exam_id = (int) $_GET['exam_id'];

// Fetch exam details
$exam_res = $conn->query("SELECT title, (SELECT COALESCE(SUM(marks),0) FROM questions WHERE exam_id = $exam_id) as total_marks FROM exams WHERE id = $exam_id");
if ($exam_res->num_rows == 0) {
    die("Exam not found");
}
$exam = $exam_res->fetch_assoc();

// Fetch results
$results = $conn->query("
    SELECT s.score, s.started_at, u.full_name,
           SUM(CASE WHEN sa.is_correct = 1 THEN 1 ELSE 0 END) as correct_count,
           SUM(CASE WHEN sa.is_correct = 0 AND sa.selected_option IS NOT NULL THEN 1 ELSE 0 END) as wrong_count
    FROM submissions s
    JOIN users u ON s.student_id = u.id
    LEFT JOIN student_answers sa ON sa.submission_id = s.id
    WHERE s.exam_id = $exam_id AND s.status = 'published'
    GROUP BY s.id
    ORDER BY s.score DESC
");

$report_title = "Exam Results Report: " . $exam['title'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>
        <?php echo $report_title; ?>
    </title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #1e293b;
            padding: 40px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            border-bottom: 2px solid #10b981;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .title {
            font-size: 24px;
            font-weight: 700;
            margin: 0;
            color: #059669;
        }

        .date {
            color: #64748b;
            font-size: 14px;
        }

        .exam-info {
            margin-bottom: 20px;
            font-size: 14px;
            color: #475569;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th {
            background: #f8fafc;
            color: #64748b;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
        }

        .rank {
            color: #64748b;
            font-weight: 600;
            width: 40px;
        }

        .score {
            font-weight: 700;
            color: #4f46e5;
        }

        .pct {
            font-weight: 700;
        }

        .stats {
            font-size: 12px;
            font-weight: 600;
        }

        .correct {
            color: #10b981;
        }

        .wrong {
            color: #ef4444;
        }

        @media print {
            .no-print {
                display: none;
            }

            body {
                padding: 0;
            }
        }
    </style>
</head>

<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()"
            style="padding: 10px 20px; background: #10b981; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Print
            / Save as PDF</button>
        <button onclick="window.close()"
            style="padding: 10px 20px; background: #f1f5f9; color: #475569; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; margin-left:10px;">Close</button>
    </div>

    <div class="header">
        <div>
            <h1 class="title">
                <?php echo $report_title; ?>
            </h1>
            <p class="exam-info">Total Marks:
                <?php echo $exam['total_marks']; ?> | Submissions:
                <?php echo $results->num_rows; ?>
            </p>
        </div>
        <div class="date">Report Generated:
            <?php echo date('d M Y, h:i A'); ?>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="rank">#</th>
                <th>Student Name</th>
                <th>Score</th>
                <th>Percentage</th>
                <th>Correct / Wrong</th>
                <th>Completion Date</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $rank = 0;
            while ($row = $results->fetch_assoc()):
                $rank++;
                $total_marks = $exam['total_marks'];
                $pct = $total_marks > 0 ? round(($row['score'] / $total_marks) * 100) : 0;
                $pct_color = $pct >= 75 ? '#10b981' : ($pct >= 40 ? '#f59e0b' : '#ef4444');
                ?>
                <tr>
                    <td class="rank">
                        <?php echo $rank; ?>
                    </td>
                    <td style="font-weight: 600;">
                        <?php echo htmlspecialchars($row['full_name']); ?>
                    </td>
                    <td class="score">
                        <?php echo $row['score']; ?> /
                        <?php echo $total_marks; ?>
                    </td>
                    <td class="pct" style="color: <?php echo $pct_color; ?>;">
                        <?php echo $pct; ?>%
                    </td>
                    <td class="stats">
                        <span class="correct">
                            <?php echo $row['correct_count']; ?> ✓
                        </span> &nbsp;
                        <span class="wrong">
                            <?php echo $row['wrong_count']; ?> ✗
                        </span>
                    </td>
                    <td style="color: #64748b; font-size: 13px;">
                        <?php echo date('d M Y', strtotime($row['started_at'])); ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div
        style="margin-top: 50px; border-top: 1px solid #e2e8f0; padding-top: 20px; text-align: center; font-size: 12px; color: #94a3b8;">
        End of Results Report - Online Examination System
    </div>
</body>

</html>