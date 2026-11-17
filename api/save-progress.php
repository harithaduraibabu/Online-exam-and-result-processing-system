<?php
// api/save-progress.php - AJAX endpoint for saving student progress
require_once '../includes/db_connect.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!$input)
    exit();

$sub_id = (int) $input['submission_id'];
$q_id = (int) $input['question_id'];
$type = $input['type'];
$answer = mysqli_real_escape_string($conn, $input['answer']);
$lang = isset($input['lang']) ? mysqli_real_escape_string($conn, $input['lang']) : null;

$is_marked = isset($input['is_marked']) ? (int) $input['is_marked'] : 0;

// Check if answer already exists
$check = $conn->query("SELECT id FROM student_answers WHERE submission_id = $sub_id AND question_id = $q_id");

if ($check->num_rows > 0) {
    if ($type == 'MCQ') {
        $conn->query("UPDATE student_answers SET selected_option = '$answer', is_marked = $is_marked WHERE submission_id = $sub_id AND question_id = $q_id");
    } else {
        $conn->query("UPDATE student_answers SET submitted_code = '$answer', student_language = '$lang', is_marked = $is_marked WHERE submission_id = $sub_id AND question_id = $q_id");
    }
} else {
    if ($type == 'MCQ') {
        $conn->query("INSERT INTO student_answers (submission_id, question_id, selected_option, is_marked) VALUES ($sub_id, $q_id, '$answer', $is_marked)");
    } else {
        $conn->query("INSERT INTO student_answers (submission_id, question_id, submitted_code, student_language, is_marked) VALUES ($sub_id, $q_id, '$answer', '$lang', $is_marked)");
    }
}

echo json_encode(['status' => 'success']);
?>