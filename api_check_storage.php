<?php
// api_check_storage.php

// Define the drive or specific folder you want to monitor
$target_drive = "E:"; 

// Security/Error check: Make sure the drive exists
if (!is_dir($target_drive)) {
    echo json_encode(["error" => "Drive not found."]);
    exit();
}

// Get the raw bytes from the server
$free_bytes = disk_free_space($target_drive);
$total_bytes = disk_total_space($target_drive);
$used_bytes = $total_bytes - $free_bytes;

// Calculate the percentage used
$percentage = ($used_bytes / $total_bytes) * 100;

// Helper function to convert bytes to Gigabytes (GB) for readability
function formatToGB($bytes) {
    return round($bytes / 1073741824, 2); 
}

// Send the data back as a clean JSON package
echo json_encode([
    "total_gb" => formatToGB($total_bytes),
    "used_gb" => formatToGB($used_bytes),
    "free_gb" => formatToGB($free_bytes),
    "percentage" => round($percentage, 1)
]);
?>