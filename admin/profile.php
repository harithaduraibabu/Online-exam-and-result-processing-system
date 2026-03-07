<?php
// admin/profile.php
// Simply include the same logic as student/profile.php but with admin check
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';
checkRole('admin');

// Redirecting to shared profile logic or just copying for now to keep paths relative
require_once '../student/profile.php';
