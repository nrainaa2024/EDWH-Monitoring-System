<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$dbFile = __DIR__ . '/datawarehouse.db';  // path to your DB

$db = new SQLite3($dbFile);

// CRITICAL LOCK FIX
$db->busyTimeout(30000); // wait longer (30 seconds)

// enable WAL mode (important for IIS)
$db->exec("PRAGMA journal_mode = WAL;");

// reduce locking
$db->exec("PRAGMA synchronous = NORMAL;");
$db->exec("PRAGMA temp_store = MEMORY;");
?>