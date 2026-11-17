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

/**
 * Set a flash message
 */
function setFlashMessage($type, $message)
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Display flash message using the toast system
 */
function displayFlashMessage()
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (isset($_SESSION['flash'])) {
        $type = $_SESSION['flash']['type'];
        $message = $_SESSION['flash']['message'];
        unset($_SESSION['flash']);
        
        $toastType = ($type == 'danger') ? 'danger' : (($type == 'warning') ? 'warning' : 'success');
        
        echo "<script>document.addEventListener('DOMContentLoaded', () => {
            if (typeof showToast === 'function') {
                showToast('" . addslashes($message) . "', '$toastType');
            }
        });</script>";
    }
}
?>