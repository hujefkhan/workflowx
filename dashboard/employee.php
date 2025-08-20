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
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background-color: #f4f6f8;
            margin: 0;
            padding: 20px;
        }

        h2, h3 {
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

        .message {
            background-color: #e0ffe0;
            border: 1px solid #70db70;
            padding: 10px 15px;
            margin-bottom: 20px;
            color: #155724;
            border-radius: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }

        th, td {
            padding: 12px;
            border: 1px solid #ccc;
            text-align: left;
        }

        th {
            background-color: #007bff;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        button {
            padding: 6px 10px;
            margin: 2px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background-color: #0056b3;
        }

        em {
            color: gray;
        }
    </style>
</head>
<body>

    <h2>Welcome, <?= htmlspecialchars($_SESSION['name']) ?></h2>
    <p><a href="request_leave.php">➕ Request Leave</a> | <a href="../logout.php">Logout</a></p>

    <?php if ($message): ?>
        <div class="message"><?= $message ?></div>
    <?php endif; ?>

    <h3>Your Leave Requests</h3>
    <table>
        <tr>
            <th>Leave Type</th>
            <th>Start</th>
            <th>End</th>
            <th>Reason</th>
            <th>Status</th>
            <th>Requested At</th>
            <th>Action</th>
        </tr>
        <?php if (count($my_leaves) > 0): ?>
            <?php foreach ($my_leaves as $leave): ?>
                <tr>
                    <td><?= htmlspecialchars($leave['leave_type']) ?></td>
                    <td><?= $leave['start_date'] ?></td>
                    <td><?= $leave['end_date'] ?></td>
                    <td><?= htmlspecialchars($leave['reason']) ?></td>
                    <td><strong><?= strtoupper($leave['status']) ?></strong></td>
                    <td><?= $leave['requested_at'] ?></td>
                    <td>
                        <?php if ($leave['status'] === 'pending'): ?>
                            <form method="POST" onsubmit="return confirm('Delete this pending request?')">
                                <input type="hidden" name="delete_id" value="<?= $leave['id'] ?>">
                                <button type="submit">Delete</button>
                            </form>
                        <?php else: ?>
                            <em>Locked</em>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="7">No leave requests found.</td></tr>
        <?php endif; ?>
    </table>

    <h3>Your Tasks</h3>
    <table>
        <tr>
            <th>Title</th>
            <th>Description</th>
            <th>Deadline</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        <?php if (count($tasks) > 0): ?>
            <?php foreach ($tasks as $task): ?>
                <tr>
                    <td><?= htmlspecialchars($task['title']) ?></td>
                    <td><?= htmlspecialchars($task['description']) ?></td>
                    <td><?= $task['deadline'] ?></td>
                    <td><strong><?= strtoupper($task['status']) ?></strong></td>
                    <td>
                        <?php if ($task['status'] === 'pending'): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                <button type="submit">Mark Completed</button>
                            </form>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this task?')">
                                <input type="hidden" name="delete_task_id" value="<?= $task['id'] ?>">
                                <button type="submit">Delete</button>
                            </form>
                        <?php else: ?>
                            <em>Completed</em>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="5">No tasks assigned.</td></tr>
        <?php endif; ?>
    </table>

</body>
</html>
