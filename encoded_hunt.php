<?php
// encoded_hunt.php
$c = mysqli_connect('localhost', 'root', '', '', 3307);

$names = [
    'exam-portal',
    'examportal',
    'exam_portal',
    'exam@002dportal',
    'online_exam_system',
    'online_exam',
    'exam_system'
];

echo "Encoded Data Hunt:\n";

foreach ($names as $name) {
    echo "Accessing: $name\n";
    if (@mysqli_select_db($c, $name)) {
        echo "  - SUCCESS selecting $name\n";
        $tables = mysqli_query($c, "SHOW TABLES");
        while ($t = mysqli_fetch_row($tables)) {
            $tbl = $t[0];
            $res = mysqli_query($c, "SELECT COUNT(*) FROM `$tbl` ");
            if ($res) {
                $count = mysqli_fetch_row($res)[0];
                if ($count > 0) {
                    echo "    * $tbl: $count rows\n";
                }
            }
        }
    } else {
        echo "  - FAILED selecting $name\n";
    }
}
?>