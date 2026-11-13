<?php
header('Content-Type: application/json');
date_default_timezone_set('Asia/Kuala_Lumpur');

try {
    $pdo = new PDO("sqlite:" . __DIR__ . '/datawarehouse.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Safety check: force upgrade if necessary
    $tables = ['SIIBS', 'FOS', 'CC'];
    foreach ($tables as $tbl) {
        try { $pdo->exec("ALTER TABLE $tbl RENAME COLUMN date TO batch_id"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE $tbl ADD COLUMN execution_time TEXT"); } catch (Exception $e) {}
    }
} catch (PDOException $e) {
    die(json_encode(["error" => "Database error: " . $e->getMessage()]));
}

// 1. Date formatting
$rawDate = isset($_GET['date']) ? $_GET['date'] : date("Y-m-d");
$cleanRaw = str_replace("-", "", $rawDate);
$formattedDate = substr($cleanRaw, 0, 4) . '-' . substr($cleanRaw, 4, 2) . '-' . substr($cleanRaw, 6, 2);

$batchId = date("Ymd", strtotime("-1 day", strtotime($formattedDate)));

$expected_targets = ['SIIBS' => 850, 'FOS' => 34, 'CC' => 19];
$results = [];

foreach ($expected_targets as $system => $fixed_amount) {
    
    try {
        // Pull data using the new batch_id column
        $stmt = $pdo->prepare("
            SELECT total_data, total_backup, total_grand, remark, execution_time 
            FROM $system 
            WHERE batch_id = ? 
            ORDER BY id DESC 
            LIMIT 1
        ");
        
        $stmt->execute([$batchId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $data_count = $row['total_data'];
            $backup_count = $row['total_backup'];
            $total_grand = $row['total_grand'];
            $status = $row['remark'];
            $exec_time = $row['execution_time'] ?? "Pending Schedule";
        } else {
            $data_count = 0;
            $backup_count = 0;
            $total_grand = 0;
            $status = "No Record";
            $exec_time = "No Record Found ";
        }
    } catch (PDOException $e) {
        // Fallback if the query fails entirely
        $data_count = 0;
        $backup_count = 0;
        $total_grand = 0;
        $status = "No Record";
        $exec_time = "No Record Found";
    }

    $results[$system] = [
        'fixed_data' => "$total_grand/$fixed_amount",
        'total_data' => $data_count,
        'total_backup' => $backup_count,
        'remark' => $status,
        'execution_time' => $exec_time
    ];
}

echo json_encode($results);
?>