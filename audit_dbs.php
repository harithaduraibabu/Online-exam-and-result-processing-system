<?php
require_once 'includes/db_connect.php';

$databases = ['#mysql50#exam-portal', 'exam-portal', 'examportal', 'exam_system', 'online_exam', 'online_exam_system', 'onlineportal'];

foreach ($databases as $db) {
    echo "Checking Database: $db\n";
    if ($conn->select_db($db)) {
        $tables_res = $conn->query("SHOW TABLES");
        if ($tables_res) {
            while ($t = $tables_res->fetch_array()) {
                $table = $t[0];
                $count_res = $conn->query("SELECT COUNT(*) FROM `$table`");
                if ($count_res) {
                    $count = $count_res->fetch_array()[0];
                    echo "  - $table: $count rows\n";
                } else {
                    echo "  - $table: error counting\n";
                }
            }
        } else {
            echo "  - Error showing tables\n";
        }
    } else {
        echo "  - Could not select database\n";
    }
    echo "\n";
}
?>