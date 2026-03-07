<?php
// includes/sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? 'student';
?>

<div class="sidebar">
    <a href="#" class="sidebar-logo">
        <span>EXAM PORTAL</span>
    </a>

    <ul class="sidebar-menu">
        <li class="menu-item">
            <a href="../<?php echo $role; ?>/index.php"
                class="menu-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                <span>Dashboard</span>
            </a>
        </li>

        <?php if ($role == 'admin'): ?>
            <li class="menu-item">
                <a href="manage-exams.php"
                    class="menu-link <?php echo $current_page == 'manage-exams.php' ? 'active' : ''; ?>">
                    <span>Assessments</span>
                </a>
            </li>

            <li class="menu-item">
                <a href="publish-results.php"
                    class="menu-link <?php echo $current_page == 'publish-results.php' ? 'active' : ''; ?>">
                    <span>Result Management</span>
                </a>
            </li>

            <li class="menu-item">
                <a href="manage-students.php"
                    class="menu-link <?php echo $current_page == 'manage-students.php' ? 'active' : ''; ?>">
                    <span>Student Management</span>
                </a>
            </li>
        <?php else: ?>
            <li class="menu-item">
                <a href="exams.php" class="menu-link <?php echo $current_page == 'exams.php' ? 'active' : ''; ?>">
                    <span>My Assessments</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="results.php" class="menu-link <?php echo $current_page == 'results.php' ? 'active' : ''; ?>">
                    <span>Performance</span>
                </a>
            </li>

        <?php endif; ?>

        <li class="menu-item" style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
            <a href="profile.php" class="menu-link <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                <span>Account Settings</span>
            </a>
        </li>
    </ul>

    <div class="sidebar-footer" style="margin-top: auto;">
        <a href="../logout.php" class="menu-link" style="color: #fda4af;">
            <span>Sign Out</span>
        </a>
    </div>
</div>