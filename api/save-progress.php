<?php
// api/save-progress.php - AJAX endpoint for saving student progress
require_once '../includes/db_connect.php';

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);

if (!$input) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    exit();
}

$sub_id = (int) $input['submission_id'];
$q_id = (int) $input['question_id'];
$type = $input['type'] ?? 'MCQ';
$answer = mysqli_real_escape_string($conn, $input['answer'] ?? '');
$lang = isset($input['lang']) ? mysqli_real_escape_string($conn, $input['lang']) : null;

// Check if answer already exists
$check = $conn->query("SELECT id FROM student_answers WHERE submission_id = $sub_id AND question_id = $q_id");

if ($check && $check->num_rows > 0) {
    if ($type == 'MCQ') {
        $conn->query("UPDATE student_answers SET selected_option = '$answer' WHERE submission_id = $sub_id AND question_id = $q_id");
    } else {
        $conn->query("UPDATE student_answers SET submitted_code = '$answer', student_language = '$lang' WHERE submission_id = $sub_id AND question_id = $q_id");
    }
} else {
    if ($type == 'MCQ') {
        $conn->query("INSERT INTO student_answers (submission_id, question_id, selected_option) VALUES ($sub_id, $q_id, '$answer')");
    } else {
        $conn->query("INSERT INTO student_answers (submission_id, question_id, submitted_code, student_language) VALUES ($sub_id, $q_id, '$answer', '$lang')");
    }
}

echo json_encode(['status' => 'success']);
?>