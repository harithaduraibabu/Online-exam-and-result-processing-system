<?php
// audit_all.php
$host = 'localhost';
$username = 'root';
$password = '';
$port = 3307;

$conn = new mysqli($host, $username, $password, '', $port);
$dbs_res = $conn->query("SHOW DATABASES");

echo "Checking all databases for data...\n";

while ($row = $dbs_res->fetch_array()) {
    $db = $row[0];
    if (in_array($db, ['information_schema', 'mysql', 'performance_schema', 'phpmyadmin']))
        continue;

    // Use backticks for database names with special characters
    $success = @$conn->select_db($db);
    if (!$success) {
        // Try skipping instead of dying
        echo "Could not select: $db\n";
        continue;
    }

    $tables = $conn->query("SHOW TABLES");
    if ($tables) {
        $has_data = false;
        $outputs = [];
        while ($t = $tables->fetch_array()) {
            $table = $t[0];
            $count_res = @$conn->query("SELECT COUNT(*) FROM `$table` ");
            if ($count_res) {
                $count = $count_res->fetch_array()[0];
                if ($count > 0) {
                    $outputs[] = "  - $table: $count rows";
                    $has_data = true;
                }
            }
        }
        if ($has_data) {
            echo "Database: $db [!] HAS DATA\n";
            foreach ($outputs as $out)
                echo $out . "\n";
        } else {
            echo "Database: $db (empty)\n";
        }
    }
}
?>