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

if ($submission['status'] !== 'pending' && $submission['status'] !== null) {
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

    if ($q['type'] === 'MCQ') {
        if ($ans['selected_option'] === $q['correct_option']) {
            $is_correct = 1;
            $marks_awarded = $q['marks'];
        }
    } else if ($q['type'] === 'Coding') {
        // Coding Evaluation via Judge0
        // Determine Language ID
        $lang_name = strtolower($ans['student_language'] ?? '');
        $lang_id = 62; // Java default
        if (strpos($lang_name, 'python') !== false)
            $lang_id = 71;
        elseif (strpos($lang_name, 'cpp') !== false || strpos($lang_name, 'c++') !== false)
            $lang_id = 54;
        elseif (strpos($lang_name, 'c') !== false && strpos($lang_name, 'cpp') === false)
            $lang_id = 50;

        $source_code = $ans['submitted_code'];

        // Evaluate against test cases
        $tests_passed = 0;
        $total_tests = 0;

        $host = "ce.judge0.com"; // Free public cluster

        // Try new test case table first
        $tc_res = $conn->query("SELECT * FROM question_test_cases WHERE question_id = $q_id");
        $all_test_cases = [];
        if ($tc_res && $tc_res->num_rows > 0) {
            while ($tc = $tc_res->fetch_assoc()) {
                $all_test_cases[] = ['in' => $tc['input'], 'out' => $tc['expected_output']];
            }
        } else {
            // Fallback to legacy 3 test case columns
            for ($i = 1; $i <= 3; $i++) {
                if (!empty(trim($q["test_case_{$i}_output"] ?? ''))) {
                    $all_test_cases[] = ['in' => $q["test_case_{$i}_input"], 'out' => $q["test_case_{$i}_output"]];
                }
            }
        }

        foreach ($all_test_cases as $tc) {
            $db_in = $tc['in'];
            $db_out = $tc['out'];
            $total_tests++;

            // Call Judge0
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => "https://$host/submissions?base64_encoded=false&wait=true",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode([
                    "language_id" => $lang_id,
                    "source_code" => $source_code,
                    "stdin" => trim($db_in),
                    "expected_output" => trim($db_out)
                ]),
                CURLOPT_HTTPHEADER => [
                    "Content-Type: application/json"
                ],
                CURLOPT_SSL_VERIFYPEER => false
            ]);

            $response = curl_exec($curl);
            curl_close($curl);

            if ($response) {
                $json_res = json_decode($response, true);
                if (isset($json_res['status']['id']) && $json_res['status']['id'] == 3) {
                    $tests_passed++;
                }
            }
        }

        if ($total_tests > 0) {
            $ratio = $tests_passed / $total_tests;
            $marks_awarded = round($ratio * $q['marks'], 2);

            if ($tests_passed == $total_tests) {
                $status = 'Accepted';
            } elseif ($tests_passed > 0) {
                $status = 'Partial';
            } else {
                $status = 'Wrong Answer';
            }
        } else {
            $status = 'Wrong Answer';
        }
    }

    $total_score += $marks_awarded;

    // Update individual answer with professional evaluation data
    if ($q['type'] === 'Coding') {
        $stmt = $conn->prepare("UPDATE student_answers SET is_correct = ?, marks_obtained = ?, passed_test_cases = ?, total_test_cases = ?, evaluation_status = ? WHERE id = ?");
        $stmt->bind_param("idiisi", $is_correct, $marks_awarded, $tests_passed, $total_tests, $status, $ans_id);
    } else {
        $stmt = $conn->prepare("UPDATE student_answers SET is_correct = ?, marks_obtained = ? WHERE id = ?");
        $stmt->bind_param("idi", $is_correct, $marks_awarded, $ans_id);
    }
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