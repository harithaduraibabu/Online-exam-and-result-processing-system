<?php
// targeted_audit.php
$c = mysqli_connect('localhost', 'root', '', '', 3307);

$candidates = [
    'exam-portal',
    'examportal',
    'exam_system',
    'online_exam',
    'online_exam_system',
    'exam_portal_old',
    'onlineportal'
];

echo "Targeted Data Hunt:\n";

foreach ($candidates as $db) {
    echo "Checking: $db\n";
    if (@mysqli_select_db($c, $db)) {
        $tables = ['users', 'submissions', 'questions', 'exams'];
        foreach ($tables as $tbl) {
            $res = @mysqli_query($c, "SELECT COUNT(*) FROM `$tbl` ");
            if ($res) {
                $count = mysqli_fetch_row($res)[0];
                echo "  - $tbl: $count rows\n";
            }
        }
    } else {
        echo "  - Could not access\n";
    }
    echo "\n";
}
?>