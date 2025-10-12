<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
require_once __DIR__ . '/includes/db.php';

// Handle AI request
$response_text = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $user_message = trim($_POST['message']);

    if (!empty($user_message)) {
        // Build prompt (you can enhance this with DB info later)
        $prompt = "You are WorkflowX assistant. User role: " . $_SESSION['role'] . 
                  ". User name: " . $_SESSION['name'] . ".\n\n" .
                  "User message: " . $user_message;

        // OpenAI API key (⚠️ replace with your real one below)
        $api_key = "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";

        // Call OpenAI API
        $ch = curl_init("https://api.openai.com/v1/chat/completions");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer " . $api_key
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            "model" => "gpt-4o-mini",
            "messages" => [
                ["role" => "system", "content" => "You are an AI assistant for WorkflowX. Be concise and professional."],
                ["role" => "user", "content" => $prompt]
            ],
            "max_tokens" => 200
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $api_response = curl_exec($ch);

        if ($api_response === false) {
            $response_text = "⚠️ Error: " . curl_error($ch);
        } else {
            $result = json_decode($api_response, true);
            $response_text = $result['choices'][0]['message']['content'] ?? "⚠️ AI did not respond.";
        }

        curl_close($ch);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>WorkflowX AI Assistant</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f4f4f9; margin:0; padding:0; }
    .chat-container { width: 70%; max-width: 800px; margin: 50px auto; background: #fff; padding: 20px;
                      border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
    h2 { margin-top: 0; }
    .messages { max-height: 400px; overflow-y: auto; padding: 10px; border: 1px solid #ddd; border-radius: 8px; }
    .user { background: #111; color: #fff; padding: 8px 12px; border-radius: 8px; margin: 8px 0; text-align: right; }
    .ai { background: #f0f0f0; color: #000; padding: 8px 12px; border-radius: 8px; margin: 8px 0; text-align: left; }
    form { display: flex; margin-top: 15px; }
    input[type="text"] { flex: 1; padding: 10px; border-radius: 6px; border: 1px solid #ccc; }
    button { padding: 10px 20px; margin-left: 10px; border: none; border-radius: 6px; background: #111; color: white; cursor: pointer; }
    button:hover { background: #333; }
  </style>
</head>
<body>
  <div class="chat-container">
    <h2>🤖 WorkflowX AI Assistant</h2>
    <div class="messages">
      <?php if (!empty($_POST['message'])): ?>
        <div class="user"><?= htmlspecialchars($_POST['message']) ?></div>
        <div class="ai"><?= nl2br(htmlspecialchars($response_text)) ?></div>
      <?php else: ?>
        <p style="color:#777;">Start the conversation by typing below 👇</p>
      <?php endif; ?>
    </div>

    <form method="POST">
      <input type="text" name="message" placeholder="Ask something..." required>
      <button type="submit">Send</button>
    </form>
  </div>
</body>
</html>
