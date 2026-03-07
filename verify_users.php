<?php
require_once 'includes/db_connect.php';

// Ensure admin exists
$email = 'admin@example.com';
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

$res = $conn->query("SELECT * FROM users WHERE email = '$email'");
if ($res->num_rows == 0) {
    echo "Creating admin...\n";
    $conn->query("INSERT INTO users (full_name, email, password, role) VALUES ('Admin User', '$email', '$hash', 'admin')");
} else {
    echo "Updating admin password...\n";
    $conn->query("UPDATE users SET password = '$hash' WHERE email = '$email'");
}

echo "Current Students:\n";
$res = $conn->query("SELECT email FROM users WHERE role = 'student'");
while ($row = $res->fetch_assoc()) {
    echo " - " . $row['email'] . "\n";
}

// Check if submissions exist
$res = $conn->query("SELECT COUNT(*) as total FROM submissions");
echo "Total submissions in 'examportal': " . $res->fetch_assoc()['total'] . "\n";
?>