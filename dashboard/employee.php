<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: ../index.php");
    exit();
}
require_once '../includes/db.php';

$user_id = $_SESSION['user_id'];
$message = '';

// Handle leave delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        $delete_id = $_POST['delete_id'];

        $stmt = $pdo->prepare("DELETE FROM leave_requests WHERE id = ? AND user_id = ? AND status = 'pending'");
        $stmt->execute([$delete_id, $user_id]);

        $message = $stmt->rowCount()
            ? "Leave request deleted successfully."
            : "⚠️ Cannot delete approved/rejected requests.";
    }

    // Handle task completion
    if (isset($_POST['task_id'])) {
        $task_id = $_POST['task_id'];
        $pdo->prepare("UPDATE tasks SET status = 'completed' WHERE id = ? AND assigned_to = ?")
            ->execute([$task_id, $user_id]);
        $message = "✅ Task marked as completed.";
    }

    // Handle task delete
    if (isset($_POST['delete_task_id'])) {
        $task_id = $_POST['delete_task_id'];
        $pdo->prepare("DELETE FROM tasks WHERE id = ? AND assigned_to = ? AND status = 'pending'")
            ->execute([$task_id, $user_id]);
        $message = "🗑️ Task deleted (pending only).";
    }

    // 🔹 Handle task overload request
    if (isset($_POST['overload_task_id'])) {
        $task_id = $_POST['overload_task_id'];
        $pdo->prepare("UPDATE tasks SET status = 'overloaded' WHERE id = ? AND assigned_to = ? AND status = 'pending'")
            ->execute([$task_id, $user_id]);
        $message = "⚡ Task marked as overloaded (escalated to admin).";
    }
}

$stmt = $pdo->prepare("SELECT * FROM leave_requests WHERE user_id = ? ORDER BY requested_at DESC");
$stmt->execute([$user_id]);
$my_leaves = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM tasks WHERE assigned_to = ? ORDER BY deadline ASC");
$stmt->execute([$user_id]);
$tasks = $stmt->fetchAll();
?>
















<!DOCTYPE html>
<html>
<head>
    <title>Employee Dashboard</title>

    <a href="../chat_ai.php">🤖 AI Assistant</a>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f7f8fc, #eaeaea);
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        header {
            background: #2b2d42;
            color: white;
            padding: 20px;
            text-align: center;
            position: relative;
        }

        header h2 {
            margin: 0;
            font-weight: 600;
            font-size: 24px;
        }

        header nav {
            margin-top: 10px;
        }

        header nav a {
            color: #ffd369;
            margin: 0 12px;
            text-decoration: none;
            font-weight: 500;
            transition: 0.3s;
        }

        header nav a:hover {
            color: #fff;
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
        }

        .message {
            background: #d4edda;
            color: #155724;
            padding: 12px 18px;
            border-radius: 6px;
            margin-bottom: 20px;
            animation: slideDown 0.6s ease;
        }

        h3 {
            margin-top: 40px;
            font-size: 22px;
            color: #333;
            border-left: 4px solid #2b2d42;
            padding-left: 10px;
        }

        /* Card Layout */
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
            transition: transform 0.3s;
            animation: fadeInUp 0.8s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card h4 {
            margin: 0 0 10px;
            font-size: 18px;
            color: #2b2d42;
        }

        .card p {
            font-size: 14px;
            color: #555;
            margin: 5px 0;
        }

        .status {
            font-weight: bold;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 12px;
            display: inline-block;
        }

        .pending { background: #fff3cd; color: #856404; }
        .approved { background: #d4edda; color: #155724; }
        .rejected { background: #f8d7da; color: #721c24; }
        .completed { background: #cce5ff; color: #004085; }

        button {
            background: #2b2d42;
            color: white;
            padding: 8px 14px;
            border: none;
            border-radius: 6px;
            margin-top: 10px;
            cursor: pointer;
            font-size: 13px;
            transition: 0.3s;
        }

        button:hover {
            background: #1c1e33;
        }

        em {
            color: gray;
            font-size: 13px;
        }

        /* Doodles */
        .doodle {
            position: absolute;
            width: 80px;
            opacity: 0.2;
            z-index: -1;
        }
        .doodle1 { top: 50px; left: 20px; transform: rotate(-15deg); }
        .doodle2 { bottom: 50px; right: 40px; transform: rotate(20deg); }
        .doodle3 { top: 200px; right: 200px; transform: rotate(-30deg); }

        /* Animations */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-15px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    <header>
        <h2>👋 Welcome, <?= htmlspecialchars($_SESSION['name']) ?></h2>
        <nav>
            <a href="request_leave.php">➕ Request Leave</a>
            <a href="../logout.php">🚪 Logout</a>
        </nav>
    </header>

    <div class="container">
        <?php if ($message): ?>
            <div class="message"><?= $message ?></div>
        <?php endif; ?>

        <!-- Leave Requests -->
        <h3>Your Leave Requests</h3>
        <div class="card-grid">
            <?php if (count($my_leaves) > 0): ?>
                <?php foreach ($my_leaves as $leave): ?>
                    <div class="card">
                        <h4>📌 <?= htmlspecialchars($leave['leave_type']) ?></h4>
                        <p><strong>From:</strong> <?= $leave['start_date'] ?> → <strong>To:</strong> <?= $leave['end_date'] ?></p>
                        <p><strong>Reason:</strong> <?= htmlspecialchars($leave['reason']) ?></p>
                        <span class="status <?= $leave['status'] ?>"><?= strtoupper($leave['status']) ?></span>
                        <p><em>Requested: <?= $leave['requested_at'] ?></em></p>

                        <?php if ($leave['status'] === 'pending'): ?>
                            <form method="POST" onsubmit="return confirm('Delete this request?')">
                                <input type="hidden" name="delete_id" value="<?= $leave['id'] ?>">
                                <button type="submit">🗑️ Delete</button>
                            </form>
                        <?php else: ?>
                            <em>Locked</em>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No leave requests found.</p>
            <?php endif; ?>
        </div>

       <!-- Tasks -->
<h3>Your Tasks</h3>
<div class="card-grid">
    <?php if (count($tasks) > 0): ?>
        <?php foreach ($tasks as $task): ?>
            <div class="card">
                <h4>📝 <?= htmlspecialchars($task['title']) ?></h4>
                <p><?= htmlspecialchars($task['description']) ?></p>
                <p><strong>Deadline:</strong> <?= $task['deadline'] ?></p>
                <span class="status <?= $task['status'] ?>"><?= strtoupper($task['status']) ?></span>

                <?php if ($task['status'] === 'pending'): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                        <button type="submit">✅ Mark Completed</button>
                    </form>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this task?')">
                        <input type="hidden" name="delete_task_id" value="<?= $task['id'] ?>">
                        <button type="submit">🗑️ Delete</button>
                    </form>
                    <!-- 🔹 New Overload Button -->
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Mark this task as overloaded?')">
                        <input type="hidden" name="overload_task_id" value="<?= $task['id'] ?>">
                        <button type="submit" style="background:#e63946;">⚡ Overload</button>
                    </form>
                <?php elseif ($task['status'] === 'completed'): ?>
                    <em>Completed</em>
                <?php elseif ($task['status'] === 'overloaded'): ?>
                    <em style="color:#e63946;">⚡ Overloaded</em>
                <?php else: ?>
                    <em><?= ucfirst($task['status']) ?></em>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No tasks assigned.</p>
    <?php endif; ?>
</div>
    <!-- Doodles -->
    <img src="https://cdn-icons-png.flaticon.com/512/2910/2910768.png" class="doodle doodle1">
    <img src="https://cdn-icons-png.flaticon.com/512/2910/2910761.png" class="doodle doodle2">
    <img src="https://cdn-icons-png.flaticon.com/512/2910/2910780.png" class="doodle doodle3">

</body>
</html>