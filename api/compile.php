<?php
// api/compile.php - Proxy to Judge0 API
require_once '../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['error' => 'Invalid request method']));
}

$input_data = json_decode(file_get_contents('php://input'), true);
$source_code = $input_data['source_code'];
$language_id = $input_data['language_id']; // Judge0 IDs: 62 (Java), 71 (Python), 50 (C), 54 (C++)
$stdin = $input_data['stdin'] ?? "";
$expected_output = $input_data['expected_output'] ?? "";

$api_key = "d39b6c6fd4msh7482ff03b540eedp1dd22ejsnd39c49f9a787";
$host = "judge0-ce.p.rapidapi.com";

// 1. Create Submission
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => "https://$host/submissions?base64_encoded=false&wait=true",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        "language_id" => $language_id,
        "source_code" => $source_code,
        "stdin" => $stdin,
        "expected_output" => $expected_output
    ]),
    CURLOPT_HTTPHEADER => [
        "X-RapidAPI-Host: $host",
        "X-RapidAPI-Key: $api_key",
        "Content-Type: application/json"
    ],
    CURLOPT_SSL_VERIFYPEER => false, // Bypass SSL for local dev
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($curl);
$err = curl_error($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

if ($err) {
    echo json_encode(["error" => "cURL Error: " . $err]);
} elseif ($http_code >= 400) {
    echo json_encode(["error" => "API Error (HTTP $http_code): " . $response]);
} else {
    echo $response;
}
?>