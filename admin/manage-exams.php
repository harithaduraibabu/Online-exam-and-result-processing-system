<?php
// admin/manage-exams.php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';
checkRole('admin');

$page_title = "Manage Exams";
$success = "";
$error = "";

if (isset($_GET['msg']) && $_GET['msg'] == 'deleted') {
    $success = "Exam and its associated data deleted successfully!";
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_exam'])) {
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $type = $_POST['type'];
        $duration = (int) $_POST['duration'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $created_by = $_SESSION['user_id'];

        $sql = "INSERT INTO exams (title, type, duration, start_date, end_date, created_by) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssissi", $title, $type, $duration, $start_date, $end_date, $created_by);

        if ($stmt->execute()) {
            $success = "Exam created successfully!";
        } else {
            $error = "Error adding exam: " . $conn->error;
        }
    }

    if (isset($_POST['edit_exam'])) {
        $id = (int) $_POST['exam_id'];
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $duration = (int) $_POST['duration'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];

        $sql = "UPDATE exams SET title=?, duration=?, start_date=?, end_date=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sissi", $title, $duration, $start_date, $end_date, $id);

        if ($stmt->execute()) {
            $success = "Exam updated successfully!";
        } else {
            $error = "Error updating exam: " . $conn->error;
        }
    }
}

// Handle Status Toggle
if (isset($_GET['toggle'])) {
    $id = (int) $_GET['toggle'];
    $current_status_res = $conn->query("SELECT status FROM exams WHERE id = $id");
    if ($current_status_res->num_rows > 0) {
        $current_status = $current_status_res->fetch_assoc()['status'];
        $new_status = $current_status == 'active' ? 'inactive' : 'active';
        $conn->query("UPDATE exams SET status = '$new_status' WHERE id = $id");
        header("Location: manage-exams.php");
        exit();
    }
}

// Handle Deletion
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $conn->query("DELETE FROM student_answers WHERE submission_id IN (SELECT id FROM submissions WHERE exam_id = $id)");
    $conn->query("DELETE FROM submissions WHERE exam_id = $id");
    $conn->query("DELETE FROM questions WHERE exam_id = $id");
    if ($conn->query("DELETE FROM exams WHERE id = $id")) {
        header("Location: manage-exams.php?msg=deleted");
        exit();
    } else {
        $error = "Error deleting exam: " . $conn->error;
    }
}

include '../includes/header.php';
?>

<div class="content-row" style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px;">
    <!-- Add Exam Form -->
    <div class="stat-card" style="padding: 35px;">
        <div class="card-title">Create New Exam</div>
        <?php if ($success)
            echo "<div class='alert' style='background: #ecfdf5; color: #065f46; margin-bottom: 20px;'>$success</div>"; ?>
        <?php if ($error)
            echo "<div class='alert alert-danger'>$error</div>"; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label>Exam Title</label>
                <input type="text" name="title" class="form-control" placeholder="e.g. Java Fundamentals" required>
            </div>
            <div class="form-group">
                <label>Exam Type</label>
                <select name="type" class="form-control" required>
                    <option value="MCQ">Multiple Choice (MCQ)</option>
                    <option value="Coding">Coding Assessment</option>
                </select>
            </div>
            <div class="form-group">
                <label>Duration (Minutes)</label>
                <input type="number" name="duration" class="form-control" value="60" required>
            </div>
            <div class="form-group">
                <label>Start Date</label>
                <input type="datetime-local" name="start_date" class="form-control" required>
            </div>
            <div class="form-group">
                <label>End Date</label>
                <input type="datetime-local" name="end_date" class="form-control" required>
            </div>
            <button type="submit" name="add_exam" class="btn btn-primary">Create Exam</button>
        </form>
    </div>

    <!-- Exams List -->
    <div class="stat-card" style="padding: 35px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <div class="card-title" style="margin: 0; display: flex; align-items: center; gap: 15px;">
                <span>Existing Exams</span>
                <a href="exam-report.php<?php echo strpos($_SERVER['REQUEST_URI'], '?') !== false ? '?' . explode('?', $_SERVER['REQUEST_URI'])[1] : ''; ?>"
                    target="_blank" class="btn"
                    style="width: auto; background: rgba(79, 70, 229, 0.05); color: var(--primary-color); font-size: 0.75rem; font-weight: 600; padding: 6px 14px; border-radius: 20px; display: flex; align-items: center; gap: 6px; text-decoration: none; border: 1px solid rgba(79, 70, 229, 0.2); transition: var(--transition);"
                    onmouseover="this.style.background='var(--primary-color)'; this.style.color='white';"
                    onmouseout="this.style.background='rgba(79, 70, 229, 0.05)'; this.style.color='var(--primary-color)';">
                    <i class="fas fa-file-export"></i> Full Report
                </a>
            </div>
        </div>

        <!-- Search & Filter Bar -->
        <form method="GET"
            style="display: flex; gap: 10px; margin-bottom: 25px; background: #f8fafc; padding: 15px; border-radius: 12px; border: 1px solid var(--border-color);">
            <div style="flex: 1; position: relative;">
                <i class="fas fa-search"
                    style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.9rem;"></i>
                <input type="text" name="search" class="form-control" placeholder="Search by exam title..."
                    value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" style="padding-left: 35px;">
            </div>
            <div style="width: 180px;">
                <select name="status_filter" class="form-control" onchange="this.form.submit()">
                    <option value="all" <?php echo ($_GET['status_filter'] ?? '') == 'all' ? 'selected' : ''; ?>>All
                        Status</option>
                    <option value="active" <?php echo ($_GET['status_filter'] ?? '') == 'active' ? 'selected' : ''; ?>>
                        Ongoing (Active)</option>
                    <option value="scheduled" <?php echo ($_GET['status_filter'] ?? '') == 'scheduled' ? 'selected' : ''; ?>>Upcoming</option>
                    <option value="completed" <?php echo ($_GET['status_filter'] ?? '') == 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="inactive" <?php echo ($_GET['status_filter'] ?? '') == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="width: auto; padding: 0 20px;">Filter</button>
            <?php if (isset($_GET['search']) || isset($_GET['status_filter'])): ?>
                <a href="manage-exams.php" class="btn"
                    style="width: auto; background: #f1f5f9; color: var(--text-muted); padding: 0 15px; display: flex; align-items: center; text-decoration: none; font-size: 0.85rem; border-radius: 8px;">Clear</a>
            <?php endif; ?>
        </form>

        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr
                    style="text-align: left; border-bottom: 2px solid var(--border-color); color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em;">
                    <th style="padding: 15px 10px;">Title</th>
                    <th style="padding: 15px 10px;">Type</th>
                    <th style="padding: 15px 10px;">Duration</th>
                    <th style="padding: 15px 10px;">Status</th>
                    <th style="padding: 15px 10px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
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
                $exams = $conn->query("SELECT * FROM exams $where_sql ORDER BY id DESC");

                while ($row = $exams->fetch_assoc()) {
                    $is_past = strtotime($row['end_date']) < strtotime($now);

                    if ($is_past) {
                        $status_bg = "#64748b"; // Gray for completed
                        $status_text = "Completed";
                        $status_link = "<span class='btn' style='padding: 5px 12px; font-size: 0.7rem; color: white; background: $status_bg; cursor: default; border-radius: 4px;'>$status_text</span>";
                    } else {
                        $is_active = $row['status'] == 'active';
                        $status_bg = $is_active ? "#10b981" : "#f43f5e"; // Emerald vs Rose
                        $status_text = $is_active ? "Active" : "Inactive";
                        $status_link = "<a href='?toggle={$row['id']}' class='btn' style='padding: 5px 12px; font-size: 0.7rem; color: white; background: $status_bg; text-decoration: none; border-radius: 4px;'>$status_text</a>";
                    }

                    echo "<tr style='border-bottom: 1px solid var(--border-color); transition: var(--transition);'>";
                    echo "<td style='padding: 16px 10px; font-weight: 600;'><a href='exam-results-report.php?exam_id={$row['id']}' target='_blank' style='color: var(--primary-color); text-decoration: none; transition: var(--transition);' onmouseover=\"this.style.textDecoration='underline'\" onmouseout=\"this.style.textDecoration='none'\">" . htmlspecialchars($row['title']) . "</a></td>";
                    echo "<td style='padding: 16px 10px; color: var(--text-muted); font-size: 0.95rem;'>{$row['type']}</td>";
                    echo "<td style='padding: 16px 10px; color: var(--text-muted); font-size: 0.95rem;'>{$row['duration']} min</td>";
                    echo "<td style='padding: 16px 10px;'>$status_link</td>";
                    echo "<td style='padding: 16px 10px;'>
                            <a href='exam-results-report.php?exam_id={$row['id']}' target='_blank' title='View Results Report' style='color: #10b981; text-decoration: none; margin-right: 15px; display: inline-flex; width: 32px; height: 32px; background: #f0fdf4; align-items: center; justify-content: center; border-radius: 8px; transition: var(--transition);' onmouseover=\"this.style.background='#10b981'; this.style.color='white';\" onmouseout=\"this.style.background='#f0fdf4'; this.style.color='#10b981';\"><i class='fas fa-file-alt'></i></a>
                            <a href='manage-questions.php?exam_id={$row['id']}' title='Manage Questions' style='color: var(--primary-color); text-decoration: none; margin-right: 15px; display: inline-flex; width: 32px; height: 32px; background: #f8fafc; align-items: center; justify-content: center; border-radius: 8px; transition: var(--transition);' onmouseover=\"this.style.background='var(--primary-color)'; this.style.color='white';\" onmouseout=\"this.style.background='#f8fafc'; this.style.color='var(--primary-color)';\"><i class='fas fa-list'></i></a>
                            <button type='button'
                               class='btn-icon delete-exam'
                               data-id='{$row['id']}'
                               style='color: var(--danger); background: #fef2f2; border: none; cursor: pointer; padding: 0; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; transition: var(--transition);' 
                               title='Delete'
                               onmouseover=\"this.style.background='var(--danger)'; this.style.color='white';\" onmouseout=\"this.style.background='#fef2f2'; this.style.color='var(--danger)';\">
                               <i class='fas fa-trash' style='pointer-events: none;'></i>
                            </button>
                          </td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.body.addEventListener('click', function (e) {
            if (e.target.closest('.delete-exam')) {
                const btn = e.target.closest('.delete-exam');
                const examId = btn.getAttribute('data-id');
                if (confirm('Are you sure? This will delete all questions and submissions.')) {
                    window.location.href = `manage-exams.php?delete=${examId}`;
                }
            }
        });
    });
</script>

<script>
    function openEditModal(id, title, duration, startDate, endDate) {
        document.getElementById('edit_exam_id').value = id;
        document.getElementById('edit_title').value = title;
        document.getElementById('edit_duration').value = duration;
        document.getElementById('edit_start_date').value = startDate ? startDate.replace(' ', 'T').substring(0, 16) : '';
        document.getElementById('edit_end_date').value = endDate ? endDate.replace(' ', 'T').substring(0, 16) : '';
        document.getElementById('editModal').style.display = 'flex';
    }
    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }
    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('editModal').addEventListener('click', function (e) {
            if (e.target === this) closeEditModal();
        });
    });
</script>

<!-- Edit Exam Modal -->
<div id="editModal"
    style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.55); z-index:1000; align-items:center; justify-content:center;">
    <div
        style="background:var(--card-bg); border-radius:16px; padding:35px; width:100%; max-width:520px; box-shadow:0 20px 60px rgba(0,0,0,0.25); position:relative;">
        <button onclick="closeEditModal()"
            style="position:absolute; top:15px; right:18px; background:none; border:none; cursor:pointer; font-size:1.4rem; color:var(--text-muted);">&times;</button>
        <h3 style="margin-bottom:25px; font-weight:700;"><i class="fas fa-edit"
                style="color:var(--primary-color); margin-right:10px;"></i>Edit Assessment</h3>
        <form action="" method="POST">
            <input type="hidden" name="exam_id" id="edit_exam_id">
            <div class="form-group">
                <label>Exam Title</label>
                <input type="text" name="title" id="edit_title" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Duration (Minutes)</label>
                <input type="number" name="duration" id="edit_duration" class="form-control" required>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                <div class="form-group">
                    <label>Start Date &amp; Time</label>
                    <input type="datetime-local" name="start_date" id="edit_start_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>End Date &amp; Time</label>
                    <input type="datetime-local" name="end_date" id="edit_end_date" class="form-control" required>
                </div>
            </div>
            <div style="display:flex; gap:10px; margin-top:10px;">
                <button type="submit" name="edit_exam" class="btn btn-primary"><i class="fas fa-save"
                        style="margin-right:6px;"></i>Save Changes</button>
                <button type="button" onclick="closeEditModal()" class="btn"
                    style="background:#f1f5f9; color:var(--text-main);">Cancel</button>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>