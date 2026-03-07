<?php
// data_discovery.php
$host = 'localhost';
$username = 'root';
$password = '';
$port = 3307;

$c = mysqli_connect($host, $username, $password, '', $port);

$dbs = ['exam-portal', 'examportal', 'exam_system', 'online_exam_system', 'onlineportal'];

echo "--- DATA DISCOVERY REPORT ---\n";

foreach ($dbs as $db) {
    if (@mysqli_select_db($c, $db)) {
        echo "Database: $db\n";
        $tables = mysqli_query($c, "SHOW TABLES");
        while ($t = mysqli_fetch_row($tables)) {
            $table = $t[0];
            $res = mysqli_query($c, "SELECT COUNT(*) FROM `$table` ");
            if ($res) {
                $count = mysqli_fetch_row($res)[0];
                if ($count > 0) {
                    echo "  - $table: $count rows\n";
                }
            }
        }
    } else {
        echo "Database: $db (could not access)\n";
    }
}
?>