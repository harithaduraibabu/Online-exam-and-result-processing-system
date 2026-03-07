<?php
// migrate_faculty_id.php
require_once 'includes/db_connect.php';

$sql = "ALTER TABLE users ADD COLUMN faculty_id VARCHAR(50) AFTER role";

if ($conn->query($sql)) {
    echo "Migration successful: faculty_id column added.";
} else {
    echo "Migration failed or column already exists: " . $conn->error;
}
?>