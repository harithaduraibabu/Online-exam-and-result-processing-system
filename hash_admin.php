<?php
require "includes/db_connect.php";
$hash = password_hash('admin123', PASSWORD_DEFAULT);
$conn->query("UPDATE users SET password = '$hash' WHERE email = 'admin@gmail.com'");
echo "Admin password hashed!\n";
?>
