<?php
// includes/ajax_handler.php
require_once 'db_connect.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

header('Content-Type: application/json');

switch ($action) {
    case 'save_appearance':
        $theme_color = mysqli_real_escape_string($conn, $_POST['theme_color'] ?? '#4F46E5');
        $dark_mode = isset($_POST['dark_mode']) ? (int) $_POST['dark_mode'] : 0;

        $sql = "UPDATE users SET theme_color = '$theme_color', dark_mode = $dark_mode WHERE id = $user_id";
        if ($conn->query($sql)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        break;


    case 'save_cover_gradient':
        if ($_SESSION['role'] !== 'student') {
            echo json_encode(['success' => false, 'message' => 'Only for students']);
            exit;
        }
        $gradient = mysqli_real_escape_string($conn, $_POST['cover_gradient'] ?? '');
        if ($conn->query("UPDATE users SET cover_gradient = '$gradient' WHERE id = $user_id")) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Update failed']);
        }
        break;

    case 'publish_submission':
        if ($_SESSION['role'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Admin access required']);
            exit;
        }

        $sub_id = (int) ($_POST['submission_id'] ?? 0);
        if (!$sub_id) {
            echo json_encode(['success' => false, 'message' => 'Invalid submission ID']);
            exit;
        }

        // Fetch exam_id and current status
        $sub_info = $conn->query("SELECT exam_id, score FROM submissions WHERE id = $sub_id")->fetch_assoc();
        if (!$sub_info) {
            echo json_encode(['success' => false, 'message' => 'Submission not found']);
            exit;
        }

        // 1. Re-calculate scores for this submission
        $conn->query("UPDATE student_answers sa 
                     JOIN questions q ON sa.question_id = q.id 
                     SET sa.is_correct = (CASE WHEN sa.selected_option = q.correct_option THEN 1 ELSE 0 END),
                         sa.marks_obtained = (CASE WHEN sa.selected_option = q.correct_option THEN q.marks ELSE 0 END)
                     WHERE sa.submission_id = $sub_id AND q.type = 'MCQ'");

        $score_row = $conn->query("SELECT SUM(marks_obtained) as total FROM student_answers WHERE submission_id = $sub_id")->fetch_assoc();
        $total_score = $score_row['total'] ?? 0;

        // 2. Publish
        $update = $conn->query("UPDATE submissions SET score = $total_score, status = 'published' WHERE id = $sub_id");

        if ($update) {
            echo json_encode([
                'success' => true,
                'score' => $total_score,
                'message' => 'Result published successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Update failed']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
        break;
}
