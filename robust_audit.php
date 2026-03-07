<?php
// robust_audit.php
$c = @mysqli_connect('localhost', 'root', '', '', 3307);
if (!$c)
    die("Connection failed");

$dbs = mysqli_query($c, "SHOW DATABASES");
while ($r = mysqli_fetch_row($dbs)) {
    $db = $r[0];
    if (in_array($db, ['information_schema', 'mysql', 'performance_schema', 'phpmyadmin']))
        continue;

    echo "DB: $db\n";
    if (!@mysqli_select_db($c, $db)) {
        echo "  (Could not select)\n";
        continue;
    }

    $tables = mysqli_query($c, "SHOW TABLES");
    while ($t = mysqli_fetch_row($tables)) {
        $tbl = $t[0];
        $count_res = @mysqli_query($c, "SELECT COUNT(*) FROM `$tbl` ");
        if ($count_res) {
            $cnt = mysqli_fetch_row($count_res)[0];
            if ($cnt > 0) {
                echo "  - $tbl: $cnt rows\n";
            }
        }
    }
}
?>