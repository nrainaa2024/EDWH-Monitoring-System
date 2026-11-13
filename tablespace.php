<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user = $db->prepare("SELECT full_name FROM users WHERE id = :id");
$user->bindValue(':id', $_SESSION['user_id'], SQLITE3_INTEGER);
$user_data = $user->execute()->fetchArray(SQLITE3_ASSOC);
$user_name = $user_data['full_name'] ?? "User";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tablespace Monitoring - System Alerts</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="theme.css">
    
<style>
* { box-sizing: border-box; }
body { height:100vh; overflow: hidden; font-family: 'Inter', sans-serif; background: linear-gradient(400deg, #FFFFFF 0%, #a9c1f5 130%); margin: 0; color: #005BAC; }

/* ================= SIDEBAR ================= */
.sidebar { position: fixed; top: 15px; left: 20px; width: 200px; height: calc(100vh - 30px); background: rgba(255,255,255,0.35); backdrop-filter:  blur(14px) saturate(140%); border: 1px solid rgba(255,255,255,0.45); border-radius: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.06); padding: 20px; display: flex; flex-direction: column; z-index: 100; }
.sidebar img.logo { width: 120px; margin: 0 auto 20px auto; display: block; }
.sidebar-label { font-size: 11px; font-weight: 700; color: #94a3b8; margin: 15px 0 10px 16px; text-transform: uppercase; }
.sidebar a, .sidebar .signout { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 16px; margin-bottom: 5px; text-decoration: none; font-weight: 600; font-size: 14px; color: #475569; transition: 0.2s ease; cursor: pointer; border: none; background: transparent; width: 100%; text-align: left; }
.sidebar .signout { margin-top: auto; }
.sidebar a:hover, .sidebar .signout:hover, .sidebar a.active { background: #3b83f6; color: #ffffff; box-shadow: 0 4px 15px rgba(59, 131, 246, 0.4); }

/* ================= MAIN LAYOUT ================= */
.main-wrapper { flex: 1; margin-left: 240px; height: 100vh; padding: 20px 30px; display: flex; flex-direction: column; overflow: hidden; }
.dashboard-content { display: flex; gap: 25px; flex: 1; min-height: 0; overflow: hidden; }
.main-column { flex: 1; display: flex; flex-direction: column; overflow-y: auto; padding-right: 10px; gap: 25px; }

/* ================= HEADER & NOTIFICATIONS ================= */
.top-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-shrink: 0; }
.welcome-text { margin: 0; font-size: 25px; font-weight: 700; color: #111827; }
.welcome-sub { margin: 4px 0 0; font-size: 14px; color: #64748b; }

.header-right { display: flex; align-items: center; gap: 20px; }
.notif-wrapper { position: relative; cursor: pointer; }
.notif-icon { font-size: 20px; color: #475569; padding: 10px; background: rgba(255,255,255,0.6); border-radius: 50%; border: 1px solid rgba(255,255,255,0.8); transition: 0.2s; }
.notif-icon:hover { background: #ffffff; color: #3b83f6; }
.notif-badge { position: absolute; top: -2px; right: -2px; background: #ef4444; color: white; font-size: 10px; font-weight: 800; padding: 2px 6px; border-radius: 10px; border: 2px solid #eef4f8; display: none; }

.notif-dropdown { position: absolute; top: 45px; right: 0; width: 320px; background: #ffffff; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; padding: 15px; display: none; z-index: 1000; flex-direction: column; max-height: 400px; overflow-y: auto; }
.notif-dropdown.show { display: flex; }

#notifList { display: flex; flex-direction: column; gap: 10px; padding-top: 10px; padding-right: 5px; }
.notif-item { display: flex; flex-direction: column; padding: 12px 15px; border-radius: 8px; background: #f8fafc; border-left: 4px solid transparent; box-shadow: 0 2px 5px rgba(0,0,0,0.02); transition: transform 0.2s, background 0.2s; }
.notif-item:hover { transform: translateX(2px); }
.notif-item.critical { border-left-color: #ff6b81; background: #fef2f2; }
.notif-item.critical:hover { background: #fee2e2; }
.notif-item.warning { border-left-color: #ffaa5e; background: #fffbeb; }
.notif-item.warning:hover { background: #fef3c7; }
.notif-item h5 { margin: 0 0 4px 0; font-size: 13px; font-weight: 700; color: #0f172a; }
.notif-item p { margin: 0; font-size: 12px; color: #475569; line-height: 1.4; }

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

.avatar {
    width: 34px; height: 34px;
    border-radius: 50%;
    background: #3b83f6;
    color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 12px;
}

/* ================= PROFILE DROPDOWN MENU ================= */
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
    flex-direction: column !important; 
    display: none; /* Hidden by default */
}

/* Fix to allow toggling via JS */
.profile-wrapper .dropdown-menu.show {
    display: flex !important;
}

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
    transition: all 0.2s ease !important;
}

.profile-wrapper .dropdown-menu a i {
    width: 16px !important; 
    text-align: center !important;
}

.profile-wrapper .dropdown-menu a:hover:not(.logout) {
    background-color: #eff6ff !important; 
    color: #2563eb !important; 
}

.profile-wrapper .dropdown-menu .logout { color: #ef4444 !important; }
.profile-wrapper .dropdown-menu .logout:hover {
    background-color: #fef2f2 !important; 
    color: #dc2626 !important; 
}

/* ================= TOP 5 CARDS ================= */
.top-5-container { 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    
    /* Starts with your active menu blue (#3b83f6) and deepens to royal blue (#1d4ed8) */
    background: linear-gradient(135deg, #3b83f6 0%, #1d4ed8 100%); 
    
    border-radius: 20px; 
    padding: 25px 20px; 
    border: 1px solid rgba(255, 255, 255, 0.15); 
    
    /* Soft blue glow matching the sidebar shadow */
    box-shadow: 0 10px 25px rgba(59, 131, 246, 0.35); 
    gap: 10px; 
}
.mini-ts-card { display: flex; flex-direction: column; align-items: center; gap: 15px; cursor: pointer; transition: transform 0.2s; flex: 1; }
.mini-ts-card:hover { transform: translateY(-4px); }
.mini-ts-card h4 { margin: 0; font-size: 11px; font-weight: 700; color: rgba(255, 255, 255, 0.95); text-transform: uppercase; letter-spacing: 0.5px; text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; width: 100%; max-width: 90px; }

/* ================= CIRCULAR PROGRESS BARS ================= */
.chart-wrapper { position: relative; width: 70px; height: 70px; flex-shrink: 0; }
.det-chart-wrapper { position: relative; width: 85px; height: 85px; margin: 0 auto 8px auto; }
.chart-svg { transform: rotate(-90deg); width: 100%; height: 100%; }
.circle-bg { fill: none; stroke: rgba(255, 255, 255, 0.15); stroke-width: 3.5; }
.circle-fill { fill: none; stroke-width: 3.5; stroke-linecap: round; animation: fillAnim 1.2s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; }
@keyframes fillAnim { 0% { stroke-dasharray: 0, 100; } }

.chart-text { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 13px; font-weight: 600; color: #ffffff; display: flex; align-items: baseline; }
.chart-text span { font-size: 10px; color: #ffffff; margin-left: 1px; font-weight: 600; }

.det-chart-text { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 15px; font-weight: 600; color: #0f172a; display: flex; align-items: baseline; }
.det-chart-text span { font-size: 15px; color: #64748b; margin-left: 2px; }

/* ================= ALL TABLESPACES LIST ================= */
.list-container { background: rgba(255,255,255,0.5); border-radius: 20px; padding: 20px; border: 1px solid rgba(255,255,255,0.6); }
.list-container h3 { font-size: 16px; font-weight: 700; margin: 0 0 15px 0; color: #0f172a; }
.ts-list { display: flex; flex-direction: column; gap: 8px; }
.ts-list-item { display: flex; align-items: center; justify-content: space-between; padding: 12px 15px; background: rgba(255,255,255,0.6); border-radius: 12px; cursor: pointer; transition: 0.2s; border: 1px solid transparent; }
.ts-list-item:hover, .ts-list-item.active { background: #ffffff; border-color: #bfdbfe; box-shadow: 0 4px 12px rgba(59,131,246,0.1); }
.ts-list-left { display: flex; align-items: center; gap: 12px; }
.list-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 14px; background: #f1f5f9; color: #64748b; }
.ts-list-item.active .list-icon { background: #dbeafe; color: #2563eb; }
.list-name { font-size: 14px; font-weight: 600; color: #1e293b; }
.list-pct { font-size: 13px; font-weight: 700; color: #64748b; }

/* ================= RIGHT SIDEBAR ================= */
.detail-sidebar { 
    width: 300px; 
    background: rgba(255,255,255,0.5);
    backdrop-filter: blur(14px) saturate(140%); 
    border: 1px solid rgba(255,255,255,0.6);
    border-radius: 24px; 
    padding: 12px 18px; 
    box-shadow: 0 10px 30px rgba(0,0,0,0.06), inset 0 1px 0 rgba(255,255,255,0.35); 
    flex-shrink: 0; 
    overflow: hidden; 
    display: flex; 
    flex-direction: column; 
    gap: 8px; 
}

.detail-header { 
    text-align: center; 
    background: transparent; 
    padding: 0 0 10px 0; 
    box-shadow: none; 
    border: none; 
}

.det-chart-wrapper { position: relative; width: 75px; height: 75px; margin: 0 auto 6px auto; }

.det-chart-text { 
    position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); 
    font-size: 15px; 
    font-weight: 800; color: #0f172a; display: flex; align-items: baseline; 
}
.det-chart-text span { font-size: 9px; color: #64748b; margin-left: 2px; }

.detail-title { font-size: 15px; font-weight: 800; color: #0f172a; margin: 0 0 2px 0; word-break: break-all; }
.detail-status { display: inline-block; padding: 3px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; margin-bottom: 4px; }

/* ================= TYPOGRAPHY & GRIDS ================= */
.detail-section { margin-top: 2px; }
.detail-section h4 { font-size: 10px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.8px; margin: 0 0 5px 0; }

.primary-stats {
    background: rgba(255, 255, 255, 0.4);
    border-radius: 12px;
    padding: 10px 15px;
    margin-bottom: 10px;
    border: 1px solid rgba(255, 255, 255, 0.6);
}

.stat-row { 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    padding: 6px 0; 
    border-bottom: 1px dashed #cbd5e1; 
}
.stat-row:last-child { border-bottom: none; }
.stat-label { display: flex; align-items: center; gap: 8px; font-size: 11px; color: #475569; font-weight: 600; }
.stat-val { font-size: 12px; font-weight: 700; color: #0f172a; }

/* 2x2 Grid for Analytics */
.secondary-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}

.grid-stat {
    background: rgba(255, 255, 255, 0.4);
    border: 1px solid rgba(255, 255, 255, 0.6);
    border-radius: 12px;
    padding: 12px 10px;
    text-align: center;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 6px;
    transition: 0.2s;
}

.grid-stat:hover {
    background: #ffffff;
    box-shadow: 0 4px 12px rgba(0,0,0,0.03);
}

.grid-label {
    font-size: 10px;
    color: #64748b;
    font-weight: 700;
    text-transform: uppercase;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.grid-val {
    font-size: 13px;
    font-weight: 800;
    color: #0f172a;
}

/* ================= LIST CONTROLS & FILTERS ================= */
.list-header-controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
.list-header-controls h3 { font-size: 16px; font-weight: 700; margin: 0; color: #0f172a; }
.controls-right { display: flex; align-items: center; gap: 12px; }
.refresh-time { font-size: 11px; font-weight: 600; color: #64748b; }

.btn-refresh { 
    background: #3b83f6; color: white; border: none; padding: 6px 12px; 
    border-radius: 20px !important; font-size: 12px; font-weight: 600; cursor: pointer; 
    display: flex; align-items: center; gap: 6px; transition: 0.2s;
}
.btn-refresh:hover { background: #2563eb; transform: translateY(-1px); box-shadow: 0 4px 10px rgba(59,131,246,0.3); }

.filter-bar { display: flex; gap: 10px; margin-bottom: 15px; }
.filter-bar input, .filter-bar select {
    padding: 8px 12px; border: 1px solid rgba(255,255,255,0.8); border-radius: 20px;
    background: rgba(255,255,255,0.7); font-size: 12px; font-weight: 500; color: #475569;
    outline: none; flex: 1; transition: 0.2s;
}
.filter-bar input:focus, .filter-bar select:focus { background: #ffffff; border-color: #3b83f6; }
.filter-bar select { flex: 0.5; cursor: pointer; }

/* Grid specific fix for the 3-item layout */
.grid-stat.full-width { grid-column: span 2; }

.search-suggestions {
    position: absolute;
    top: 100%;
    left: 0;
    width: 100%;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    z-index: 1000;
    box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
    display: none; /* Hidden by default */
    max-height: 200px;
    overflow-y: auto;
}

.suggestion-item {
    padding: 8px 12px;
    font-size: 12px;
    color: #475569;
    cursor: pointer;
}

.suggestion-item:hover { background: #f1f5f9; color: #0f172a; }

/* ================= CUSTOM SORT DROPDOWN ================= */
.custom-dropdown { 
    position: relative; 
    flex: 0.5; /* Keeps it the same width as your old select */
    font-size: 12px; 
    font-weight: 500; 
    color: #475569; 
    user-select: none;
}

.dropdown-selected {
    padding: 8px 12px; 
    background: #ffffff; 
    border: 1px solid #3b83f6;/* The distinct blue border from your image */
    border-radius: 20px;
    cursor: pointer; 
    display: flex; 
    justify-content: space-between; 
    align-items: center;
    transition: 0.2s;
}

.dropdown-selected:hover {
    box-shadow: 0 2px 8px rgba(59, 131, 246, 0.15);
}

.dropdown-options {
    position: absolute; 
    top: 100%; 
    left: 0; 
    width: 100%; 
    background: #ffffff;
    border: 1px solid #93c5fd;
    border-radius: 12px;
    margin-top: 4px; 
    z-index: 1000;
    display: none; /* Hidden until clicked */
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); 
    padding: 8px; /* Padding inside the box */
    overflow: hidden;
}

.dropdown-option { 
    padding: 8px 12px; 
    cursor: pointer; 
    border-radius: 8px;
    transition: 0.1s; 
}

.dropdown-option:last-child {
    margin-bottom: 0;
}

/* The exact solid blue highlight from your reference image */
.dropdown-option:hover { 
    background: #1d4ed8; 
    color: #ffffff; 
}

/* ================= CUSTOM SCROLLBARS ================= */
::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.notif-dropdown::-webkit-scrollbar { width: 5px; }
.notif-dropdown::-webkit-scrollbar-track { background: transparent; }
.notif-dropdown::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

/* ================= NOTIFICATION DISMISS BUTTON ================= */
.notif-item { 
    position: relative; 
    padding-right: 35px; /* Make room for the X button */
}

.close-alert {
    position: absolute;
    top: 12px;
    right: 12px;
    color: #cbd5e1;
    font-size: 14px;
    cursor: pointer;
    transition: color 0.2s;
}

.close-alert:hover {
    color: #ef4444; /* Turns red on hover */
}

/* ================= DROPDOWN STYLING ================= */
.filter-bar select {
    appearance: auto; /* Ensures the native OS dropdown style is used */
    cursor: pointer;
    background-color: #ffffff;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
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
        <a href="analytic.php" >
            <i class="fa-solid fa-chart-simple"></i>
            <span class="text">Analytic</span>
        </a>
        <a href="tablespace.php" class="active">
            <i class="fa-solid fa-database"></i>
            <span class="text">Tablespace</span>
        </a>

        <!-- OTHERS SECTION -->
        <div class="sidebar-label">OTHERS</div>
        <a href="profile.php">
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
        <div class="top-header">
            <div>
                <h2 class="welcome-text">Storage Capacity</h2>
                <p class="welcome-sub">System Tablespace Monitoring & Analytics</p>
            </div>

            <div class="header-right">
                <!-- Notification Bell -->
                <div class="notif-wrapper" id="notifWrapper">
                    <i class="fa-solid fa-bell notif-icon"></i>
                    <span class="notif-badge" id="notifBadge">0</span>
                    <div class="notif-dropdown" id="notifDropdown">
                        <div style="font-weight: 700; font-size: 14px; padding-bottom: 5px; border-bottom: 1px solid #f1f5f9;">Alerts</div>
                        <div id="notifList">
                            <div style="text-align: center; color: #94a3b8; font-size: 12px; padding: 10px;">No new alerts</div>
                        </div>
                    </div>
                </div>

                <!-- Profile Box -->
                <div class="profile-wrapper" onclick="toggleDropdown(event)">
                    <div class="profile-box">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_name); ?>&background=2A4E9E&color=fff" class="avatar">
                        <span class="profile-name"><?php echo htmlspecialchars($user_name); ?></span>
                        <i class="fa fa-chevron-down profile-arrow"></i>
                    </div>
                    
                    <div class="dropdown-menu" id="dropdownMenu">
                        <a href="profile.php"><i class="fa fa-user"></i> Profile</a>
                        <a href="settings.php"><i class="fa fa-gear"></i> Settings</a>
                        <a href="logout.php" class="logout"><i class="fa fa-right-from-bracket"></i> Sign out</a>
                    </div>
                </div>
            </div>
        </div>

       <!-- Master-Detail Layout -->
        <div class="dashboard-content">
            
            <!-- Left Side: Lists & Summaries -->
            <div class="main-column">
                <div>
                    <h4 style="font-size: 14px; font-weight: 700; color: #64748b; margin: 0 0 10px 0; text-transform: uppercase;">Top 5 Critical Tablespaces</h4>
                    <div class="top-5-container" id="top5Grid">
                        <!-- JS injects top 5 here -->
                    </div>
                </div>

               <div class="list-container">
                    <div class="list-header-controls">
                        <h3>All Tablespaces</h3>
                        <div class="controls-right">
                            <span class="refresh-time" id="lastRefreshTime">Last Refresh: --</span>
                            <button class="btn-refresh" onclick="forceRefresh()">
                                <i class="fa-solid fa-rotate-right"></i> Refresh
                            </button>
                        </div>
                    </div>
                    
                    <!-- Search and Sort Filters -->
                    <div class="filter-bar" style="position: relative;">
                        <input type="text" id="searchInput" placeholder="Search tablespace..." onkeyup="handleSearchInput()">
                        <div id="searchSuggestions" class="search-suggestions"></div>
                        
                       <div class="custom-dropdown" id="customSortDropdown">
                            <div class="dropdown-selected" onclick="toggleSortDropdown()">
                                Highest Usage % (Default) <i class="fa-solid fa-caret-down" style="font-size: 14px; color: #3b83f6;"></i>
                            </div>
                            <div class="dropdown-options" id="sortOptionsList">
                                <div class="dropdown-option" onclick="selectSortOption('usage_desc', 'Highest Usage % (Default)')">Highest Usage % (Default)</div>
                                <div class="dropdown-option" onclick="selectSortOption('free_asc', 'Lowest Free Space (GB)')">Lowest Free Space (GB)</div>
                                <div class="dropdown-option" onclick="selectSortOption('total_desc', 'Most Total Space (GB)')">Most Total Space (GB)</div>
                                <div class="dropdown-option" onclick="selectSortOption('alpha_asc', 'Alphabetical (A-Z)')">Alphabetical (A-Z)</div>
                            </div>
                        </div>
                        
                        <input type="hidden" id="sortFilter" value="usage_desc">
                    </div>

                    <div class="ts-list" id="fullTsList">
                        <div style="text-align: center; color: #94a3b8; padding: 20px;"><i class="fa-solid fa-circle-notch fa-spin"></i> Loading...</div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Details Panel -->
            <div class="detail-sidebar" id="detailSidebar" style="display: none;">
                <h4 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0; text-align: center;">Tablespace Details</h4>
                
                <div class="detail-header">
                    <div id="detChartContainer" class="det-chart-wrapper"></div>
                    <div class="detail-status" id="detStatusBadge">Safe</div>
                    <h3 class="detail-title" id="detTitle">--</h3>
                </div>

                <div class="primary-stats">
                    <div class="stat-row">
                        <div class="stat-label"><i class="fa-solid fa-chart-pie" style="color: #3b82f6;"></i> Total Limit</div>
                        <div class="stat-val" id="detTotal">-- GB</div>
                    </div>
                    <div class="stat-row">
                        <div class="stat-label"><i class="fa-solid fa-server" style="color: #64748b;"></i> Used Storage</div>
                        <div class="stat-val" id="detUsed">-- GB</div>
                    </div>
                    <div class="stat-row">
                        <div class="stat-label"><i class="fa-solid fa-box-open" style="color: #10b981;"></i> Free Space</div>
                        <div class="stat-val" id="detFree">-- GB</div>
                    </div>
                </div>

                <div class="secondary-grid">
                    <div class="grid-stat">
                        <div class="grid-label"><i class="fa-solid fa-arrow-trend-up" style="color: #f59e0b;"></i> Daily Trend</div>
                        <div class="grid-val" id="detAvg">--</div>
                    </div>
                    <div class="grid-stat">
                        <div class="grid-label"><i class="fa-solid fa-bolt" style="color: #ef4444;"></i> Max Growth</div>
                        <div class="grid-val" id="detMax">--</div>
                    </div>
                    <div class="grid-stat full-width">
                        <div class="grid-label"><i class="fa-solid fa-hourglass-half" style="color: #8b5cf6;"></i> Est. Days Left</div>
                        <div class="grid-val" id="detEstDays">--</div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    
    <script src="js/tablespace.js?v=<?php echo time(); ?>"></script>
</body>
</html>