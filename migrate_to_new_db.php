<?php
require_once 'includes/db_connect.php';

// New DB name is already set in db_connect.php

// 1. Initialize schema in the new database
echo "Initializing schema in 'examportal'...\n";
$sql = file_get_contents('schema.sql');
if ($conn->multi_query($sql)) {
    do {
        if ($res = $conn->store_result())
            $res->free();
    } while ($conn->next_result());
    echo "Schema initialized.\n";
}

// 2. Restore students
echo "Restoring students...\n";
include 'tmp_add_students.php';

// 3. Restore admin
echo "Restoring admin...\n";
$email = 'admin@example.com';
$hash = password_hash('admin123', PASSWORD_DEFAULT);
$conn->query("UPDATE users SET password = '$hash' WHERE email = '$email'");

// 4. Run migrations
echo "Running migrations...\n";
include 'migrate_students.php';

echo "Migration to 'examportal' complete.\n";
?>