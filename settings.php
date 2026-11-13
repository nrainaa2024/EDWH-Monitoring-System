<?php
include 'security_check.php';
include 'db.php';

// Security check: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch user name for the profile box
$user = $db->prepare("SELECT full_name FROM users WHERE id = :id");
$user->bindValue(':id', $_SESSION['user_id'], SQLITE3_INTEGER);
$user_data = $user->execute()->fetchArray(SQLITE3_ASSOC);
$user_name = $user_data['full_name'] ?? "User";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Settings - ETL Monitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css"/>
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    <link rel="stylesheet" href="theme.css">
    
<style>
* { box-sizing: border-box; }
body { height: 100vh; overflow: hidden; margin: 0; font-family: 'Inter', sans-serif; background: linear-gradient(400deg, #FFFFFF 0%, #a9c1f5 130%); color: #005BAC; }

/* ================= SIDEBAR ================= */
.sidebar { position: fixed; top: 15px; left: 20px; width: 200px; height: calc(100vh - 30px); background: rgba(255,255,255,0.35); backdrop-filter: blur(14px) saturate(140%); border: 1px solid rgba(255,255,255,0.45); border-radius: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.06), inset 0 1px 0 rgba(255,255,255,0.35); padding: 20px; display: flex; flex-direction: column; z-index: 100; }
.sidebar img.logo { width: 120px; margin: 0 auto 20px auto; display: block; filter: brightness(1.2) drop-shadow(0 0 6px rgba(255,255,255,0.3)); }
.sidebar-label { font-size: 11px; font-weight: 700; color: #94a3b8; margin: 15px 0 10px 16px; text-transform: uppercase; letter-spacing: 0.5px; }
.sidebar a, .sidebar .signout { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 16px; margin-bottom: 5px; text-decoration: none; font-weight: 600; font-size: 14px; color: #475569; background: transparent; cursor: pointer; transition: all 0.2s ease; }
.sidebar .signout { margin-top: auto; border: none; width: 100%; text-align: left; }
.sidebar a:hover, .sidebar .signout:hover, .sidebar a:active, .sidebar a.active { background: #3b83f6 !important; color: #ffffff !important; box-shadow: 0 4px 15px rgba(59, 131, 246, 0.4) !important; }
.sidebar a:hover i, .sidebar a.active i { color: #ffffff !important; }

/* ================= LAYOUT ================= */
.main-wrapper { flex: 1; margin-left: 260px; width: calc(100% - 260px); height: 100vh; padding: 15px 30px; box-sizing: border-box; display: flex; flex-direction: column; overflow: hidden; }
.top-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-shrink: 0; }
.welcome-text { margin: 0; font-size: 24px; font-weight: 700; color: #1e293b; }
.welcome-sub { margin: 4px 0 0; font-size: 13px; color: #64748b; font-weight: 500; }

/* Fixed Content Area - Removed duplicate/broken CSS */
.content-area { 
    flex: 1; 
    display: flex; 
    justify-content: flex-start; 
    align-items: stretch; 
    width: 100%; 
    overflow: hidden; /* STRICTLY disables scrolling globally */
    padding-right: 0; 
}

/* ================= PROFILE BOX ================= */
.profile-wrapper { position: relative; cursor: pointer; display: flex; align-items: center; }
.profile-box { display: flex; align-items: center; gap: 10px; padding: 8px 14px; border-radius: 16px; background: rgba(255,255,255,0.55); backdrop-filter: blur(18px); border: 1px solid rgba(255,255,255,0.5); font-weight: 500; font-size: 12px; transition: all 0.2s ease; box-shadow: 0 8px 20px rgba(0,0,0,0.05); }
.profile-box:hover { background: rgba(59, 131, 246, 1); color: #fff; box-shadow: 0 4px 15px rgba(59, 131, 246, 0.6); }
.avatar { width: 34px; height: 34px; border-radius: 50%; background: #3b83f6; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px; }
.dropdown-menu { display: none; position: absolute; top: calc(100% + 8px); right: 0; width: 200px; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 10px 30px rgba(0,0,0,0.1); padding: 8px; z-index: 10000; flex-direction: column; }
.dropdown-menu.show { display: flex; }
.dropdown-menu a { display: flex; align-items: center; gap: 12px; padding: 10px 16px; text-decoration: none; color: #475569; font-size: 13px; font-weight: 500; border-radius: 8px; transition: all 0.2s ease; }
.dropdown-menu a:hover { background-color: #eff6ff; color: #2563eb; }
.dropdown-menu .logout { color: #ef4444; }
.dropdown-menu .logout:hover { background-color: #fef2f2; color: #dc2626; }

/* ================= INNER SETTINGS LAYOUT ================= */
.settings-layout {
    display: flex;
    gap: 25px; 
    width: 100%;
    max-width: 1100px;
    align-items: flex-start; 
    height: 100%; 
}

/* Left Sidebar Menu (Glassmorphism Container) */
.inner-sidebar {
    width: 250px; 
    flex-shrink: 0;
    background: rgba(255,255,255,0.55);
    backdrop-filter: blur(18px) saturate(140%);
    border: 1px solid rgba(255,255,255,0.6);
    border-radius: 24px;
    padding: 25px 20px;
    box-shadow: 0 10px 30px rgba(15,23,42,0.05);
}

.inner-menu { list-style: none; padding: 0; margin: 0; }
.inner-menu li { margin-bottom: 12px; }
.inner-menu a { display: flex; align-items: center; padding: 8px 10px; font-size: 14px; font-weight: 500; color: #64748b; text-decoration: none; transition: all 0.2s ease; border-radius: 12px; }
.inner-menu a:hover { color: #3b83f6; background: rgba(255,255,255,0.4); }

.inner-menu li.active a { color: #1e293b; font-weight: 600; }
.inner-menu .indicator { color: #3b83f6; margin-right: 10px; font-size: 13px; opacity: 0; transform: translateX(-5px); transition: all 0.2s ease; }
.inner-menu li.active .indicator { opacity: 1; transform: translateX(0); }

/* Right Content Pane */
.settings-content-pane { flex: 1; min-width: 0; }

.settings-container {
    background: rgba(255,255,255,0.55);
    backdrop-filter: blur(18px) saturate(140%);
    border: 1px solid rgba(255,255,255,0.6);
    border-radius: 24px;
    padding: 30px 40px;
    box-shadow: 0 10px 30px rgba(15,23,42,0.05);
    width: 100%; 
    max-width: none; 
    margin-top: 0; 
    margin-bottom: 0; 
    display: none; /* Hidden by default for JS toggling */
}

.settings-container.active {
    display: block; /* Only the active tab is shown */
}

/* Header inside Settings Card */
.settings-header { margin-bottom: 25px; border-bottom: 1px solid rgba(0, 0, 0, 0.05); padding-bottom: 15px; }
.settings-title { margin: 0; font-size: 20px; font-weight: 700; color: #1e293b; }
.settings-subtitle { margin: 4px 0 0; font-size: 13px; color: #64748b; font-weight: 500; }

/* Setting Rows */
.setting-row { display: flex; justify-content: space-between; align-items: center; padding: 20px 0; border-bottom: 1px solid rgba(0, 0, 0, 0.05); }
.setting-info h4 { margin: 0 0 4px 0; font-size: 15px; font-weight: 600; color: #334155; }
.setting-info p { margin: 0; font-size: 13px; color: #64748b; }

/* Settings Controls (Dropdowns) */
.settings-dropdown { appearance: none; -webkit-appearance: none; height: 40px; padding: 0px 40px 0px 20px; border-radius: 20px; border: 1px solid #e2e8f0; background-color: #ffffff; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%231e293b' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 14px center; background-size: 14px; font-family: 'Inter', sans-serif; font-weight: 600; font-size: 13px; color: #1e293b; cursor: pointer; outline: none; transition: 0.2s ease; box-shadow: 0 4px 10px rgba(0,0,0,0.02); }
.settings-dropdown:hover { border-color: #cbd5e1; }
.settings-dropdown:focus { border-color: #3b83f6; }

/* Settings Controls (Toggle Switch) */
.theme-switch { position: relative; display: inline-block; width: 50px; height: 28px; }
.theme-switch input { opacity: 0; width: 0; height: 0; }
.theme-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .3s; border-radius: 34px; }
.theme-slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 4px; bottom: 4px; background-color: white; transition: .3s; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
.theme-switch input:checked + .theme-slider { background-color: #3b83f6; }
.theme-switch input:checked + .theme-slider:before { transform: translateX(22px); }

/* Save Button */
.settings-footer { margin-top: 30px; display: flex; justify-content: flex-end; }
.btn-save-settings { background-color: #3b83f6; color: white; border: none; padding: 12px 24px; border-radius: 12px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 4px 15px rgba(59, 131, 246, 0.4); }
.btn-save-settings:hover { background-color: #2563eb; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(59, 131, 246, 0.5); }

/* ==========================================
   CUSTOM DROPDOWN STYLING (Choices.js)
   ========================================== */
/* Main Container */
.choices {
    width: 220px !important; /* Slightly wider to fit settings text */
    margin-bottom: 0 !important;
}

/* Main Button Shape (Pill) */
.choices__inner {
    height: 40px !important; 
    min-height: 40px !important;
    padding: 6px 40px 6px 20px !important; 
    border-radius: 20px !important; 
    display: flex !important;
    align-items: center !important;
    background: #ffffff !important; 
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 4px 10px rgba(0,0,0,0.02) !important;
    transition: all 0.2s ease !important;
}

/* Blue border when clicked */
.choices.is-open .choices__inner {
    border: 1px solid #3b83f6 !important; 
}

/* Text inside the main button */
.choices__inner .choices__item {
    color: #1e293b !important; 
    font-weight: 600 !important;
    font-size: 13px !important;
    white-space: nowrap !important; 
    overflow: hidden !important;
    text-overflow: ellipsis !important; 
}

/* Solid Blue Triangle Arrow */
.choices[data-type*="select-one"]::after {
    border: none !important;
    width: 0 !important;
    height: 0 !important;
    border-left: 5px solid transparent !important;
    border-right: 5px solid transparent !important;
    border-top: 6px solid #3b83f6 !important; 
    right: 18px !important;
    top: 50% !important;
    margin-top: -2px !important;
}

/* Flips triangle upside down when open */
.choices[data-type*="select-one"].is-open::after {
    border-top: none !important;
    border-bottom: 6px solid #3b83f6 !important; 
}

/* The Dropdown Menu Box */
.choices__list--dropdown {
    margin-top: 8px !important; 
    background: #ffffff !important;
    border-radius: 12px !important;
    border: 1px solid #93c5fd !important; 
    box-shadow: 0 10px 30px rgba(0,0,0,0.1) !important;
    padding: 8px !important; 
    z-index: 1000 !important;
}

/* The List Items (Unselected) */
.choices__list--dropdown .choices__item {
    padding: 10px 16px !important; 
    font-size: 13px !important;
    color: #475569 !important; 
    font-weight: 500 !important;
    border-radius: 8px !important; 
    margin-bottom: 4px !important; 
    transition: all 0.2s ease !important;
}

.choices__list--dropdown .choices__item:last-child {
    margin-bottom: 0 !important;
}

/* Item Hover/Selected State (Floating Highlight Box) */
.choices__list--dropdown .choices__item--selectable.is-highlighted {
    background-color: #eff6ff !important; 
    color: #2563eb !important; 
}

</style>
</head>
<body>

    <!-- Main Outer Sidebar -->
    <div class="sidebar">
        <img src="BR_logo.png" class="logo" alt="Logo">

        <!-- MENU SECTION -->
        <div class="sidebar-label">MENU</div>
        <a href="user_dashboard.php">
            <i class="fa-solid fa-border-all"></i>
            <span class="text">Dashboard</span>
        </a>
        <a href="history_report.php">
            <i class="fa-solid fa-clock-rotate-left"></i>
            <span class="text">Report</span>
        </a>
        <a href="analytic.php">
            <i class="fa-solid fa-chart-simple"></i>
            <span class="text">Analytic</span>
        </a>
        <a href="tablespace.php">
            <i class="fa-solid fa-database"></i>
            <span class="text">Tablespace</span>
        </a>

        <!-- OTHERS SECTION -->
        <div class="sidebar-label">OTHERS</div>
        <a href="profile.php">
            <i class="fa-regular fa-user"></i>
            <span class="text">Account</span>
        </a>
        <a href="settings.php" class="active">
            <i class="fa-solid fa-gear"></i>
            <span class="text">Settings</span>
        </a>

        <!-- SIGN OUT -->
        <div class="signout" onclick="window.location.href='logout.php'">
            <i class="fa fa-right-from-bracket"></i>
            <span class="text">Sign Out</span>
        </div>
    </div>

    <div class="main-wrapper">
        
        <!-- Top Header -->
        <div class="top-header">
            <div>
                <h2 class="welcome-text">Settings</h2>
                <p class="welcome-sub">Manage your dashboard display and preferences.</p>
            </div>

            <div class="profile-wrapper" onclick="document.getElementById('dropdownMenu').classList.toggle('show'); event.stopPropagation();">
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

      <!-- Main Content Area -->
        <div class="content-area">
            
            <div class="settings-layout">
                <!-- Left Column: Inner Navigation Sidebar -->
                <div class="inner-sidebar">
                    <ul class="inner-menu">
                        <!-- Added onclick events to trigger JavaScript -->
                        <li class="active" id="nav-dashboard">
                            <a href="#" onclick="switchTab('dashboard')"><i class="fa-solid fa-caret-right indicator"></i> Dashboard & Display</a>
                        </li>
                        <li id="nav-notifications">
                            <a href="#" onclick="switchTab('notifications')"><i class="fa-solid fa-caret-right indicator"></i> Notifications & Alerts</a>
                        </li>
                        <li id="nav-reporting">
                            <a href="#" onclick="switchTab('reporting')"><i class="fa-solid fa-caret-right indicator"></i> Reporting & History</a>
                        </li>
                    </ul>
                </div>

                <!-- Right Column: Settings Content Pane -->
                <div class="settings-content-pane">
                    
                    <!-- TAB 1: Dashboard & Display (Active by default) -->
                    <div id="pane-dashboard" class="settings-container active">
                        <div class="settings-header">
                            <h3 class="settings-title">Dashboard & Display</h3>
                            <p class="settings-subtitle">Customize how your dashboard looks and behaves when you log in.</p>
                        </div>

                        <div class="setting-row">
                            <div class="setting-info">
                                <h4>Default System View</h4>
                                <p>Choose which system data loads first on your dashboard.</p>
                            </div>
                            <div class="setting-action">
                                <select class="settings-dropdown">
                                    <option value="ALL">ALL DATAFILE</option>
                                    <option value="SIIBS">SIIBS</option>
                                    <option value="FOS">FOS</option>
                                    <option value="CC">CC</option>
                                </select>
                            </div>
                        </div>

                        <div class="setting-row">
                            <div class="setting-info">
                                <h4>Auto-Refresh Interval</h4>
                                <p>Set how often the dashboard automatically pulls live data.</p>
                            </div>
                            <div class="setting-action">
                                <select class="settings-dropdown">
                                    <option value="manual">Manual only</option>
                                    <option value="5">Every 5 minutes</option>
                                    <option value="15" selected>Every 15 minutes</option>
                                    <option value="30">Every 30 minutes</option>
                                </select>
                            </div>
                        </div>

                        <div class="setting-row" style="border-bottom: none; margin-bottom: 0; padding-bottom: 0;">
                            <div class="setting-info">
                                <h4>Dark Mode Appearance</h4>
                                <p>Switch the dashboard to a darker theme for low-light environments.</p>
                            </div>
                            <div class="setting-action">
                                <label class="theme-switch">
                                    <input type="checkbox" id="darkModeToggle">
                                    <span class="theme-slider"></span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="settings-footer">
                            <button class="btn-save-settings">Save Preferences</button>
                        </div>
                    </div>

                    <!-- TAB 2: Notifications & Alerts (Hidden initially) -->
                    <div id="pane-notifications" class="settings-container">
                        <div class="settings-header">
                            <h3 class="settings-title">Notifications & Alerts</h3>
                            <p class="settings-subtitle">Manage how and when you receive system alerts.</p>
                        </div>

                        <div class="setting-row">
                            <div class="setting-info">
                                <h4>Execution Failure Alerts</h4>
                                <p>Receive an email if a system execution fails or returns "No Record".</p>
                            </div>
                            <div class="setting-action">
                                <label class="theme-switch">
                                    <input type="checkbox" id="darkModeToggle">
                                    <span class="theme-slider"></span>
                                </label>
                            </div>
                        </div>

                        <div class="setting-row">
                            <div class="setting-info">
                                <h4>Server Capacity Warnings</h4>
                                <p>Alert me when available server capacity falls below a specific limit.</p>
                            </div>
                            <div class="setting-action">
                                <select class="settings-dropdown">
                                    <option value="none">Do not alert me</option>
                                    <option value="1gb">Below 1 GB Left</option>
                                    <option value="10percent" selected>Below 10% Capacity</option>
                                    <option value="20percent">Below 20% Capacity</option>
                                </select>
                            </div>
                        </div>

                        <div class="setting-row" style="border-bottom: none; margin-bottom: 0; padding-bottom: 0;">
                            <div class="setting-info">
                                <h4>Daily Summary Email</h4>
                                <p>Receive a morning email summarizing the previous day's ETL performance.</p>
                            </div>
                            <div class="setting-action">
                                <label class="theme-switch">
                                    <input type="checkbox">
                                    <span class="theme-slider"></span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="settings-footer">
                            <button class="btn-save-settings">Save Preferences</button>
                        </div>
                    </div>

                    <!-- TAB 3: Reporting & History (Hidden initially) -->
                    <div id="pane-reporting" class="settings-container">
                        <div class="settings-header">
                            <h3 class="settings-title">Reporting & History</h3>
                            <p class="settings-subtitle">Set your defaults for viewing and exporting report data.</p>
                        </div>

                        <div class="setting-row">
                            <div class="setting-info">
                                <h4>Default Export Format</h4>
                                <p>Choose your preferred file format when downloading system reports.</p>
                            </div>
                            <div class="setting-action">
                                <select class="settings-dropdown">
                                    <option value="excel" selected>Excel (.xlsx)</option>
                                    <option value="csv">CSV (.csv)</option>
                                    <option value="pdf">PDF Document</option>
                                </select>
                            </div>
                        </div>

                        <div class="setting-row" style="border-bottom: none; margin-bottom: 0; padding-bottom: 0;">
                            <div class="setting-info">
                                <h4>Date Range Default</h4>
                                <p>Select the default timeframe shown when opening the History page.</p>
                            </div>
                            <div class="setting-action">
                                <select class="settings-dropdown">
                                    <option value="7days">Last 7 Days</option>
                                    <option value="30days" selected>Last 30 Days</option>
                                    <option value="thismonth">This Month</option>
                                    <option value="alltime">All Time</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="settings-footer">
                            <button class="btn-save-settings">Save Preferences</button>
                        </div>
                    </div>

                </div> <!-- End Settings Content Pane -->
            </div> <!-- End Settings Layout -->

        </div> <!-- End Content Area -->
    
        <script src="js/global.js"></script>
        <script src="js/settings.js"></script>

</body>
</html>