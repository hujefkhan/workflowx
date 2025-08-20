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
<html>
<head>
    <title>WorkFlowX - Login</title>
    <style>
        body { font-family: Arial; background: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .login-box { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px #ccc; width: 320px; }
        h2 { text-align: center; }
        input[type="email"], input[type="password"] {
            width: 100%; padding: 10px; margin-top: 10px; border: 1px solid #ccc; border-radius: 4px;
        }
        input[type="submit"] {
            width: 100%; padding: 10px; margin-top: 15px; background-color: #007BFF; color: white; border: none; border-radius: 4px;
            cursor: pointer;
        }
        .error { color: red; text-align: center; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>WorkFlowX Login</h2>
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="submit" value="Login">
        </form>
    </div>
</body>
</html>
