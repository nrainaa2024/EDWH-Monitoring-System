<?php
session_start();
include 'db.php';
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

$response = ['status' => 'success'];

// ==========================================
// 1. KPI DATA QUERIES
// ==========================================
$kpi_storage = @$db->querySingle("SELECT COUNT(*) FROM Tablespace WHERE USED_PCT >= 90") ?: 0;
$kpi_failed  = @$db->querySingle("SELECT COUNT(*) FROM master_etl_history WHERE remark = 'Failed' AND DATE(execution_time) = DATE('now')") ?: 0;
$success_tdy = @$db->querySingle("SELECT COUNT(*) FROM master_etl_history WHERE remark = 'Success' AND DATE(execution_time) = DATE('now')") ?: 0;
$total_tdy   = @$db->querySingle("SELECT COUNT(*) FROM master_etl_history WHERE DATE(execution_time) = DATE('now')") ?: 0;
$kpi_rate    = ($total_tdy > 0) ? round(($success_tdy / $total_tdy) * 100, 1) : 0;
$kpi_vol     = @$db->querySingle("SELECT SUM(total_data) FROM master_etl_history WHERE DATE(execution_time) = DATE('now')") ?: 0;

$response['kpis'] = [
    'storage' => $kpi_storage,
    'failed'  => $kpi_failed,
    'rate'    => $kpi_rate,
    'volume'  => round($kpi_vol, 2)
];

// ==========================================
// 2. CHART QUERIES
// ==========================================

// Chart 1: Doughnut (Success vs Failed)
$res1 = @$db->query("SELECT remark, COUNT(*) as count FROM master_etl_history GROUP BY remark");
$c1_labels = []; $c1_data = [];
while($res1 && $row = $res1->fetchArray(SQLITE3_ASSOC)) {
    $c1_labels[] = $row['remark'];
    $c1_data[] = $row['count'];
}

// Chart 2: Top 5 Loads (Horizontal Bar)
$res2 = @$db->query("SELECT system_name, SUM(total_data) as total FROM master_etl_history GROUP BY system_name ORDER BY total DESC LIMIT 5");
$c2_labels = []; $c2_data = [];
while($res2 && $row = $res2->fetchArray(SQLITE3_ASSOC)) {
    $c2_labels[] = $row['system_name'];
    $c2_data[] = $row['total'];
}

// Chart 3: Reliability (Stacked Bar)
$res3 = @$db->query("SELECT system_name, remark, COUNT(*) as count FROM master_etl_history GROUP BY system_name, remark");
$raw_c3 = [];
while($res3 && $row = $res3->fetchArray(SQLITE3_ASSOC)) {
    $sys = $row['system_name'];
    if(!isset($raw_c3[$sys])) { $raw_c3[$sys] = ['Success'=>0, 'Failed'=>0]; }
    $raw_c3[$sys][$row['remark']] = $row['count'];
}
$c3_labels = []; $c3_success = []; $c3_fail = [];
foreach($raw_c3 as $sys => $counts) {
    $c3_labels[] = $sys;
    $c3_success[] = $counts['Success'];
    $c3_fail[] = $counts['Failed'];
}

// Chart 4: Daily Ingestion (Line)
$res4 = @$db->query("SELECT DATE(execution_time) as date, SUM(total_data) as total FROM master_etl_history GROUP BY DATE(execution_time) ORDER BY date ASC LIMIT 14");
$c4_labels = []; $c4_data = [];
while($res4 && $row = $res4->fetchArray(SQLITE3_ASSOC)) {
    $c4_labels[] = $row['date'];
    $c4_data[] = $row['total'];
}

// --- FALLBACK MOCK DATA (In case your DB is empty during testing) ---
if(empty($c1_data)) {
    $c1_labels = ['Success', 'Failed']; $c1_data = [840, 62];
    $c2_labels = ['SIIBS_GL', 'CASA_TRX', 'IRAKYAT', 'FOS_CORE', 'CC_MAIN']; $c2_data = [1204, 950, 780, 540, 310];
    $c3_labels = ['SIIBS', 'CC', 'FOS']; $c3_success = [350, 240, 180]; $c3_fail = [30, 12, 5];
    $c4_labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']; $c4_data = [450, 480, 510, 490, 600, 320, 300];
    $response['kpis'] = ['storage' => 2, 'failed' => 4, 'rate' => 96.5, 'volume' => 124.5];
}

$response['charts'] = [
    'status' => ['labels' => $c1_labels, 'data' => $c1_data],
    'topLoads' => ['labels' => $c2_labels, 'data' => $c2_data],
    'reliability' => ['labels' => $c3_labels, 'success' => $c3_success, 'fail' => $c3_fail],
    'ingestion' => ['labels' => $c4_labels, 'data' => $c4_data]
];

echo json_encode($response);
exit();
?>