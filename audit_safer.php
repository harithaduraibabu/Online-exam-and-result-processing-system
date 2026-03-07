<?php
// audit_safer.php
$host = 'localhost';
$username = 'root';
$password = '';
$port = 3307;

$conn = new mysqli($host, $username, $password, '', $port);

$dbs_res = $conn->query("SHOW DATABASES");
echo "Starting Database Audit...\n";

while ($row = $dbs_res->fetch_array()) {
    $db = $row[0];
    if (in_array($db, ['information_schema', 'mysql', 'performance_schema', 'phpmyadmin']))
        continue;

    echo "Checking: $db\n";
    $success = @$conn->select_db($db);
    if (!$success) {
        echo "  - Error: Could not select database.\n";
        continue;
    }

    $tables = $conn->query("SHOW TABLES");
    if ($tables) {
        while ($t = $tables->fetch_array()) {
            $table = $t[0];
            $count_query = "SELECT COUNT(*) FROM `$table`";
            $count_res = @$conn->query($count_query);
            if ($count_res) {
                $count = $count_res->fetch_array()[0];
                if ($count > 0) {
                    echo "  - $table: $count rows [DATA]\n";
                } else {
                    echo "  - $table: 0 rows\n";
                }
            } else {
                echo "  - $table: Error counting\n";
            }
        }
    }
}
echo "Audit complete.\n";
?>