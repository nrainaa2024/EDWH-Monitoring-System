<?php
// Start the session if it hasn't been started yet
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // If they have no session, kick them back to the login page
    header("Location: login.php");
    exit();
}

// 2. (Optional but recommended for your Admin Approval feature)
// If you track approval status in the session, you can block pending users here:
if (isset($_SESSION['status']) && $_SESSION['status'] !== 'Approved') {
    // Kick them out or redirect them to a "Waiting for Admin Approval" page
    header("Location: pending_approval.php");
    exit();
}
?>