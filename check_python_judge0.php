<?php
$source_code = "# Read the year from standard input dynamically\nyear = int(input())\n\n# Check leap year condition\nif (year % 400 == 0) or (year % 4 == 0 and year % 100 != 0):\n    print(\"Leap Year\")\nelse:\n    print(\"Not a Leap Year\")\n";
$stdin = "2024";

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => "https://ce.judge0.com/submissions?base64_encoded=false&wait=true",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        "language_id" => 71,
        "source_code" => $source_code,
        "stdin" => trim($stdin)
    ]),
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json"
    ],
    CURLOPT_SSL_VERIFYPEER => false
]);

$response = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$error = curl_error($curl);
curl_close($curl);

echo "HTTP Code: $http_code\n";
echo "Error: $error\n";
echo "Response: \n$response\n";
?>
