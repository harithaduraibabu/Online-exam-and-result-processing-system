<?php
// admin/manage-students.php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';
checkRole('admin');

$page_title = "Student Management";
$success = "";
$error = "";

// Handle Student Deletion
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    if ($conn->query("DELETE FROM users WHERE id = $id AND role = 'student'")) {
        $success = "Student deleted successfully.";
    } else {
        $error = "Error deleting student.";
    }
}

// Handle Student Updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_student'])) {
    $id = (int) ($_POST['student_id'] ?? 0);
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name'] ?? '');
    $email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
    $register_number = mysqli_real_escape_string($conn, $_POST['register_number'] ?? '');
    $degree = mysqli_real_escape_string($conn, $_POST['degree'] ?? '');
    $batch = mysqli_real_escape_string($conn, $_POST['batch'] ?? '');
    $college = "VEL TECH UNIVERSITY";
    $password = $_POST['password'] ?? '';

    $sql = "UPDATE users SET full_name = '$full_name', email = '$email', register_number = '$register_number', degree = '$degree', batch = '$batch', college = '$college'";
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql .= ", password = '$hashed_password'";
    }
    $sql .= " WHERE id = $id AND role = 'student'";

    if ($conn->query($sql)) {
        $success = "Student details updated successfully.";
    } else {
        $error = "Error updating student. Email might already exist.";
    }
}

include '../includes/header.php';
?>

<div class="content-row">
    <div class="stat-card" style="padding: 35px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <div class="card-title" style="margin: 0;">Registered Students</div>
        </div>

        <?php if ($success)
            echo "<div class='alert' style='background: #ecfdf5; color: #065f46; margin-bottom: 20px; padding: 12px; border-radius: 8px;'>$success</div>"; ?>
        <?php if ($error)
            echo "<div class='alert' style='background: #fef2f2; color: #991b1b; margin-bottom: 20px; padding: 12px; border-radius: 8px;'>$error</div>"; ?>

        <!-- Search & Sort Bar -->
        <div style="display: flex; gap: 15px; margin-bottom: 25px; flex-wrap: wrap; align-items: center;">
            <div style="position: relative; flex: 1; max-width: 400px;">
                <i class="fas fa-search"
                    style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                <input type="text" id="studentSearch" class="form-control" placeholder="Search by name or email..."
                    style="padding-left: 45px; border-radius: 12px; height: 45px;">
            </div>

            <form action="" method="GET" style="display: flex; gap: 10px; align-items: center;">
                <label style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted); white-space: nowrap;">Sort
                    By:</label>
                <select name="sort" class="form-control" style="width: 180px; border-radius: 12px; height: 45px;"
                    onchange="this.form.submit()">
                    <option value="newest" <?php echo ($_GET['sort'] ?? '') == 'newest' ? 'selected' : ''; ?>>Newest First
                    </option>
                    <option value="name_asc" <?php echo ($_GET['sort'] ?? '') == 'name_asc' ? 'selected' : ''; ?>>Name
                        (A-Z)</option>
                    <option value="reg_asc" <?php echo ($_GET['sort'] ?? '') == 'reg_asc' ? 'selected' : ''; ?>>Reg.
                        Number (Asc)</option>
                </select>
            </form>
        </div>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; min-width: 600px;">
                <thead>
                    <tr
                        style="text-align: left; border-bottom: 2px solid var(--border-color); color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em;">
                        <th style="padding: 15px 10px;">Full Name</th>
                        <th style="padding: 15px 10px;">Reg. Number</th>
                        <th style="padding: 15px 10px;">Degree</th>
                        <th style="padding: 15px 10px;">Email</th>
                        <th style="padding: 15px 10px; text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody id="studentTableBody">
                    <?php
                    $sort_option = $_GET['sort'] ?? 'newest';
                    $order_by = "created_at DESC";

                    if ($sort_option == 'name_asc') {
                        $order_by = "full_name ASC";
                    } elseif ($sort_option == 'reg_asc') {
                        $order_by = "register_number ASC";
                    }

                    $students = $conn->query("SELECT * FROM users WHERE role = 'student' ORDER BY $order_by");
                    if ($students->num_rows > 0) {
                        while ($row = $students->fetch_assoc()) {
                            echo "<tr class='student-row' style='border-bottom: 1px solid var(--border-color); transition: var(--transition);'> ";
                            echo "<td class='stu-name' style='padding: 16px 10px; font-weight: 600; color: var(--text-main);'>" . htmlspecialchars($row['full_name']) . "</td>";
                            echo "<td style='padding: 16px 10px; color: var(--text-muted); font-size: 0.9rem;'>" . htmlspecialchars($row['register_number'] ?? '') . "</td>";
                            echo "<td style='padding: 16px 10px; color: var(--text-muted); font-size: 0.9rem;'>" . htmlspecialchars($row['degree'] ?? '') . "</td>";
                            echo "<td class='stu-email' style='padding: 16px 10px; color: var(--text-muted); font-size: 0.95rem;'>" . htmlspecialchars($row['email']) . "</td>";
                            echo "<td style='padding: 16px 10px; text-align: right;'>
                                    <button onclick='editStudent(" . json_encode($row) . ")' class='btn-icon' style='color: var(--primary-color); background: #f8fafc; border: none; cursor: pointer; width: 32px; height: 32px; border-radius: 8px; margin-right: 5px;' title='Edit'><i class='fas fa-edit'></i></button>
                                    <a href='?delete=" . $row['id'] . "' onclick='return confirm(\"Are you sure you want to delete this student?\")' class='btn-icon' style='color: var(--danger); background: #fef2f2; border: none; cursor: pointer; width: 32px; height: 32px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none;' title='Delete'><i class='fas fa-trash'></i></a>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' style='padding: 30px; text-align: center; color: var(--text-muted);'>No students found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Student Modal -->
<div id="editModal"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div class="stat-card" style="width: 100%; max-width: 500px; padding: 35px; position: relative;">
        <button onclick="closeModal()"
            style="position: absolute; right: 20px; top: 20px; background: none; border: none; font-size: 1.5rem; color: var(--text-muted); cursor: pointer;"><i
                class="fas fa-times"></i></button>
        <div class="card-title" style="margin-bottom: 25px;">Edit Student Details</div>

        <form action="" method="POST">
            <input type="hidden" name="student_id" id="edit_id">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" id="edit_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" id="edit_email" class="form-control" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label>Register Number</label>
                    <input type="text" name="register_number" id="edit_reg_no" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Degree / Branch</label>
                    <input type="text" name="degree" id="edit_degree" class="form-control" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label>Batch</label>
                    <input type="text" name="batch" id="edit_batch" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>College</label>
                    <input type="text" name="college" id="edit_college" class="form-control" value="VEL TECH UNIVERSITY"
                        readonly style="background: #f1f5f9; cursor: not-allowed;">
                </div>
            </div>

            <div class="form-group">
                <label>New Password (leave blank to keep current)</label>
                <input type="password" name="password" class="form-control">
            </div>
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" name="update_student" class="btn btn-primary">Save Changes</button>
                <button type="button" onclick="closeModal()" class="btn"
                    style="background: #f1f5f9; color: var(--text-muted);">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Search Functionality
    document.getElementById('studentSearch').addEventListener('keyup', function () {
        let searchValue = this.value.toLowerCase();
        let rows = document.querySelectorAll('.student-row');

        rows.forEach(row => {
            let name = row.querySelector('.stu-name').textContent.toLowerCase();
            let email = row.querySelector('.stu-email').textContent.toLowerCase();
            if (name.includes(searchValue) || email.includes(searchValue)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // Modal Functions
    function editStudent(student) {
        document.getElementById('edit_id').value = student.id;
        document.getElementById('edit_name').value = student.full_name;
        document.getElementById('edit_email').value = student.email;
        document.getElementById('edit_reg_no').value = student.register_number || '';
        document.getElementById('edit_degree').value = student.degree || '';
        document.getElementById('edit_batch').value = student.batch || '';
        document.getElementById('edit_college').value = student.college || '';
        document.getElementById('editModal').style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('editModal').style.display = 'none';
    }

    // Close modal on background click
    window.onclick = function (event) {
        let modal = document.getElementById('editModal');
        if (event.target == modal) {
            closeModal();
        }
    }
</script>

<?php include '../includes/footer.php'; ?>