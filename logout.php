<?php
session_start();


session_unset();
session_destroy();

// Redirect ke login page
header("Location: login.php");
exit();
?>