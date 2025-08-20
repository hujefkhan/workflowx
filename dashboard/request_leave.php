<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: ../index.php");
    exit();
}
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $leave_type = $_POST['leave_type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $reason = $_POST['reason'];

    $stmt = $pdo->prepare("INSERT INTO leave_requests (user_id, leave_type, start_date, end_date, reason, status, requested_at)
                           VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
    $stmt->execute([$user_id, $leave_type, $start_date, $end_date, $reason]);

    // ✅ Redirect to employee dashboard after successful request
    header("Location: employee.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Request Leave</title>
</head>
<body>
    <h2>Leave Request Form</h2>
    <p>Welcome, <?= htmlspecialchars($_SESSION['name']) ?></p>

    <form method="POST" action="">
        <label>Leave Type:</label><br>
        <select name="leave_type" required>
            <option value="sick">Sick Leave</option>
            <option value="casual">Casual Leave</option>
            <option value="emergency">Emergency Leave</option>
        </select><br><br>

        <label>Start Date:</label><br>
        <input type="date" name="start_date" required><br><br>

        <label>End Date:</label><br>
        <input type="date" name="end_date" required><br><br>

        <label>Reason:</label><br>
        <textarea name="reason" rows="4" cols="30" required></textarea><br><br>

        <input type="submit" value="Submit Request">
    </form>
</body>
</html>
