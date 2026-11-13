<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $staff_id = trim($_POST['staff_id']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if ($staff_id === '' || $full_name === '' || $email === '' || $password === '' || $confirm_password === '') {
        $error = "Please fill in all fields";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {

        // Check if Staff ID or Email already exists
        $check = $db->prepare("SELECT * FROM users WHERE staff_id = :staff_id OR email = :email");
        $check->bindValue(':staff_id', $staff_id, SQLITE3_TEXT);
        $check->bindValue(':email', $email, SQLITE3_TEXT);
        $existingUser = $check->execute()->fetchArray(SQLITE3_ASSOC);

        if ($existingUser) {
            if ($existingUser['staff_id'] === $staff_id) {
                $error = "Staff ID already registered";
            } else {
                $error = "Email already registered";
            }
        } else {
            // Hash the password for security (Highly Recommended!)
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert new user
            $stmt = $db->prepare("
                INSERT INTO users (staff_id, full_name, email, password, role, status, created_at)
                VALUES (:staff_id, :full_name, :email, :password, :role, :status, :created_at)
            ");

            $stmt->bindValue(':staff_id', $staff_id, SQLITE3_TEXT);
            $stmt->bindValue(':full_name', $full_name, SQLITE3_TEXT);
            $stmt->bindValue(':email', $email, SQLITE3_TEXT);
            $stmt->bindValue(':password', $hashed_password, SQLITE3_TEXT); // Save hashed password
            $stmt->bindValue(':role', 'user', SQLITE3_TEXT);
            
            // CRITICAL FIX: Set new users to pending (This line was missing!)
            $stmt->bindValue(':status', 'pending', SQLITE3_TEXT); 
            $stmt->bindValue(':created_at', date('Y-m-d H:i:s'), SQLITE3_TEXT);

            if ($stmt->execute()) {
                // 1. Save the message in a Session variable
                $_SESSION['success'] = "Account created successfully. Your request is pending admin approval.";
                
                // 2. Force the browser to redirect to a clean version of the page
                header("Location: signup.php"); 
                exit(); // Stop running the rest of the code
            } else {
                $error = "Failed to create account";
            }
    }
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>EDWH Sign Up</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
/* ================= GLOBAL SETTINGS ================= */
* { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }

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

.wrapper {
    width: 1000px;
    max-width: 100%;
    display: flex;
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 20px 50px rgba(0,0,0,0.5);
}

/* ================= LEFT SIDE: BANK RAKYAT CONTENT ================= */
.left-panel {
    flex: 1;
    padding: 60px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    color: white;
    /* Glass Effect */
    background: rgba(255, 255, 255, 0.1); 
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-right: 1px solid rgba(255, 255, 255, 0.1);
}

.left-panel img { width: 160px; margin-bottom: 40px;  filter: brightness(1.2) drop-shadow(0 0 6px rgba(255,255,255,0.3));}
.auth-label { font-size: 11px; text-transform: uppercase; letter-spacing: 2px; opacity: 0.8; margin-bottom: 10px; }
.left-panel h1 { font-size: 36px; line-height: 1.2; margin-bottom: 20px; }
.left-panel h1 span { color: #2196f3; }
.left-panel p { font-size: 14px; opacity: 0.7; line-height: 1.5; }

.right-panel {
    flex: 1;
    padding: 60px;
    background-color: #24262f; /* Solid dark background */
    position: relative;
}

/* Tell the password wrappers to take up the full width instead of just one grid column */
.right-panel .password-wrapper {
    grid-column: span 2; /* Forces it to use both columns of the grid */
    margin-bottom: 15px;
}

h2 { color: white; font-size: 28px; margin-bottom: 30px; }
h2 span { color: #2196f3; }

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    width: 100%;
}

.input-box {
    position: relative;
    width: 100%;
    margin-bottom: 20px; /* Add breathing room */
}

.input-box input {
    width: 100%;
    padding: 14px 15px;
    border-radius: 10px;
    border: none;
    background: #313442;
    color: white;
    font-size: 13px;
}

button {
    width: 100%;
    padding: 16px;
    margin-top: 10px;
    border: none;
    border-radius: 12px;
    background: #1e88e5;
    color: white;
    font-weight: 600;
    cursor: pointer;
}

.back-login { text-align: right; color: rgba(255,255,255,0.5); font-size: 13px; margin-bottom: 20px; }
.back-login a { color: #2196f3; text-decoration: none; font-weight: 600; }

.password-wrapper {
    grid-column: span 2; /* Forces both password boxes to be full-width */
    position: relative;
    width: 100%;
    margin-bottom: 15px;
}

/* Fix the eye icon positioning */
.toggle-password {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #9CA3AF;
    z-index: 10;
}

.password-requirements {
    display: none; /* Hide until JS toggles it */
    margin-bottom: 20px;
    color: #9CA3AF;
}

/* This class will be added by JS to show it */
.password-requirements.active {
    display: block; 
}

.password-requirements ul {
    list-style: none;
    padding: 0;
    font-size: 14px;
    margin-top: 10px;
}

.password-requirements li {
    color: #6B7280; 
    transition: color 0.3s ease;
}

/* JavaScript will toggle this class to change color to green */
.password-requirements li.valid {
    color: #10B981; /* Success Green */
}

.password-requirements li.valid i {
    color: #10B981;
}

@media(max-width: 800px) { .wrapper { flex-direction: column; } .left-panel { display: none; } }
</style>

<div class="wrapper">
    <div class="left-panel">
        <img src="BR_logo.png" alt="Logo">
        <div class="auth-label">Authorized Access</div>
        <h1>Secure your <span>data</span> inside EDWH, Monitor with <span>ease</span></h1>
        <p>Get started with the most secure system for enterprise data monitoring and management for authorized personnel.</p>
    </div>
    
   <div class="right-panel">
    <h2>Create an account<span></span></h2>
    
    <?php if ($error): ?>
    <div style="color: #ef4444; background: rgba(239, 68, 68, 0.1); padding: 10px; border-radius: 8px; margin-bottom: 15px; font-size: 14px;">
        <?php echo $error; ?>
    </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
    <div style="color: #10b981; background: rgba(16, 185, 129, 0.1); padding: 10px; border-radius: 8px; margin-bottom: 15px; font-size: 14px;">
        <?php echo $_SESSION['success']; ?>
    </div>
    <?php unset($_SESSION['success']); // This is critical: it deletes the message so it doesn't show up again if you refresh a second time ?>
    <?php endif; ?>

    <form method="post">
        <div class="form-grid">
            <div class="input-box"><input type="text" name="staff_id" placeholder="Staff ID" required></div>
            <div class="input-box"><input type="text" name="full_name" placeholder="Full Name" required></div>
        </div>
        <div class="input-box"><input type="email" name="email" placeholder="Email" required></div>
        
        <div class="form-grid">
           <div class="input-box password-wrapper">
    <input type="password" name="password" id="password" placeholder="Password" required>
    <i class="fa fa-eye-slash toggle-password" onclick="togglePassword('password', this)"></i>
</div>

<div class="input-box password-wrapper">
    <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required>
    <i class="fa fa-eye-slash toggle-password" onclick="togglePassword('confirm_password', this)"></i>
</div>
        </div>

        <div class="password-requirements" id="reqBox">
            <p>Password must:</p>
            <ul>
                <li id="len-req"><i class="fa fa-check-circle"></i> Contain 8 to 30 characters</li>
                <li id="case-req"><i class="fa fa-check-circle"></i> Contain both lower and uppercase letters</li>
                <li id="num-req"><i class="fa fa-check-circle"></i> Contain 1 number</li>
                <li id="spec-req"><i class="fa fa-check-circle"></i> Contain 1 special character</li>
            </ul>
        </div>

        <div class="back-login">Already have an account? <a href="login.php">Sign In</a></div>
        <button type="submit">Sign Up</button>
    </form>
</div></div><script src="js/password_handler.js"></script>

</body>
</html>