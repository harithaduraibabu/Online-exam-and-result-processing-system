<?php
// register.php - Student Registration Page
require_once 'includes/db_connect.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: admin/index.php");
    } else {
        header("Location: student/index.php");
    }
    exit();
}

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if email already exists
        $check_sql = "SELECT id FROM users WHERE email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error = "Email already registered.";
        } else {
            $hashed_password = $password;
            $role = 'student';
            $register_number = mysqli_real_escape_string($conn, $_POST['register_number'] ?? '');
            $degree = mysqli_real_escape_string($conn, $_POST['degree'] ?? '');
            $batch = mysqli_real_escape_string($conn, $_POST['batch'] ?? '');
            $college = "VEL TECH UNIVERSITY";

            $insert_sql = "INSERT INTO users (full_name, email, password, role, register_number, degree, batch, college) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("ssssssss", $full_name, $email, $hashed_password, $role, $register_number, $degree, $batch, $college);

            if ($insert_stmt->execute()) {
                $success = "Registration successful! You can now login.";
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Exam Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/dynamic.js" defer></script>
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

        <div class="auth-card" style="max-width: 450px;">
            <h1 style="position:relative; z-index:10;">Create Account</h1>
            <p style="margin-bottom: 20px; color: var(--text-muted); position:relative; z-index:10;">Join the
                examination portal</p>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert" style="background: #ecfdf5; color: #065f46; margin-bottom: 20px;">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" class="form-control" required>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Register Number</label>
                        <input type="text" name="register_number" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Degree / Branch</label>
                        <input type="text" name="degree" class="form-control" required>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Batch</label>
                        <input type="text" name="batch" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>College Name</label>
                        <input type="text" name="college" class="form-control" value="VEL TECH UNIVERSITY" readonly
                            style="background: #f1f5f9; cursor: not-allowed;">
                    </div>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="name@gmail.com" required>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                </div>
                <button type="submit" name="register" class="btn btn-primary"
                    style="margin-top: 10px; width: 100%;">Create Student Account</button>
            </form>

            <div style="margin-top: 25px; font-size: 0.9rem;">
                <p>Already have an account? <a href="index.php"
                        style="color: var(--primary-color); font-weight: 600; text-decoration: none;">Login here</a></p>
            </div>
        </div>
    </div>
</body>

</html>