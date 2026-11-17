<?php
require_once 'includes/db_connect.php';
$res = $conn->query("SELECT id, student_language, evaluation_status FROM student_answers ORDER BY id DESC LIMIT 5");
while($row = $res->fetch_assoc()) {
    echo json_encode($row) . "\n";
}
?>
