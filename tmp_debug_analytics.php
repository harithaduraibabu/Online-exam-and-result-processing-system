<?php
require_once 'includes/db_connect.php';

$student_id = 3; // Arjun

$analytics_sql = "
    SELECT 
        SUM(CASE WHEN sa.is_correct = 1 THEN 1 ELSE 0 END) AS correct_count,
        SUM(CASE WHEN (sa.selected_option IS NOT NULL OR (sa.submitted_code IS NOT NULL AND sa.submitted_code != '')) AND sa.is_correct = 0 THEN 1 ELSE 0 END) AS incorrect_count,
        SUM(CASE WHEN (sa.selected_option IS NULL OR sa.selected_option = '') AND (sa.submitted_code IS NULL OR sa.submitted_code = '') THEN 1 ELSE 0 END) AS unanswered_count,
        COUNT(sa.id) AS total_questions
    FROM submissions s
    JOIN student_answers sa ON sa.submission_id = s.id
    WHERE s.student_id = $student_id AND s.status = 'published'
";

$analytics_res = $conn->query($analytics_sql);
$analytics = $analytics_res ? $analytics_res->fetch_assoc() : null;

print_r($analytics);
?>