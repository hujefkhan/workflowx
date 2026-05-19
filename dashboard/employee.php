<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$ai_response = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['delete_id'])) {
        $stmt = $pdo->prepare("DELETE FROM leave_requests WHERE id = ? AND user_id = ? AND status = 'pending'");
        $stmt->execute([$_POST['delete_id'], $user_id]);
        $message = $stmt->rowCount() ? "Leave request deleted successfully." : "⚠️ Cannot delete approved/rejected requests.";
    }

    if (isset($_POST['task_id'])) {
        $stmt = $pdo->prepare("UPDATE tasks SET status = 'completed' WHERE id = ? AND assigned_to = ?");
        $stmt->execute([$_POST['task_id'], $user_id]);
        $message = $stmt->rowCount() ? "✅ Task marked as completed." : "⚠️ Could not update task.";
    }

    if (isset($_POST['overload_task_id'])) {
        $stmt = $pdo->prepare("UPDATE tasks SET status = 'overloaded' WHERE id = ? AND assigned_to = ? AND status IN ('pending','in-progress')");
        $stmt->execute([$_POST['overload_task_id'], $user_id]);
        if ($stmt->rowCount() > 0) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?msg=" . urlencode("⚡ Task marked as overloaded."));
            exit();
        } else {
            $message = "⚠️ Could not mark as overloaded.";
        }
    }

    if (isset($_POST['ai_question'])) {
        $question_raw = trim($_POST['ai_question']);
        $question = strtolower($question_raw);
        $stmt = $pdo->prepare("SELECT * FROM tasks WHERE assigned_to = ? ORDER BY deadline ASC");
        $stmt->execute([$user_id]);
        $tasks_for_ai = $stmt->fetchAll();

        if ((strpos($question, 'how many') !== false || strpos($question, 'count') !== false) && strpos($question, 'task') !== false) {
            $ai_response = "🤖 You currently have <strong>" . count($tasks_for_ai) . "</strong> task(s).";
        } elseif (strpos($question, 'deadline') !== false || strpos($question, 'urgent') !== false) {
            $today = date('Y-m-d');
            $urgent = array_filter($tasks_for_ai, fn($t) => $t['status'] === 'pending' && $t['deadline'] <= date('Y-m-d', strtotime('+3 days')));
            if ($urgent) {
                $ai_response = "🚨 Urgent tasks:<br>";
                foreach ($urgent as $u) {
                    $days_left = round((strtotime($u['deadline']) - strtotime($today)) / 86400);
                    $ai_response .= "• <strong>" . htmlspecialchars($u['title']) . "</strong> — {$u['deadline']} ({$days_left} days left)<br>";
                }
            } else $ai_response = "😊 No urgent tasks.";
        } else $ai_response = "🤖 Ask about tasks, deadlines, or motivation.";
    }
}

if (isset($_GET['msg'])) $message = htmlspecialchars($_GET['msg']);

$my_leaves = $pdo->prepare("SELECT * FROM leave_requests WHERE user_id = ? ORDER BY requested_at DESC");
$my_leaves->execute([$user_id]);
$my_leaves = $my_leaves->fetchAll();

$tasks = $pdo->prepare("SELECT * FROM tasks WHERE assigned_to = ? ORDER BY deadline ASC");
$tasks->execute([$user_id]);
$tasks = $tasks->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Employee Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<style>
body{margin:0;font-family:'Poppins',sans-serif;background:#f5f6fa;color:#111;}
header{background:#2b2d42;color:#fff;padding:16px;display:flex;justify-content:space-between;align-items:center;}
header h1{margin:0;font-size:18px;font-weight:600;}
header nav a{color:#ffd369;text-decoration:none;margin-left:12px;font-weight:500;}
.container{max-width:1000px;margin:20px auto;padding:16px;}
.message{background:#d4edda;color:#155724;padding:12px;border-radius:8px;margin-bottom:16px;}
.stack{display:grid;gap:18px;}
.card{background:#fff;border-radius:12px;padding:16px;box-shadow:0 4px 12px rgba(0,0,0,0.05);}
h3{margin-bottom:12px;font-size:16px;color:#2b2d42;}
.card p{margin:4px 0;color:#6b7280;font-size:14px;}
.card-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;}
.task-title{font-weight:600;color:#2b2d42;}
.status{display:inline-block;padding:6px 10px;border-radius:8px;font-size:12px;font-weight:600;}
.pending{background:#fff3cd;color:#856404;}
.approved{background:#d4edda;color:#155724;}
.rejected{background:#f8d7da;color:#721c24;}
.completed{background:#cce5ff;color:#004085;}
.overloaded{background:#fbe3db;color:#a4161a;}
button{background:#2b2d42;color:#fff;border:none;padding:8px 12px;border-radius:8px;cursor:pointer;}
button:hover{opacity:.95;}
.small{font-size:13px;color:#6b7280;}
.ai-card{background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;padding:18px;border-radius:12px;}
.ai-card input[type="text"]{width:100%;padding:10px;border-radius:10px;border:0;margin-bottom:8px;font-size:14px;}
.ai-response{background:rgba(255,255,255,0.12);padding:10px;border-radius:8px;margin-top:8px;color:#fff;text-align:left;}
.btn-danger{background:#e63946;padding:7px 10px;border-radius:8px;}
.btn-secondary{background:#6b7280;}
.footer{margin-top:18px;padding:12px;color:#6b7280;font-size:13px;text-align:center;}
</style>
</head>
<body>
<header>
<h1>👋 Welcome, <?= htmlspecialchars($_SESSION['name']) ?></h1>
<nav>
<a href="request_leave.php">➕ Request Leave</a>
<a href="../logout.php">🚪 Logout</a>
</nav>
</header>

<div class="container">
<?php if ($message): ?><div class="message"><?= $message ?></div><?php endif; ?>

<div class="stack">
    <div class="card ai-card">
        <h3>🤖 Assistant</h3>
        <form method="POST">
            <input type="text" name="ai_question" placeholder="Ask about tasks, deadlines, conversions..." required>
            <button type="submit">Ask AI</button>
        </form>
        <?php if ($ai_response): ?><div class="ai-response"><?= $ai_response ?></div><?php endif; ?>
    </div>

    <div class="card">
        <h3>Your Leave Requests</h3>
        <?php if ($my_leaves): ?>
        <div class="card-grid">
            <?php foreach ($my_leaves as $leave): ?>
            <div class="card">
                <div class="task-title"><?= htmlspecialchars($leave['leave_type']) ?></div>
                <p><strong>From:</strong> <?= htmlspecialchars($leave['start_date']) ?> — <strong>To:</strong> <?= htmlspecialchars($leave['end_date']) ?></p>
                <p class="small"><strong>Reason:</strong> <?= htmlspecialchars($leave['reason']) ?></p>
                <div><span class="status <?= $leave['status'] ?>"><?= strtoupper($leave['status']) ?></span> <span class="small">Requested: <?= htmlspecialchars($leave['requested_at']) ?></span></div>
                <?php if ($leave['status'] === 'pending'): ?>
                <form method="POST" style="margin-top:6px;" onsubmit="return confirm('Delete this leave request?')">
                    <input type="hidden" name="delete_id" value="<?= $leave['id'] ?>">
                    <button type="submit" class="btn-secondary">🗑 Delete</button>
                </form>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?><p class="small">No leave requests found.</p><?php endif; ?>
    </div>

    <div class="card">
        <h3>Your Tasks</h3>
        <?php if ($tasks): ?>
        <div class="card-grid">
            <?php foreach ($tasks as $task): ?>
            <div class="card">
                <div class="task-title"><?= htmlspecialchars($task['title']) ?></div>
                <p class="small"><?= htmlspecialchars($task['description']) ?></p>
                <p class="small"><strong>Deadline:</strong> <?= htmlspecialchars($task['deadline']) ?></p>
                <div><span class="status <?= $task['status'] ?>"><?= strtoupper($task['status']) ?></span></div>
                <div style="margin-top:8px;">
                    <?php if ($task['status'] === 'pending' || $task['status'] === 'in-progress'): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                        <button type="submit">✅ Complete</button>
                    </form>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Mark this task as overloaded?')">
                        <input type="hidden" name="overload_task_id" value="<?= $task['id'] ?>">
                        <button type="submit" class="btn-danger">⚡ Overload</button>
                    </form>
                    <?php elseif ($task['status'] === 'completed'): ?><span class="small">✅ Completed</span>
                    <?php elseif ($task['status'] === 'overloaded'): ?><span style="color:#e63946;font-weight:600">⚡ Overloaded</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?><p class="small">No tasks assigned.</p><?php endif; ?>
    </div>
</div>

<div class="footer">WorkflowX © 2025 — Employee Dashboard</div>
</div>
</body>
</html>