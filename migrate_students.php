<?php
require_once 'includes/db_connect.php';

$sql = "ALTER TABLE users 
        ADD COLUMN register_number VARCHAR(50) AFTER role,
        ADD COLUMN degree VARCHAR(100) AFTER register_number,
        ADD COLUMN batch VARCHAR(10) AFTER degree,
        ADD COLUMN college VARCHAR(255) AFTER batch,
        ADD COLUMN profile_image VARCHAR(255) DEFAULT NULL AFTER college,
        ADD COLUMN profile_banner VARCHAR(255) DEFAULT NULL AFTER profile_image";

if ($conn->query($sql)) {
    echo "Database migrated successfully.";
} else {
    echo "Error migrating database: " . $conn->error;
}
?>