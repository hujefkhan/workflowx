<?php
session_start();
require_once 'includes/db.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    echo "<strong>Entered Email:</strong> $email<br>";
    echo "<strong>Entered Password:</strong> $password<br>";

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        echo "✅ Email found in DB<br>";
        echo "🔐 DB hash: " . $user['password'] . "<br>";

        if (password_verify($password, $user['password'])) {
            echo "✅ Password matched<br>";

            // Store session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];

            echo "✅ Logged in as " . $_SESSION['role'] . "<br>";

            // Redirect based on role
            if ($user['role'] === 'admin') {
                header("Location: dashboard/manager.php");
            } else {
                header("Location: dashboard/employee.php");
            }
            exit;
        } else {
            echo "❌ Password does NOT match<br>";
            $error = "Invalid email or password.";
        }
    } else {
        echo "❌ No user found with that email<br>";
        $error = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>WorkFlowX - Login</title>
<style>
    body {
        margin: 0;
        height: 100vh;
        display: flex;
        font-family: "Segoe UI", sans-serif;
        overflow: hidden;
        background: #111;
        color: white;
    }

    /* Split layout */
    .left {
        flex: 1;
        background: linear-gradient(135deg, #222, #111);
        position: relative;
        display: flex;
        justify-content: center;
        align-items: center;
        overflow: hidden;
    }

    .right {
        flex: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        background: linear-gradient(135deg, #111, #222);
    }

    /* Doodles (floating decorations) */
    .doodle {
        position: absolute;
        opacity: 0.2;
        stroke: white;
        fill: none;
    }
    svg {
        stroke-width: 2;
    }

    /* Animations */
    @keyframes floaty {
        0%   { transform: translateY(0) rotate(0deg);}
        50%  { transform: translateY(-25px) rotate(10deg);}
        100% { transform: translateY(0) rotate(0deg);}
    }
    @keyframes spin {
        0%   { transform: rotate(0deg);}
        100% { transform: rotate(360deg);}
    }
    @keyframes twinkle {
        0%,100% { opacity: 0.2; transform: scale(1);}
        50% { opacity: 0.8; transform: scale(1.3);}
    }
    @keyframes wave {
        0% { transform: translateX(0);}
        50% { transform: translateX(15px);}
        100% { transform: translateX(0);}
    }

    /* Place different doodles */
    .d1 { top: 15%; left: 10%; animation: floaty 12s infinite ease-in-out; }
    .d2 { top: 70%; left: 20%; animation: spin 20s linear infinite; }
    .d3 { top: 30%; right: 15%; animation: twinkle 3s infinite; }
    .d4 { bottom: 15%; right: 10%; animation: floaty 15s infinite ease-in-out; }
    .d5 { bottom: 30%; left: 40%; animation: wave 6s infinite ease-in-out; }
    .d6 { top: 50%; left: 70%; animation: spin 10s linear infinite; }
    .d7 { top: 10%; right: 30%; animation: twinkle 4s infinite; }
    .d8 { bottom: 5%; left: 15%; animation: floaty 18s infinite ease-in-out; }

    /* Title */
    .left h1 {
        font-size: 50px;
        font-weight: bold;
        z-index: 2;
        animation: slideDown 1.2s ease;
    }
    @keyframes slideDown {
        from {opacity: 0; transform: translateY(-50px);}
        to {opacity: 1; transform: translateY(0);}
    }

    /* Glassmorphism login panel */
    .login-box {
        background: rgba(255,255,255,0.05);
        backdrop-filter: blur(12px);
        border-radius: 16px;
        padding: 40px;
        width: 350px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.6);
        animation: slideUp 1.2s ease;
    }

    @keyframes slideUp {
        from {opacity: 0; transform: translateY(50px);}
        to {opacity: 1; transform: translateY(0);}
    }

    .login-box h2 {
        margin-bottom: 25px;
        font-size: 26px;
        font-weight: bold;
        text-align: center;
    }

    input[type="email"], 
    input[type="password"] {
        width: 100%;
        padding: 12px;
        margin: 12px 0;
        border: none;
        border-radius: 8px;
        background: rgba(255,255,255,0.1);
        color: #fff;
        font-size: 15px;
        transition: 0.3s;
        outline: none;
    }

    input[type="email"]:focus, 
    input[type="password"]:focus {
        background: rgba(255,255,255,0.2);
        transform: scale(1.02);
    }

    input[type="submit"] {
        width: 100%;
        padding: 12px;
        margin-top: 18px;
        background: #fff;
        color: #111;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 15px;
        font-weight: bold;
        transition: all 0.3s;
    }

    input[type="submit"]:hover {
        background: #ddd;
        transform: scale(1.05);
    }

    .error {
        color: #ff4d4d;
        margin-top: 10px;
        text-align: center;
        font-size: 14px;
    }

    .extra-links {
        margin-top: 15px;
        font-size: 13px;
        text-align: center;
    }

    .extra-links a {
        color: #fff;
        text-decoration: none;
    }
    .extra-links a:hover {
        text-decoration: underline;
    }
</style>
</head>
<body>
    <div class="left">
        <h1>WorkFlowX</h1>

        <!-- Doodles -->
        <svg class="doodle d1" width="60" height="60"><circle cx="30" cy="30" r="20"/></svg>
        <svg class="doodle d2" width="80" height="80"><polygon points="40,10 70,70 10,70"/></svg>
        <svg class="doodle d3" width="30" height="30"><circle cx="15" cy="15" r="5"/></svg>
        <svg class="doodle d4" width="100" height="40"><line x1="10" y1="20" x2="90" y2="20" stroke-dasharray="5,5"/></svg>
        <svg class="doodle d5" width="120" height="60"><path d="M10 30 Q 60 5, 110 30 T 210 30"/></svg>
        <svg class="doodle d6" width="70" height="70"><path d="M10 35 L60 10 L60 60 Z"/></svg>
        <svg class="doodle d7" width="50" height="50"><path d="M25 5 L30 20 L45 20 L33 30 L38 45 L25 35 L12 45 L17 30 L5 20 L20 20 Z"/></svg>
        <svg class="doodle d8" width="80" height="80"><circle cx="40" cy="40" r="30" stroke-dasharray="5,10"/></svg>
    </div>

    <div class="right">
        <div class="login-box">
            <h2>Login</h2>
            <?php if ($error): ?>
                <div class="error"><?= $error ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <input type="email" name="email" placeholder="Email address" required>
                <input type="password" name="password" placeholder="Password" required>
                <input type="submit" value="Login">
            </form>
            <div class="extra-links">
                <p><a href="#">Forgot Password?</a> | <a href="#">Create Account</a></p>
            </div>
        </div>
    </div>
</body>
</html>