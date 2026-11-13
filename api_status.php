<?php
// 1. Tell the browser this is pure data, not a web page, and never cache it
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// 2. Connect to your database
include 'db.php'; 

// 3. Count Successes
$success_query = $db->query("SELECT COUNT(*) as total_success FROM stgd_data WHERE ERR_CD = '0'");
$success_row = $success_query->fetchArray();
$total_success = $success_row['total_success'];

// 4. Count Failures
$failed_query = $db->query("SELECT COUNT(*) as total_failed FROM stgd_data WHERE ERR_CD != '0'");
$failed_row = $failed_query->fetchArray();
$total_failed = $failed_row['total_failed'];

// 5. Send the data back as a simple JSON package
echo json_encode([
    'success' => $total_success,
    'failed' => $total_failed
]);
?>