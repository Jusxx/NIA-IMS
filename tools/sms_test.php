<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/philsms.php';

$result = null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $recipient = trim($_POST['recipient'] ?? '');
  $message = trim($_POST['message'] ?? '');
  $sender = trim($_POST['sender_id'] ?? '');

  if ($recipient === '' || $message === '') {
    $error = "Recipient and message are required.";
  } else {
    $sender = $sender !== '' ? $sender : ($GLOBALS['philsms_sender_id'] ?? 'PhilSMS');
    $result = send_philsms($recipient, $message, $sender);
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PhilSMS Test</title>
  <style>
    body { font-family: Arial, sans-serif; background:#f5f5f5; padding:24px; }
    .card { max-width: 640px; margin: 0 auto; background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 10px rgba(0,0,0,.05); }
    label { display:block; margin-top:12px; font-size:14px; }
    input, textarea { width:100%; padding:10px; margin-top:6px; border:1px solid #ccc; border-radius:6px; }
    button { margin-top:16px; padding:10px 16px; background:#28a745; color:#fff; border:none; border-radius:6px; font-weight:bold; cursor:pointer; }
    pre { background:#f0f0f0; padding:12px; border-radius:6px; overflow:auto; }
    .error { color:#b00020; margin-top:12px; }
  </style>
</head>
<body>
  <div class="card">
    <h2>PhilSMS Test Sender</h2>
    <form method="POST">
      <label>Recipient (e.g., 639XXXXXXXXX or 09XXXXXXXXX)</label>
      <input name="recipient" value="<?= htmlspecialchars($_POST['recipient'] ?? '') ?>">

      <label>Sender ID (optional)</label>
      <input name="sender_id" value="<?= htmlspecialchars($_POST['sender_id'] ?? '') ?>" placeholder="PhilSMS">

      <label>Message</label>
      <textarea name="message" rows="4"><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>

      <button type="submit">Send SMS</button>
    </form>

    <?php if ($error): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($result): ?>
      <h3>Result</h3>
      <pre><?= htmlspecialchars(print_r($result, true)) ?></pre>
    <?php endif; ?>
  </div>
</body>
</html>
