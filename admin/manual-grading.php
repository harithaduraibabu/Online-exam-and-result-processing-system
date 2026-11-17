<?php
// admin/manual-grading.php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';
checkRole('admin');

$page_title = "Manual Grading";
$success = "";

// Handle Grading Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['grade_answer'])) {
    $answer_id = (int) $_POST['answer_id'];
    $marks = (float) $_POST['marks'];

    $conn->query("UPDATE student_answers SET marks_obtained = $marks, is_correct = ($marks > 0 ? 1 : 0) WHERE id = $answer_id");
    $success = "Marks updated successfully.";
}

include '../includes/header.php';
?>

<div class="content-row">
    <div class="stat-card">
        <div class="card-title">Coding Submissions to Grade</div>
        <?php if ($success)
            echo "<div class='alert' style='background: #ecfdf5; color: #065f46; margin-bottom: 20px;'>$success</div>"; ?>

        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="text-align: left; border-bottom: 2px solid var(--bg-color);">
                    <th style="padding: 15px 0;">Student</th>
                    <th>Exam</th>
                    <th>Language</th>
                    <th>Code Preview</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT sa.*, u.full_name, e.title as exam_title FROM student_answers sa 
                        JOIN submissions s ON sa.submission_id = s.id 
                        JOIN users u ON s.student_id = u.id 
                        JOIN exams e ON s.exam_id = e.id 
                        JOIN questions q ON sa.question_id = q.id 
                        WHERE q.type = 'Coding' AND (sa.marks_obtained = 0 OR sa.marks_obtained IS NULL)";
                $res = $conn->query($sql);
                while ($row = $res->fetch_assoc()) {
                    echo "<tr style='border-bottom: 1px solid var(--bg-color);'>";
                    echo "<td style='padding: 15px 0;'>{$row['full_name']}</td>";
                    echo "<td>{$row['exam_title']}</td>";
                    echo "<td>{$row['student_language']}</td>";
                    echo "<td><pre style='font-size: 0.7rem; max-height: 50px; overflow: hidden;'>" . htmlspecialchars(substr($row['submitted_code'], 0, 50)) . "...</pre></td>";
                    echo "<td>
                            <form action='' method='POST' style='display: flex; gap: 5px;'>
                                <input type='hidden' name='answer_id' value='{$row['id']}'>
                                <input type='number' name='marks' step='0.1' style='width: 60px;' class='form-control' placeholder='Marks' required>
                                <button type='submit' name='grade_answer' class='btn btn-primary' style='width: auto; padding: 5px 10px; font-size: 0.75rem;'>Save</button>
                            </form>
                          </td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>