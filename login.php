<?php
session_start();
include 'db.php';


$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $input_staff = trim($_POST['staff_id']);
    $input_password = trim($_POST['password']);

    $stmt = $db->prepare("SELECT * FROM users WHERE staff_id = :staff_id");
    $stmt->bindValue(':staff_id', $input_staff, SQLITE3_TEXT);
    $user = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

    if (!$user) {
        $error = "Invalid Staff ID or Password";
    } else {
        // Verify the password
        if (password_verify($input_password, $user['password'])) {
            
            // Check status
            if ($user['status'] === 'pending') {
                $error = "Your access request is currently pending Administrator approval.";
            } elseif ($user['status'] === 'rejected') {
                $error = "Your access request was declined. Please contact your supervisor.";
            } else {
                // SUCCESS! Proceed with login
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['staff_id'] = $user['staff_id'];
                
                // --- SECURITY FEATURE: GENERATE API TOKEN ---
                // Generates a 64-character secure random token for dashboard charts
                if (empty($_SESSION['api_token'])) {
                $_SESSION['api_token'] = bin2hex(random_bytes(32));                }

                // Execute queries normally (SQLite will auto-manage the locks safely)
                $update = $db->prepare("UPDATE users SET last_login_time = :time WHERE id = :id");
                $update->bindValue(':time', date('Y-m-d H:i:s'), SQLITE3_TEXT);
                $update->bindValue(':id', $user['id'], SQLITE3_INTEGER);
                $update->execute();

                $log = $db->prepare("INSERT INTO login_history (user_id, login_time, action) VALUES (:uid, :time, :action)");
                $log->bindValue(':uid', $user['id'], SQLITE3_INTEGER);
                $log->bindValue(':time', date('Y-m-d H:i:s'), SQLITE3_TEXT);
                $log->bindValue(':action', 'login', SQLITE3_TEXT);
                $log->execute();

                // Role-based redirection
                if ($user['role'] === 'admin') {
                    header("Location: admin_dashboard.php");
                } else {
                    header("Location: user_dashboard.php");
                }
                exit();
            }
        } else {
            // Password verify failed
            $error = "Invalid Staff ID or Password";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>EDWH Login</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>

/* ================= BACKGROUND ================= */
body {
    margin: 0;
    font-family: 'Inter', sans-serif;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;

    background:
    linear-gradient(
        120deg,
        rgba(0,0,0,0.55),
        rgba(10,15,25,0.45),
        rgba(0,0,0,0.75)),
        
    url('bank rakyat.jpg') center/cover no-repeat;
}

/* ================= LOGIN WRAPPER ================= */
.wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
}

/* ================= LOGIN CARD ================= */
.card {
    width: 400px;
    padding: 42px;

    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);

    border: 1px solid rgba(255,255,255,0.18);
    border-radius: 28px;

    box-shadow:
        0 35px 90px rgba(0,0,0,0.65),
        inset 0 1px 0 rgba(255,255,255,0.08);

    color: white;
    text-align: center;
    transition: 0.3s ease;
}

.card:hover {
    transform: translateY(-3px);
    box-shadow:
        0 45px 110px rgba(0,0,0,0.75),
        inset 0 1px 0 rgba(255,255,255,0.1);
}

/* ================= LOGO INSIDE CARD ================= */
.card img {
    width: 160px; /* Increased slightly for better clarity */
    margin-bottom: 12px;
    border-radius: 0px; /* Gives the logo block smooth edges */
    filter: brightness(1.2) drop-shadow(0 0 6px rgba(255,255,255,0.3));
}

.card img::after {
    content: "";
    display: block;
    width: 60px;
    height: 1px;
    margin: 15px auto;
    background: rgba(255,255,255,0.2);
}
/* ================= TITLE ================= */
.card h2 {
    margin: 0;
    font-size: 24px;
    font-weight: 600;
}

/* ================= INPUT ================= */
.input-box {
    position: relative;
    margin-top: 18px;
}

.input-box input {
    width: 100%;
    padding: 14px 44px;
    border-radius: 14px;

    border: 1px solid rgba(255,255,255,0.15);
    outline: none;

    background: rgba(255,255,255,0.08);
    color: white;

    font-size: 14px;
    letter-spacing: 0.2px;
    box-sizing: border-box;
}

.input-box input::placeholder {
    color: rgba(255,255,255,0.65);
}

.input-box i.left {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: rgba(255,255,255,0.75);
}

input:-webkit-autofill,
input:-webkit-autofill:hover,
input:-webkit-autofill:focus,
input:-webkit-autofill:active {
    -webkit-box-shadow: 0 0 0 1000px rgba(255,255,255,0.08) inset !important;
    -webkit-text-fill-color: white !important;
    transition: background-color 5000s ease-in-out 0s;
}

.eye {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: rgba(255,255,255,0.75);
}

/* ================= BUTTON ================= */
button {
    width: 100%;
    margin-top: 22px;
    padding: 14px;

    border: none;
    border-radius: 14px;
    background: #2196f3; 
    color: white;

    font-size: 15px;
    font-weight: 600;

    cursor: pointer;
    letter-spacing: 0.3px;
}


/* ================= LINKS ================= */
.links {
    margin-top: 24px; /* Gives more breathing room below the button */
    font-size: 13px; /* Slightly larger for better readability */
    display: flex;
    flex-direction: column; /* This stacks the links vertically */
    align-items: center; /* Centers everything perfectly */
    gap: 12px; /* Adds neat space between the two links */
}

.links a {
    color: white;
    opacity: 0.85;
    text-decoration: none;
}

.links a:hover {
    text-decoration: underline;
    opacity: 1;
}

/* ================= FOOTER ================= */
.footer {
    margin-top: 18px;
    font-size: 11px;
    color: rgba(244, 244, 244, 0.91);
}

/* ================= ERROR ================= */
.error {
    background: rgba(255,0,0,0.15);
    padding: 10px;
    border-radius: 12px;
    margin-bottom: 10px;
    font-size: 12px;
}

.signup-text {
    color: rgba(255,255,255,0.8);
}

.signup-text a {
    color: white;
    font-weight: 600;
    text-decoration: none;
}

.signup-text a:hover {
    text-decoration: underline;
}

/* ================= MOBILE ================= */
@media(max-width: 600px){
    .card {
        width: 90%;
    }
}
.secure-note {
    margin-top: 24px; /* Added more space above */
    text-align: center;
    font-size: 11px;
    letter-spacing: 0.5px;
    opacity: 0.5;
    text-transform: uppercase;
}

</style>
</head>

<body>

<div class="wrapper">

    <div class="card">

        <!-- LOGO INSIDE BOX -->
        <img src="BR_logo.png" alt="Bank Rakyat Logo">
<div style="font-size:10px; opacity:0.6; letter-spacing:2px; margin-bottom:6px;">
    ENTERPRISE DATA SYSTEM
</div>
        <h2>EDWH Monitoring System</h2>

        <!-- SECURITY MICRO TEXT (ADD ONLY) -->
<div style="margin-top:10px;">
    <div style="font-size:12px; opacity:0.75; letter-spacing:0.2px;">
        
    </div>
    
</div>

        <?php if ($error) echo "<div class='error'>$error</div>"; ?>

        <form method="post">

            <div class="input-box">
                <i class="fa fa-user left"></i>
                <input type="Staff ID" name="staff_id" placeholder="Staff ID" required>
            </div>

            <div class="input-box">
                <i class="fa fa-lock left"></i>
                <input type="password" name="password" id="password" placeholder="Password" required>
                <i class="fa fa-eye eye" onclick="togglePassword()"></i>
            </div>

            <button type="submit">Sign In</button>

            <div class="links">
                <a href="#">Forgot Password?</a>
            <div class="signup-text">
            Don't have an account? <a href="signup.php">Create Account</a>
            </div>
            
            </div>
            <div class="secure-note">
    Authorized personnel only
</div>
        </form>

    </div>

    <!-- FOOTER OUTSIDE BOX -->
    <div class="footer">
        © 2026 EDWH Monitoring System. All rights reserved.
    </div>

</div>

<script>
function togglePassword(){
    var pass = document.getElementById("password");
    pass.type = (pass.type === "password") ? "text" : "password";
}
</script>

</body>
</html>