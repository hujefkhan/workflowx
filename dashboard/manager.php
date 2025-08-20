<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}
require_once '../includes/db.php';

// Handle leave actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['request_id'], $_POST['action'])) {
        $request_id = $_POST['request_id'];
        $action = $_POST['action'];

        if ($action === 'approved' || $action === 'rejected') {
            $stmt = $pdo->prepare("UPDATE leave_requests SET status = ? WHERE id = ?");
            $stmt->execute([$action, $request_id]);
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM leave_requests WHERE id = ?");
            $stmt->execute([$request_id]);
        }
    }

    // Handle task deletion
    if (isset($_POST['delete_task_id'])) {
        $task_id = $_POST['delete_task_id'];
        $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
        $stmt->execute([$task_id]);
    }
}

$stmt = $pdo->query("SELECT lr.*, u.name FROM leave_requests lr JOIN users u ON lr.user_id = u.id ORDER BY requested_at DESC");
$leave_requests = $stmt->fetchAll();

$task_stmt = $pdo->query("
    SELECT t.*, u.name AS employee_name
    FROM tasks t
    JOIN users u ON t.assigned_to = u.id
    ORDER BY t.created_at DESC
");
$tasks = $task_stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
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

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
            background: #fff;
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

        form {
            display: inline;
        }

        em {
            color: gray;
        }
    </style>
</head>
<body>
    <h2>Welcome Admin, <?= htmlspecialchars($_SESSION['name']) ?></h2>
    <p>
        <a href="assign_task.php">➕ Assign New Task</a> |
        <a href="../logout.php">Logout</a>
    </p>

    <h3>Leave Requests:</h3>
    <table>
        <tr>
            <th>Employee</th>
            <th>Type</th>
            <th>Dates</th>
            <th>Reason</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        <?php foreach ($leave_requests as $req): ?>
            <tr>
                <td><?= htmlspecialchars($req['name']) ?></td>
                <td><?= htmlspecialchars($req['leave_type']) ?></td>
                <td><?= $req['start_date'] ?> to <?= $req['end_date'] ?></td>
                <td><?= htmlspecialchars($req['reason']) ?></td>
                <td><strong><?= strtoupper($req['status']) ?></strong></td>
                <td>
                    <form method="POST" onsubmit="return confirm('Are you sure?')">
                        <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                        <?php if ($req['status'] === 'pending'): ?>
                            <button type="submit" name="action" value="approved">Approve</button>
                            <button type="submit" name="action" value="rejected">Reject</button>
                        <?php endif; ?>
                        <button type="submit" name="action" value="delete">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h3>Assigned Tasks:</h3>
    <table>
        <tr>
            <th>Employee</th>
            <th>Title</th>
            <th>Description</th>
            <th>Deadline</th>
            <th>Status</th>
            <th>Assigned At</th>
            <th>Action</th>
        </tr>
        <?php foreach ($tasks as $task): ?>
            <tr>
                <td><?= htmlspecialchars($task['employee_name']) ?></td>
                <td><?= htmlspecialchars($task['title']) ?></td>
                <td><?= htmlspecialchars($task['description']) ?></td>
                <td><?= $task['deadline'] ?></td>
                <td><strong><?= strtoupper($task['status']) ?></strong></td>
                <td><?= $task['created_at'] ?></td>
                <td>
                    <form method="POST" onsubmit="return confirm('Delete this task?')">
                        <input type="hidden" name="delete_task_id" value="<?= $task['id'] ?>">
                        <button type="submit">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
