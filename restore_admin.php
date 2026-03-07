<?php
require_once 'includes/db_connect.php';

$email = 'admin@example.com';
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

$check = $conn->query("SELECT id FROM users WHERE email = '$email'");
if ($check->num_rows > 0) {
    $conn->query("UPDATE users SET password = '$hash', role = 'admin' WHERE email = '$email'");
    echo "Updated admin password.\n";
} else {
    $conn->query("INSERT INTO users (full_name, email, password, role) VALUES ('Admin User', '$email', '$hash', 'admin')");
    echo "Created new admin user.\n";
}
?>