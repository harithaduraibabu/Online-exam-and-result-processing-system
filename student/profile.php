<?php
// student/profile.php (Handles both roles)
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Fetch latest user info
$user_data = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();

$page_title = "Account Settings";
$success = "";
$error = "";

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);

    if ($role == 'student') {
        $register_number = mysqli_real_escape_string($conn, $_POST['register_number'] ?? '');
        $degree = mysqli_real_escape_string($conn, $_POST['degree'] ?? '');
        $batch = mysqli_real_escape_string($conn, $_POST['batch'] ?? '');
        $college = "VEL TECH UNIVERSITY";
        $sql = "UPDATE users SET full_name = '$full_name', register_number = '$register_number', degree = '$degree', batch = '$batch', college = '$college' WHERE id = $user_id";
    } else {
        $faculty_id = mysqli_real_escape_string($conn, $_POST['faculty_id'] ?? '');
        $sql = "UPDATE users SET full_name = '$full_name', faculty_id = '$faculty_id' WHERE id = $user_id";
    }

    if ($conn->query($sql)) {
        $success = "Profile details updated successfully.";
        // Refresh data
        $user_data = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();
    } else {
        $error = "Error updating profile details.";
    }
}

// Handle Appearance Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_appearance'])) {
    $theme_color = mysqli_real_escape_string($conn, $_POST['theme_color'] ?? '#4F46E5');
    $dark_mode = isset($_POST['dark_mode']) ? 1 : 0;

    if ($conn->query("UPDATE users SET theme_color = '$theme_color', dark_mode = $dark_mode WHERE id = $user_id")) {
        $success = "Appearance settings saved successfully.";
        // Refresh data
        $user_data = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();
    } else {
        $error = "Error saving appearance settings.";
    }
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_password'])) {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];

    $res = $conn->query("SELECT password FROM users WHERE id = $user_id");
    $user = $res->fetch_assoc();

    if ($current_pass === $user['password']) {
        $conn->query("UPDATE users SET password = '$new_pass' WHERE id = $user_id");
        $success = "Password updated successfully.";
    } else {
        $error = "Current password is incorrect.";
    }
}

include '../includes/header.php';
?>

<div class="content-row">
    <div class="stat-card" style="max-width: 600px; margin: 0 auto;">
        <h3>Profile Settings</h3>
        <p style="color: var(--text-muted); margin-bottom: 30px;">Manage your account information and preferences.</p>

        <?php if ($success)
            echo "<div class='alert alert-success'>$success</div>"; ?>
        <?php if ($error)
            echo "<div class='alert alert-danger'>$error</div>"; ?>

        <!-- Basic Info Form -->
        <form action="" method="POST">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" class="form-control"
                    value="<?php echo htmlspecialchars($user_data['full_name']); ?>" required>
            </div>
            <div class="form-group">
                <label>Email ID (Cannot be changed)</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_data['email']); ?>"
                    disabled>
            </div>

            <?php if ($role == 'student'): ?>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Register Number</label>
                        <input type="text" name="register_number" class="form-control"
                            value="<?php echo htmlspecialchars($user_data['register_number'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Degree / Branch</label>
                        <input type="text" name="degree" class="form-control"
                            value="<?php echo htmlspecialchars($user_data['degree'] ?? ''); ?>" required>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Batch</label>
                        <input type="text" name="batch" class="form-control"
                            value="<?php echo htmlspecialchars($user_data['batch'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>College</label>
                        <input type="text" name="college" class="form-control" value="VEL TECH UNIVERSITY" readonly
                            style="background: var(--bg-color); cursor: not-allowed;">
                    </div>
                </div>
            <?php else: ?>
                <div class="form-group">
                    <label>Faculty ID</label>
                    <input type="text" name="faculty_id" class="form-control"
                        value="<?php echo htmlspecialchars($user_data['faculty_id'] ?? ''); ?>"
                        placeholder="Enter your Faculty ID" required>
                </div>
            <?php endif; ?>

            <button type="submit" name="update_profile" class="btn btn-primary" style="margin-bottom: 10px;">Update
                Details</button>
        </form>

        <hr style="margin: 30px 0; border: 0; border-top: 1px solid var(--border-color);">

        <!-- Appearance Form -->
        <form action="" method="POST" id="appearanceForm">
            <h4 style="margin-bottom: 20px;">Appearance</h4>

            <div class="form-group"
                style="display: flex; align-items: center; justify-content: space-between; background: var(--bg-color); padding: 15px; border-radius: 12px; border: 1px solid var(--border-color); margin-bottom: 25px; transition: var(--transition);">
                <div>
                    <p style="font-weight: 600; margin: 0; color: var(--text-main);">Dark Mode</p>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0;">Switch to a darker interface for
                        night use.</p>
                </div>
                <label class="switch">
                    <input type="checkbox" name="dark_mode" id="darkModeToggle" <?php echo ($user_data['dark_mode'] ?? 0) ? 'checked' : ''; ?> onchange="previewDarkMode(this.checked)">
                    <span class="slider round"></span>
                </label>
                <div id="syncIndicator"
                    style="position: absolute; right: 0; bottom: -20px; font-size: 0.75rem; color: var(--secondary-color); opacity: 0; transition: opacity 0.3s;">
                    <i class="fas fa-check-circle"></i> Synced
                </div>
            </div>

            <style>
                .switch {
                    position: relative;
                    display: inline-block;
                    width: 44px;
                    height: 24px;
                }

                .switch input {
                    opacity: 0;
                    width: 0;
                    height: 0;
                }

                .slider {
                    position: absolute;
                    cursor: pointer;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background-color: #cbd5e1;
                    transition: .4s;
                    border-radius: 24px;
                }

                .slider:before {
                    position: absolute;
                    content: "";
                    height: 18px;
                    width: 18px;
                    left: 3px;
                    bottom: 3px;
                    background-color: white;
                    transition: .4s;
                    border-radius: 50%;
                }

                input:checked+.slider {
                    background-color: var(--primary-color);
                }

                input:checked+.slider:before {
                    transform: translateX(20px);
                }

                .theme-swatch {
                    width: 32px;
                    height: 32px;
                    border-radius: 50%;
                    cursor: pointer;
                    border: 3px solid transparent;
                    transition: var(--transition);
                    display: inline-block;
                    margin-right: 10px;
                }

                .theme-swatch:hover {
                    transform: scale(1.1);
                }

                .theme-swatch.active {
                    border-color: #1e293b;
                    transform: scale(1.2);
                }

                .dark-mode .theme-swatch.active {
                    border-color: #f8fafc;
                }
            </style>

            <div class="form-group">
                <label style="display: block; margin-bottom: 12px;">Theme Color</label>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <?php
                    $themes = [
                        'Indigo' => '#4F46E5',
                        'Emerald' => '#10B981',
                        'Rose' => '#F43F5E',
                        'Amber' => '#F59E0B',
                        'Violet' => '#8B5CF6',
                        'Blue' => '#3B82F6',
                        'Cyan' => '#06B6D4'
                    ];
                    foreach ($themes as $name => $color): ?>
                        <div class="theme-swatch <?php echo ($user_data['theme_color'] ?? '#4F46E5') == $color ? 'active' : ''; ?>"
                            style="background-color: <?php echo $color; ?>;"
                            onclick="selectTheme('<?php echo $color; ?>', this)" title="<?php echo $name; ?>"></div>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="theme_color" id="selected_theme_color"
                    value="<?php echo htmlspecialchars($user_data['theme_color'] ?? '#4F46E5'); ?>">
            </div>


            <?php if ($role == 'student'): ?>
                <!-- Cover Gradient Selection -->
                <div class="form-group" style="margin-top: 30px;">
                    <label style="display: block; margin-bottom: 15px;">Dashboard Cover Gradient</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <?php
                        $gradients = [
                            'Default' => 'linear-gradient(135deg, #4F46E5 0%, #8B5CF6 100%)',
                            'Ocean' => 'linear-gradient(135deg, #06B6D4 0%, #3B82F6 100%)',
                            'Sunset' => 'linear-gradient(135deg, #F43F5E 0%, #F59E0B 100%)',
                            'Emerald' => 'linear-gradient(135deg, #10B981 0%, #059669 100%)',
                            'Midnight' => 'linear-gradient(135deg, #1E293B 0%, #334155 100%)',
                            'Royal' => 'linear-gradient(135deg, #6366F1 0%, #A855F7 100%)'
                        ];
                        foreach ($gradients as $name => $grad): ?>
                            <div class="gradient-preset <?php echo ($user_data['cover_gradient'] ?? '') == $grad ? 'active' : ''; ?>"
                                style="background: <?php echo $grad; ?>;"
                                onclick="selectGradient('<?php echo $grad; ?>', this)">
                                <span><?php echo $name; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" id="selected_cover_gradient"
                        value="<?php echo htmlspecialchars($user_data['cover_gradient'] ?? ''); ?>">
                </div>
            <?php endif; ?>

            <style>
                .gradient-preset {
                    height: 50px;
                    border-radius: 10px;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: white;
                    font-size: 0.8rem;
                    font-weight: 700;
                    border: 2px solid transparent;
                    transition: var(--transition);
                }

                .gradient-preset:hover {
                    transform: translateY(-2px);
                    opacity: 0.9;
                }

                .gradient-preset.active {
                    border-color: #1e293b;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                }

                .dark-mode .gradient-preset.active {
                    border-color: white;
                }
            </style>

            <script>
                function showSyncStatus() {
                    const indicator = document.getElementById('syncIndicator');
                    indicator.style.opacity = '1';
                    setTimeout(() => { indicator.style.opacity = '0'; }, 2000);
                }

                function saveAppearance() {
                    const isDark = document.getElementById('darkModeToggle').checked ? 1 : 0;
                    const color = document.getElementById('selected_theme_color').value;

                    const formData = new FormData();
                    formData.append('action', 'save_appearance');
                    formData.append('dark_mode', isDark);
                    formData.append('theme_color', color);

                    fetch('../includes/ajax_handler.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) showSyncStatus();
                        })
                        .catch(err => console.error('Sync failed:', err));
                }

                function previewDarkMode(isDark) {
                    if (isDark) {
                        document.documentElement.classList.add('dark-mode');
                    } else {
                        document.documentElement.classList.remove('dark-mode');
                    }
                    saveAppearance();
                }

                function selectTheme(color, el) {
                    document.getElementById('selected_theme_color').value = color;
                    document.querySelectorAll('.theme-swatch').forEach(s => s.classList.remove('active'));
                    el.classList.add('active');

                    // Instant preview of theme color
                    document.documentElement.style.setProperty('--primary-color', color);

                    // Simple hover color calculation
                    let r = parseInt(color.slice(1, 3), 16);
                    let g = parseInt(color.slice(3, 5), 16);
                    let b = parseInt(color.slice(5, 7), 16);
                    let hover = `rgb(${Math.max(0, r - 30)}, ${Math.max(0, g - 30)}, ${Math.max(0, b - 30)})`;
                    document.documentElement.style.setProperty('--primary-hover', hover);

                    saveAppearance();
                }

                function selectGradient(grad, el) {
                    document.getElementById('selected_cover_gradient').value = grad;
                    document.querySelectorAll('.gradient-preset').forEach(s => s.classList.remove('active'));
                    el.classList.add('active');

                    const formData = new FormData();
                    formData.append('action', 'save_cover_gradient');
                    formData.append('cover_gradient', grad);

                    fetch('../includes/ajax_handler.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => { if (data.success) showSyncStatus(); })
                        .catch(err => console.error(err));
                }
            </script>

            <button type="submit" name="update_appearance" class="btn btn-primary"
                style="margin-top: 10px; display: none;">Save
                Appearance Settings</button>
        </form>

        <hr style="margin: 40px 0; border: 0; border-top: 2px dashed var(--border-color);">

        <form action="" method="POST">
            <h4>Security Settings</h4>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 20px;">Update your password to stay
                secure.</p>
            <div class="form-group">
                <label>Current Password</label>
                <input type="password" name="current_password" class="form-control" required>
            </div>
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" class="form-control" required>
            </div>

            <button type="submit" name="update_password" class="btn"
                style="background: #1e293b; color: white; margin-top: 10px;">Change Password</button>
        </form>
    </div>
</div>
</div>
</div>

<?php include '../includes/footer.php'; ?>