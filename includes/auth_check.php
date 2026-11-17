<?php
// includes/auth_check.php
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$session_uid = (int) $_SESSION['user_id'];
$auth_check_stmt = $conn->prepare("SELECT id, full_name, email, role FROM users WHERE id = ?");
$auth_check_stmt->bind_param("i", $session_uid);
$auth_check_stmt->execute();
$auth_check_res = $auth_check_stmt->get_result();

if (!$auth_check_res || $auth_check_res->num_rows === 0) {
    // Session is invalid or stale from another project on localhost
    session_unset();
    session_destroy();
    header("Location: ../index.php");
    exit();
}

$auth_user = $auth_check_res->fetch_assoc();
if (empty($_SESSION['full_name'])) {
    $_SESSION['full_name'] = $auth_user['full_name'];
}
if (empty($_SESSION['role'])) {
    $_SESSION['role'] = $auth_user['role'];
}
if (empty($_SESSION['email'])) {
    $_SESSION['email'] = $auth_user['email'];
}

function checkRole($role)
{
    $current_role = $_SESSION['role'] ?? '';
    if ($current_role !== $role) {
        if ($current_role === 'admin') {
            header("Location: ../admin/index.php");
        } else {
            header("Location: ../student/index.php");
        }
        exit();
    }
}
?>