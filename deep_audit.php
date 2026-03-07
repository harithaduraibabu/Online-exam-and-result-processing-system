<?php
require_once 'includes/db_connect.php';

$dbs_res = $conn->query("SHOW DATABASES");
$dbs = [];
while ($row = $dbs_res->fetch_array()) {
    $dbs[] = $row[0];
}

foreach ($dbs as $db) {
    if (in_array($db, ['information_schema', 'mysql', 'performance_schema', 'phpmyadmin']))
        continue;

    echo "Checking Database: $db\n";
    // Connect to specific DB to avoid permission issues with some characters
    $tmp_conn = new mysqli($host, $username, $password, $db, $port);
    if ($tmp_conn->connect_error) {
        echo "  - Connection failed\n";
        continue;
    }

    $tables = $tmp_conn->query("SHOW TABLES");
    if ($tables) {
        while ($t = $tables->fetch_array()) {
            $table = $t[0];
            $count_res = $tmp_conn->query("SELECT COUNT(*) FROM `$table`");
            if ($count_res) {
                $count = $count_res->fetch_array()[0];
                if ($count > 0) {
                    echo "  - $table: $count rows [DATA FOUND]\n";
                    if ($table == 'submissions' || $table == 'student_answers') {
                        echo "    >>> POTENTIAL RECOVERY SOURCE <<<\n";
                    }
                } else {
                    echo "  - $table: 0 rows\n";
                }
            }
        }
    }
    $tmp_conn->close();
    echo "\n";
}
?>