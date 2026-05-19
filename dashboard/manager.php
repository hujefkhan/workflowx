<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

require_once '../includes/db.php';

// ---------------- Handle POST Actions ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ✅ Handle leave approval/rejection
    if (isset($_POST['request_id'], $_POST['action'])) {
        $request_id = $_POST['request_id'];
        $action = $_POST['action'];

        if (in_array($action, ['approved', 'rejected'])) {
            $stmt = $pdo->prepare("UPDATE leave_requests SET status = ? WHERE id = ?");
            $stmt->execute([$action, $request_id]);
        }
    }

    // 🗑️ Handle task deletion
    if (isset($_POST['delete_task_id'])) {
        $task_id = $_POST['delete_task_id'];
        $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
        $stmt->execute([$task_id]);
    }

    // ⚡ Handle overloaded task actions
    if (isset($_POST['overloaded_task_id'], $_POST['admin_action'])) {
        $task_id = $_POST['overloaded_task_id'];
        $admin_action = $_POST['admin_action'];

        if ($admin_action === 'reassign') {
            // Reset task back to pending (could later extend to choose another employee)
            $stmt = $pdo->prepare("UPDATE tasks SET status = 'pending' WHERE id = ?");
            $stmt->execute([$task_id]);
        } elseif ($admin_action === 'close') {
            // Mark task as closed
            $stmt = $pdo->prepare("UPDATE tasks SET status = 'closed' WHERE id = ?");
            $stmt->execute([$task_id]);
        }
    }
}

// ---------------- Fetch Data ----------------

// 📋 Pending leave requests
$stmt = $pdo->query("
    SELECT lr.*, u.name 
    FROM leave_requests lr 
    JOIN users u ON lr.user_id = u.id 
    WHERE lr.status = 'pending'
    ORDER BY lr.requested_at DESC
");
$leave_requests = $stmt->fetchAll();

// 🕓 Leave history
$history_stmt = $pdo->query("
    SELECT lr.*, u.name 
    FROM leave_requests lr 
    JOIN users u ON lr.user_id = u.id 
    ORDER BY lr.requested_at DESC
");
$leave_history = $history_stmt->fetchAll();

// 🧾 All tasks (except overloaded — keep dashboards clean)
$task_stmt = $pdo->query("
    SELECT t.*, u.name AS employee_name
    FROM tasks t
    JOIN users u ON t.assigned_to = u.id
    WHERE t.status NOT IN ('overloaded')
    ORDER BY t.created_at DESC
");
$tasks = $task_stmt->fetchAll();

// ⚡ Overloaded tasks (main fix)
$overloaded_stmt = $pdo->prepare("
    SELECT t.*, u.name AS employee_name
    FROM tasks t
    JOIN users u ON t.assigned_to = u.id
    WHERE LOWER(t.status) = 'overloaded'
    ORDER BY t.deadline ASC
");
$overloaded_stmt->execute();
$overloaded_tasks = $overloaded_stmt->fetchAll();

// Determine which view to show
$view = isset($_GET['view']) ? $_GET['view'] : 'dashboard';
?>




<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
  <a href="../chat_ai.php">🤖 AI Assistant</a>

  <style>
    body {
      font-family: 'Segoe UI', Tahoma, sans-serif;
      background: #f9f9f9;
      margin: 0;
      padding: 0;
      overflow-x: hidden;
    }

    header {
      background: #111;
      color: white;
      padding: 20px 40px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: sticky;
      top: 0;
      z-index: 10;
      box-shadow: 0 3px 8px rgba(0,0,0,0.2);
    }

    header h2 { margin: 0; }

    header a {
      color: #fff;
      margin-left: 15px;
      text-decoration: none;
      font-weight: bold;
    }

    header a:hover { text-decoration: underline; }

    .container {
      padding: 30px 50px;
      position: relative;
      z-index: 2;
    }

    h3 {
      color: #222;
      margin-top: 40px;
      font-size: 22px;
    }

    /* Card-style tables */
    .card-table {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 20px;
      margin-top: 20px;
    }

    .card {
      background: #fff;
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.08);
      animation: fadeInUp 0.6s ease forwards;
      opacity: 0;
    }

    .card:nth-child(1) { animation-delay: 0.2s; }
    .card:nth-child(2) { animation-delay: 0.4s; }
    .card:nth-child(3) { animation-delay: 0.6s; }
    .card:nth-child(4) { animation-delay: 0.8s; }

    .card h4 {
      margin: 0 0 10px;
      font-size: 18px;
      color: #111;
    }

    .card p {
      margin: 5px 0;
      color: #444;
      font-size: 14px;
    }

    button {
      padding: 6px 12px;
      margin: 5px 3px 0 0;
      background-color: #111;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      transition: transform 0.2s;
    }

    button:hover {
      transform: scale(1.05);
      background: #333;
    }

    /* Animations */
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* Doodles background */
    .doodle {
      position: fixed;
      z-index: 0;
      opacity: 0.08;
    }

    .doodle1 { top: 10%; left: 5%; width: 150px; }
    .doodle2 { bottom: 15%; right: 10%; width: 180px; }
    .doodle3 { top: 50%; right: 40%; width: 120px; }

    /* History Table */
    .history-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
      background: #fff;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .history-table th, .history-table td {
      padding: 12px 15px;
      border-bottom: 1px solid #ddd;
      text-align: left;
    }
    .history-table th {
      background: #111;
      color: white;
    }
    .history-table tr:hover { background: #f1f1f1; }
  </style>
</head>
<body>
  <!-- Header -->
  <header>
    <h2>Welcome Admin, <?= htmlspecialchars($_SESSION['name']) ?></h2>
    <nav>
      <a href="?view=dashboard">🏠 Dashboard</a>
      <a href="?view=history">📜 Leave History</a>
      <a href="assign_task.php">➕ Assign Task</a>
      <a href="add_user.php">👤 Add User</a>
      <a href="../logout.php">Logout</a>
    </nav>
  </header>

  <!-- Background doodles -->
  <img src="https://www.svgrepo.com/show/306500/abstract-doodle.svg" class="doodle doodle1">
  <img src="https://www.svgrepo.com/show/306513/doodle-shape.svg" class="doodle doodle2">
  <img src="https://www.svgrepo.com/show/306508/doodle-line.svg" class="doodle doodle3">

  <!-- Content -->
  <div class="container">
    <?php if ($view === 'history'): ?>
      <h3>📜 Leave History</h3>
      <table class="history-table">
        <tr>
          <th>Employee</th>
          <th>Leave Type</th>
          <th>Dates</th>
          <th>Reason</th>
          <th>Status</th>
          <th>Requested At</th>
        </tr>
        <?php foreach ($leave_history as $lh): ?>
        <tr>
          <td><?= htmlspecialchars($lh['name']) ?></td>
          <td><?= htmlspecialchars($lh['leave_type']) ?></td>
          <td><?= $lh['start_date'] ?> → <?= $lh['end_date'] ?></td>
          <td><?= htmlspecialchars($lh['reason']) ?></td>
          <td><?= strtoupper($lh['status']) ?></td>
          <td><?= $lh['requested_at'] ?></td>
        </tr>
        <?php endforeach; ?>
      </table>

    <?php else: ?>
      <h3>Pending Leave Requests</h3>
      <div class="card-table">
        <?php if (empty($leave_requests)): ?>
          <p>No pending leave requests 🎉</p>
        <?php endif; ?>
        <?php foreach ($leave_requests as $req): ?>
        <div class="card">
          <h4><?= htmlspecialchars($req['name']) ?> (<?= htmlspecialchars($req['leave_type']) ?>)</h4>
          <p><strong>Dates:</strong> <?= $req['start_date'] ?> to <?= $req['end_date'] ?></p>
          <p><strong>Reason:</strong> <?= htmlspecialchars($req['reason']) ?></p>
          <p><strong>Status:</strong> <?= strtoupper($req['status']) ?></p>
          <form method="POST" onsubmit="return confirm('Are you sure?')">
            <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
            <button type="submit" name="action" value="approved">Approve</button>
            <button type="submit" name="action" value="rejected">Reject</button>
          </form>
        </div>
        <?php endforeach; ?>
      </div>

      <h3>Assigned Tasks</h3>
      <div class="card-table">
        <?php foreach ($tasks as $task): ?>
        <div class="card">
          <h4><?= htmlspecialchars($task['title']) ?></h4>
          <p><strong>Employee:</strong> <?= htmlspecialchars($task['employee_name']) ?></p>
          <p><strong>Description:</strong> <?= htmlspecialchars($task['description']) ?></p>
          <p><strong>Deadline:</strong> <?= $task['deadline'] ?></p>
          <p><strong>Status:</strong> <?= strtoupper($task['status']) ?></p>
          <p><em>Assigned at: <?= $task['created_at'] ?></em></p>
          <form method="POST" onsubmit="return confirm('Delete this task?')">
            <input type="hidden" name="delete_task_id" value="<?= $task['id'] ?>">
            <button type="submit">Delete</button>
          </form>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Overloaded Tasks Section -->
      <h3>⚠️ Overloaded Tasks</h3>
      <div class="card-table">
        <?php if (empty($overloaded_tasks)): ?>
          <p>No overloaded tasks 🎉</p>
        <?php endif; ?>
        <?php foreach ($overloaded_tasks as $otask): ?>
        <div class="card" style="border-left: 6px solid crimson;">
          <h4><?= htmlspecialchars($otask['title']) ?></h4>
          <p><strong>Employee:</strong> <?= htmlspecialchars($otask['employee_name']) ?></p>
          <p><strong>Description:</strong> <?= htmlspecialchars($otask['description']) ?></p>
          <p><strong>Deadline:</strong> <?= $otask['deadline'] ?></p>
          <p><strong>Status:</strong> <?= strtoupper($otask['status']) ?></p>
          <p><em>Assigned at: <?= $otask['created_at'] ?></em></p>
          <form method="POST" onsubmit="return confirm('Handle overloaded task?')">
            <input type="hidden" name="overloaded_task_id" value="<?= $otask['id'] ?>">
            <button type="submit" name="admin_action" value="reassign">Reassign</button>
            <button type="submit" name="admin_action" value="close">Close</button>
          </form>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>
