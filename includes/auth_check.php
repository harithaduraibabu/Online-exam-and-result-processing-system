<?php
// includes/auth_check.php
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

function checkRole($role)
{
    if ($_SESSION['role'] !== $role) {
        if ($_SESSION['role'] == 'admin') {
            header("Location: ../admin/index.php");
        } else {
            header("Location: ../student/index.php");
        }
        exit();
    }
}
?>