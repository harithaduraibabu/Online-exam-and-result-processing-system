<?php
require_once 'includes/db_connect.php';

$res = $conn->query("SHOW DATABASES");
echo "Available Databases:\n";
while ($row = $res->fetch_array()) {
    echo " - " . $row[0] . "\n";
}

$db_candidates = ['exam_system', 'online_exam', 'online_exam_system', 'examportal', 'dbold'];

foreach ($db_candidates as $db) {
    echo "\nScanning $db...\n";
    if (@$conn->select_db($db)) {
        $tables = ['users', 'submissions', 'questions', 'exams'];
        foreach ($tables as $tbl) {
            $count_res = @$conn->query("SELECT COUNT(*) FROM `$tbl` ");
            if ($count_res) {
                $count = $count_res->fetch_array()[0];
                if ($count > 0) {
                    echo "  [FOUND] $tbl: $count rows\n";
                    if ($tbl == 'submissions') {
                        echo "  SUCCESS! FOUND SUBMISSIONS IN $db\n";
                    }
                }
            }
        }
    } else {
        echo "  Unavailable\n";
    }
}
?>