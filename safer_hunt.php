<?php
require_once 'includes/db_connect.php';

echo "Database: examportal\n";
$res = $conn->query("SELECT id, email, role FROM users");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo " - " . $row['email'] . " [" . $row['role'] . "]\n";
    }
}

echo "\nSubmissions count: ";
$res = $conn->query("SELECT COUNT(*) as total FROM submissions");
echo ($res ? $res->fetch_assoc()['total'] : "Error") . "\n";

echo "\nScanning other databases safely:\n";
$dbs = $conn->query("SHOW DATABASES");
while ($row = $dbs->fetch_array()) {
    $db = $row[0];
    if (in_array($db, ['information_schema', 'mysql', 'performance_schema', 'phpmyadmin', 'examportal']))
        continue;

    echo "DB: $db\n";
    $c2 = @mysqli_connect($host, $username, $password, $db, $port);
    if ($c2) {
        $res = @mysqli_query($c2, "SELECT COUNT(*) FROM submissions");
        if ($res) {
            $count = mysqli_fetch_row($res)[0];
            echo "  - submissions: $count rows\n";
        }
        mysqli_close($c2);
    } else {
        echo "  - Could not connect\n";
    }
}
?>