<?php
session_start();
include 'db.php';

// Security check: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 1. Grab all active filters and search query from the URL
$selected_month  = isset($_GET['month'])  ? $_GET['month']  : 'ALL';
$selected_system = isset($_GET['system']) ? $_GET['system'] : 'ALL';
$selected_status = isset($_GET['status']) ? $_GET['status'] : 'ALL';
$search_query    = isset($_GET['search']) ? $_GET['search'] : '';

// 2. Start the base query 
$query = "SELECT system_name, batch_id, total_data, total_backup, total_grand, remark, execution_time FROM master_etl_history";
$conditions = [];

// Strictly filter out "Processing". ONLY show Success or Failed records.
$conditions[] = "(remark = 'Success' OR remark LIKE '%Failed%' OR remark LIKE '%ERROR%')";

// 3. Stack the filters if they are active
if ($selected_month !== 'ALL') {
    $conditions[] = "execution_time LIKE '" . $selected_month . "%'";
}
if ($selected_system !== 'ALL') {
    $conditions[] = "system_name = '" . $selected_system . "'";
}
if ($selected_status !== 'ALL') {
    $conditions[] = "remark LIKE '%" . $selected_status . "%'"; 
}
if ($search_query !== '') {
    $conditions[] = "batch_id LIKE '%" . $search_query . "%'";
}

// 4. Combine them and execute (Newest first)
if (count($conditions) > 0) {
    $query .= " WHERE " . implode(' AND ', $conditions);
}

$query .= " ORDER BY execution_time DESC";
$history_records = $db->query($query);



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
    <title>History Report - ETL Monitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="theme.css">
    
<style>
* { box-sizing: border-box; }
body { height:100vh; overflow: hidden; }
body {
    font-family: 'Inter', sans-serif;
    background: linear-gradient(400deg, #FFFFFF 0%, #a9c1f5 130%);
    margin: 0;
    color: #005BAC;
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
    margin-bottom: 5px; 
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

.main-wrapper {
    flex: 1;
    margin-left: 260px; 
    width: calc(100% - 260px);
    height: 100vh; 
    padding: 20px 30px;
    box-sizing: border-box; 
    display: flex;
    flex-direction: column;
    overflow: hidden; 
}

.top-header {
    display: flex;
    justify-content: space-between; 
    align-items: center;
    margin-bottom: 20px;
    flex-shrink:0;
}

.welcome-text { margin: 0; font-size: 25px; font-weight: 700; color: #111827; }
.welcome-sub { margin: 4px 0 0; font-size: 14px; color: #64748b; }

.dashboard-layout {
    display: flex; 
    gap: 25px;
    flex: 1;
    min-height: 0; 
    overflow: hidden; 
}

.main-content {
    flex: 1;
    height: 100%;
    overflow-y: hidden; 
    overflow-x: hidden;
    padding-right: 10px;
    padding-bottom: 20px;
    display: flex;
    flex-direction: column; 
}

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

.avatar {
    width: 34px; height: 34px;
    border-radius: 50%;
    background: #3b83f6;
    color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 12px;
}

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
    display: none;
}

.profile-wrapper.active .dropdown-menu {
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
    background-color: transparent !important;
    transition: all 0.2s ease !important;
}

.profile-wrapper .dropdown-menu a:hover:not(.logout) {
    background-color: #eff6ff !important; 
    color: #2563eb !important; 
}

.profile-wrapper .dropdown-menu .logout { color: #ef4444 !important; }
.profile-wrapper .dropdown-menu .logout:hover { background-color: #fef2f2 !important; color: #dc2626 !important; }

/* ================= IMAGE-MATCHED FILTER UI ================= */
.image-filter-btn {
    background-color: #ffffff;
    border: 1px solid #e2e8f0; 
    border-radius: 50px; 
    padding: 10px 20px;
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    font-weight: 500;
    color: #475569;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: 0.2s ease;
}

.image-filter-btn.has-active,
.image-filter-btn:hover {
    background-color: #dbeafe; 
    color: #1e3a8a; 
    border-color: #bfdbfe;
}

.image-filter-card {
    position: absolute;
    top: calc(100% + 10px); 
    right: 0;
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.12);
    border: 1px solid #e2e8f0;
    width: 280px; 
    padding: 20px;
    z-index: 99999 !important; 
    transition: opacity 0.2s ease !important; 
    transform: none !important; 
    overflow: visible !important;
    height: max-content !important; 
}

.image-filter-card.hidden {
    opacity: 0;
    pointer-events: none;
    transform: none !important;;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.card-header .title { font-weight: 700; font-size: 15px; color: #0f172a; }
.card-header .clear-btn { font-size: 12px; color: #ef4444; text-decoration: none; font-weight: 600; }

.card-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    height: max-content !important; 
}

.grid-square {
    position: relative; 
    background: #f8fafc;
    border-radius: 12px;
    padding: 20px 10px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border: 2px solid transparent;
    transition: 0.2s ease;
}

.grid-square i { font-size: 20px; color: #64748b; }
.grid-square .label { font-size: 13px; font-weight: 600; color: #475569; }
.grid-square:hover { background: #f1f5f9; }

.grid-square.active-blue { background: #dbeafe; border-color: #bfdbfe; }
.grid-square.active-blue i,
.grid-square.active-blue .label { color: #1e3a8a; }

/* ================= CUSTOM SQUARE DROPDOWNS ================= */
.square-wrapper {
    position: relative !important;
}

.custom-filter-dropdown {
    position: absolute;
    top: calc(100% + 8px); 
    left: 50%;
    transform: translateX(-50%);
    min-width: 130px;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    border: 1px solid #e2e8f0;
    padding: 8px;
    z-index: 999999 !important; 
    display: none; 
    flex-direction: column;
    gap: 4px;
}

.custom-filter-dropdown.show {
    display: flex;
}

.dropdown-option {
    padding: 8px 12px;
    font-size: 13px;
    font-weight: 500;
    color: #475569;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.dropdown-option:hover {
    background-color: #eff6ff; 
    color: #2563eb;
}

/* ================= REPORT FOLDER TABS ================= */
.report-folders-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 25px;
    flex-shrink: 0;
}

.folder-card {
    background: rgba(255, 255, 255, 0.7);
    border: 1px solid rgba(255, 255, 255, 0.9);
    border-radius: 16px;
    padding: 20px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 16px;
    transition: all 0.2s ease;
    box-shadow: 0 4px 15px rgba(0,0,0,0.03);
    backdrop-filter: blur(10px);
}

.folder-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
    background: rgba(255, 255, 255, 0.9);
}

.folder-card.active {
    border: 2px solid #3b83f6;
    background: #ffffff;
    box-shadow: 0 10px 30px rgba(59, 131, 246, 0.15);
}

.folder-icon {
    width: 50px;
    height: 50px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
}

.folder-icon.blue { background: #dbeafe; color: #2563eb; }
.folder-icon.purple { background: #f3e8ff; color: #9333ea; }
.folder-icon.orange { background: #ffedd5; color: #ea580c; }

.folder-info { display: flex; flex-direction: column; gap: 4px; }
.folder-info h4 { margin: 0; font-size: 15px; font-weight: 700; color: #0f172a; }
.folder-info p { margin: 0; font-size: 12px; color: #64748b; font-weight: 500; }

.tab-content { display: none; flex-direction: column; flex-grow: 1; min-height: 0; position: relative; z-index: 1;}
.tab-content.active { display: flex; }

.btn-download {
    background: #10b981;
    color: white;
    padding: 0 20px;
    height: 45px;
    border-radius: 50px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.25);
    transition: 0.2s;
    border: none;
}
.btn-download:hover {
    background: #059669;
    transform: translateY(-2px);
    color: white;
}

/* ================= GRAB-STYLE SMART LIST ================= */
.table-scroll-area::-webkit-scrollbar { width: 6px; }
.table-scroll-area::-webkit-scrollbar-track { background: transparent; margin: 12px 0; }
.table-scroll-area::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 20px; transition: background 0.2s ease; }
.table-scroll-area::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
.table-scroll-area { scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent; }

.smart-list-wrapper {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.date-group-header {
    font-size: 12px;
    font-weight: 800;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: 15px 0 5px 0;
    padding-bottom: 5px;
    border-bottom: 2px solid #f1f5f9;
}

.smart-list-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #ffffff;
    border: 1px solid #f1f5f9;
    padding: 16px 20px;
    border-radius: 16px;
    transition: 0.2s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.02);
    cursor: pointer;
}
.smart-list-row:hover {
    box-shadow: 0 8px 20px rgba(59,131,246,0.08);
    border-color: #dbeafe;
    transform: translateY(-1px);
}

.row-left { display: flex; align-items: center; gap: 15px; }
.sys-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
.sys-details { display: flex; flex-direction: column; gap: 4px; }
.sys-name { font-size: 15px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 8px; }
.sys-batch { font-size: 12px; font-weight: 500; color: #64748b; }

.row-right { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; }
.sys-time { font-size: 12px; font-weight: 700; color: #1e3a8a; }
.sys-status { font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 12px; }

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
        <a href="history_report.php" class="active">
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
                <h2 class="welcome-text">History List</h2>
                <p class="welcome-sub">Overview of report records and monitoring activities</p>
            </div>

            <!-- Profile Menu -->
            <div class="profile-wrapper" id="profileWrapper">
                <div class="profile-box">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_name); ?>&background=2A4E9E&color=fff" class="avatar">
                    <span class="profile-name"><?php echo htmlspecialchars($user_name); ?></span>
                    <i class="fa fa-chevron-down profile-arrow"></i>
                </div>
                
                <div class="dropdown-menu">
                    <a href="profile.php"><i class="fa fa-user"></i> Profile</a>
                    <a href="settings.php"><i class="fa fa-gear"></i> Settings</a>
                    <a href="logout.php" class="logout"><i class="fa fa-right-from-bracket"></i> Sign out</a>
                </div>
            </div>
        </div>

        <div class="dashboard-layout">
            <div class="main-content">

         <!-- History Container Card -->
        <div class="history-container" style="background: rgba(255, 255, 255, 0.55); backdrop-filter: blur(18px) saturate(140%); border: 1px solid rgba(255, 255, 255, 0.5); border-radius: 20px; padding: 25px; padding-bottom: 0; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06); width: 100%; height: 85vh; display: flex; flex-direction: column; overflow: visible;">
    
    <!-- 1. FOLDER TABS -->
    <div class="report-folders-grid">
        <div class="folder-card active" onclick="switchReportTab('tab-monthly', 'Monthly Execution History', this)">
            <div class="folder-icon blue"><i class="fa-solid fa-server"></i></div>
            <div class="folder-info">
                <h4>Monthly Executions</h4>
                <p>ETL Batch Data Loads</p>
            </div>
        </div>
        
        <div class="folder-card" onclick="switchReportTab('tab-stgd', 'STGD Logs Archive', this)">
            <div class="folder-icon purple"><i class="fa-solid fa-file-shield"></i></div>
            <div class="folder-info">
                <h4>STGD Logs</h4>
                <p>System Audit Archive</p>
            </div>
        </div>
        
        <div class="folder-card" onclick="switchReportTab('tab-tablespace', 'Tablespace Capacity Records', this)">
            <div class="folder-icon orange"><i class="fa-solid fa-hard-drive"></i></div>
            <div class="folder-info">
                <h4>Tablespace</h4>
                <p>Server Storage History</p>
            </div>
        </div>
    </div>

    <!-- 2. TITLE AND FILTER ROW -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-shrink: 0; position: relative; z-index: 9999;">
        
        <h3 id="dynamic-report-title" style="margin: 0; color: #1e3a8a; font-weight: 800; font-size: 18px;">Monthly Execution History</h3>
        
      <!-- MASTER FORM WITH POPOVER FILTER -->
        <form id="gridFilterForm" method="GET" action="history_report.php" style="display: flex; gap: 12px; align-items: center; margin: 0; width: 100%; justify-content: flex-end;">
            
            <!-- HIDDEN INPUTS (Stores the active filters) -->
            <input type="hidden" name="month" id="monthInput" value="<?php echo htmlspecialchars($selected_month); ?>">
            <input type="hidden" name="system" id="systemInput" value="<?php echo htmlspecialchars($selected_system); ?>">
            <input type="hidden" name="status" id="statusInput" value="<?php echo htmlspecialchars($selected_status); ?>">

            <!-- FILTER BUTTON UI -->
            <div style="position: relative;">
                <button type="button" id="popoverToggleBtn" class="image-filter-btn <?php echo (count($conditions) > 1) ? 'has-active' : ''; ?>">
                    <i class="fa-solid fa-filter"></i> Filter
                </button>

                <!-- THE FILTER CARD POPUP -->
                <div id="filterCard" class="image-filter-card hidden">
                    <div class="card-header">
                        <span class="title">Add Filter</span>
                        <?php if(count($conditions) > 1): ?>
                            <a href="history_report.php" class="clear-btn">Clear All</a>
                        <?php endif; ?>
                    </div>

                    <div class="card-grid">
                        <!-- Date Square -->
                        <div class="square-wrapper">
                            <div class="grid-square <?php echo ($selected_month !== 'ALL') ? 'active-blue' : ''; ?>" onclick="toggleSquareMenu(event, 'monthMenu')">
                                <i class="fa-solid fa-calendar-days"></i>
                                <span class="label">Date</span>
                            </div>
                            <div class="custom-filter-dropdown" id="monthMenu">
                                <div class="dropdown-option" onclick="applyGridFilter('monthInput', 'ALL')">All Time</div>
                                <div class="dropdown-option" onclick="applyGridFilter('monthInput', '2026-07')">July 2026</div>
                                <div class="dropdown-option" onclick="applyGridFilter('monthInput', '2026-08')">August 2026</div>
                            </div>
                        </div>

                        <!-- System Square -->
                        <div class="square-wrapper">
                            <div class="grid-square <?php echo ($selected_system !== 'ALL') ? 'active-blue' : ''; ?>" onclick="toggleSquareMenu(event, 'systemMenu')">
                                <i class="fa-solid fa-network-wired"></i>
                                <span class="label">System</span>
                            </div>
                            <div class="custom-filter-dropdown" id="systemMenu">
                                <div class="dropdown-option" onclick="applyGridFilter('systemInput', 'ALL')">All Systems</div>
                                <div class="dropdown-option" onclick="applyGridFilter('systemInput', 'SIIBS')">SIIBS</div>
                                <div class="dropdown-option" onclick="applyGridFilter('systemInput', 'FOS')">FOS</div>
                                <div class="dropdown-option" onclick="applyGridFilter('systemInput', 'CC')">CC</div>
                            </div>
                        </div>

                        <!-- Status Square -->
                        <div class="square-wrapper">
                            <div class="grid-square <?php echo ($selected_status !== 'ALL') ? 'active-blue' : ''; ?>" onclick="toggleSquareMenu(event, 'statusMenu')">
                                <i class="fa-solid fa-circle-check"></i>
                                <span class="label">Status</span>
                            </div>
                            <div class="custom-filter-dropdown" id="statusMenu">
                                <div class="dropdown-option" onclick="applyGridFilter('statusInput', 'ALL')">All Statuses</div>
                                <div class="dropdown-option" onclick="applyGridFilter('statusInput', 'Success')">Success</div>
                                <div class="dropdown-option" onclick="applyGridFilter('statusInput', 'Failed')">Failed</div>
                            </div>
                        </div>
                    </div> 
                </div> 
            </div> 

            <!-- Excel Download Link -->
            <a href="export_history.php" class="btn-download">
                <i class="fa-solid fa-file-excel"></i> Export Excel
            </a>
            
        </form>
    </div>

    <!-- 3. TAB CONTENT AREAS -->
    
  <!-- TAB 1: Monthly Execution (Active by default) -->
    <div id="tab-monthly" class="tab-content active">
        <div class="table-scroll-area" style="flex-grow: 1; overflow-y: auto; padding-right: 5px; padding-bottom: 25px;">
            
            <div class="smart-list-wrapper">
                <?php 
                if ($history_records) {
                    $current_date = "";
                    
                    while ($row = $history_records->fetchArray(SQLITE3_ASSOC)) {
                        // Extract and format the date
                        $raw_date = strtotime($row['execution_time']);
                        $row_date = date('Y-m-d', $raw_date);
                        $display_date = date('F j, Y', $raw_date);
                        
                        // 1. Grouping Logic: Print a header if the date changes
                        if ($row_date !== $current_date) {
                            $header_text = ($row_date == date('Y-m-d')) ? "Today, " . $display_date : $display_date;
                            echo "<div class='date-group-header'>" . $header_text . "</div>";
                            $current_date = $row_date;
                        }

                        // 2. Dynamic Status Styling
                        $statusColor = "#64748b";
                        $statusBg = "#f1f5f9";
                        if ($row['remark'] === "Success") { 
                            $statusColor = "#10b981"; $statusBg = "#ecfdf5"; 
                        } elseif (strpos($row['remark'], "Failed") !== false || strpos($row['remark'], "ERROR") !== false) { 
                            $statusColor = "#ef4444"; $statusBg = "#fef2f2"; 
                        }
                        
                        // 3. Dynamic Icon Styling based on System
                        $iconClass = "fa-folder-open";
                        $iconColor = "#3b83f6"; $iconBg = "#dbeafe";
                        if ($row['system_name'] == 'FOS') { 
                            $iconColor = "#9333ea"; $iconBg = "#f3e8ff"; 
                        } elseif ($row['system_name'] == 'CC') { 
                            $iconColor = "#ea580c"; $iconBg = "#ffedd5"; 
                        }
                        
                        // Add a fallback for target data so it's never blank
                        $target_data = !empty($row['total_grand']) ? htmlspecialchars($row['total_grand']) : '0';
                ?>
                
               <!-- The Rich Row UI -->
                        <div class="smart-list-row" onclick="alert('View details for Batch: <?php echo htmlspecialchars($row['batch_id'] ?? ''); ?>')">
                            <div class="row-left">
                                <div class="sys-icon" style="color: <?php echo $iconColor; ?>; background: <?php echo $iconBg; ?>">
                                    <i class="fa-solid <?php echo $iconClass; ?>"></i>
                                </div>
                                <div class="sys-details">
                                    <span class="sys-name">
                                        <?php echo htmlspecialchars($row['system_name'] ?? ''); ?>
                                    </span>
                                    <span class="sys-batch">Batch ID: <?php echo htmlspecialchars($row['batch_id'] ?? ''); ?> • Target Data: <?php echo $target_data; ?></span>
                                </div>
                            </div>
                            <div class="row-right">
                                <span class="sys-time"><?php echo date('h:i A', $raw_date); ?></span>
                                <span class="sys-status" style="color: <?php echo $statusColor; ?>; background: <?php echo $statusBg; ?>">
                                    <?php echo htmlspecialchars($row['remark'] ?? ''); ?>
                                </span>
                            </div>
                        </div>

                <?php
                    }
                } else {
                    echo "<div style='padding: 40px; text-align: center; color: #94a3b8;'><i class='fa-solid fa-inbox' style='font-size: 32px; margin-bottom: 10px;'></i><br>No historical records found.</div>";
                }
                ?>
            </div>

        </div>
    </div>

    <!-- TAB 2: STGD Logs Placeholder -->
    <div id="tab-stgd" class="tab-content">
        <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: #94a3b8; padding: 40px;">
            <i class="fa-solid fa-file-shield" style="font-size: 48px; color: #d8b4fe; margin-bottom: 15px;"></i>
            <h4 style="color: #64748b;">STGD Logs Data</h4>
            <p>Your STGD historical table will load here.</p>
        </div>
    </div>

    <!-- TAB 3: Tablespace Placeholder -->
    <div id="tab-tablespace" class="tab-content">
        <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: #94a3b8; padding: 40px;">
            <i class="fa-solid fa-hard-drive" style="font-size: 48px; color: #fdba74; margin-bottom: 15px;"></i>
            <h4 style="color: #64748b;">Tablespace Records</h4>
            <p>Your storage capacity history table will load here.</p>
        </div>
                    </div>

                </div> <!-- End of history-container -->
            </div> <!-- End of main-content -->
        </div> <!-- End of dashboard-layout -->
    </div> <!-- End of main-wrapper -->


 <script src="js/history_report.js?v=<?php echo time(); ?>"></script>
 
</body>
</html>