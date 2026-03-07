<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db_connect.php';
$u_id = $_SESSION['user_id'] ?? 0;
$theme_data = ['theme_color' => '#4F46E5', 'dark_mode' => 0];
if ($u_id) {
    $theme_res = $conn->query("SELECT theme_color, dark_mode FROM users WHERE id = $u_id");
    if ($theme_res && $theme_res->num_rows > 0) {
        $theme_data = $theme_res->fetch_assoc();
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="<?php echo $theme_data['dark_mode'] ? 'dark-mode' : ''; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo $page_title ?? 'Dashboard'; ?> | Exam Portal
    </title>

    <!-- Google Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Charts.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <!-- Site-wide Dynamic JS -->
    <script src="../assets/js/dynamic.js" defer></script>

    <style>
        :root {
            --primary-color:
                <?php echo $theme_data['theme_color']; ?>
            ;
            <?php
            // Calculate hover color (slightly darker)
            $hex = str_replace('#', '', $theme_data['theme_color']);
            if (strlen($hex) == 3)
                $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
            $r = max(0, $r - 30);
            $g = max(0, $g - 30);
            $b = max(0, $b - 30);
            $hover = sprintf("#%02x%02x%02x", $r, $g, $b);
            ?>
            --primary-hover:
                <?php echo $hover; ?>
            ;
        }

        /* Dark Mode Overrides */
        .dark-mode {
            --bg-color: #0f172a;
            --card-bg: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: #334155;
            --sidebar-bg: #020617;
            --sidebar-hover: #1e293b;
            --glass-bg: rgba(30, 41, 59, 0.85);
            --glass-border: rgba(51, 65, 85, 0.4);
        }

        .dark-mode .stat-card {
            background: var(--card-bg);
            border-color: var(--border-color);
        }

        .dark-mode .top-nav {
            background: var(--glass-bg);
            border-color: var(--border-color);
        }

        .dark-mode .form-control {
            background: #0f172a;
            border-color: var(--border-color);
            color: var(--text-main);
        }

        .dark-mode .user-avatar {
            background: #334155 !important;
            color: #f8fafc !important;
            border-color: #475569 !important;
        }

        .dark-mode .user-profile {
            background: var(--card-bg) !important;
            border-color: var(--border-color) !important;
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <?php include __DIR__ . '/sidebar.php'; ?>

        <div class="main-content">
            <header class="top-nav">
                <div class="nav-left">
                    <h2 style="font-weight: 600;">
                        <?php echo $page_title ?? 'Overview'; ?>
                    </h2>
                </div>
                <div class="nav-right">
                    <div class="user-profile">
                        <div class="user-info" style="text-align: right;">
                            <p style="font-weight: 500; font-size: 0.875rem; color: var(--text-main);">
                                <?php echo $_SESSION['full_name']; ?>
                            </p>
                            <p
                                style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">
                                <?php echo $_SESSION['role']; ?>
                            </p>
                        </div>
                        <div class="user-avatar"
                            style="background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; font-size: 0.75rem;">
                            <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
                        </div>
                    </div>
                </div>
            </header>