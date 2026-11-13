<?php
// Prevent caching to ensure the queue is always up to date
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include 'db.php'; 

// Fetch ONLY the failed records
$error_query = $db->query("SELECT * FROM stgd_data WHERE ERR_CD != '0'");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Action Queue - Active Errors</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Matches your Dashboard Soft Blue Background */
        body { 
            font-family: 'Inter', sans-serif; 
            background: linear-gradient(400deg, #FFFFFF 0%, #a9c1f5 130%);
            margin: 0; 
            padding: 30px 40px; 
            color: #005BAC; 
            min-height: 100vh;
        }
        
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .page-title-wrap { display: flex; align-items: center; gap: 15px; }
        .page-title { color: #0f172a; margin: 0; font-size: 26px; font-weight: 700; }
        .queue-alert-icon { color: #ef4444; font-size: 24px; }
        
        .back-btn { 
            padding: 10px 18px; 
            background: rgba(255, 255, 255, 0.7); 
            color: #475569; 
            text-decoration: none; 
            border-radius: 8px; 
            font-weight: 600; 
            font-size: 14px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.9);
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
            transition: 0.2s;
        }
        .back-btn:hover { background: #ffffff; color: #0f172a; }

        /* Responsive Table Wrapper for many columns */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            padding-bottom: 15px;
        }

        /* Responsive Table Wrapper */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            padding-bottom: 15px;
        }


        /* Modern Spaced Table Design */
       table { 
            width: 100%; 
            /* Removed the 1400px min-width so it naturally fits the screen */
            border-collapse: separate; 
            border-spacing: 0 10px; /* Reduced from 12px for a tighter, cleaner look */
        }
        
        /* Subtle Headers */
       th { 
            text-align: left; 
            padding: 0 15px 8px 15px; 
            color: #94a3b8; 
            font-size: 11px; 
            font-weight: 700; 
            text-transform: uppercase; 
            letter-spacing: 0.5px;
            border: none;
        }

       /* Glassmorphism Table Rows */
        tbody tr { 
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
            transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }
        
        tbody tr:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.06);
            background: rgba(255, 255, 255, 0.9);
        }

       td { 
            padding: 12px 16px; /* Tightened the padding so columns aren't excessively wide */
            font-size: 12px; /* Slightly reduced font size for a sleeker dashboard feel */
            color: #1e293b; 
            font-weight: 500;
            vertical-align: middle;
            white-space: nowrap;
            /* Creates the top and bottom borders of the card */
            border-top: 1px solid rgba(148, 163, 184, 0.3);
            border-bottom: 1px solid rgba(148, 163, 184, 0.3);
        }

        tbody td:first-child {
            border-top-left-radius: 12px;
            border-bottom-left-radius: 12px;
            border-left: 1px solid rgba(148, 163, 184, 0.3);
        }
        tbody td:last-child {
            border-top-right-radius: 12px;
            border-bottom-right-radius: 12px;
            border-right: 1px solid rgba(148, 163, 184, 0.3);
        }
        
        /* Ensure these are in your CSS to style the stacked text */
        .primary-text { font-weight: 700; color: #0f172a; display: block; margin-bottom: 4px; }
        .secondary-text { color: #64748b; font-size: 12px; display: block; }

        .back-btn { 
            padding: 10px 18px; 
            background: rgba(255, 255, 255, 0.7); 
            color: #475569; 
            text-decoration: none; 
            border-radius: 12px; /* Made slightly rounder to match sidebar */
            font-weight: 600; 
            font-size: 14px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.9);
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
            transition: all 0.2s ease;
        }
        
        /* New Sidebar-Style Blue Hover */
        .back-btn:hover { 
            background: #3b82f6; 
            color: #ffffff; 
            border-color: #3b82f6;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        .status-badge { 
            background: #fee2e2; 
            color: #ef4444; 
            padding: 4px 10px; 
            border-radius: 20px; 
            font-size: 11px; 
            font-weight: 700; 
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .success-count { color: #10b981; font-weight: 700; }
        .fail-count { color: #ef4444; font-weight: 700; }

        /* Action Buttons */
        .action-group {
            display: flex;
            gap: 6px;
            justify-content: flex-end;
        }
        
        .btn-primary { 
            background: #e0f2fe; 
            color: #0284c7; 
            border: none; 
            padding: 8px 12px; 
            border-radius: 6px; 
            cursor: pointer; 
            font-size: 12px; 
            font-weight: 600; 
            transition: 0.2s;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .btn-primary:hover { background: #bae6fd; }

        .btn-icon {
            background: #f1f5f9;
            color: #64748b;
            border: none;
            padding: 8px 10px;
            border-radius: 6px;
            cursor: pointer;
            transition: 0.2s;
            font-size: 13px;
        }
        .btn-icon:hover { background: #e2e8f0; color: #0f172a; }
        
        .btn-icon.danger { background: #fee2e2; color: #ef4444; }
        .btn-icon.danger:hover { background: #fca5a5; color: #b91c1c; }
    </style>
</head>
<body>

    <div class="page-header">
        <div class="page-title-wrap">
            <i class="fa-solid fa-triangle-exclamation queue-alert-icon"></i>
            <div>
                <h1 class="page-title">Action Required Queue</h1>
                <p style="margin: 4px 0 0 0; color: #64748b; font-size: 14px;">Manage and resolve active data load errors.</p>
            </div>
        </div>
        <a href="user_dashboard.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Mapping / Workflow</th>
                    <th>Folder / Module</th>
                    <th>Dates (ETL / DATA)</th>
                    <th>Source / Target</th>
                    <th>Total Cent</th>
                    <th>Rows (Success/Fail)</th>
                    <th>Job Timings</th>
                    <th>Status / Error</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $has_errors = false;
                while($row = $error_query->fetchArray()): 
                    $has_errors = true;
                    
                    // Fetching data with fallbacks if columns are null or empty
                    $mapping = $row['MAPPING_NAME'] ?? 'N/A';
                    $workflow = $row['WORKFLOW_NAME'] ?? 'N/A';
                    $folder = $row['FOLDER'] ?? $row['FOLDER_NAME'] ?? 'N/A';
                    $module = $row['DATA_MODULE'] ?? 'N/A';
                    $etl_dt = $row['ETL_DT'] ?? 'N/A';
                    $data_dt = $row['DATA_DT'] ?? 'N/A';
                    $source = $row['SOURCE_SYSTEM'] ?? 'N/A';
                    $target = $row['TARGET_NAME'] ?? 'N/A';
                    $total_cent = $row['ROW_CNT_SOURCE'] ?? '0';
                    $row_success = $row['TOTALROWSUCCESS'] ?? '0';
                    $row_failed = $row['TOTALROWFAILED'] ?? '0';
                    $start_job = $row['START_JOB'] ?? 'N/A';
                    $end_job = $row['END_JOB'] ?? 'N/A';
                    $err_code = $row['ERR_CD'] ?? 'Unknown';
                ?>
                <tr>
                    <tr>
                    <!-- Mapping & Workflow -->
                    <td>
                        <div class="primary-text"><?php echo htmlspecialchars($mapping); ?></div>
                        <div class="secondary-text"><i class="fa-solid fa-code-branch"></i> <?php echo htmlspecialchars($workflow); ?></div>
                    </td>
                    
                    <!-- Folder & Module -->
                    <td>
                        <div class="primary-text"><i class="fa-regular fa-folder"></i> <?php echo htmlspecialchars($folder); ?></div>
                        <div class="secondary-text"><?php echo htmlspecialchars($module); ?></div>
                    </td>
                    
                    <!-- Dates -->
                    <td>
                        <div class="primary-text">ETL: <?php echo htmlspecialchars($etl_dt); ?></div>
                        <div class="secondary-text">DATA: <?php echo htmlspecialchars($data_dt); ?></div>
                    </td>

                    <!-- Source & Target -->
                    <td>
                        <div class="primary-text">Src: <?php echo htmlspecialchars($source); ?></div>
                        <div class="secondary-text">Tgt: <?php echo htmlspecialchars($target); ?></div>
                    </td>
                    
                    <!-- Total Cent Source -->
                    <td><div class="primary-text"><?php echo htmlspecialchars($total_cent); ?></div></td>

                    <!-- Rows Success/Fail -->
                    <td>
                        <div class="secondary-text">Success: <span class="success-count"><?php echo htmlspecialchars($row_success); ?></span></div>
                        <div class="secondary-text">Failed: <span class="fail-count"><?php echo htmlspecialchars($row_failed); ?></span></div>
                    </td>

                    <!-- Start & End Job -->
                    <td>
                        <div class="secondary-text">Start: <?php echo htmlspecialchars($start_job); ?></div>
                        <div class="secondary-text">End: <?php echo htmlspecialchars($end_job); ?></div>
                    </td>
                    
                    <!-- Status & Error Code -->
                    <td>
                        <div class="status-badge"><i class="fa-solid fa-circle-exclamation"></i> Code: <?php echo htmlspecialchars($err_code); ?></div>
                    </td>
                    
                    <!-- Actions -->
                    <td>
                        <div class="action-group">
                            <button class="btn-primary" onclick="alert('Viewing log for <?php echo htmlspecialchars($mapping); ?>')">
                                Investigate <i class="fa-solid fa-chevron-down" style="font-size: 10px;"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
                
                <?php if(!$has_errors): ?>
                <tr style="background: transparent; box-shadow: none;">
                    <td colspan="9" style="text-align: center; padding: 60px; color: #94a3b8; border: none;">
                        <i class="fa-solid fa-shield-check" style="font-size: 32px; margin-bottom: 12px; display: block; color: #10b981;"></i>
                        <span style="font-size: 16px; font-weight: 600;">System Healthy</span><br>
                        No active errors in the queue.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</body>
</html>