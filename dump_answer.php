<?php
require_once 'includes/db_connect.php';
$res = $conn->query("SELECT *, REPLACE(submitted_code, '\r\n', '\n') as clean_code FROM student_answers WHERE student_language LIKE '%Python%' ORDER BY id DESC LIMIT 1");
if($row = $res->fetch_assoc()) {
    print_r($row);
    // Let's run Judge0 on this exactly how submit-exam does
    $q_id = $row['question_id'];
    $tc_res = $conn->query("SELECT * FROM question_test_cases WHERE question_id = $q_id LIMIT 1");
    if($tc = $tc_res->fetch_assoc()) {
        $stdin = $tc['input'];
        $db_out = $tc['expected_output'];
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://ce.judge0.com/submissions?base64_encoded=false&wait=true",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                "language_id" => 71,
                "source_code" => $row['submitted_code'],
                "stdin" => trim($stdin)
            ]),
            CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        
        echo "\n==== JUDGE0 EXEC ====\n";
        echo "Stdin: " . UserInput($stdin) . "\n";
        echo "Expected: " . UserInput($db_out) . "\n";
        echo "Response: $response\n";
    }
} else {
    echo "No python submissions found\n";
}

function UserInput($str) { return json_encode($str); }
?>
