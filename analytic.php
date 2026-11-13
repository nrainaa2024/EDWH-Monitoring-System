<?php
include 'security_check.php';
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
    <title>System Analytics - ETL Monitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="theme.css">
    
<style>
* { box-sizing: border-box; }
body { height: 100vh; overflow: hidden; font-family: 'Inter', sans-serif; background: linear-gradient(400deg, #f4f7fc 0%, #e0eaff 100%); margin: 0; color: #0f172a; }

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

.main-wrapper {
    flex: 1;
    margin-left: 260px; 
    width: calc(100% - 260px);
    height: 100vh; 
    padding: 15px 30px; /* This 15px top/bottom strictly matches the left sidebar */
    box-sizing: border-box; 
    display: flex;
    flex-direction: column;
    overflow: hidden; 
}
.content-area {
    flex: 1; 
    display: flex;
    justify-content: flex-end; 
    align-items: stretch; /* Changed from flex-end to allow standard boundaries */
    width: 100%;
}

.top-header {
    display: flex;
    justify-content: space-between; 
    align-items: center; /* Aligns text perfectly with the profile button */
    margin-bottom: 15px; /* Tighter gap saves vertical space */
    flex-shrink: 0;
}
.welcome-text { 
    margin: 0; 
    font-size: 22px; /* Sleek, balanced size matching the History page */
    font-weight: 700; 
    color: #1e293b; 
}
.welcome-sub { 
    margin: 4px 0 0; 
    font-size: 13px; /* Smaller, cleaner subtitle */
    color: #64748b; 
    font-weight: 500;
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


.dropdown-menu { display: none; position: absolute; top: calc(100% + 8px); right: 0; width: 200px; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 10px 30px rgba(0,0,0,0.1); padding: 8px; z-index: 10000; flex-direction: column; }
.dropdown-menu.show { display: flex; }
.dropdown-menu a { display: flex; align-items: center; gap: 12px; padding: 10px 16px; text-decoration: none; color: #475569; font-size: 13px; font-weight: 500; border-radius: 8px; transition: all 0.2s ease; }
.dropdown-menu a:hover { background-color: #eff6ff; color: #2563eb; }
.dropdown-menu .logout { color: #ef4444; }
.dropdown-menu .logout:hover { background-color: #fef2f2; color: #dc2626; }

.kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 16px; flex-shrink: 0; }
.kpi-card { background: #fff; border-radius: 16px; padding: 16px; border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 16px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); }
.kpi-icon { width: 45px; height: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 18px; color: #fff; flex-shrink: 0; }
.kpi-info h4 { margin: 0 0 4px 0; font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase; }
.kpi-info h2 { margin: 0; font-size: 20px; font-weight: 800; color: #0f172a; }

/* Grid Layout for Charts */
.analytics-grid { display: grid; grid-template-columns: repeat(2, 1fr); grid-template-rows: repeat(2, 1fr); gap: 16px; flex: 1; min-height: 0; padding-bottom: 10px; }
.chart-card { background: #fff; border-radius: 16px; padding: 15px 20px; border: 1px solid #e2e8f0; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.02); }
.chart-header { flex-shrink: 0; margin-bottom: 10px; }
.chart-header h3 { margin: 0; font-size: 15px; font-weight: 700; color: #0f172a; }
.chart-header p { margin: 2px 0 0 0; font-size: 12px; color: #64748b; }
.chart-wrapper { position: relative; flex: 1; min-height: 0; width: 100%; }
</style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <img src="BR_logo.png" class="logo" alt="Logo">
        <div class="sidebar-label">MENU</div>
        <a href="user_dashboard.php"><i class="fa-solid fa-border-all"></i> Dashboard</a>
        <a href="history_report.php"><i class="fa-solid fa-clock-rotate-left"></i> Report</a>
        <a href="analytic.php" class="active"><i class="fa-solid fa-chart-simple"></i> Analytic</a>
        <a href="tablespace.php"><i class="fa-solid fa-database"></i> Tablespace</a>
        <div class="sidebar-label">OTHERS</div>
        <a href="profile.php"><i class="fa-regular fa-user"></i> Account</a>
        <a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a>
        <button class="signout" onclick="window.location.href='logout.php'"><i class="fa fa-right-from-bracket"></i> Sign Out</button>
    </div>

    <div class="main-wrapper">
        
        <!-- Header -->
        <div class="top-header">
            <div>
                <h2 class="welcome-text">System Analytics</h2>
                <p class="welcome-sub">Live monitoring of ETL jobs and core data infrastructure</p>
            </div>
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

        <!-- KPI Row (4 Cards) -->
        <div class="kpi-row">
            <div class="kpi-card">
                <div class="kpi-icon" style="background: #ef4444;"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <div class="kpi-info"><h4>Critical tablespace</h4><h2 id="kpi-storage">--</h2></div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon" style="background: #f97316;"><i class="fa-solid fa-circle-xmark"></i></div>
                <div class="kpi-info"><h4>Failed Jobs (Today)</h4><h2 id="kpi-failed">--</h2></div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon" style="background: #10b981;"><i class="fa-solid fa-shield-halved"></i></div>
                <div class="kpi-info"><h4> ETL Success Rate (24h)</h4><h2 id="kpi-rate">--%</h2></div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon" style="background: #3b83f6;"><i class="fa-solid fa-server"></i></div>
                <div class="kpi-info"><h4>Data Processed (Today)</h4><h2 id="kpi-volume">--</h2></div>
            </div>
        </div>

        <!-- Charts Grid (2x2) -->
        <div class="analytics-grid">
            
            <!-- Chart 1: ETL Status (Doughnut) -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3>1. Overall ETL Job Status</h3>
                    <p>Total Success vs Failed jobs executed</p>
                </div>
                <div class="chart-wrapper" style="display: flex; justify-content: center;"><canvas id="statusChart"></canvas></div>
            </div>

            <!-- Chart 2: Top 5 Loads (Horizontal Bar) -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3>2. Top 5 Heaviest Data Loads</h3>
                    <p>Core systems processing the most data</p>
                </div>
                <div class="chart-wrapper"><canvas id="topLoadsChart"></canvas></div>
            </div>

            <!-- Chart 3: Reliability Breakdown (Stacked Bar) -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3>3. System Reliability Breakdown</h3>
                    <p>Success and failure distribution by system</p>
                </div>
                <div class="chart-wrapper"><canvas id="reliabilityChart"></canvas></div>
            </div>

            <!-- Chart 4: Daily Ingestion (Line) -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3>4. Daily Data Ingestion Volume</h3>
                    <p>Total data processed over recent days</p>
                </div>
                <div class="chart-wrapper"><canvas id="ingestionChart"></canvas></div>
            </div>

        </div>
    </div>

<script src="js/analytic.js?v=<?php echo time(); ?>"></script>
</body>
</html>