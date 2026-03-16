<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../includes/config.php';

$topTitle = "Change Password";
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $p1 = trim($_POST['password'] ?? '');
  $p2 = trim($_POST['password2'] ?? '');

  if ($p1 === '' || $p1 !== $p2) {
    $error = "Passwords do not match.";
  } elseif (strlen($p1) < 6) {
    $error = "Password must be at least 6 characters.";
  } else {
    $hash = password_hash($p1, PASSWORD_BCRYPT);
    $uid = (int)($_SESSION['user']['user_id'] ?? 0);

    $stmt = $conn->prepare("UPDATE users SET password=?, password_change_required=0 WHERE user_id=?");
    $stmt->bind_param("si", $hash, $uid);
    $stmt->execute();
    $stmt->close();

    $role = $_SESSION['user']['role'] ?? '';
    $home = match ($role) {
      'Farmer' => 'farmer_dashboard',
      default  => 'dashboard',
    };

    header("Location: " . route($home));
    exit;
  }
}

include __DIR__ . '/../includes/head.php';
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<div class="min-h-screen flex items-center justify-center p-4 sm:p-6 bg-gradient-to-b from-background-light to-gray-100/80 dark:from-background-dark dark:to-gray-900/60">
  <div class="max-w-md w-full bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark rounded-xl p-6 shadow-sm">
    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-primary/10 text-primary text-xs font-semibold">
      <span class="material-symbols-outlined text-[16px] leading-none">lock_reset</span>
      First Login Security
    </div>
    <h1 class="text-xl font-black text-text-light dark:text-text-dark mt-3">Change Password</h1>
    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Set your new account password before continuing.</p>

    <?php if ($error): ?>
      <div class="mt-4 p-3 rounded bg-red-100 text-red-700"><?= h($error) ?></div>
    <?php endif; ?>

    <div class="mt-4 rounded-lg border border-border-light dark:border-border-dark bg-background-light/70 dark:bg-background-dark/40 p-3 text-xs text-gray-600 dark:text-gray-300">
      <p class="font-semibold text-text-light dark:text-text-dark mb-1">Password Tips</p>
      <p>Use at least 6 characters and avoid using your name or phone number.</p>
    </div>

    <form method="POST" class="mt-4 space-y-4">
      <div>
        <label class="text-sm">New Password</label>
        <div class="mt-1 relative">
          <input id="newPassword" type="password" name="password" required class="w-full pr-10 rounded-DEFAULT border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
          <button
            type="button"
            id="toggleNewPassword"
            class="absolute inset-y-0 right-0 w-10 inline-flex items-center justify-center text-gray-500 hover:text-gray-700 dark:text-gray-300 dark:hover:text-gray-100"
            aria-label="Show password"
            title="Show password"
          >
            <i id="newPasswordIcon" class="fa-solid fa-eye-slash"></i>
          </button>
        </div>
      </div>
      <div>
        <label class="text-sm">Confirm Password</label>
        <div class="mt-1 relative">
          <input id="confirmPassword" type="password" name="password2" required class="w-full pr-10 rounded-DEFAULT border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
          <button
            type="button"
            id="toggleConfirmPassword"
            class="absolute inset-y-0 right-0 w-10 inline-flex items-center justify-center text-gray-500 hover:text-gray-700 dark:text-gray-300 dark:hover:text-gray-100"
            aria-label="Show password"
            title="Show password"
          >
            <i id="confirmPasswordIcon" class="fa-solid fa-eye-slash"></i>
          </button>
        </div>
      </div>
      <button class="w-full px-4 py-2.5 rounded-DEFAULT bg-primary text-white font-bold">Update Password</button>
    </form>
  </div>
</div>
<script>
(() => {
  const wirePasswordToggle = (buttonId, inputId, iconId) => {
    const btn = document.getElementById(buttonId);
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if (!btn || !input || !icon) return;

    btn.addEventListener('click', () => {
      const visible = input.type === 'password';
      input.type = visible ? 'text' : 'password';
      icon.classList.toggle('fa-eye', visible);
      icon.classList.toggle('fa-eye-slash', !visible);
      btn.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
      btn.setAttribute('title', visible ? 'Hide password' : 'Show password');
    });
  };

  wirePasswordToggle('toggleNewPassword', 'newPassword', 'newPasswordIcon');
  wirePasswordToggle('toggleConfirmPassword', 'confirmPassword', 'confirmPasswordIcon');
})();
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
