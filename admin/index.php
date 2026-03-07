<?php
// admin/index.php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';
checkRole('admin');

$page_title = "Admin Dashboard";

// Fetch counts for stats
$exam_count = $conn->query("SELECT COUNT(*) as total FROM exams")->fetch_assoc()['total'];
$student_count = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'student'")->fetch_assoc()['total'];
$pending_results = $conn->query("SELECT COUNT(*) as total FROM submissions WHERE status = 'pending'")->fetch_assoc()['total'];

include '../includes/header.php';
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-title"><i class="fas fa-file-signature"
                style="color: var(--primary-color); margin-right: 8px;"></i> Total Exams</div>
        <div class="stat-value">
            <?php echo $exam_count; ?>
        </div>
    </div>
    <a href="manage-students.php" class="stat-card"
        style="text-decoration: none; color: inherit; transition: transform 0.2s;"
        onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
        <div class="stat-title"><i class="fas fa-user-graduate"
                style="color: var(--secondary-color); margin-right: 8px;"></i> Registered Students</div>
        <div class="stat-value">
            <?php echo $student_count; ?>
        </div>
    </a>
    <div class="stat-card">
        <div class="stat-title"><i class="fas fa-hourglass-half" style="color: var(--warning); margin-right: 8px;"></i>
            Pending Results</div>
        <div class="stat-value">
            <?php echo $pending_results; ?>
        </div>
    </div>
</div>

<div class="content-row" style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
    <!-- Recent Exams Table -->
    <div class="stat-card" style="padding: 35px;">
        <div class="card-title"
            style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;">
            <span style="white-space: nowrap;"><i class="fas fa-history"
                    style="color: var(--text-muted); margin-right: 12px;"></i>Recent Exams</span>
            <a href="manage-exams.php" class="btn"
                style="width: auto; font-size: 0.8rem; font-weight: 600; color: var(--primary-color); background: rgba(79, 70, 229, 0.1); padding: 6px 14px; border-radius: 12px; text-decoration: none; transition: var(--transition);">View
                All</a>
        </div>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; min-width: 500px;">
                <thead>
                    <tr
                        style="text-align: left; border-bottom: 2px solid var(--border-color); color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em;">
                        <th style="padding: 15px 10px;">Exam Title</th>
                        <th style="padding: 15px 10px;">Type</th>
                        <th style="padding: 15px 10px;">Status</th>
                        <th style="padding: 15px 10px; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $exams = $conn->query("SELECT * FROM exams ORDER BY id DESC LIMIT 5");
                    if ($exams->num_rows > 0) {
                        while ($row = $exams->fetch_assoc()) {
                            $status_color = $row['status'] == 'active' ? 'var(--secondary-color)' : 'var(--danger)';
                            $status_bg = $row['status'] == 'active' ? 'rgba(16, 185, 129, 0.1)' : 'rgba(239, 68, 68, 0.1)';
                            echo "<tr style='border-bottom: 1px solid var(--border-color); transition: var(--transition);'> ";
                            echo "<td style='padding: 16px 10px; font-weight: 600; color: var(--text-main);'>" . htmlspecialchars($row['title']) . "</td>";
                            echo "<td style='padding: 16px 10px; color: var(--text-muted); font-size: 0.95rem;'>" . $row['type'] . "</td>";
                            echo "<td style='padding: 16px 10px;'><span style='color: $status_color; background: $status_bg; padding: 4px 12px; border-radius: 20px; font-weight: 600; font-size: 0.8rem; text-transform: capitalize; letter-spacing: 0.05em;'>" . $row['status'] . "</span></td>";
                            echo "<td style='padding: 16px 10px; text-align: right;'><a href='manage-exams.php?edit=" . $row['id'] . "' style='color: var(--primary-color); display: inline-flex; width: 32px; height: 32px; background: #f8fafc; align-items: center; justify-content: center; border-radius: 8px; transition: var(--transition);' onmouseover=\"this.style.background='var(--primary-color)'; this.style.color='white';\" onmouseout=\"this.style.background='#f8fafc'; this.style.color='var(--primary-color)';\"><i class='fas fa-edit'></i></a></td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4' style='padding: 30px; text-align: center; color: var(--text-muted);'>No exams found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="stat-card" style="padding: 35px; display: flex; flex-direction: column;">
        <div class="card-title" style="margin-bottom: 25px;">
            <span><i class="fas fa-bolt" style="color: var(--warning); margin-right: 10px;"></i> Quick Actions</span>
        </div>
        <div class="action-buttons" style="display: flex; flex-direction: column; gap: 16px;">
            <a href="manage-exams.php" class="btn btn-primary"
                style="text-align: left; text-decoration: none; padding: 16px 20px; font-size: 1.05rem; display: flex; align-items: center; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);">
                <div
                    style="width: 36px; height: 36px; background: rgba(255,255,255,0.2); border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-right: 15px;">
                    <i class="fas fa-plus"></i>
                </div>
                Create New Exam
            </a>
            <a href="manage-questions.php" class="btn"
                style="background: #eef2ff; color: var(--primary-color); text-align: left; text-decoration: none; padding: 16px 20px; font-size: 1.05rem; display: flex; align-items: center;">
                <div
                    style="width: 36px; height: 36px; background: white; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-right: 15px; box-shadow: var(--shadow-sm);">
                    <i class="fas fa-question"></i>
                </div>
                Add Questions
            </a>
            <a href="publish-results.php" class="btn"
                style="background: #ecfdf5; color: var(--secondary-color); text-align: left; text-decoration: none; padding: 16px 20px; font-size: 1.05rem; display: flex; align-items: center;">
                <div
                    style="width: 36px; height: 36px; background: white; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-right: 15px; box-shadow: var(--shadow-sm);">
                    <i class="fas fa-check-double"></i>
                </div>
                Publish Results
            </a>
            <a href="manage-students.php" class="btn"
                style="background: #fff7ed; color: #9a3412; text-align: left; text-decoration: none; padding: 16px 20px; font-size: 1.05rem; display: flex; align-items: center;">
                <div
                    style="width: 36px; height: 36px; background: white; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-right: 15px; box-shadow: var(--shadow-sm);">
                    <i class="fas fa-users-cog"></i>
                </div>
                Manage Students
            </a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>