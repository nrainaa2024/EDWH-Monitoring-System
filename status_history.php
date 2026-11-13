<?php
session_start();
include 'db.php';

// --- 1. STRICT ADMIN SECURITY CHECK ---
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$admin = $db->prepare("SELECT full_name, role FROM users WHERE id = :id");
$admin->bindValue(':id', $_SESSION['user_id'], SQLITE3_INTEGER);
$admin_data = $admin->execute()->fetchArray(SQLITE3_ASSOC);

if ($admin_data['role'] !== 'admin') {
    header("Location: user_dashboard.php");
    exit();
}

$admin_name = $admin_data['full_name'] ?? "Admin User";
$admin_role = "Admin";

// --- 2. FETCH PROCESSED USERS (Active & Rejected) ---
// This grabs everyone who is no longer 'pending'
$history_query = $db->query("SELECT staff_id, full_name, email, status, last_login_time FROM users WHERE status IN ('active', 'rejected') ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin - Status History</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* { box-sizing: border-box; }
body { height:100vh; overflow: hidden; font-family: 'Inter', sans-serif; background: linear-gradient(400deg, #FFFFFF 0%, #a9c1f5 130%); margin: 0; color: #005BAC; }
.sidebar { position: fixed; top: 15px; left: 20px; width: 200px; height: calc(100vh - 30px); background: rgba(255,255,255,0.35); backdrop-filter: blur(14px) saturate(140%); border: 1px solid rgba(255,255,255,0.45); border-radius: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.06), inset 0 1px 0 rgba(255,255,255,0.35); padding: 20px; display: flex; flex-direction: column; z-index: 100; }
.sidebar img.logo { width: 120px; margin: 0 auto 20px auto; display: block; filter: brightness(1.2) drop-shadow(0 0 6px rgba(255,255,255,0.3)); }
.sidebar-label { font-size: 11px; font-weight: 700; color: #94a3b8; margin: 15px 0 10px 16px; text-transform: uppercase; letter-spacing: 0.5px; }
.sidebar a, .sidebar .signout { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 16px; margin-bottom: 5px; text-decoration: none; font-weight: 600; font-size: 14px; color: #475569; background: transparent; cursor: pointer; transition: all 0.2s ease; }
.sidebar .signout { margin-top: auto; border: none; width: 100%; text-align: left; }
.sidebar a:hover, .sidebar .signout:hover, .sidebar a.active { background: #3b83f6 !important; color: #ffffff !important; box-shadow: 0 4px 15px rgba(59, 131, 246, 0.4) !important; }
.sidebar a:hover i, .sidebar .signout:hover i, .sidebar a.active i { color: #ffffff !important; }
.main-wrapper { margin-left: 260px; width: calc(100% - 260px); height: 100vh; padding: 15px 40px; display: flex; flex-direction: column; overflow: hidden; }
.top-header { display: flex; justify-content: space-between; align-items: center; width: 100%; margin: 0 0 15px 0; flex-shrink: 0; }
.welcome-text { margin: 0; font-size: 25px; font-weight: 700; color: #111827; }
.welcome-sub { margin: 4px 0 0; font-size: 14px; color: #64748b; }
.profile-wrapper { position: relative; cursor: pointer; display: flex; align-items: center; }
.profile-box { display: flex; align-items: center; gap: 10px; position: relative; font-size: 12px; padding: 8px 14px; border-radius: 16px; background: rgba(255,255,255,0.55); backdrop-filter: blur(18px) saturate(140%); border: 1px solid rgba(255,255,255,0.5); font-weight: 500; transition: all 0.2s ease; box-shadow: 0 8px 20px rgba(0,0,0,0.05); }
.avatar { width: 34px; height: 34px; border-radius: 50%; background: #3b83f6; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px; }
.dropdown-menu { display: none; flex-direction: column; position: absolute; top: 100%; right: 0; background: #fff; border-radius: 12px; padding: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); width: 150px; z-index: 1000; }
.dropdown-menu a { padding: 10px; text-decoration: none; color: #333; display: block; border-radius: 8px; }
.dropdown-menu a:hover { background: #f1f5f9; }
</style>
</head>
<body>
    <div class="app-container">
        <!-- SIDEBAR -->
        <div class="sidebar">
            <img src="BR_logo.png" class="logo">
            <div class="sidebar-label">MENU</div>
            <a href="admin_dashboard.php">
                <i class="fa fa-home"></i><span class="text">Overview</span>
            </a>
            <a href="admin_requests.php">
                <i class="fa fa-users"></i><span class="text">Requests</span>
            </a>
            <!-- Notice this link is now active! -->
            <a href="status_history.php" class="active">
                <i class="fa fa-history"></i><span class="text">Status History</span>
            </a>
            <div class="signout" onclick="window.location.href='logout.php'">
                <i class="fa fa-right-from-bracket"></i><span class="text">Sign Out</span>
            </div>
        </div>

        <!-- MAIN WRAPPER -->
        <div class="main-wrapper">
            <!-- TOP HEADER -->
            <div class="top-header">
                <div class="header-left">
                    <h2 class="welcome-text">Status History</h2>
                    <p class="welcome-sub">Audit trail of processed staff accounts.</p>
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
    
            <!-- TABLE CONTENT -->
            <div class="card shadow-sm border-0 mt-3" style="border-radius: 16px; background: rgba(255,255,255,0.85); backdrop-filter: blur(10px); flex-grow: 1; overflow: hidden; display: flex; flex-direction: column;">
                <div class="card-body p-0" style="overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0" style="font-size: 14px;">
                        <thead style="position: sticky; top: 0; background: #f8fafc; z-index: 1;">
                            <tr>
                                <th class="py-3 px-4 text-muted fw-bold border-bottom-0">Staff ID</th>
                                <th class="py-3 px-4 text-muted fw-bold border-bottom-0">Full Name</th>
                                <th class="py-3 px-4 text-muted fw-bold border-bottom-0">Email</th>
                                <th class="py-3 px-4 text-muted fw-bold border-bottom-0">Current Status</th>
                                <th class="py-3 px-4 text-muted fw-bold border-bottom-0">Last Login</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $has_history = false;
                            while ($row = $history_query->fetchArray(SQLITE3_ASSOC)) {
                                $has_history = true;
                                
                                // Format the badge color based on status
                                $status_badge = '';
                                if ($row['status'] === 'active') {
                                    $status_badge = '<span class="badge bg-success px-3 py-2" style="border-radius: 8px;"><i class="fa fa-check-circle me-1"></i> Approved</span>';
                                } elseif ($row['status'] === 'rejected') {
                                    $status_badge = '<span class="badge bg-danger px-3 py-2" style="border-radius: 8px;"><i class="fa fa-times-circle me-1"></i> Rejected</span>';
                                } else {
                                    $status_badge = '<span class="badge bg-secondary px-3 py-2" style="border-radius: 8px;">' . htmlspecialchars($row['status']) . '</span>';
                                }
                                
                                // Format the login time
                                $last_login = !empty($row['last_login_time']) ? date('M j, Y g:i A', strtotime($row['last_login_time'])) : '<span class="text-muted fst-italic">Never logged in</span>';
                            ?>
                            <tr>
                                <td class="py-3 px-4 fw-bold text-dark"><?php echo htmlspecialchars($row['staff_id']); ?></td>
                                <td class="py-3 px-4 text-secondary"><?php echo htmlspecialchars($row['full_name']); ?></td>
                                <td class="py-3 px-4 text-secondary"><?php echo htmlspecialchars($row['email']); ?></td>
                                <td class="py-3 px-4"><?php echo $status_badge; ?></td>
                                <td class="py-3 px-4 text-secondary" style="font-size: 13px;"><?php echo $last_login; ?></td>
                            </tr>
                            <?php } ?>
                            
                            <?php if (!$has_history) { ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="fa fa-folder-open fa-3x mb-3 text-secondary opacity-50"></i>
                                    <h5>No History Found</h5>
                                    <p class="mb-0">There are no approved or rejected accounts in the system yet.</p>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>