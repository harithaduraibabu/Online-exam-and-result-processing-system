<?php
// index.php - Login Page
require_once 'includes/db_connect.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    $uid = (int) $_SESSION['user_id'];
    $check_stmt = $conn->prepare("SELECT id, full_name, email, role FROM users WHERE id = ?");
    $check_stmt->bind_param("i", $uid);
    $check_stmt->execute();
    $check_res = $check_stmt->get_result();

    if ($check_res && $check_res->num_rows === 1) {
        $u = $check_res->fetch_assoc();
        $_SESSION['full_name'] = $u['full_name'];
        $_SESSION['role'] = $u['role'];
        $_SESSION['email'] = $u['email'];
        if ($u['role'] == 'admin') {
            header("Location: admin/index.php");
        } else {
            header("Location: student/index.php");
        }
        exit();
    } else {
        session_unset();
        session_destroy();
        session_start();
    }
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    $sql = "SELECT id, full_name, password, role FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password']) || $password === $user['password']) {
            if ($password === $user['password']) {
                $new_hash = password_hash($password, PASSWORD_DEFAULT);
                $conn->query("UPDATE users SET password = '$new_hash' WHERE id = " . $user['id']);
            }
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $email;
            $_SESSION['role'] = $user['role'];

            if ($user['role'] == 'admin') {
                header("Location: admin/index.php");
            } else {
                header("Location: student/index.php");
            }
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "User not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Exam Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Animated Background Blobs */
        .bg-blob {
            position: absolute;
            filter: blur(80px);
            z-index: 0;
            opacity: 0.6;
            animation: float 20s infinite alternate ease-in-out;
            border-radius: 50%;
        }

        .blob-1 {
            top: -10%;
            left: -10%;
            width: 50vw;
            height: 50vw;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.4) 0%, rgba(168, 85, 247, 0.1) 70%);
            animation-duration: 25s;
        }

        .blob-2 {
            bottom: -20%;
            right: -10%;
            width: 60vw;
            height: 60vw;
            background: radial-gradient(circle, rgba(56, 189, 248, 0.4) 0%, rgba(59, 130, 246, 0.1) 70%);
            animation-duration: 22s;
            animation-direction: alternate-reverse;
        }

        .blob-3 {
            top: 40%;
            left: 50%;
            width: 40vw;
            height: 40vw;
            background: radial-gradient(circle, rgba(236, 72, 153, 0.3) 0%, rgba(217, 70, 239, 0.1) 70%);
            animation-duration: 28s;
            transform: translate(-50%, -50%);
        }

        @keyframes float {
            0% {
                transform: translate(0, 0) scale(1);
            }

            33% {
                transform: translate(5%, 10%) scale(1.1);
            }

            66% {
                transform: translate(-5%, 5%) scale(0.9);
            }

            100% {
                transform: translate(0, -10%) scale(1.05);
            }
        }

        .auth-container {
            position: relative;
            overflow: hidden;
            background-color: #f8fafc;
        }

        .auth-card {
            position: relative;
            z-index: 10;
            backdrop-filter: blur(16px);
            background: rgba(255, 255, 255, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
        }
    </style>
</head>

<body>
    <div class="auth-container">
        <!-- Animated Background Elements -->
        <div class="bg-blob blob-1"></div>
        <div class="bg-blob blob-2"></div>
        <div class="bg-blob blob-3"></div>

        <div class="auth-card">
            <h1 style="position:relative; z-index:10;">Exam Portal</h1>
            <p style="margin-bottom: 20px; color: var(--text-muted); position:relative; z-index:10;">Sign in to your
                account</p>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="name@example.com" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                <button type="submit" name="login" class="btn btn-primary">Login Now</button>
            </form>

            <div style="margin-top: 25px; font-size: 0.9rem;">
                <p>Don't have an account? <a href="register.php"
                        style="color: var(--primary-color); font-weight: 600; text-decoration: none;">Create one now</a>
                </p>
            </div>

        </div>
    </div>
</body>

</html>