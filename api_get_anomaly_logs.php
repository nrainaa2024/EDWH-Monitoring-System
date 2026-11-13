<?php
header('Content-Type: application/json');

$batch_id = $_GET['batch_id'] ?? '';
$system_name = $_GET['system'] ?? '';

if (!$batch_id || !$system_name) {
    die(json_encode(["error" => "Missing parameters"]));
}

try {
    $pdo = new PDO("sqlite:" . __DIR__ . '/datawarehouse.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT file_name, anomaly_type FROM anomaly_logs WHERE batch_id = ? AND system_name = ?");
    $stmt->execute([$batch_id, $system_name]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(["status" => "success", "data" => $logs]);

} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>