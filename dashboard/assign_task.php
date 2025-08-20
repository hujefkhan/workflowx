<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}
require_once '../includes/db.php';

// Fetch all employees
$employees = $pdo->query("SELECT id, name FROM users WHERE role = 'employee' ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $deadline = $_POST['deadline'];
    $assigned_to = $_POST['assigned_to'];
    $assigned_by = $_SESSION['user_id'];

    $stmt = $pdo->prepare("INSERT INTO tasks (title, description, deadline, assigned_to, assigned_by, status, created_at)
                           VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
    $stmt->execute([$title, $description, $deadline, $assigned_to, $assigned_by]);

    // Redirect to admin dashboard
    header("Location: manager.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Assign Task</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f2f4f8;
            margin: 0;
            padding: 20px;
        }

        h2 {
            color: #333;
        }

        a {
            text-decoration: none;
            color: #007bff;
            margin-right: 10px;
        }

        a:hover {
            text-decoration: underline;
        }

        form {
            background: #ffffff;
            padding: 20px;
            border-radius: 10px;
            width: 400px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #444;
        }

        input[type="text"],
        input[type="date"],
        textarea,
        select {
            width: 100%;
            padding: 8px 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        input[type="submit"] {
            background-color: #28a745;
            color: white;
            padding: 10px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <h2>Assign Task</h2>
    <p><a href="manager.php">← Back to Dashboard</a> | <a href="../logout.php">Logout</a></p>

    <form method="POST" action="">
        <label>Title:</label>
        <input type="text" name="title" required>

        <label>Description:</label>
        <textarea name="description" rows="4" required></textarea>

        <label>Deadline:</label>
        <input type="date" name="deadline" required>

        <label>Assign To:</label>
        <select name="assigned_to" required>
            <option value="">-- Select Employee --</option>
            <?php foreach ($employees as $emp): ?>
                <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <input type="submit" value="Assign Task">
    </form>
</body>
</html>
