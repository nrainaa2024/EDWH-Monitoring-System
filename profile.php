<?php
session_start();
include 'db.php';

// Security check: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch user data from the database
$stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
$stmt->bindValue(':id', $_SESSION['user_id'], SQLITE3_INTEGER);
$result = $stmt->execute();
$user_data = $result->fetchArray(SQLITE3_ASSOC);

$user_name = $user_data['full_name'] ?? "User";
$staff_id  = $user_data['staff_id'] ?? "";
$email     = $user_data['email'] ?? "";
$role      = $user_data['role'] ?? "user";

$success_msg = "";

// ==========================================
// 1. HANDLE AVATAR UPLOAD & REMOVAL
// ==========================================
$upload_dir = 'uploads/avatars/';
if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }

// Determine current avatar URL
$avatar_url = "https://ui-avatars.com/api/?name=" . urlencode($user_name) . "&background=2A4E9E&color=fff";
$allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];

foreach ($allowed_exts as $ext) {
    if (file_exists($upload_dir . "avatar_" . $_SESSION['user_id'] . "." . $ext)) {
        $avatar_url = $upload_dir . "avatar_" . $_SESSION['user_id'] . "." . $ext . "?v=" . time();
        break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Process Image Upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['profile_image']['tmp_name'];
        $file_ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        
        if (in_array($file_ext, $allowed_exts) && $_FILES['profile_image']['size'] <= 10485760) {
            // Delete old avatars first
            foreach ($allowed_exts as $old_ext) {
                $old_file = $upload_dir . "avatar_" . $_SESSION['user_id'] . "." . $old_ext;
                if (file_exists($old_file)) { unlink($old_file); }
            }
            
            $new_filename = "avatar_" . $_SESSION['user_id'] . "." . $file_ext;
            if (move_uploaded_file($file_tmp, $upload_dir . $new_filename)) {
                $success_msg = "Profile picture updated!";
                $avatar_url = $upload_dir . $new_filename . "?v=" . time();
            }
        } else {
            $success_msg = "Invalid file type or size exceeds 10MB.";
        }
    }

    // Process Image Removal
    if (isset($_POST['remove_image'])) {
        foreach ($allowed_exts as $old_ext) {
            $old_file = $upload_dir . "avatar_" . $_SESSION['user_id'] . "." . $old_ext;
            if (file_exists($old_file)) { unlink($old_file); }
        }
        $success_msg = "Profile picture removed!";
        $avatar_url = "https://ui-avatars.com/api/?name=" . urlencode($user_name) . "&background=2A4E9E&color=fff";
    }

    // ==========================================
    // 2. HANDLE MAIN PROFILE UPDATE
    // ==========================================
    if (isset($_POST['update_profile']) && isset($_POST['full_name'])) {
        $new_name  = $_POST['full_name'];
        $new_email = $_POST['email'];
        
        $update = $db->prepare("UPDATE users SET full_name = :name, email = :email WHERE id = :id");
        $update->bindValue(':name', $new_name, SQLITE3_TEXT);
        $update->bindValue(':email', $new_email, SQLITE3_TEXT);
        $update->bindValue(':id', $_SESSION['user_id'], SQLITE3_INTEGER);
        
        if ($update->execute()) {
            $success_msg = "Profile updated successfully!";
            $user_name = $new_name;
            $email = $new_email;
        }
    }

    // ==========================================
    // 3. HANDLE EMAIL MODAL UPDATE
    // ==========================================
    if (isset($_POST['update_email_action'])) {
        $new_email = trim($_POST['new_email']);
        $update = $db->prepare("UPDATE users SET email = :email WHERE id = :id");
        $update->bindValue(':email', $new_email, SQLITE3_TEXT);
        $update->bindValue(':id', $_SESSION['user_id'], SQLITE3_INTEGER);
        
        if ($update->execute()) {
            $success_msg = "Email address updated successfully!";
            $email = $new_email;
        }
    }

    // ==========================================
    // 4. HANDLE PASSWORD MODAL UPDATE
    // ==========================================
    if (isset($_POST['update_password_action'])) {
        $current = $_POST['current_password'];
        $new_pw = $_POST['new_password'];
        $confirm_pw = $_POST['confirm_password'];
        
        if ($new_pw === $confirm_pw) {
            $update = $db->prepare("UPDATE users SET password = :password WHERE id = :id");
            $update->bindValue(':password', $new_pw, SQLITE3_TEXT);
            $update->bindValue(':id', $_SESSION['user_id'], SQLITE3_INTEGER);
            
            if ($update->execute()) {
                $success_msg = "Password changed successfully!";
            }
        } else {
            $success_msg = "New passwords do not match!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Account Profile - ETL Monitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="theme.css">
    
<style>
* { box-sizing: border-box; }
/* Lock body scrolling completely */
body { height:100vh; overflow: hidden; margin: 0; font-family: 'Inter', sans-serif; background: linear-gradient(400deg, #FFFFFF 0%, #a9c1f5 130%); color: #005BAC; }

/* ================= SIDEBAR ================= */
.sidebar {
    position: fixed;
    top: 15px;
    left: 20px;
    width: 200px;
    height: calc(100vh - 30px);
    background: rgba(255,255,255,0.35);
    backdrop-filter: blur(14px) saturate(140%);
    border: 1px solid rgba(255,255,255,0.45);
    border-radius: 24px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.06), inset 0 1px 0 rgba(255,255,255,0.35);
    padding: 20px;
    display: flex;
    flex-direction: column;
    z-index: 100;
}

.sidebar img.logo {
    width: 120px;
    margin: 0 auto 20px auto;
    display: block;
    filter: brightness(1.2) drop-shadow(0 0 6px rgba(255,255,255,0.3));
}

/* New class for the structural labels */
.sidebar-label {
    font-size: 11px;
    font-weight: 700;
    color: #94a3b8; 
    margin: 15px 0 10px 16px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.sidebar a, .sidebar .signout {
    display: flex;
    align-items: center;
    gap: 12px; 
    padding: 12px 16px;
    border-radius: 16px;
    margin-bottom: 5px; /* Slightly reduced to fit more items comfortably */
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    color: #475569; 
    background: transparent; 
    cursor: pointer;
    transition: all 0.2s ease;
}

.sidebar .signout {
    margin-top: auto;
    border: none;
    width: 100%;
    text-align: left;
}

.sidebar a:hover, .sidebar .signout:hover,
.sidebar a:active, .sidebar .signout:active,
.sidebar a.active {
    background: #3b83f6 !important; 
    color: #ffffff !important; 
    box-shadow: 0 4px 15px rgba(59, 131, 246, 0.4) !important;
}

.sidebar a:hover i, .sidebar .signout:hover i,
.sidebar a:active i, .sidebar .signout:active i,
.sidebar a.active i {
    color: #ffffff !important;
}

.sidebar a:focus-visible, .sidebar .signout:focus-visible {
    outline: none;
    background: rgba(59, 131, 246, 0.3);
}

/* Make main wrapper a rigid flex column */
.main-wrapper { 
    margin-left: 260px; 
    width: calc(100% - 260px); 
    height: 100vh; 
    padding: 15px 40px; 
    display: flex; 
    flex-direction: column; 
    overflow: hidden; 
}

.top-header { 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    width: 100%; 
    max-width: none; /* Removes the center restriction */
    margin: 0 0 15px 0; /* Aligns to the far left of the main wrapper */
    flex-shrink: 0; 
}

.welcome-text { margin: 0; font-size: 25px; font-weight: 700; color: #111827; }
.welcome-sub { margin: 4px 0 0; font-size: 14px; color: #64748b; }

/* ================= 3-CARD GRID LAYOUT ================= */
.profile-layout {
    width: 100%;
    max-width: 1100px;
    display: grid;
    grid-template-columns: 290px 1fr; /* Slightly narrower left column */
    gap: 20px;
    margin: 0;
    flex: 1; /* Forces the grid to strictly fill available height */
    min-height: 0; /* Prevents flexbox from overflowing the screen */
}

/* Right Column Wrapper */
.profile-column-right {
    display: flex;
    flex-direction: column;
    gap: 15px;
    height: 100%;
}

/* Glassmorphism Card Style */
.profile-card {
    background: rgba(255,255,255,0.55);
    backdrop-filter: blur(18px) saturate(140%);
    border: 1px solid rgba(255,255,255,0.5);
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.06);
    padding: 20px; /* Reduced from 25px to save vertical space */
    display: flex;
    flex-direction: column;
}

.card-title { 
    margin: 0 0 12px 0; 
    font-size: 15px; 
    font-weight: 700; 
    color: #0f172a; 
    display: flex; 
    align-items: center; 
    gap: 8px; 
}

/* --- Left Card Specifics --- */
.profile-card-left {
    align-items: center;
    text-align: center;
    height: 100%; /* Matches right column height */
}

.large-avatar-left {
    width: 110px; /* Slightly smaller */
    height: 110px;
    border-radius: 50%;
    margin-bottom: 12px;
    background-color: #3b83f6;
    box-shadow: 0 8px 20px rgba(59, 131, 246, 0.2);
    overflow: hidden;
}

.profile-name-left { 
    font-size: 16px; /* Reduced from 20px to prevent awkward wrapping */
    font-weight: 700; 
    color: #0f172a; 
    margin: 0 0 4px 0; 
    line-height: 1.3;
}
.profile-role-left { font-size: 13px; color: #64748b; margin: 0; }

/* Wrapper to cleanly push the left card buttons to the bottom */
.left-card-bottom {
    margin-top: auto;
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.btn-group-left { display: flex; gap: 8px; justify-content: center; width: 100%; }
.picture-hint { margin: 12px 0 0 0; font-size: 11px; color: #94a3b8; line-height: 1.4; padding: 0 10px; }

/* --- Form Fields & Buttons --- */
.form-grid { 
    display: grid; 
    grid-template-columns: 1fr 1fr; 
    gap: 12px; 
    margin-bottom: 12px; 
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 4px; 
}

.form-group label {
    font-size: 13px;
    font-weight: 600;
    color: #0f172a;
}

.form-control-custom {
    height: 36px; /* Shaved 2 pixels off height to save space */
    padding: 0 14px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    font-family: 'Inter', sans-serif;
    font-size: 13px; 
    color: #475569;
    background-color: #ffffff;
    width: 100%;
}

.form-control-custom:focus {
    outline: none;
    border-color: #3b83f6;
    box-shadow: 0 0 0 3px rgba(59, 131, 246, 0.1);
}

.form-control-custom:disabled, .form-control-custom[readonly] {
    background-color: #f8fafc;
    color: #94a3b8;
    cursor: not-allowed;
}

.input-hint { font-size: 11px; color: #94a3b8; margin-top: 2px; }

/* --- Action Buttons --- */
.btn-upload, .btn-save {
    background-color: #3b83f6; color: #ffffff; border: none; padding: 8px 16px; border-radius: 50px; font-size: 13px; font-weight: 600; cursor: pointer; transition: 0.2s ease;
}
.btn-upload:hover, .btn-save:hover { background-color: #2563eb; }

.btn-remove, .btn-cancel {
    background-color: #ffffff; color: #475569; border: 1px solid #e2e8f0; padding: 8px 16px; border-radius: 50px; font-size: 13px; font-weight: 600; cursor: pointer; transition: 0.2s ease;
}
.btn-remove:hover, .btn-cancel:hover { background-color: #f1f5f9; color: #0f172a; }

.account-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: auto; /* Pushes buttons to the bottom of the right column */
    padding-bottom: 5px; /* Ensures buttons don't scrape the absolute bottom edge */
}

/* ================= MODAL STYLES ================= */
.modal-overlay {
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(15, 23, 42, 0.4);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 99999;
    opacity: 0;
    pointer-events: none;
    transition: all 0.3s ease;
}

.modal-overlay.active {
    opacity: 1;
    pointer-events: auto;
}

.modal-box {
    background: #ffffff;
    border-radius: 20px;
    width: 100%;
    max-width: 450px;
    padding: 30px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    transform: translateY(20px);
    transition: all 0.3s ease;
}

.modal-overlay.active .modal-box {
    transform: translateY(0);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.modal-header h4 { margin: 0; font-size: 18px; font-weight: 700; color: #0f172a; }
.close-modal { background: none; border: none; font-size: 20px; color: #94a3b8; cursor: pointer; transition: 0.2s; }
.close-modal:hover { color: #ef4444; }

.alert-success {
    background-color: #dcfce7;
    color: #166534;
    padding: 12px 24px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 600;
    border: 1px solid #bbf7d0;
    box-shadow: 0 10px 30px rgba(22, 101, 52, 0.15);
    
    /* Detach it from the document flow so it NEVER pushes buttons down */
    position: fixed;
    top: 30px;
    right: 40px;
    z-index: 99999;
    animation: slideInFadeOut 4s forwards;
}

/* 2. Animation to slide in, stay for a few seconds, and fade away */
@keyframes slideInFadeOut {
    0% { transform: translateY(-20px); opacity: 0; }
    10% { transform: translateY(0); opacity: 1; }
    80% { transform: translateY(0); opacity: 1; }
    100% { transform: translateY(-20px); opacity: 0; visibility: hidden; }
}

</style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <img src="BR_logo.png" class="logo" alt="Logo">

        <!-- MENU SECTION -->
        <div class="sidebar-label">MENU</div>
        <a href="user_dashboard.php">
            <i class="fa-solid fa-border-all"></i>
            <span class="text">Dashboard</span>
        </a>
        <a href="history_report.php" >
            <i class="fa-solid fa-clock-rotate-left"></i>
            <span class="text">Report</span>
        </a>
        <a href="#">
            <i class="fa-solid fa-chart-simple"></i>
            <span class="text">Analytic</span>
        </a>
        <a href="tablespace.php">
            <i class="fa-solid fa-database"></i>
            <span class="text">Tablespace</span>
        </a>

        <!-- OTHERS SECTION -->
        <div class="sidebar-label">OTHERS</div>
        <a href="profile.php" class="active">
            <i class="fa-regular fa-user"></i>
            <span class="text">Account</span>
        </a>
        <a href="settings.php">
            <i class="fa-solid fa-gear"></i>
            <span class="text">Settings</span>
        </a>

        <!-- SIGN OUT -->
        <div class="signout" onclick="window.location.href='logout.php'">
            <i class="fa fa-right-from-bracket"></i>
            <span class="text">Sign Out</span>
        </div>
    </div>

    <!-- Main Wrapper -->
    <div class="main-wrapper">
        
        <!-- Header -->
        <div class="top-header">
            <div>
                <h2 class="welcome-text">My Profile</h2>
                <p class="welcome-sub">Manage your personal information and security</p>
            </div>
        </div>

           <?php if($success_msg): ?>
            <div class="alert-success">
                <i class="fa-solid fa-circle-check"></i> <?php echo $success_msg; ?>
            </div>
                 <?php endif; ?>
               <!-- Convert the form itself into the Grid Layout Container -->
              <form method="POST" action="profile.php" enctype="multipart/form-data" class="profile-layout">
                    
                <!-- CARD 1: Left Profile Picture -->
            <div class="profile-card profile-card-left">
                <div class="large-avatar-left">
                    <img src="<?php echo $avatar_url; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                <h3 class="profile-name-left"><?php echo htmlspecialchars($user_name); ?></h3>
                <p class="profile-role-left">Staff ID: <?php echo htmlspecialchars($staff_id); ?></p>

                <!-- Hidden file input -->
                <input type="file" id="imageUploadInput" name="profile_image" accept=".png, .jpg, .jpeg, .gif" style="display: none;" onchange="document.getElementById('saveProfileBtn').click();">

                <!-- New Wrapper pushes these to the bottom -->
                <div class="left-card-bottom">
                    <div class="btn-group-left">
                        <button type="button" class="btn-upload" onclick="document.getElementById('imageUploadInput').click();">
                            <i class="fa-solid fa-cloud-arrow-up"></i> Upload Image
                        </button>
                        <button type="submit" name="remove_image" class="btn-remove">Remove</button>
                    </div>
                    <p class="picture-hint">We support PNGs, JPEGs and GIFs under 10MB</p>
                </div>
            </div>

                    <!-- RIGHT COLUMN: Stacked Cards & Buttons -->
            <div class="profile-column-right">
                
                <!-- CARD 2: Personal Information -->
                <div class="profile-card">
                    <h4 class="card-title"><i class="fa-solid fa-address-card" style="color: #64748b;"></i> Personal Information</h4>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="full_name" class="form-control-custom" value="<?php echo htmlspecialchars($user_name); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Staff ID</label>
                            <input type="text" class="form-control-custom" value="<?php echo htmlspecialchars($staff_id); ?>" disabled>
                            <span class="input-hint">Staff ID cannot be changed.</span>
                        </div>
                    </div>

                    <div class="form-group">
                    <label>Email Address</label>
                    <div style="display: flex; gap: 12px; align-items: center;">
                        <!-- Added the "readonly" attribute to lock the input box -->
                        <input type="email" name="email" class="form-control-custom" style="flex: 1;" value="<?php echo htmlspecialchars($email); ?>" readonly>
                        <button type="button" class="btn-remove" style="border-radius: 12px; height: 38px;" onclick="openModal('emailModal')">Edit Email</button>
                    </div>
                    <span class="input-hint">Used to log in to your account.</span>
                </div>
                </div>

                  <!-- CARD 3: Account Security / Password -->
                <div class="profile-card">
                    <h4 class="card-title"><i class="fa-solid fa-shield-halved" style="color: #64748b;"></i> Account Security</h4>

                    <div class="form-group">
                        <label>Password</label>
                        <div style="display: flex; gap: 12px; align-items: center;">
                            <input type="password" class="form-control-custom" style="flex: 1;" value="••••••••••••" readonly>
                            <button type="button" class="btn-remove" style="border-radius: 12px; height: 38px;" onclick="openModal('passwordModal')">Change Password</button>
                        </div>
                        <span class="input-hint">Log in with your password instead of using temporary login codes</span>
                    </div>
                </div>

                    <!-- Action Buttons: Placed under Card 3 on the right side -->
                <div class="account-footer">
                    <button type="button" class="btn-cancel" onclick="window.location.href='user_dashboard.php'">Cancel</button>
                    <button type="submit" name="update_profile" id="saveProfileBtn" class="btn-save">Save Changes</button>
                </div>

                  </div> <!-- End Right Column -->
                </form>
            </div>
        </div>

    </div>
    
<!-- EMAIL MODAL -->
<div class="modal-overlay" id="emailModal">
    <div class="modal-box">
        <div class="modal-header">
            <h4>Update Email Address</h4>
            <button type="button" class="close-modal" onclick="closeModal('emailModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="profile.php">
            <div class="form-group" style="margin-bottom: 20px;">
                <label>New Email Address</label>
                <input type="email" name="new_email" class="form-control-custom" placeholder="Enter new email..." required>
            </div>
            <div class="account-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('emailModal')">Cancel</button>
                <button type="submit" name="update_email_action" class="btn-save">Update Email</button>
            </div>
        </form>
    </div>
</div>

<!-- PASSWORD MODAL -->
<div class="modal-overlay" id="passwordModal">
    <div class="modal-box">
        <div class="modal-header">
            <h4>Change Password</h4>
            <button type="button" class="close-modal" onclick="closeModal('passwordModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="profile.php">
            <div class="form-group" style="margin-bottom: 15px;">
                <label>Current Password</label>
                <input type="password" name="current_password" class="form-control-custom" required>
            </div>
            <div class="form-group" style="margin-bottom: 15px;">
                <label>New Password</label>
                <input type="password" name="new_password" class="form-control-custom" required>
            </div>
            <div class="form-group" style="margin-bottom: 25px;">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control-custom" required>
            </div>
            <div class="account-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('passwordModal')">Cancel</button>
                <button type="submit" name="update_password_action" class="btn-save">Save Password</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal(modalId) { document.getElementById(modalId).classList.add('active'); }
    function closeModal(modalId) { document.getElementById(modalId).classList.remove('active'); }
</script>

    <!-- Instantly check local storage before the page even loads -->
    <script>
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark-mode');
        }
    </script>

</body>
</html>