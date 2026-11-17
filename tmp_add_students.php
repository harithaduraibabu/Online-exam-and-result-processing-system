<?php
require_once 'c:/xampp/htdocs/exam-portal/includes/db_connect.php';

$students = [
    ['Ethan', 'ethan@gmail.com', 'ethan'],
    ['Sophia', 'sophia@gmail.com', 'sophia'],
    ['Marcus', 'marcus@gmail.com', 'marcus'],
    ['Chloe', 'chloe@gmail.com', 'chloe'],
    ['Arjun', 'arjun@gmail.com', 'arjun']
];

foreach ($students as $s) {
    // Check if email already exists
    $check = $conn->query("SELECT id FROM users WHERE email = '{$s[1]}'");
    if ($check->num_rows == 0) {
        $hash = password_hash($s[2], PASSWORD_DEFAULT);
        $conn->query("INSERT INTO users (full_name, email, password, role) VALUES ('{$s[0]}', '{$s[1]}', '{$hash}', 'student')");
        echo "Created " . $s[0] . "\n";
    } else {
        echo "Exists " . $s[0] . "\n";
    }
}
?>