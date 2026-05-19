<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

if (isset($_POST['add'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    $stmt = $pdo->prepare("
        INSERT INTO users (name, email, password, role)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$name, $email, $password, $role]);

    $success = "User added successfully!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add User</title>

<style>
body {
    font-family: 'Segoe UI', Tahoma, sans-serif;
    background: #f9f9f9;
    margin: 0;
}

/* Header (same as admin) */
header {
    background: #111;
    color: white;
    padding: 20px 40px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 3px 8px rgba(0,0,0,0.2);
}

header a {
    color: #fff;
    text-decoration: none;
    font-weight: bold;
}

/* Page container */
.container {
    display: flex;
    justify-content: center;
    margin-top: 60px;
}

/* Card */
.card {
    background: white;
    width: 420px;
    padding: 30px;
    border-radius: 14px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.12);
    animation: fadeUp 0.6s ease;
}

.card h2 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #111;
    text-align: center;
}

input, select {
    width: 100%;
    padding: 12px;
    margin-bottom: 15px;
    border-radius: 6px;
    border: 1px solid #ccc;
    font-size: 14px;
}

button {
    width: 100%;
    padding: 12px;
    background: #111;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 15px;
    cursor: pointer;
}

button:hover {
    background: #333;
}

.success {
    background: #e6ffef;
    color: #116b3c;
    padding: 10px;
    border-radius: 6px;
    margin-bottom: 15px;
    text-align: center;
}

/* Animation */
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
</head>

<body>

<header>
    <h2>Add New User</h2>
    <a href="manager.php">⬅ Back to Dashboard</a>
</header>

<div class="container">
    <div class="card">
        <?php if (isset($success)): ?>
            <div class="success"><?= $success ?></div>
        <?php endif; ?>

        <h2>👤 Create User</h2>

        <form method="POST">
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email Address" required>
            <input type="password" name="password" placeholder="Password" required>

            <select name="role">
                <option value="employee">Employee</option>
                <option value="admin">Admin</option>
            </select>

            <button type="submit" name="add">Add User</button>
        </form>
    </div>
</div>

</body>
</html>
