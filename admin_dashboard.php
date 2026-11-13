<?php
session_start();
include 'db.php';

// Auth & Role check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch user data to verify they are actually an admin
$admin = $db->prepare("SELECT full_name, role FROM users WHERE id = :id");
$admin->bindValue(':id', $_SESSION['user_id'], SQLITE3_INTEGER);
$admin_data = $admin->execute()->fetchArray(SQLITE3_ASSOC);
$admin_name = $admin_data['full_name'] ?? "Admin User";
$admin_role = "Admin";

// --- DASHBOARD KPI QUERIES ---
// 1. Count pending approval requests
$pending_count = $db->querySingle("SELECT COUNT(*) FROM users WHERE status = 'pending'") ?: 0;

// 2. Count active normal users
$active_users = $db->querySingle("SELECT COUNT(*) FROM users WHERE role = 'user' AND status = 'active'") ?: 0;

// 3. Count total administrators
$total_admins = $db->querySingle("SELECT COUNT(*) FROM users WHERE role = 'admin' AND status = 'active'") ?: 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>

* { box-sizing: border-box; }
body { height:100vh; overflow: hidden; }
body {
    font-family: 'Inter', sans-serif;
    background: linear-gradient(400deg, #FFFFFF 0%, #a9c1f5 130%);
    margin: 0;
    color: #005BAC; ;
}


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

/* ================= PROFILE BOX ================= */
.profile-wrapper {
    position: relative; 
    cursor: pointer;
    display: flex;
    align-items: center;
}

.profile-box {
    display: flex;
    align-items: center;
    gap: 10px;
    position: relative;
    font-size: 12px;
    padding: 8px 14px;
    border-radius: 16px;
    background: rgba(255,255,255,0.55);
    backdrop-filter: blur(18px) saturate(140%);
    border: 1px solid rgba(255,255,255,0.5);
    font-weight: 500;
    transition: all 0.2s ease;
    box-shadow: 0 8px 20px rgba(0,0,0,0.05);
    cursor: pointer;
}

.profile-box:hover {
    background: rgba(59, 131, 246, 1);
    box-shadow: 0 4px 15px rgba(59, 131, 246, 0.6);
    color: #fff;
}

.profile-box:active {
    background: rgba(59, 131, 246, 0.45);
    transform: scale(0.98); 
}

.profile-box:focus {
    outline: none;
    background: rgba(59, 131, 246, 0.3);
}

.avatar {
    width: 34px; height: 34px;
    border-radius: 50%;
    background: #3b83f6;
    color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 12px;
}

/* ==========================================
   PROFILE DROPDOWN MENU
   ========================================== */
.profile-wrapper .dropdown-menu {
    position: absolute !important;
    top: calc(100% + 8px) !important; 
    right: 0 !important;
    left: auto !important; 
    width: 200px !important;
    background: #ffffff !important;
    border-radius: 12px !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1) !important;
    padding: 8px !important; 
    z-index: 10000 !important;
    
    /* THIS IS THE FIX: Forces items to stack top-to-bottom */
    flex-direction: column !important; 
}

/* The List Items (Profile, Settings, Sign Out) */
.profile-wrapper .dropdown-menu a {
    display: flex !important;
    align-items: center !important;
    gap: 12px !important; 
    padding: 10px 16px !important;
    text-decoration: none !important;
    color: #475569 !important; 
    font-size: 13px !important;
    font-weight: 500 !important;
    border-radius: 8px !important; 
    margin-bottom: 4px !important; 
    margin-top: 0 !important; 
    background-color: transparent !important;
    transition: all 0.2s ease !important;
}

.profile-wrapper .dropdown-menu a:last-child {
    margin-bottom: 0 !important; 
}

/* Fix Icon Alignment */
.profile-wrapper .dropdown-menu a i {
    width: 16px !important; 
    text-align: center !important;
    margin-right: 0 !important; 
}

/* Normal Hover State (Floating Light Blue Box) */
.profile-wrapper .dropdown-menu a:hover:not(.logout) {
    background-color: #eff6ff !important; 
    color: #2563eb !important; 
}

/* Sign Out Specific Styling */
.profile-wrapper .dropdown-menu .logout {
    color: #ef4444 !important; 
}

/* Sign Out Hover State (Floating Light Red Box) */
.profile-wrapper .dropdown-menu .logout:hover {
    background-color: #fef2f2 !important; 
    color: #dc2626 !important; 
}

body.sidebar-collapsed .sidebar {
    width: 80px !important;
}
  
body.sidebar-collapsed .sidebar a {
    justify-content: center;
    gap: 0;
}

body.sidebar-collapsed .sidebar .text {
    display: none !important ;
}

body.sidebar-collapsed .logo {
    display: none ;
}

body.sidebar-collapsed .profile-name {
    display: none ;
}

body.sidebar-collapsed .profile-arrow {
    display: none ;
}

/* Snaps the dashboard flush with the mini sidebar when collapsed */
body.sidebar-collapsed .main-wrapper {
    margin-left: 100px; /* 80px sidebar + 20px gap */
    width: calc(100% - 100px);
    transition: margin-left 0.3s ease, width 0.3s ease;
}

.app-container {
    display: flex;
}
/* Fix the top header spacing when collapsed */
body.sidebar-collapsed .top-header {
    padding: 25px 25px 20px 20px; 
}

/* Keep the welcome text on one line */
body.sidebar-collapsed .welcome-text {
    font-size: 20px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

</style>
</head>
<body>
    <div class="app-container">
    <div class="sidebar">
    <img src="BR_logo.png" class="logo">

    <!-- MENU SECTION -->
    <div class="sidebar-label">MENU</div>
    <a href="admin_dashboard.php" class="active">
    <i class="fa fa-home"></i>
    <span class="text">Overview</span>
</a>

    <a href="admin_requests.php">
    <i class="fa fa-users"></i>
    <span class="text">Requests</span>
</a>

    <a href="status_history.php">
    <i class="fa fa-history"></i>
    <span class="text">Status History</span>
</a>

     <!-- SIGN OUT -->
        <div class="signout" onclick="window.location.href='logout.php'">
            <i class="fa fa-right-from-bracket"></i>
            <span class="text">Sign Out</span>
        </div>
    </div>


   <div class="main-wrapper">
    <div class="top-header">
        <div class="header-left">
            <h2 class="welcome-text">My Admin Dashboard</h2>
             <p class="welcome-sub">Welcome back, have a great day!</p>
        </div>
        
        <div class="header-right">
            <div class="profile-wrapper" onclick="toggleDropdown(event)">
                <div class="profile-box">
                        <!-- Circular Avatar -->
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($admin_name ?? 'User'); ?>&background=1e3a8a&color=fff" class="avatar">
                        
                        <!-- Stacked Text Wrapper -->
                        <div style="display: flex; flex-direction: column; text-align: left; line-height: 1.2;">
                            <!-- Full Name -->
                            <span class="profile-name" style="font-size: 13px; font-weight: 700; text-transform: uppercase; white-space: nowrap;">
                                <?php echo htmlspecialchars($admin_name ?? 'User'); ?>
                            </span>
                            <!-- Role text underneath (Changed to dark blue) -->
                            <span class="profile-role" style="font-size: 10px; color: #1e3a8a; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;">
                                <?php echo htmlspecialchars($admin_role ?? 'Admin'); ?>
                            </span>
                        </div>
                        
                        <i class="fa fa-chevron-down profile-arrow" style="margin-left: 8px;"></i>
                    </div>
                <div class="dropdown-menu" id="dropdownMenu">
                    <a href="profile.php"><i class="fa fa-user"></i> Profile</a>
                    <a href="settings.php"><i class="fa fa-gear"></i> Settings</a>
                    <a href="logout.php" class="logout"><i class="fa fa-right-from-bracket"></i> Sign out</a>
                </div>
            </div>
        </div>
    </div> <!-- End of top-header -->

    <!-- ================= DASHBOARD CONTENT ================= -->
    <div class="row mt-4">
        <!-- Card 1: Pending Requests -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0 h-100" style="border-radius: 16px; background: linear-gradient(135deg, #fff3cd 0%, #ffe69c 100%); color: #856404;">
                <div class="card-body p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase fw-bold mb-1" style="font-size: 12px; letter-spacing: 0.5px;">Pending Requests</h6>
                        <h2 class="mb-0 fw-bolder display-5"><?php echo $pending_count ?? 0; ?></h2>
                    </div>
                    <div style="background: rgba(255,255,255,0.4); padding: 15px; border-radius: 50%;">
                        <i class="fa fa-user-clock fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Card 2: Active Users -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0 h-100" style="border-radius: 16px; background: linear-gradient(135deg, #d1e7dd 0%, #a3cfbb 100%); color: #0f5132;">
                <div class="card-body p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase fw-bold mb-1" style="font-size: 12px; letter-spacing: 0.5px;">Active Staff</h6>
                        <h2 class="mb-0 fw-bolder display-5"><?php echo $active_users ?? 0; ?></h2>
                    </div>
                    <div style="background: rgba(255,255,255,0.4); padding: 15px; border-radius: 50%;">
                        <i class="fa fa-users fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: Total Admins -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0 h-100" style="border-radius: 16px; background: linear-gradient(135deg, #cfe2ff 0%, #9ec5fe 100%); color: #084298;">
                <div class="card-body p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase fw-bold mb-1" style="font-size: 12px; letter-spacing: 0.5px;">Administrators</h6>
                        <h2 class="mb-0 fw-bolder display-5"><?php echo $total_admins ?? 0; ?></h2>
                    </div>
                    <div style="background: rgba(255,255,255,0.4); padding: 15px; border-radius: 50%;">
                        <i class="fa fa-user-shield fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Action Notice -->
    <div class="card shadow-sm border-0 mt-1" style="border-radius: 16px; background: rgba(255,255,255,0.7); backdrop-filter: blur(10px);">
        <div class="card-body p-4 d-flex align-items-center gap-3">
            <div style="background: #e0e7ff; color: #4338ca; padding: 12px 15px; border-radius: 12px;">
                <i class="fa fa-bell fa-lg"></i>
            </div>
            <div>
                <h6 class="fw-bold mb-1 text-dark">Action Required</h6>
                <p class="text-muted mb-0" style="font-size: 14px;">
                    There are currently <strong><?php echo $pending_count ?? 0; ?></strong> staff accounts awaiting administrator verification. 
                    <a href="admin_requests.php" class="fw-bold text-primary text-decoration-none">Review Requests <i class="fa fa-arrow-right ms-1"></i></a>
                </p>
            </div>
        </div>
    </div>
    <!-- ================= END DASHBOARD CONTENT ================= -->

   </div> <!-- End of main-wrapper -->
</div> <!-- End of app-container -->

<script src="js/admin_dashboard.js"></script>
</body>
</html>