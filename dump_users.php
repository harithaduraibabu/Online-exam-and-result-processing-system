<?php
require "includes/db_connect.php";
$res = $conn->query("SELECT id, full_name, email, password, role FROM users");
$users = [];

while($row = $res->fetch_assoc()) {
    $users[] = $row;
    
    // Check if the password looks like a plain text password (not a hash)
    if (strlen($row['password']) < 60) {
        $hash = password_hash($row['password'], PASSWORD_DEFAULT);
        $conn->query("UPDATE users SET password = '$hash' WHERE id = " . $row['id']);
        $row['password_note'] = " (was plain text: '" . $row['password'] . "', now hashed)";
    } else {
        $row['password_note'] = " (is hashed)";
    }
}
echo json_encode($users, JSON_PRETTY_PRINT);
?>
