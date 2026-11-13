<?php
header('Content-Type: application/json');
// Prevent caching so the dashboard always gets the latest storage data
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include 'db.php'; 

try {
    // The $query variable is explicitly defined here to fix the "Undefined variable" error
    $query = "
        SELECT 
            t1.TABLESPACE_NAME, 
            t1.TOTAL_MB, 
            t1.FREE_MB, 
            t1.USED_MB, 
            t1.USED_PCT,
            t2.AVG_DAILY_GB,
            t2.MAX_DAILY_GB
        FROM Tablespace t1
        LEFT JOIN trend_tablespace t2 ON t1.TABLESPACE_NAME = t2.TABLESPACE_NAME
        ORDER BY t1.USED_PCT DESC 
    ";
    
    $result = $db->query($query);
    
    // Safety check: if the query fails, throw an error instead of crashing
    if (!$result) {
        throw new Exception("Database query failed: " . $db->lastErrorMsg());
    }
    
    $data = [];
    
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        // Ensure we always have a numeric value for the percentages and trends
        $row['USED_PCT'] = (float) $row['USED_PCT'];
        $row['AVG_DAILY_GB'] = isset($row['AVG_DAILY_GB']) ? (float) $row['AVG_DAILY_GB'] : 0;
        $row['MAX_DAILY_GB'] = isset($row['MAX_DAILY_GB']) ? (float) $row['MAX_DAILY_GB'] : 0;
        $data[] = $row;
    }
    
    echo json_encode(["status" => "success", "data" => $data]);
    
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>