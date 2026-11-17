<?php
// api/submit-exam.php - Auto-evaluates submission
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';
checkRole('student');

$submission_id = isset($_GET['submission_id']) ? (int) $_GET['submission_id'] : 0;
if (!$submission_id) {
    header("Location: ../student/index.php?error=invalid_submission");
    exit;
}

// 1. Fetch submission and verify ownership
$student_id = $_SESSION['user_id'];
$sub_query = $conn->query("SELECT * FROM submissions WHERE id = $submission_id AND student_id = $student_id");
if ($sub_query->num_rows == 0) {
    header("Location: ../student/index.php?error=not_found");
    exit;
}
$submission = $sub_query->fetch_assoc();

if ($submission['status'] !== null) {
    // Already evaluated
    header("Location: ../student/index.php?status=submitted");
    exit;
}

$exam_id = $submission['exam_id'];

// Fetch all questions for this exam
$q_res = $conn->query("SELECT * FROM questions WHERE exam_id = $exam_id");
$questions_by_id = [];
$total_exam_marks = 0;
while ($q = $q_res->fetch_assoc()) {
    $questions_by_id[$q['id']] = $q;
    $total_exam_marks += $q['marks'];
}

// Fetch all student answers for this submission
$ans_res = $conn->query("SELECT * FROM student_answers WHERE submission_id = $submission_id");
$answers = [];
while ($a = $ans_res->fetch_assoc()) {
    $answers[] = $a;
}

$total_score = 0;

// Evaluation Loop
foreach ($answers as $ans) {
    $q_id = $ans['question_id'];
    $ans_id = $ans['id'];
    $q = $questions_by_id[$q_id] ?? null;

    if (!$q)
        continue;

    $is_correct = 0;
    $marks_awarded = 0;

    if ($ans['type'] === 'MCQ') {
        if ($ans['answer_text'] === $q['correct_option']) {
            $is_correct = 1;
            $marks_awarded = $q['marks'];
        }
    } else if ($ans['type'] === 'Coding') {
        // Coding Evaluation via Judge0
        // Determine Language ID
        $lang_name = strtolower($ans['language']);
        $lang_id = 62; // Java default
        if (strpos($lang_name, 'python') !== false)
            $lang_id = 71;
        if (strpos($lang_name, 'cpp') !== false || strpos($lang_name, 'c++') !== false)
            $lang_id = 54;
        elseif (strpos($lang_name, 'c') !== false && strpos($lang_name, 'cpp') === false)
            $lang_id = 50;

        $source_code = $ans['answer_text'];

        // Evaluate against 3 test cases
        $tests_passed = 0;
        $total_tests = 0;

        $api_key = "d39b6c6fd4msh7482ff03b540eedp1dd22ejsnd39c49f9a787";
        $host = "judge0-ce.p.rapidapi.com";

        for ($i = 1; $i <= 3; $i++) {
            $db_in = $q["test_case_{$i}_input"];
            $db_out = $q["test_case_{$i}_output"];

            // Only evaluate if test case is defined
            if (!empty(trim($db_out))) {
                $total_tests++;

                // Call Judge0
                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL => "https://$host/submissions?base64_encoded=false&wait=true",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => json_encode([
                        "language_id" => $lang_id,
                        "source_code" => $source_code,
                        "stdin" => trim($db_in),
                        "expected_output" => trim($db_out)
                    ]),
                    CURLOPT_HTTPHEADER => [
                        "X-RapidAPI-Host: $host",
                        "X-RapidAPI-Key: $api_key",
                        "Content-Type: application/json"
                    ],
                ]);

                $response = curl_exec($curl);
                curl_close($curl);

                if ($response) {
                    $json_res = json_decode($response, true);
                    // status id 3 means Accepted
                    if (isset($json_res['status']['id']) && $json_res['status']['id'] == 3) {
                        $tests_passed++;
                    }
                }
            }
        }

        if ($total_tests > 0) {
            $ratio = $tests_passed / $total_tests;
            $marks_awarded = round($ratio * $q['marks'], 2);
            $is_correct = ($tests_passed == $total_tests) ? 1 : 0;
        }
    }

    $total_score += $marks_awarded;

    // Update individual answer
    $stmt = $conn->prepare("UPDATE student_answers SET is_correct = ?, marks_obtained = ? WHERE id = ?");
    $stmt->bind_param("idi", $is_correct, $marks_awarded, $ans_id);
    $stmt->execute();
}

// Ensure total score does not exceed exam total marks (fail-safe)
$total_score = min($total_score, $total_exam_marks);

// Close submission
$update_sub = $conn->prepare("UPDATE submissions SET score = ?, status = 'evaluated', finished_at = NOW() WHERE id = ?");
$update_sub->bind_param("di", $total_score, $submission_id);
$update_sub->execute();

header("Location: ../student/index.php?status=submitted");
exit;
?>