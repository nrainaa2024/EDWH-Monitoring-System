<?php
$dbFile = __DIR__ . '/datawarehouse.db';

$db = new SQLite3($dbFile);
$db->busyTimeout(30000);

$db->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    staff_id TEXT UNIQUE,
    full_name TEXT,
    phone_num TEXT,
    email TEXT UNIQUE,
    position TEXT,
    password TEXT,
    last_login_time TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
)");

$db->exec("CREATE TABLE IF NOT EXISTS login_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    login_time TEXT,
    action TEXT
)");
$db->exec("CREATE TABLE IF NOT EXISTS access_requests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    staff_id TEXT,
    full_name TEXT,
    email TEXT,
    position TEXT,
    status TEXT DEFAULT 'pending',
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
)");
?>