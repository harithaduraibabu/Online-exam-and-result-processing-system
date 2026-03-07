<?php
// includes/functions.php - Helper functions
function getLanguageId($name)
{
    switch (strtolower($name)) {
        case 'java':
            return 62;
        case 'python':
            return 71;
        case 'c':
            return 50;
        case 'cpp':
            return 54;
        default:
            return 71;
    }
}

/**
 * Re-calculates and updates the total_marks for an exam based on its questions.
 */
function updateExamTotalMarks($conn, $exam_id)
{
    $res = $conn->query("SELECT SUM(marks) as total FROM questions WHERE exam_id = $exam_id");
    $total = 0;
    if ($res && $row = $res->fetch_assoc()) {
        $total = (int) $row['total'];
    }
    $conn->query("UPDATE exams SET total_marks = $total WHERE id = $exam_id");
}
?>