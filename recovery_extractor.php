<?php
// recovery_extractor.php
$c = mysqli_connect('localhost', 'root', '', '', 3307);

// This is how MySQL encodes 'exam-portal' in the file system
$old_db = 'exam@002dportal';

echo "Attempting to extract data from $old_db...\n";

if (@mysqli_select_db($c, $old_db)) {
    echo "Successfully connected to $old_db\n";

    $tables = ['submissions', 'student_answers', 'exams', 'questions'];
    foreach ($tables as $tbl) {
        $res = mysqli_query($c, "SELECT COUNT(*) FROM `$tbl` ");
        if ($res) {
            $cnt = mysqli_fetch_row($res)[0];
            echo "Table $tbl: $cnt rows found.\n";

            if ($cnt > 0) {
                echo "Sample data from $tbl:\n";
                $data = mysqli_query($c, "SELECT * FROM `$tbl` LIMIT 1");
                print_r(mysqli_fetch_assoc($data));
            }
        } else {
            echo "Table $tbl: Not found or error.\n";
        }
    }
} else {
    echo "Could not select $old_db. Error: " . mysqli_error($c) . "\n";
}
?>