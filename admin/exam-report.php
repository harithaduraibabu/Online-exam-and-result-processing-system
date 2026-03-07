<?php
// admin/exam-report.php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';
checkRole('admin');

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$status_filter = $_GET['status_filter'] ?? 'all';
$now = date('Y-m-d H:i:s');

$where_clauses = [];
if ($search) {
    $where_clauses[] = "title LIKE '%$search%'";
}

if ($status_filter == 'active') {
    $where_clauses[] = "status = 'active' AND (start_date <= '$now' AND end_date >= '$now')";
} elseif ($status_filter == 'scheduled') {
    $where_clauses[] = "start_date > '$now'";
} elseif ($status_filter == 'completed') {
    $where_clauses[] = "end_date < '$now'";
} elseif ($status_filter == 'inactive') {
    $where_clauses[] = "status = 'inactive'";
}

$where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";
$exams = $conn->query("
    SELECT e.*, 
           (SELECT COUNT(*) FROM submissions s WHERE s.exam_id = e.id) as sub_count,
           (SELECT COUNT(*) FROM questions q WHERE q.exam_id = e.id) as q_count
    FROM exams e 
    $where_sql 
    ORDER BY e.id DESC
");

$report_title = "Exam Summary Report";
if ($status_filter != 'all')
    $report_title .= " - " . ucfirst($status_filter);
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
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .title {
            font-size: 24px;
            font-weight: 700;
            margin: 0;
            color: #4f46e5;
        }

        .date {
            color: #64748b;
            font-size: 14px;
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

        .status-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        .active {
            background: #dcfce7;
            color: #166534;
        }

        .scheduled {
            background: #fef9c3;
            color: #854d0e;
        }

        .completed {
            background: #f1f5f9;
            color: #475569;
        }

        .inactive {
            background: #fee2e2;
            color: #991b1b;
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
            style="padding: 10px 20px; background: #4f46e5; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Print
            / Save as PDF</button>
        <button onclick="window.close()"
            style="padding: 10px 20px; background: #f1f5f9; color: #475569; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; margin-left:10px;">Close</button>
    </div>

    <div class="header">
        <div>
            <h1 class="title">
                <?php echo $report_title; ?>
            </h1>
            <p style="margin: 5px 0 0 0; color: #64748b; font-size: 14px;">Total Exams Listed:
                <?php echo $exams->num_rows; ?>
            </p>
        </div>
        <div class="date">Report Generated:
            <?php echo date('d M Y, h:i A'); ?>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Exam Title</th>
                <th>Type</th>
                <th>Duration</th>
                <th>Questions</th>
                <th>Submissions</th>
                <th>Timeline</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $exams->fetch_assoc()):
                $status = 'inactive';
                if ($row['status'] == 'active') {
                    if (strtotime($row['end_date']) < time())
                        $status = 'completed';
                    elseif (strtotime($row['start_date']) > time())
                        $status = 'scheduled';
                    else
                        $status = 'active';
                }
                ?>
                <tr>
                    <td style="font-weight: 600;">
                        <?php echo htmlspecialchars($row['title']); ?>
                    </td>
                    <td>
                        <?php echo $row['type']; ?>
                    </td>
                    <td>
                        <?php echo $row['duration']; ?> min
                    </td>
                    <td>
                        <?php echo $row['q_count']; ?> qns
                    </td>
                    <td>
                        <?php echo $row['sub_count']; ?> subs
                    </td>
                    <td style="font-size: 12px; color: #64748b;">
                        <?php echo date('d M Y', strtotime($row['start_date'])); ?> - <br>
                        <?php echo date('d M Y', strtotime($row['end_date'])); ?>
                    </td>
                    <td>
                        <span class="status-badge <?php echo $status; ?>">
                            <?php echo ucfirst($status); ?>
                        </span>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div
        style="margin-top: 50px; border-top: 1px solid #e2e8f0; padding-top: 20px; text-align: center; font-size: 12px; color: #94a3b8;">
        End of Report - Online Examination System
    </div>
</body>

</html>