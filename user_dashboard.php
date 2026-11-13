<?php

// Prevent browser caching for live dashboard
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include 'security_check.php';
include 'db.php';

// Security check: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch user name for the profile box
$user = $db->prepare("SELECT full_name FROM users WHERE id = :id");
$user->bindValue(':id', $_SESSION['user_id'], SQLITE3_INTEGER);
$user_data = $user->execute()->fetchArray(SQLITE3_ASSOC);
$user_name = $user_data['full_name'] ?? "User";

// 2. Count Successful Jobs (ERR_CD is exactly '0')
$success_query = $db->query("SELECT COUNT(*) as total_success FROM stgd_data WHERE err_cd = '0'");
$success_row = $success_query->fetchArray();
$total_success = $success_row['total_success'];

// 3. Count Failed Jobs (ERR_CD is anything other than '0')
$failed_query = $db->query("SELECT COUNT(*) as total_failed FROM stgd_data WHERE err_cd != '0'");
$failed_row = $failed_query->fetchArray();
$total_failed = $failed_row['total_failed'];


?>

<!DOCTYPE html>
<html lang="en">
<head>
    
    <meta charset="UTF-8">
    <title>User Dashboard - ETL Monitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css"/>
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    <link rel="stylesheet" href="theme.css">
    
<style>

* { box-sizing: border-box; }
body { height:100vh; overflow: hidden; }
body {
    font-family: 'Inter', sans-serif;
    background: linear-gradient(400deg, #FFFFFF 0%, #a9c1f5 130%);
    margin: 0;
    color: #005BAC; ;
}


/* ================= SIDEBAR ================= */
.sidebar {
    position: fixed;
    top: 15px;
    left: 20px;
    width: 200px;
    height: calc(100vh - 30px);
    background: rgba(255,255,255,0.35);
    backdrop-filter: blur(14px) saturate(140%);
    border: 1px solid rgba(255,255,255,0.45);
    border-radius: 24px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.06), inset 0 1px 0 rgba(255,255,255,0.35);
    padding: 20px;
    display: flex;
    flex-direction: column;
    z-index: 100;
}

.sidebar img.logo {
    width: 120px;
    margin: 0 auto 20px auto;
    display: block;
    filter: brightness(1.2) drop-shadow(0 0 6px rgba(255,255,255,0.3));
}

/* New class for the structural labels */
.sidebar-label {
    font-size: 11px;
    font-weight: 700;
    color: #94a3b8; 
    margin: 15px 0 10px 16px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.sidebar a, .sidebar .signout {
    display: flex;
    align-items: center;
    gap: 12px; 
    padding: 12px 16px;
    border-radius: 16px;
    margin-bottom: 5px; /* Slightly reduced to fit more items comfortably */
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    color: #475569; 
    background: transparent; 
    cursor: pointer;
    transition: all 0.2s ease;
}

.sidebar .signout {
    margin-top: auto;
    border: none;
    width: 100%;
    text-align: left;
}

.sidebar a:hover, .sidebar .signout:hover,
.sidebar a:active, .sidebar .signout:active,
.sidebar a.active {
    background: #3b83f6 !important; 
    color: #ffffff !important; 
    box-shadow: 0 4px 15px rgba(59, 131, 246, 0.4) !important;
}

.sidebar a:hover i, .sidebar .signout:hover i,
.sidebar a:active i, .sidebar .signout:active i,
.sidebar a.active i {
    color: #ffffff !important;
}

.sidebar a:focus-visible, .sidebar .signout:focus-visible {
    outline: none;
    background: rgba(59, 131, 246, 0.3);
}

.main-wrapper {
    flex: 1;
    margin-left: 260px; 
    width: calc(100% - 260px);
    height: 100vh; 
    padding: 15px 30px; /* This 15px top/bottom strictly matches the left sidebar */
    box-sizing: border-box; 
    display: flex;
    flex-direction: column;
    overflow: hidden; 
}
.top-header { 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    width: 100%; 
    max-width: none; /* Removes the center restriction */
    margin: 0 0 15px 0; /* Aligns to the far left of the main wrapper */
    flex-shrink: 0; 
}

.top-header {
    display: flex;
    justify-content: space-between; 
    align-items: center; /* Aligns text perfectly with the profile button */
    margin-bottom: 15px; /* Tighter gap saves vertical space */
    flex-shrink: 0;
}
.welcome-text { margin: 0; font-size: 25px; font-weight: 700; color: #111827; }
.welcome-sub { margin: 4px 0 0; font-size: 14px; color: #64748b; }



/* 2. Control the layout structure and match the bottom padding boundary */
.dashboard-layout {
    display: flex; 
    gap: 25px;
    flex: 1;
    min-height: 0; 
    overflow: hidden; 
    padding-bottom: 15px; /* Creates the exact bottom gap that mirrors the sidebar */
}

/* 3. Let the center column fit comfortably */
.main-content {
    flex: 1;
    height: 100%;
    overflow-y: auto; 
    overflow-x: hidden;
    padding-right: 10px;
    display: flex;
    flex-direction: column; 
}
.main-content::-webkit-scrollbar { width: 8px; }
.main-content::-webkit-scrollbar-thumb { background: rgba(59,131,246,.35); border-radius: 8px; }

.right-sidebar { 
    width: 275px; 
    height: 100%;
    display: flex;
    flex-direction: column; 
    gap: 12px; 
    overflow: hidden; 
    flex-shrink: 0; 
}


/* ================= PROFILE BOX ================= */
.profile-wrapper {
    position: relative; 
    cursor: pointer;
    display: flex;
    align-items: center;
}

.profile-box {
    display: flex;
    align-items: center;
    gap: 10px;
    position: relative;
    font-size: 12px;
    padding: 8px 14px;
    border-radius: 16px;
    background: rgba(255,255,255,0.55);
    backdrop-filter: blur(18px) saturate(140%);
    border: 1px solid rgba(255,255,255,0.5);
    font-weight: 500;
    transition: all 0.2s ease;
    box-shadow: 0 8px 20px rgba(0,0,0,0.05);
    cursor: pointer;
}

.profile-box:hover {
    background: rgba(59, 131, 246, 1);
    box-shadow: 0 4px 15px rgba(59, 131, 246, 0.6);
    color: #fff;
}

.profile-box:active {
    background: rgba(59, 131, 246, 0.45);
    transform: scale(0.98); 
}

.profile-box:focus {
    outline: none;
    background: rgba(59, 131, 246, 0.3);
}

.avatar {
    width: 34px; height: 34px;
    border-radius: 50%;
    background: #3b83f6;
    color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 12px;
}

/* ==========================================
   PROFILE DROPDOWN MENU
   ========================================== */
.profile-wrapper .dropdown-menu {
    position: absolute !important;
    top: calc(100% + 8px) !important; 
    right: 0 !important;
    left: auto !important; 
    width: 200px !important;
    background: #ffffff !important;
    border-radius: 12px !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1) !important;
    padding: 8px !important; 
    z-index: 10000 !important;
    
    /* THIS IS THE FIX: Forces items to stack top-to-bottom */
    flex-direction: column !important; 
}

/* The List Items (Profile, Settings, Sign Out) */
.profile-wrapper .dropdown-menu a {
    display: flex !important;
    align-items: center !important;
    gap: 12px !important; 
    padding: 10px 16px !important;
    text-decoration: none !important;
    color: #475569 !important; 
    font-size: 13px !important;
    font-weight: 500 !important;
    border-radius: 8px !important; 
    margin-bottom: 4px !important; 
    margin-top: 0 !important; 
    background-color: transparent !important;
    transition: all 0.2s ease !important;
}

.profile-wrapper .dropdown-menu a:last-child {
    margin-bottom: 0 !important; 
}

/* Fix Icon Alignment */
.profile-wrapper .dropdown-menu a i {
    width: 16px !important; 
    text-align: center !important;
    margin-right: 0 !important; 
}

/* Normal Hover State (Floating Light Blue Box) */
.profile-wrapper .dropdown-menu a:hover:not(.logout) {
    background-color: #eff6ff !important; 
    color: #2563eb !important; 
}

/* Sign Out Specific Styling */
.profile-wrapper .dropdown-menu .logout {
    color: #ef4444 !important; 
}

/* Sign Out Hover State (Floating Light Red Box) */
.profile-wrapper .dropdown-menu .logout:hover {
    background-color: #fef2f2 !important; 
    color: #dc2626 !important; 
}


body.sidebar-collapsed .sidebar {
    width: 80px !important;
}
  
body.sidebar-collapsed .sidebar a {
    justify-content: center;
    gap: 0;
}

body.sidebar-collapsed .sidebar .text {
    display: none !important ;
}

body.sidebar-collapsed .logo {
    display: none ;
}

body.sidebar-collapsed .profile-name {
    display: none ;
}

body.sidebar-collapsed .profile-arrow {
    display: none ;
}

/* Snaps the dashboard flush with the mini sidebar when collapsed */
body.sidebar-collapsed .main-wrapper {
    margin-left: 100px; /* 80px sidebar + 20px gap */
    width: calc(100% - 100px);
    transition: margin-left 0.3s ease, width 0.3s ease;
}

.app-container {
    display: flex;
}
/* Fix the top header spacing when collapsed */
body.sidebar-collapsed .top-header {
    padding: 25px 25px 20px 20px; 
}

/* Keep the welcome text on one line */
body.sidebar-collapsed .welcome-text {
    font-size: 20px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Server Capacity */
.server-card {
    background: rgba(255,255,255,0.55);
    backdrop-filter: blur(18px) saturate(140%);
    border: 1px solid rgba(255,255,255,.5);
    border-radius: 16px;
    padding: 12px 16px;
    box-shadow: 0 10px 25px rgba(15, 23, 42, 0.04);
    display: flex;
    flex-direction: column; 
    width: 100%;
    
    /* THIS IS THE FIX: Forces the card to stretch to the exact boundary and stop */
    flex: 1 1 0; 
    justify-content: space-evenly; 
    min-height: 0;
    overflow: hidden; /* Prevents internal text from pushing the boundary down */
    margin: 0;
}
.server-header { 
    font-size: 10px; 
    margin-bottom: 0px; 
    font-weight: 700; 
    color: #1e3a8a; 
    letter-spacing: 0.5px;
    text-transform: uppercase;
    display: flex; 
    justify-content: space-between; 
    align-items: center;
}
.server-header i:last-child { cursor: pointer; }

.server-gb { 
    font-size: 20px; 
    font-weight: 800; 
    color: #3b83f6; /* Deep Bank Rakyat blue to match the image */
    margin-bottom: 0px; 
}

.storage-label { 
    text-align: center; 
    display: block;      /* Forces it to take up its own line */
    width: 100%;         /* Spans the full width of the card */
    font-size: 11px; 
    color: #64748b; 
    font-weight: 600; 
    margin: 5px auto;    /* Centers it and adds a tiny bit of breathing room */
}

.circular-progress {
    width: 75px; 
    height: 75px;
    margin: 5px auto; 
    /* Removed the transform scale so it returns to full size */
    background: radial-gradient(closest-side, #ffffff 72%, transparent 74%),
                conic-gradient(#22c55e 7.6%, #dbe6f7 0);
    border-radius: 50%;
    display: flex; 
    align-items: center; 
    justify-content: center;
    font-size: 13px; 
    font-weight: 800; 
    color: #1e3a8a;
    flex-shrink: 0;
}

.mini-stat-container {
    display: flex;
    gap: 8px;
    margin-top: 0; 
}

.mini-stat-card {
    flex: 1;
    background: rgba(255,255,255,.7);/* Clean white matching the target image */
    border-radius: 10px;
    padding: 8px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.mini-stat-title {
    font-size: 8px;
    font-weight: 600;
    color: #64748b;
    margin-bottom: 2px;
}
.mini-stat-value { 
    font-size: 13px; 
    font-weight: 700; 
    color: #3b83f6; 
    margin-top: 0; 
}
.forecast { text-align: center; margin-top: 14px; font-size: 12px; font-weight: 600; color: #F58220; }

/* Custom Calendar Widget */

.calendar-widget {
    background: rgba(255,255,255,0.65);
    backdrop-filter: blur(18px) saturate(140%);
    border: 1px solid rgba(255,255,255,0.6);
    width: 100%; 
    border-radius: 20px; /* Softer, rounder corners matching Image 2 */
    padding: 20px; /* Generous breathing room inside the card */
    color: #1e3a8a; 
    font-family: inherit;
    box-shadow: 0 10px 30px rgba(15,23,42,0.05);
    flex: 0 0 auto; /* THIS IS KEY: Prevents the calendar from squishing */
    margin: 0; 
}

.calendar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}


.calendar-month-year { font-size: 15px; font-weight: 700; color: #1e3a8a; }

.calendar-nav {
    display: flex;
    gap: 8px;
}

.calendar-nav button {
    background: #f1f5f9;
    border: none;
    width: 22px; height: 22px;
    border-radius: 50%;
    cursor: pointer;
    color: #475569;
    margin-left: 4px;
    font-size: 11px;
}

.calendar-nav button:hover {
    background: #e2e8f0;
    color: #1e293b;
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    text-align: center;
    row-gap: 2px; /* Aggressively reduced from 8px to save height */
}

.calendar-day-names {
    font-size: 10px;
    font-weight: 600;
    color: #94a3b8;
    padding-bottom: 2px;
}

.calendar-date {
    width: 20px; /* Slightly smaller date hitboxes */
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: auto;
    font-size: 11px;
    font-weight: 500;
    border-radius: 50%;
    cursor: pointer;
    color: #334155;
    padding: 0;
}

.calendar-date:hover:not(.empty) {
    background: #f1f5f9;
}

.calendar-date.active {
    background: #3b83f6;
    color: #fff;
    font-weight: 700;
    box-shadow: 0 4px 10px rgba(59,131,246,0.35);
}

.calendar-date.empty {
    cursor: default;
}


.system-dropdown {
    appearance: none;
    -webkit-appearance: none;
    height: 38px; /* Matches the Refresh button height */
    padding: 0px 40px 0px 20px; /* Aligns text vertically with button */
    border-radius: 20px; /* Perfect pill shape */
    border: 1px solid #e2e8f0;
    background-color: #ffffff;
    
    /* Custom clean arrow icon */
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%231e293b' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    background-size: 14px;
    
    font-family: 'Inter', sans-serif;
    font-weight: 600;
    font-size: 13px; /* Matches Refresh button font size */
    color: #1F2937;
    cursor: pointer;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05); /* Matches Refresh button shadow */
    outline: none;
    transition: 0.2s ease;
}

.system-dropdown:hover {
    border-color: #cbd5e1;
}
/* Optional: Clean up the focus outline */
.system-dropdown:focus {
    outline: none;
    border-color: #3b83f6;
}

#all-systems-breakdown {
    background: rgba(255, 255, 255, 0.55) !important; /* Matches calendar background */
    backdrop-filter: blur(18px) saturate(140%);       /* Matches calendar blur */
    border: 1px solid rgba(255, 255, 255, 0.5) !important; /* Matches calendar border */
    border-radius: 20px !important; 
    padding: 20px 24px !important; 
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06) !important;
    margin-top: 0px;
    width: 100%;
}

/* Ensure the header text also matches the calendar's dark blue aesthetic */
#all-systems-breakdown h3 {
    color: #1e3a8a !important; /* Deep blue used in calendar month/year */
}

.breakdown-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
}

.breakdown-table th {
    padding: 12px 10px 12px 0; 
    color: #94a3b8; 
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    border-bottom: 1px solid #e2e8f0; /* Crisp line under the header */
}

.breakdown-table td {
    padding: 10px 10px 10px 0; /* Very tight row height */
    font-size: 13px; /* Slightly smaller text for a sleek look */
    color: #475569;
    border-bottom: none; /* Removed the lines between rows to match your image */
    vertical-align: middle;
}

/* Adds a nice subtle hover effect to the rows */
.breakdown-table tbody tr {
    border-radius: 12px;
    transition: background 0.2s ease;
}
.breakdown-table tbody tr:hover td {
    background: #f8fafc;
}


/* ==========================================
   DROPDOWN STYLING (Matches Reference Image)
   ========================================== */

/* 1. Main Container (Fixed Width) */
.choices {
    width: 180px !important; 
    margin-bottom: 0 !important;
}

/* 2. Main Button Shape (Pill, matches Refresh Button) */
.choices__inner {
    height: 38px !important; 
    min-height: 38px !important;
    padding: 4px 40px 4px 20px !important; 
    border-radius: 20px !important; /* Pill shape */
    display: flex !important;
    align-items: center !important;
    background: #ffffff !important; 
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05) !important;
    transition: all 0.2s ease !important;
}

/* Blue border when clicked/open (Matches image) */
.choices.is-open .choices__inner {
    border: 1px solid #3b83f6 !important; 
}

/* Text inside the main button */
.choices__inner .choices__item {
    color: #475569 !important; 
    font-weight: 500 !important;
    font-size: 13px !important;
    white-space: nowrap !important; /* Forces the text to stay on exactly one line */
    overflow: hidden !important;
    text-overflow: ellipsis !important; /* Adds '...' if the text ever gets too long */
}

/* 3. Solid Blue Triangle Arrow (Matches image) */
.choices[data-type*="select-one"]::after {
    border: none !important;
    width: 0 !important;
    height: 0 !important;
    border-left: 5px solid transparent !important;
    border-right: 5px solid transparent !important;
    border-top: 6px solid #3b83f6 !important; /* Solid blue triangle pointing down */
    right: 18px !important;
    top: 50% !important;
    margin-top: -2px !important;
}

/* Flips triangle upside down when open */
.choices[data-type*="select-one"].is-open::after {
    border-top: none !important;
    border-bottom: 6px solid #3b83f6 !important; 
}

/* 4. The Dropdown Menu Box */
.choices__list--dropdown {
    margin-top: 8px !important; /* Gap below the button */
    background: #ffffff !important;
    border-radius: 12px !important;
    border: 1px solid #93c5fd !important; /* Light blue border from image */
    box-shadow: 0 10px 30px rgba(0,0,0,0.1) !important;
    
    /* This padding pushes the items away from the edges so the highlight "floats" */
    padding: 8px !important; 
    z-index: 1000 !important;
}

/* 5. The List Items (Unselected) */
.choices__list--dropdown .choices__item {
    padding: 10px 16px !important; 
    font-size: 13px !important;
    color: #475569 !important; 
    font-weight: 500 !important;
    border-radius: 8px !important; /* Rounds the corners of the highlight box */
    margin-bottom: 4px !important; /* Gap between items */
    transition: all 0.2s ease !important;
}

/* Remove bottom margin from the very last item */
.choices__list--dropdown .choices__item:last-child {
    margin-bottom: 0 !important;
}

/* 6. Item Hover/Selected State (The Floating Highlight Box) */
.choices__list--dropdown .choices__item--selectable.is-highlighted {
    background-color: #eff6ff !important; /* Light blue background */
    color: #2563eb !important; /* Deeper blue text */
}

/* --- ETL DASHBOARD SECTION --- */
.etl-header-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    width: 100%;
    flex-wrap: wrap;
}

.etl-title {
    margin: 0;
    font-size: 15px;
    font-weight: 700;
    color: #1e3a8a ;
    flex: 1;
    text-align: left;
}

.etl-cards-container {
    display: grid;
    grid-template-columns: repeat(4, 1fr); /* Forces exactly 4 equal columns side-by-side */
    gap: 20px;
    width: 100%;
}
.etl-stat-card {
    width: 100%; 
    height: 170px; /* STRICT HEIGHT: Prevents the card from ever stretching! */
    padding: 15px; /* Slightly tighter padding to make room for the button */
    border-radius: 22px;

    background: rgba(255,255,255,0.55);
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
    border: 1px solid rgba(255,255,255,0.4);

    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;

    position: relative;
    transition: all 0.25s ease;
    box-shadow: 0 10px 30px rgba(15,23,42,0.08);
}

.etl-stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 18px 40px rgba(15,23,42,0.12);
}

.stat-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    margin-bottom: 10px;
    background: rgba(0,91,172,0.08); /* default blue */
    color: #005BAC;
}

.stat-value {
    font-size: 30px;
    font-weight: 800;
    margin-bottom: 5px;
    color: #0B2447;
}

.stat-underline { width: 32px; height: 3px; border-radius: 3px; margin: 10px 0; }

.stat-label {
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.6px;
    text-transform: uppercase;
    color: #64748B;
    text-align: center;
}

.status-text {
    font-size: 20px !important;
}

/* Card variants */
.card-orange { border-left: 6px solid #F58220; }
.card-orange .stat-icon { background: #ffedd5; color: #F58220; }
.card-orange .stat-value { color: #F58220; }
.card-orange .stat-underline { background: #F58220; }

.card-blue { border-left: 6px solid #3b83f6; }
.card-blue .stat-icon { background: #dbeafe; color: #3b83f6; }
.card-blue .stat-value { color: #3b83f6; }
.card-blue .stat-underline { background: #3b83f6; }

.card-green { border-left: 6px solid #22C55E; }
.card-green .stat-icon{ background: #dcfce7; color: #22C55E; }
.card-green .stat-value { color: #22C55E; font-size: 32px; }
.card-green .stat-underline { background: #22C55E; }

.card-yellow { border-left: 6px solid #fbbf24; }
.card-yellow .stat-icon { background: #fef3c7; color: #fbbf24; }
.card-yellow .stat-value { color: #fbbf24; }
.card-yellow .stat-underline { background: #fbbf24; }

.card-red { border-left: 6px solid #ef4444; }
.card-red .stat-icon { background: #fee2e2; color: #ef4444; }
.card-red .stat-value { color: #ef4444; }
.card-red .stat-underline { background: #ef4444; }

/* Gray card for No Record / No Data */
.card-gray { border-left: 6px solid #94a3b8; }
.card-gray .stat-icon { background: #f1f5f9; color: #94a3b8; }
.card-gray .stat-value { color: #94a3b8; }
.card-gray .stat-underline { background: #94a3b8; }

.card-highlight {
    background: linear-gradient(135deg, #71A5FF 0%, #3b83f6 100%);
    color: #fff;
    border: none;
    box-shadow: 0 16px 40px -8px rgba(59,131,246,0.55);
}
.card-highlight .stat-icon{ background: rgba(255,255,255,0.2); color: #fff; }
.card-highlight .stat-value { color: #fff; }
.card-highlight .stat-underline { background: rgba(255,255,255,0.6); }
.card-highlight .stat-label { color: rgba(255,255,255,0.85); }

/* Animation for the refresh button */
@keyframes spin-fast {
    100% { transform: rotate(360deg); }
}
.spin-anim {
    animation: spin-fast 0.5s linear;
}

/* ================= NEW JOB STATUS CARDS ================= */
.status-cards-container {
    display: grid;
    grid-template-columns: 1fr 1fr; /* Puts them side-by-side perfectly */
    gap: 20px;
    margin-top: 25px;
    width: 100%;
}

.status-card {
    border-radius: 20px;
    padding: 24px;
    color: #ffffff;
    position: relative;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    height: 150px;
    box-shadow: 0 12px 30px rgba(0,0,0,0.08);
    overflow: hidden;
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.status-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 18px 40px rgba(0,0,0,0.12);
}

/* Gradients matching your reference image */
.success-card { 
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); 
}
.failed-card { 
    background: linear-gradient(135deg, #fb7185 0%, #ef4444 100%); 
}

.sc-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    z-index: 2;
}

.sc-title {
    font-size: 14px;
    font-weight: 600;
    opacity: 0.95;
    line-height: 1.3;
}

.sc-arrow {
    width: 26px;
    height: 26px;
    border: 1.5px solid rgba(255,255,255,0.4);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    color: #ffffff;
    transition: background 0.2s ease;
}

.status-card:hover .sc-arrow {
    background: rgba(255,255,255,0.2);
}

.sc-body {
    z-index: 2;
    margin-top: auto;
}

.sc-value {
    font-size: 34px;
    font-weight: 800;
    letter-spacing: -0.5px;
}

/* ML Prediction Feature Badge */
.sc-forecast {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    font-weight: 700;
    background: rgba(255, 255, 255, 0.25);
    padding: 5px 10px;
    border-radius: 12px;
    margin-top: 5px;
    backdrop-filter: blur(4px);
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

/* Abstract Background Graphics */
.sc-graphic {
    position: absolute;
    right: 20px;
    bottom: 20px;
    width: 100px;
    height: 40px;
    z-index: 1;
}

.sc-donut {
    width: 70px;
    height: 70px;
    border: 12px solid rgba(255,255,255,0.2);
    border-top-color: #ffffff;
    border-radius: 50%;
    position: absolute;
    right: 20px;
    bottom: 10px;
    z-index: 1;
    transform: rotate(45deg);
}

</style>
</head>
<body>
    
        
        <!-- Sidebar -->
    `   <div class="sidebar">
        <img src="BR_logo.png" class="logo" alt="Logo">

        <!-- MENU SECTION -->
        <div class="sidebar-label">MENU</div>
        <a href="user_dashboard.php" class="active">
            <i class="fa-solid fa-border-all"></i>
            <span class="text">Dashboard</span>
        </a>
        <a href="history_report.php" >
            <i class="fa-solid fa-clock-rotate-left"></i>
            <span class="text">Report</span>
        </a>
        <a href="analytic.php">
            <i class="fa-solid fa-chart-simple"></i>
            <span class="text">Analytic</span>
        </a>
        <a href="tablespace.php">
            <i class="fa-solid fa-database"></i>
            <span class="text">Tablespace</span>
        </a>

        <!-- OTHERS SECTION -->
        <div class="sidebar-label">OTHERS</div>
        <a href="profile.php">
            <i class="fa-regular fa-user"></i>
            <span class="text">Account</span>
        </a>
            <a href="settings.php">
            <i class="fa-solid fa-gear"></i>
            <span class="text">Settings</span>
        </a>

        <!-- SIGN OUT -->
        <div class="signout" onclick="window.location.href='logout.php'">
            <i class="fa fa-right-from-bracket"></i>
            <span class="text">Sign Out</span>
        </div>
    </div>

    <div class="app-container"></div>

        <div class="main-wrapper">
    
            <div class="top-header">
                <div>
                    <h2 class="welcome-text">My Dashboard</h2>
                    <p class="welcome-sub">Welcome back, have a great day!</p>
                </div>

                <div class="profile-wrapper" onclick="toggleDropdown(event)">
                    <div class="profile-box">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_name); ?>&background=2A4E9E&color=fff" class="avatar">
                        <span class="profile-name"><?php echo htmlspecialchars($user_name); ?></span>
                        <i class="fa fa-chevron-down profile-arrow"></i>
                    </div>
                    
                    <div class="dropdown-menu" id="dropdownMenu">
                        <a href="profile.php"><i class="fa fa-user"></i> Profile</a>
                        <a href="settings.php"><i class="fa fa-gear"></i> Settings</a>
                        <a href="logout.php" class="logout"><i class="fa fa-right-from-bracket"></i> Sign out</a>
                    </div>
                </div>
            </div>

            <!-- Removed inline flex styles so grid works properly -->
            <div class="dashboard-layout">
                
                <div class="main-content">
                
                    <div class="etl-header-row">
            <div style="display: flex; flex-direction: column; align-items: flex-start; text-align: left; min-width: max-content;">
                <h3 class="etl-title" style="margin: 0; white-space: nowrap;">List of Data Files</h3>
                <div style="font-size: 10px; color: #64748B; font-weight: 600; margin-top: 5px; white-space: nowrap;">
                    System Last Executed: <span id="last-exec-time" style="color: #005BAC;">No Record Found</span>
                </div>
            </div>

            <div style="display: flex; gap: 15px; align-items: center;">
                <select id="system-selector" class="system-dropdown" onchange="updateDashboardCards()">
                    <option value="SIIBS">SIIBS</option>
                    <option value="FOS">FOS</option>
                    <option value="CC">CC</option>
                    <option value="ALL">ALL DATAFILE</option>
                </select>

                <button id="execute-now-btn" style="padding: 8px 15px; background-color: #3b82f6; color: white; border: none; border-radius: 20px; cursor: pointer; font-weight: 600; font-size: 13px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); transition: 0.2s ease;">
                    <i class="fa fa-sync" style="margin-right: 5px;"></i> Refresh
                </button>
            </div>
        </div>
                                    
                    <div class="etl-cards-container">
                        
                        <div class="etl-stat-card card-orange">
                            <div class="stat-icon"><i class="fa-solid fa-file-lines"></i></div>
                            <div class="stat-value" id="val-data">0</div>
                            <div class="stat-underline"></div>
                            <div class="stat-label">Total Data</div>
                        </div>

                        <div class="etl-stat-card card-blue">
                            <div class="stat-icon"><i class="fa-solid fa-database"></i></div>
                            <div class="stat-value" id="val-backup">0</div>
                            <div class="stat-underline"></div>
                            <div class="stat-label">Total Backup</div>
                        </div>

                        <div class="etl-stat-card card-highlight">
                            <div class="stat-icon"><i class="fa-solid fa-bullseye"></i></div>
                            <div class="stat-value" id="val-fixed">0</div>
                            <div class="stat-underline"></div>
                            <div class="stat-label">Data Target</div>
                        </div>

                       <div id="remark-card-container" class="etl-stat-card card-green">
                            <div class="stat-icon"><i id="remark-icon" class="fa-solid fa-check"></i></div>
                            <div class="stat-value status-text" id="val-remark">Loading</div>
                            <div class="stat-underline"></div>
                            <div class="stat-label">Remarks</div>
                        </div>
                    </div>

                 <!-- The System Breakdown Table -->
                    <div id="all-systems-breakdown" style="display: none;">
                        <h3 style="margin-top: 0; margin-bottom: 24px; color: #334155; font-size: 15px; font-weight: 800;">System Breakdown</h3>
                        <table class="breakdown-table">
                            <thead>
                                <tr>
                                    <th>System</th>
                                    <th>Total Data</th>
                                    <th>Backup</th>
                                    <th>Target</th>
                                    <th>Status</th>
                                    <th style="text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="breakdown-tbody">
                            </tbody>
                        </table>
                    </div>
<!-- JOB STATUS CARDS -->
<div class="status-cards-container">
    
    <!-- Success Card -->
    <div class="status-card success-card" onclick="document.getElementById('successModal').style.display='block'" style="cursor: pointer;">
        <div class="sc-header">
            <span class="sc-title">Successful Data Loads</span>
            <div class="sc-arrow"><i class="fa-solid fa-chevron-right"></i></div>
        </div>
        <div class="sc-body">
            
            <div class="sc-value" id="live-success"><?php echo $total_success; ?></div>
            
            <?php if ($total_success > 0 && $total_failed == 0): ?>
                <div class="sc-forecast" id="success-badge"><i class="fa-solid fa-circle-check"></i> Optimal Execution</div>
            <?php elseif ($total_success > 0 && $total_failed > 0): ?>
                <div class="sc-forecast" id="success-badge" style="background: rgba(255, 255, 255, 0.95); color: #d97706;"><i class="fa-solid fa-circle-exclamation"></i> Completed With Errors</div>
            <?php else: ?>
                <div class="sc-forecast" id="success-badge" style="background: rgba(255,255,255,0.4);"><i class="fa-solid fa-circle-pause"></i> No Data Loaded</div>
            <?php endif; ?>

        </div>
        <svg class="sc-graphic" viewBox="0 0 100 50" preserveAspectRatio="none">
            <rect x="10" y="30" width="12" height="20" rx="3" fill="rgba(255,255,255,0.3)" />
            <rect x="35" y="20" width="12" height="30" rx="3" fill="rgba(255,255,255,0.5)" />
            <rect x="60" y="10" width="12" height="40" rx="3" fill="rgba(255,255,255,0.7)" />
            <rect x="85" y="0"  width="12" height="50" rx="3" fill="rgba(255,255,255,0.95)" />
        </svg>
    </div>

    <!-- Failed Card -->
    <div class="status-card failed-card" onclick="document.getElementById('errorModal').style.display='block'" style="cursor: pointer;">
        <div class="sc-header">
            <span class="sc-title">Failed Data Loads</span>
            <div class="sc-arrow"><i class="fa-solid fa-chevron-right"></i></div>
        </div>
        <div class="sc-body">
            
            <div class="sc-value" id="live-failed"><?php echo $total_failed; ?></div>
            
            <?php if ($total_failed == 0): ?>
                <div class="sc-forecast" id="failed-badge"><i class="fa-solid fa-shield-halved"></i> System Healthy</div>
            <?php else: ?>
                <div class="sc-forecast" id="failed-badge" style="background: rgba(255, 200, 0, 0.9); color: #000;"><i class="fa-solid fa-triangle-exclamation"></i> Action Required</div>
            <?php endif; ?>
            
        </div>
        <svg class="sc-graphic" viewBox="0 0 100 50" preserveAspectRatio="none">
            <path d="M0,25 H30 L40,5 L50,45 L60,25 H100" fill="none" stroke="rgba(255,255,255,0.5)" stroke-width="4" stroke-linejoin="round" stroke-linecap="round"/>
        </svg>
    </div>
    
</div> 
</div> <!-- CRITICAL FIX: The missing extra closing div for your layout -->

                <!-- Removed negative margins to keep alignment clean -->
                <div class="right-sidebar">
                    
                    <div class="calendar-widget">
                        <div class="calendar-header">
                            <div class="calendar-month-year" id="calendar-month-year">Loading...</div>
                            <div class="calendar-nav">
                                <button id="prev-month"><i class="fa fa-chevron-left"></i></button>
                                <button id="next-month"><i class="fa fa-chevron-right"></i></button>
                            </div>
                        </div>
                        
                        <div class="calendar-grid calendar-day-names">
                            <div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div><div>Sun</div>
                        </div>
                        
                        <div class="calendar-grid" id="calendar-dates">
                        </div>
                    </div>
                    
                    <div class="server-card">
                        <div class="server-header">
                            <span><i class="fa-solid fa-server"></i> SERVER CAPACITY</span>
                            <i id="refresh-storage-btn" class="fa-solid fa-arrows-rotate" style="cursor: pointer;"></i>
                        </div>
                        
                        <div id="storage-left-text" class="server-gb">2.71 GB Left</div>
                        
                        <div id="storage-bar" class="circular-progress">
                            <span id="storage-used-percent">7.6%</span>
                        </div>
                        
                        <div class="storage-label">Storage Utilization</div>

                        <div class="mini-stat-container">
                            <div class="mini-stat-card">
                                <div class="mini-stat-title">Used Storage</div>
                                <div id="storage-used-gb" class="mini-stat-value">0.22 GB</div>
                            </div>
                            <div class="mini-stat-card">
                                <div class="mini-stat-title">Total Capacity</div>
                                <div id="storage-total-gb" class="mini-stat-value">2.92 GB</div>
                            </div>
                        </div>

                        <div id="capacity-forecast" style="color: #f97316; font-size: 11px; font-weight: 600; text-align: center; margin-top: 10px;">
                            Calculating forecast...
                        </div>
                    </div>
                    <!-- ==========================================
                         NOTIFICATION MODALS 
                         ========================================== -->

                    <!-- 1. ERROR NOTIFICATION MODAL (Hidden by default) -->
                    <div id="errorModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center;">
                        
                        <div style="background: #ffffff; width: 450px; max-width: 90%; border-radius: 20px; padding: 30px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); position: relative; margin: 10% auto;">
                            
                            <!-- Modal Header -->
                            <h3 style="margin-top: 0; color: #ef4444; font-size: 18px; display: flex; align-items: center; gap: 10px;">
                                <i class="fa-solid fa-triangle-exclamation"></i> Action Required: Failed Loads
                            </h3>
                            <p style="color: #64748b; font-size: 14px; margin-bottom: 20px;">The following data mappings encountered an error and require immediate attention.</p>
                            
                            <!-- List of Errors from Database -->
                            <div style="max-height: 250px; overflow-y: auto; margin-bottom: 25px; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px;">
                                <?php 
                                // Fetch ONLY the records where err_cd is NOT 0
                                $error_query = $db->query("SELECT MAPPING_NAME, ERR_CD FROM stgd_data WHERE ERR_CD != '0'");
                                
                                // Loop through and display them
                                while($err_row = $error_query->fetchArray()): 
                                ?>
                                    <div style="display: flex; justify-content: space-between; padding: 12px; border-bottom: 1px solid #f1f5f9;">
                                        <span style="font-weight: 700; color: #334155; font-size: 13px;">
                                            <?php echo htmlspecialchars($err_row['MAPPING_NAME'] ?? ''); ?>
                                        </span>
                                        <span style="background: #fee2e2; color: #dc2626; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 700;">
                                            Error Code: <?php echo htmlspecialchars($err_row['ERR_CD'] ?? ''); ?>
                                        </span>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                            
                            <!-- Modal Buttons -->
                            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                                <button onclick="document.getElementById('errorModal').style.display='none'" style="padding: 10px 16px; border: none; background: #f1f5f9; color: #475569; border-radius: 10px; font-weight: 600; cursor: pointer; transition: 0.2s;">
                                    Dismiss
                                </button>
                                
                                <a href="action_queue.php" style="padding: 10px 16px; background: #ef4444; color: #ffffff; text-decoration: none; border-radius: 10px; font-weight: 600; transition: 0.2s;">
                                    View Full Details
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- 2. SUCCESS NOTIFICATION MODAL (Hidden by default) -->
                    <div id="successModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center;">
                        
                        <div style="background: #ffffff; width: 450px; max-width: 90%; border-radius: 20px; padding: 30px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); position: relative; margin: 10% auto;">
                            
                            <!-- Modal Header -->
                            <h3 style="margin-top: 0; color: #16a34a; font-size: 18px; display: flex; align-items: center; gap: 10px;">
                                <i class="fa-solid fa-circle-check"></i> Successful Data Loads
                            </h3>
                            <p style="color: #64748b; font-size: 14px; margin-bottom: 20px;">The following data mappings were executed successfully without any errors.</p>
                            
                            <!-- List of Successes from Database -->
                            <div style="max-height: 250px; overflow-y: auto; margin-bottom: 25px; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px;">
                                <?php 
                                // Fetch ONLY the records where err_cd IS 0 (Success)
                                $success_list_query = $db->query("SELECT MAPPING_NAME FROM stgd_data WHERE ERR_CD = '0'");
                                
                                // Loop through and display them
                                while($succ_row = $success_list_query->fetchArray()): 
                                ?>
                                    <div style="display: flex; justify-content: space-between; padding: 12px; border-bottom: 1px solid #f1f5f9;">
                                        <span style="font-weight: 700; color: #334155; font-size: 13px;">
                                            <?php echo htmlspecialchars($succ_row['MAPPING_NAME'] ?? ''); ?>
                                        </span>
                                        <span style="background: #dcfce7; color: #16a34a; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 700;">
                                            <i class="fa-solid fa-check" style="margin-right: 4px;"></i> Success
                                        </span>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                            
                            <!-- Modal Buttons -->
                            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                                <button onclick="document.getElementById('successModal').style.display='none'" style="padding: 10px 16px; border: none; background: #f1f5f9; color: #475569; border-radius: 10px; font-weight: 600; cursor: pointer; transition: 0.2s;">
                                    Dismiss
                                </button>
                            </div>
                            
                        </div>
                    </div>
                    <!-- ================= END MODALS ================= -->
                            
                        </div>
                    </div>
                </div>
            </div> <!-- End Dashboard Layout -->
        </div> <!-- End Main Wrapper -->
    </div> <!-- End App Container -->

    <!-- BULLETPROOF INLINE REFRESH SCRIPT -->
<script>
    // 1. Auto-Refresh Timer (60 seconds) with Cache-Busting
    setTimeout(function() {
        window.location.href = window.location.pathname + "?t=" + new Date().getTime();
    }, 60000); 
</script>

    <script src="js/global.js"></script>
    <script src="js/user_dashboard.js"></script>
 
</body>
</html>