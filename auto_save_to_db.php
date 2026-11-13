<?php
// 1. OUTPUT BUFFER: Prevents raw PHP errors from breaking your JSON UI
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
date_default_timezone_set('Asia/Kuala_Lumpur');
header('Content-Type: application/json');

$db_file = __DIR__ . '/datawarehouse.db';
try {
    $pdo = new PDO("sqlite:" . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("PRAGMA busy_timeout = 5000;");
} catch (PDOException $e) {
    ob_end_clean();
    die(json_encode(["error" => "DB Connection Failed: " . $e->getMessage()]));
}

// ==========================================
// 2. DATE LOGIC
// ==========================================
$rawDate = isset($_GET['date']) ? $_GET['date'] : date("Y-m-d");
$cleanRaw = str_replace("-", "", $rawDate);

if (strlen($cleanRaw) === 8) {
    $formattedDate = substr($cleanRaw, 0, 4) . '-' . substr($cleanRaw, 4, 2) . '-' . substr($cleanRaw, 6, 2);
} else {
    $formattedDate = date("Y-m-d");
}

$target_timestamp = strtotime("-1 day", strtotime($formattedDate));
$batchId = date("Ymd", $target_timestamp);
$math_date = date("Y-m-d", $target_timestamp);

// ==========================================
// 3. LIVE CLOCK SCHEDULE (Time Hack Removed!)
// ==========================================
$current_hour = (int)date("G"); 
if ($current_hour < 8){ $active_schedule = "6:00 am"; }
elseif ($current_hour < 9) { $active_schedule = "8:00 am"; }
elseif ($current_hour < 18) { $active_schedule = "9:00 am"; }
else { $active_schedule = "6:00 pm"; }

$time_limit = strtotime("$math_date 23:59:59");
$expected_targets = ['SIIBS' => 850, 'FOS' => 34, 'CC' => 19];
$basePath = "E:/Data";

// ==========================================
// 4. MAIN ETL SCANNER LOOP
// ==========================================
foreach ($expected_targets as $system => $fixed_amount) {
    $dataFolder = "$basePath/$system/data";
    $backupFolder = "$basePath/$system/backup/$batchId";

    $data_count = 0;
    $backup_count = 0;
    $latest = "No Record Found";
    $actual_files = [];
    $expected_files = [];

    // A. SCAN DATA FOLDER
    if (is_dir($dataFolder)) {
        foreach (scandir($dataFolder) as $file) {
            if ($file === '.' || $file === '..') continue;
            $fTime = @filemtime("$dataFolder/$file");
            if ($fTime && date("Ymd", $fTime) === $batchId && $fTime <= $time_limit) {
                $data_count++;
                $actual_files[] = $file; // Add to master list
                $latest = ($latest === "No record Found" || $fTime > strtotime($latest)) ? date("Y-m-d H:i:s", $fTime) : $latest;
            }
        }
    }

    // B. GENERATE EXPECTED ARRAY
    $totalLoops = ($system === 'CC') ? $fixed_amount : ($fixed_amount / 2);
    for ($i = 1; $i <= $totalLoops; $i++) {
        $num = str_pad($i, 4, '0', STR_PAD_LEFT);
        if ($system === 'CC') {
            $expected_files[] = "dummy_file_{$num}.TXT";
        } else {
            $expected_files[] = "dummy_file_{$num}.CTL";
            $expected_files[] = "dummy_file_{$num}.DAT";
        }
    }

    // C. SCAN BACKUP FOLDER
    if (is_dir($backupFolder)) {
        foreach (scandir($backupFolder) as $file) {
            if ($file === '.' || $file === '..') continue;
            $fTime = @filemtime("$backupFolder/$file");
            if ($fTime && $fTime <= $time_limit) {
                $backup_count++;
                $actual_files[] = $file; // Add to master list
                $latest = ($latest === "No Record Found" || $fTime > strtotime($latest)) ? date("Y-m-d H:i:s", $fTime) : $latest;
            }
        }
    }

    // D. MATH: FIND ANOMALIES
    $missing_files = array_diff($expected_files, $actual_files);
    $surplus_files = array_diff($actual_files, $expected_files);
    $total_grand = $data_count + $backup_count;


    // ==========================================
    // 5. PERFECTED STATUS LOGIC
    // ==========================================
    if ($total_grand == 0) {
        $status = "No Record";
    } elseif ($total_grand > 0 && $total_grand < $fixed_amount) {
        // THE FIX: Combines the high-level error with the specific reason
        $status = "Failed (Missing)"; 
    } elseif ($total_grand > $fixed_amount) {
        $status = "Failed (Surplus)"; 
    } else {
        if ($active_schedule === "6:00 pm") {
            if ($backup_count == $fixed_amount) {
                $status = "Success"; 
            } else {
                $status = "Failed (Pending Move)"; 
            }
        } else {
            if ($backup_count == $fixed_amount) {
                $status = "Success"; 
            } elseif ($backup_count > 0) {
                $status = "Scheduled"; 
            } else {
                $status = "Processing"; 
            }
        }
    }

    $execution_time = date("Y-m-d h:i:s A");

   // ==========================================
    // 6. SAVE TO MAIN DATABASE
    // ==========================================
    try {
        // 1. Save to the individual system backup table (SIIBS, FOS, CC)
        $stmt = $pdo->prepare("INSERT OR REPLACE INTO $system (batch_id, total_data, total_backup, total_grand, remark, latest_file_time, execution_time) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$batchId, $data_count, $backup_count, $total_grand, $status, $latest, $execution_time]);
        
        // 2. Prevent Duplicates: Delete old record for this specific batch & system from master history
        $stmt_delete = $pdo->prepare("DELETE FROM master_etl_history WHERE batch_id = ? AND system_name = ?");
        $stmt_delete->execute([$batchId, $system]);

        // 3. Save the fresh data to master_etl_history
        $stmt_master = $pdo->prepare("INSERT INTO master_etl_history (system_name, batch_id, total_data, total_backup, total_grand, remark, latest_file_time, execution_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_master->execute([$system, $batchId, $data_count, $backup_count, $total_grand, $status, $latest, $execution_time]);
        
    } catch (PDOException $e) {
        $database_errors[] = "Error inserting $system: " . $e->getMessage();
    }

    // ==========================================
    // 7. WRITE ANOMALY DATABASE (True Historical Audit Trail)
    // ==========================================
    // THE FIX: strpos() checks if the word "Failed" exists ANYWHERE in the status string!
    if (strpos($status, 'Failed') !== false) {
        try {
            $local_time = date("Y-m-d H:i:s"); 
            
            $stmt_log = $pdo->prepare("INSERT INTO anomaly_logs (batch_id, system_name, file_name, anomaly_type, logged_at) VALUES (?, ?, ?, ?, ?)");
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM anomaly_logs WHERE batch_id = ? AND system_name = ? AND file_name = ? AND anomaly_type = ?");

            if ($total_grand == $fixed_amount && $backup_count < $fixed_amount) {
                $msg = "END OF DAY ERROR: $data_count files were not moved to the Backup folder.";
                $stmt_check->execute([$batchId, $system, $msg, 'Pending Move']);
                if ($stmt_check->fetchColumn() == 0) {
                    $stmt_log->execute([$batchId, $system, $msg, 'Pending Move', $local_time]);
                }
            } else {
                // SMART LOGGING (Missing Files)
                if (count($missing_files) == $fixed_amount) {
                    $msg = "TOTAL FAILURE: All $fixed_amount files are missing";
                    $stmt_check->execute([$batchId, $system, $msg, 'Missing']);
                    if ($stmt_check->fetchColumn() == 0) {
                        $stmt_log->execute([$batchId, $system, $msg, 'Missing', $local_time]);
                    }
                } else {
                    foreach ($missing_files as $m_file) {
                        $stmt_check->execute([$batchId, $system, $m_file, 'Missing']);
                        if ($stmt_check->fetchColumn() == 0) {
                            $stmt_log->execute([$batchId, $system, $m_file, 'Missing', $local_time]);
                        }
                    }
                }

                // SMART LOGGING (Surplus Files)
                foreach ($surplus_files as $s_file) {
                    $stmt_check->execute([$batchId, $system, $s_file, 'Surplus']);
                    if ($stmt_check->fetchColumn() == 0) {
                        $stmt_log->execute([$batchId, $system, $s_file, 'Surplus', $local_time]);
                    }
                }
            }
        } catch (PDOException $e) {}
    }
}

$hidden_warnings = ob_get_clean();
echo json_encode(["message" => "Scan complete", "batch_scanned" => $batchId]);
?>