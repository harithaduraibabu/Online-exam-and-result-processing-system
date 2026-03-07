<?php
// admin/learning-space.php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';
checkRole('admin');

$page_title = "Learning Space";

// Fetch completed exams for reference materials
$completed_exams = $conn->query("SELECT * FROM exams WHERE end_date < NOW() ORDER BY end_date DESC");

// Handle Material Publishing Toggle
if (isset($_GET['publish_material'])) {
    $id = (int) $_GET['publish_material'];
    $current = $conn->query("SELECT is_material_published FROM exams WHERE id = $id")->fetch_assoc()['is_material_published'];
    $new_status = $current ? 0 : 1;
    $conn->query("UPDATE exams SET is_material_published = $new_status WHERE id = $id");
    header("Location: learning-space.php?msg=updated");
    exit();
}

// Handle New Resource Upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_resource'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $type = mysqli_real_escape_string($conn, $_POST['type']); // 'document' or 'video'

    if (!empty($_FILES['resource_file']['name'])) {
        $file_name = $_FILES['resource_file']['name'];
        $file_tmp = $_FILES['resource_file']['tmp_name'];

        // Secure file naming
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $new_file_name = time() . '_' . preg_replace("/[^a-zA-Z0-9]/", "_", pathinfo($file_name, PATHINFO_FILENAME)) . '.' . $file_ext;
        $upload_dir = '../uploads/resources/';

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $dest_path = $upload_dir . $new_file_name;

        // Basic validation
        $allowed_exts = ['pdf', 'docx', 'txt', 'mp4', 'webm', 'png', 'jpg', 'jpeg'];
        if (in_array($file_ext, $allowed_exts)) {
            if (move_uploaded_file($file_tmp, $dest_path)) {
                $db_path = 'uploads/resources/' . $new_file_name;
                $stmt = $conn->prepare("INSERT INTO resources (title, file_path, type, uploaded_by) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("sssi", $title, $db_path, $type, $_SESSION['user_id']);
                if ($stmt->execute()) {
                    header("Location: learning-space.php?msg=resource_added");
                    exit();
                } else {
                    $error = "Database Error: " . $conn->error;
                }
            } else {
                $error = "Failed to move uploaded file.";
            }
        } else {
            $error = "Invalid file type. Allowed: PDF, DOCX, TXT, MP4, WEBM, Images.";
        }
    } else {
        $error = "Please select a file to upload.";
    }
}

// Handle Delete Resource
if (isset($_GET['delete_res'])) {
    $res_id = (int) $_GET['delete_res'];
    $stmt = $conn->prepare("SELECT file_path FROM resources WHERE id = ?");
    $stmt->bind_param("i", $res_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();

    if ($res) {
        $file_path = '../' . $res['file_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        $conn->query("DELETE FROM resources WHERE id = $res_id");
        header("Location: learning-space.php?msg=resource_deleted");
        exit();
    }
}

include '../includes/header.php';
?>

<?php if (isset($_GET['msg']) && $_GET['msg'] == 'updated')
    echo "<div class='alert' style='background: #ecfdf5; color: #065f46; margin: 20px 0;'>Material status updated successfully!</div>"; ?>

<div class="content-row">
    <div style="margin-bottom: 30px;">
        <h2 style="font-weight: 700;">Learning Space</h2>
        <p style="color: var(--text-muted);">Repository of completed assessments, answers, and learning resources.</p>
    </div>

    <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));">
        <!-- Section: Completed Assessments -->
        <div class="stat-card">
            <h3 style="margin-bottom: 20px; font-weight: 600;">Completed Assessments</h3>
            <div style="display: flex; flex-direction: column; gap: 15px;">
                <?php if ($completed_exams->num_rows > 0): ?>
                    <?php while ($exam = $completed_exams->fetch_assoc()): ?>
                        <div style="padding: 15px; background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0;">
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <h4 style="font-weight: 600;">
                                    <?php echo htmlspecialchars($exam['title']); ?>
                                </h4>
                                <span style="font-size: 0.75rem; background: #f1f5f9; padding: 4px 10px; border-radius: 20px;">
                                    <?php echo $exam['type']; ?>
                                </span>
                                </div> <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 15px;">
                                Ended on:
                                <?php echo date('d M, Y', strtotime($exam['end_date'])); ?>
                                    </p>
                                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                        <a href="manage-questions.php?exam_id=<?php echo $exam['id']; ?>" class="btn"
                                style="padding: 6px 12px; font-size: 0.8rem; background: var(--bg-color); color:
                        var(--text-color); text-decoration: none; border: 1px solid #e2e8f0;">Edit/View
                                Questions</a>
                                <a href="publish-results.php?id=<?php echo $exam['id']; ?>" class="btn btn-primary"
                                            style="padding: 6px 12px; font-size: 0.8rem; text-decoration: none;">View Analytics</a>

                                        <?php
                                        $pub_class = $exam['is_material_published'] ? 'background: #0ea5e9; color: white;' : 'background: #f1f5f9; color: #64748b;';
                                        $pub_text = $exam['is_material_published'] ? 'Published as Material' : 'Publish Material';
                                        ?>
                                <a href="?publish_material=<?php echo $exam['id']; ?>" class="btn"
                                    style="padding: 6px 12px; font-size: 0.8rem; text-decoration: none; border: 1px solid #e2e8f0; <?php echo $pub_class; ?>">
                                    <i class="fas fa-bullhorn" style="margin-right: 5px;"></i>
                                    <?php echo $pub_text; ?>
                                                </a>
                                                </div>
                            </div>
                            <?php endwhile; ?>
                    <?php else: ?>
                        <p style="color: var(--text-muted); font-size: 0.9rem;">No completed assessments yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Section: Learning Materials -->
            <div class="stat-card">
                <h3 style="margin-bottom: 20px; font-weight: 600;">Learning Materials</h3>
                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <div style="padding: 20px; background: #ecfdf5; border-radius: 12px; border: 1px solid #d1fae5;">
                        <h4 style="font-weight: 600; color: #065f46;">Resource Management</h4>
                        <p style="font-size: 0.85rem; color: #065e46; margin-top: 5px; margin-bottom: 15px;">
                            Upload and manage study materials, video lectures, and documentation for students.
                        </p>
                            <button class="btn btn-primary" onclick="document.getElementById('resource-modal').style.display='flex'" style="background: #10b981; border: none;">Add New Resource</button>
                </div>

                <?php
                $resources = $conn->query("SELECT * FROM resources ORDER BY created_at DESC");
                if ($resources->num_rows > 0):
                    while ($res = $resources->fetch_assoc()):
                        $icon = $res['type'] == 'video' ? 'fa-video' : 'fa-file-alt';
                        ?>
                                <div
                                    style="padding: 15px; border: 1px solid #cbd5e1; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; background: white;">
                                    <div style="display: flex; align-items: center; gap: 15px;">
                                        <div
                                            style="width: 40px; height: 40px; border-radius: 8px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: var(--primary-color);">
                                            <i class="fas <?php echo $icon; ?>"></i>
                                        </div>
                                        <div>
                                            <h5 style="font-size: 0.95rem; font-weight: 600;">
                                                <?php echo htmlspecialchars($res['title']); ?>
                                            </h5>
                                            <span style="font-size: 0.75rem; color: var(--text-muted);">Uploaded:
                                                <?php echo date('M d, Y', strtotime($res['created_at'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div style="display: flex; gap: 10px;">
                                        <a href="../<?php echo $res['file_path']; ?>" target="_blank" class="btn"
                                            style="padding: 6px 12px; font-size: 0.8rem; background: #eef2ff; color: var(--accent-color); text-decoration: none;">View</a>
                                                    <a href="?delete_res=<?php echo $res['id']; ?>" class="btn" onclick="return confirm('Delete this resource?')" style="padding: 6px 12px; font-size: 0.8rem; background: #fee2e2; color: var(--danger); text-decoration: none;"><i class="fas fa-trash"></i></a>
                        
                                    </div>
                                </div>
                            <?php endwhile;
                else: ?>
                                <div style="padding: 15px; border: 1px dashed #cbd5e1; border-radius: 12px; text-align:
                        center;">
                            <p style="font-size: 0.85rem; color: var(--text-muted);">No additional resources uploaded.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Resource Modal -->
    <div id="resource-modal"
        style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div class="auth-card" style="width: 100%; max-width: 500px; padding: 30px; border-radius: 15px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="font-weight: 600;">Upload New Resource</h3>
                <button onclick="document.getElementById('resource-modal').style.display='none'"
                    style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: var(--text-muted);">&times;</button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Resource Title</label>
                    <input type="text" name="title" class="form-control" required placeholder="e.g., Chapter 1 Notes">
                </div>
                <div class="form-group">
                    <label>Resource Type</label>
                    <select name="type" class="form-control" required>
                        <option value="document">Document (PDF, DOCX)</option>
                        <option value="video">Video (MP4)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Select File</label>
                    <input type="file" name="resource_file" class="form-control" required style="padding: 8px;">
                </div>
                <button type="submit" name="upload_resource" class="btn btn-primary" style="margin-top: 10px;">Upload
                    Resource</button>
            </form>
                </div>
        </div>

        <?php include '../includes/footer.php'; ?>