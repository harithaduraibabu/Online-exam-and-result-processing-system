<?php
// student/exams.php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';
checkRole('student');

$page_title = "Available Exams";

include '../includes/header.php';
?>

<!-- Search & Filter Bar -->
<form method="GET"
    style="display: flex; gap: 10px; margin-bottom: 25px; background: var(--card-bg); padding: 20px; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); width: 100%;">
    <div style="flex: 1; position: relative;">
        <i class="fas fa-search"
            style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.95rem;"></i>
        <input type="text" name="search" class="form-control" placeholder="Search assessments..."
            value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
            style="padding-left: 42px; border-radius: 10px;">
    </div>
    <div style="width: 200px;">
        <select name="filter" class="form-control" onchange="this.form.submit()" style="border-radius: 10px;">
            <option value="all" <?php echo ($_GET['filter'] ?? '') == 'all' ? 'selected' : ''; ?>>All Assessments</option>
            <option value="available" <?php echo ($_GET['filter'] ?? '') == 'available' ? 'selected' : ''; ?>>Available
                Now</option>
            <option value="upcoming" <?php echo ($_GET['filter'] ?? '') == 'upcoming' ? 'selected' : ''; ?>>Upcoming
            </option>
            <option value="submitted" <?php echo ($_GET['filter'] ?? '') == 'submitted' ? 'selected' : ''; ?>>Submitted
            </option>
        </select>
    </div>
    <button type="submit" class="btn btn-primary"
        style="width: auto; padding: 0 25px; border-radius: 10px;">Search</button>
    <?php if (isset($_GET['search']) || isset($_GET['filter'])): ?>
        <a href="exams.php" class="btn"
            style="width: auto; background: var(--bg-color); color: var(--text-muted); padding: 0 15px; display: flex; align-items: center; text-decoration: none; font-size: 0.85rem; border-radius: 10px; border: 1px solid var(--border-color);">Clear</a>
    <?php endif; ?>
</form>

<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));">
    <?php
    $student_id = $_SESSION['user_id'];
    $current_time = date('Y-m-d H:i:s');
    $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
    $filter = $_GET['filter'] ?? 'all';

    $where_clauses = ["e.status = 'active'"];
    if ($search) {
        $where_clauses[] = "e.title LIKE '%$search%'";
    }

    if ($filter == 'available') {
        $where_clauses[] = "e.start_date <= '$current_time' AND e.end_date >= '$current_time'";
    } elseif ($filter == 'upcoming') {
        $where_clauses[] = "e.start_date > '$current_time'";
    }

    $where_sql = implode(" AND ", $where_clauses);

    // Joint query to handle "Submitted" filter more efficiently if needed, 
    // but for simplicity we'll keep the logic consistent with index.php
    $sql = "SELECT e.*, s.id as submission_id 
            FROM exams e 
            LEFT JOIN submissions s ON e.id = s.exam_id AND s.student_id = $student_id
            WHERE $where_sql 
            ORDER BY e.start_date ASC";

    if ($filter == 'submitted') {
        $sql = "SELECT e.*, s.id as submission_id 
                FROM exams e 
                JOIN submissions s ON e.id = s.exam_id AND s.student_id = $student_id
                WHERE e.status = 'active' " . ($search ? " AND e.title LIKE '%$search%'" : "") . "
                ORDER BY e.start_date ASC";
    }

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $is_available = $current_time >= $row['start_date'] && ($row['end_date'] === null || $current_time <= $row['end_date']);
            $is_expired = $row['end_date'] !== null && $current_time > $row['end_date'];
            $attempted = ($row['submission_id'] !== null);

            // Skip if filter is 'available' and it's already attempted
            if ($filter == 'available' && $attempted)
                continue;
            // Skip if filter is 'upcoming' and it's already attempted (unlikely but safe)
            if ($filter == 'upcoming' && $attempted)
                continue;
            ?>

            <div class="stat-card"
                style="display: flex; flex-direction: column; justify-content: space-between; padding: 35px;">
                <div>
                    <div style="display: flex; gap: 20px; align-items: flex-start; margin-bottom: 25px;">
                        <div
                            style="width: 48px; height: 48px; border-radius: 14px; background: <?php echo $row['type'] == 'Coding' ? 'rgba(99, 102, 241, 0.1)' : 'rgba(16, 185, 129, 0.1)'; ?>; display: flex; align-items: center; justify-content: center; color: <?php echo $row['type'] == 'Coding' ? 'var(--primary-color)' : 'var(--secondary-color)'; ?>; flex-shrink: 0; font-size: 1.5rem;">
                            <i class="<?php echo $row['type'] == 'Coding' ? 'fas fa-code' : 'fas fa-list-ul'; ?>"></i>
                        </div>
                        <div style="flex: 1;">
                            <h4
                                style="font-weight: 700; font-size: 1.25rem; margin-bottom: 4px; color: var(--text-main); line-height: 1.3;">
                                <?php echo htmlspecialchars($row['title']); ?>
                            </h4>
                            <span
                                style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: <?php echo $row['type'] == 'Coding' ? 'var(--primary-color)' : 'var(--secondary-color)'; ?>; background: <?php echo $row['type'] == 'Coding' ? 'rgba(99, 102, 241, 0.1)' : 'rgba(16, 185, 129, 0.1)'; ?>; padding: 4px 10px; border-radius: 20px; display: inline-block; margin-top: 5px;">
                                <?php echo $row['type']; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div
                    style="margin-bottom: 25px; background: var(--bg-color); padding: 15px; border-radius: 12px; border: 1px solid var(--border-color);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <div
                            style="display: flex; align-items: center; gap: 8px; color: var(--text-muted); font-size: 0.85rem; font-weight: 500;">
                            <i class="far fa-clock"></i> Duration
                        </div>
                        <div style="font-weight: 700; color: var(--text-main);"><?php echo $row['duration']; ?> Min</div>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <div
                            style="display: flex; align-items: center; gap: 8px; color: var(--text-muted); font-size: 0.85rem; font-weight: 500;">
                            <i class="far fa-calendar"></i> Starts
                        </div>
                        <div style="font-weight: 600; color: var(--text-main); font-size: 0.85rem;">
                            <?php echo date('d M h:i A', strtotime($row['start_date'])); ?>
                        </div>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div
                            style="display: flex; align-items: center; gap: 8px; color: var(--text-muted); font-size: 0.85rem; font-weight: 500;">
                            <i class="far fa-calendar-times"></i> Ends
                        </div>
                        <div style="font-weight: 600; color: var(--text-main); font-size: 0.85rem;">
                            <?php echo date('d M h:i A', strtotime($row['end_date'])); ?>
                        </div>
                    </div>
                </div>

                <?php if ($attempted): ?>
                    <button class="btn"
                        style="width: 100%; background: var(--bg-color); color: var(--text-muted); cursor: not-allowed; border: 1px solid var(--border-color); padding: 14px; font-size: 1.05rem; display: flex; align-items: center; justify-content: center; gap: 10px;"
                        disabled>
                        <i class="fas fa-check-circle" style="color:#10b981;"></i> Submitted
                    </button>
                <?php elseif ($is_expired): ?>
                    <button class="btn"
                        style="width: 100%; background: rgba(239,68,68,0.08); color: #ef4444; cursor: not-allowed; border: 1px solid rgba(239,68,68,0.2); padding: 14px; font-size: 1.05rem; display: flex; align-items: center; justify-content: center; gap: 10px;"
                        disabled>
                        <i class="fas fa-clock"></i> Exam Finished
                    </button>
                <?php elseif ($is_available): ?>
                    <a href="take-exam.php?id=<?php echo $row['id']; ?>" class="btn btn-primary"
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

            <?php
        }
    } else {
        echo "<div class='stat-card' style='grid-column: 1 / -1; text-align: center; padding: 60px 40px; display: flex; flex-direction: column; align-items: center; justify-content: center;'>
                <div style='width: 80px; height: 80px; background: #f1f5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; color: var(--text-muted); padding: 25px;'>
                    <i class='fas fa-file-alt' style='font-size: 2rem;'></i>
                </div>
                <h4 style='font-size: 1.25rem; font-weight: 700; color: var(--text-main); margin-bottom: 10px;'>No exams scheduled</h4>
                <p style='color: var(--text-muted); max-width: 400px; line-height: 1.6;'>There are no upcoming or active assessments at this time.</p>
              </div>";
    }
    ?>
</div>

<?php include '../includes/footer.php'; ?>