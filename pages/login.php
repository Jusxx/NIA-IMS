<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/config.php';

$topTitle = "Login - NIA IMS";
$error = '';
$notice = '';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$notice = trim((string)($_SESSION['auth_notice'] ?? ''));
unset($_SESSION['auth_notice']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $password = trim($_POST['password'] ?? '');

  $stmt = $conn->prepare("SELECT user_id, fullname, username, password, role, is_active, password_change_required FROM users WHERE username = ? LIMIT 1");
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $res = $stmt->get_result();
  $u = $res ? $res->fetch_assoc() : null;
  $stmt->close();

  if (!$u || !password_verify($password, $u['password'])) {
    $error = "Invalid username or password.";
  } elseif ((int)$u['is_active'] !== 1) {
    if (($u['role'] ?? '') === 'Farmer') {
      $error = "Your membership is currently inactive. Please visit the NIA office for membership verification and account reactivation.";
    } else {
      $error = "Your account is inactive. Please contact the system administrator.";
    }
  } else {
    $_SESSION['user'] = [
      'user_id'  => (int)$u['user_id'],
      'username' => $u['username'],
      'fullname' => $u['fullname'],
      'role'     => $u['role'],
    ];

    if ((int)$u['password_change_required'] === 1) {
      header("Location: " . route('force_password'));
      exit;
    } else {
      $role = $_SESSION['user']['role'] ?? '';
      $home = match ($role) {
        'Farmer' => 'farmer_dashboard',
        default  => 'dashboard',
      };

      header("Location: " . route($home));
      exit;
    }
  }
}

include __DIR__ . '/../includes/head.php';
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">

<style>
  .login-title {
    font-family: "Outfit", "Public Sans", sans-serif;
  }
  @keyframes loginFadeInUp {
    from {
      opacity: 0;
      transform: translateY(18px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }
  .login-card-animate {
    animation: loginFadeInUp 0.45s ease-out both;
  }
</style>

<?php $postedUsername = trim((string)($_POST['username'] ?? '')); ?>

<div class="relative min-h-screen overflow-hidden">
  <div
    class="absolute inset-0 bg-cover bg-center"
    style="background-image:url('https://lh3.googleusercontent.com/aida-public/AB6AXuB5I8t0ejFhvThwIvUp8g_ChEhz-ot4xffNEz7twYIUSdEe2mdJT5AhNqzcp4UFPrHhlxDP91Zfm7yM3El-T3ebHP2DPgyXEdtB_EMpmLa19FzqXOZ39_-skAwgG877onv8xmcaOLYSj-i7K_rkLjr8v5HHxYOGogGr4qRJd2VzKVELZH1NBFTlByqIj_97JkWi5qIGGW36wfgYcfwidVxt6b4c2vjug5yMdo9X2yQzu394AjjqDkxrT65naCFFVajkZSK9jyh0rUoL');"
  ></div>
  <div class="absolute inset-0 bg-gradient-to-br from-emerald-950/70 via-emerald-900/55 to-lime-700/35"></div>

  <div class="relative min-h-screen grid lg:grid-cols-2">
    <section class="hidden lg:flex flex-col justify-between p-10 xl:p-14 text-white">
      <div>
        <div class="inline-flex items-center gap-3 rounded-full border border-white/35 bg-white/10 backdrop-blur px-4 py-2">
          <img src="../images/nia-logo.png" class="h-8 w-8 object-contain" alt="NIA Logo">
          <span class="text-sm font-semibold tracking-wide">National Irrigation Administration</span>
        </div>
      </div>

      <div class="max-w-xl">
        <h1 class="login-title text-4xl xl:text-5xl font-bold leading-tight">
          Water service operations, requests, and schedules in one secure portal.
        </h1>
        <p class="mt-4 text-base text-white/85">
          Official NIA Irrigation Management System access point for administrators, staff, technicians, and farmers.
        </p>
      </div>

      <div class="text-sm text-white/80">
        Need access help? Contact your system administrator.
      </div>
    </section>

    <section class="flex items-center justify-center p-4 sm:p-6 lg:p-10">
      <div class="w-full max-w-md">
        <div class="mb-4 text-center lg:hidden">
          <img src="../images/nia-logo.png" class="mx-auto h-14 w-14 object-contain" alt="NIA Logo">
          <h2 class="login-title mt-2 text-2xl font-bold text-white">NIA IMS</h2>
          <p class="text-xs text-white/85">Official portal login</p>
        </div>

        <div class="login-card-animate rounded-2xl border border-white/45 bg-white/90 backdrop-blur-xl shadow-2xl p-6 sm:p-7">
          <div class="mb-6">
            <h3 class="login-title text-3xl font-bold text-gray-900 leading-tight">Sign In</h3>
            <p class="mt-1 text-sm text-gray-600">Enter your account credentials to continue.</p>
          </div>

          <?php if ($notice): ?>
            <div class="flex items-start gap-2 p-3 mb-4 text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-xl">
              <span class="material-symbols-outlined text-[18px] leading-none mt-0.5">warning</span>
              <span><?= h($notice) ?></span>
            </div>
          <?php endif; ?>

          <?php if ($error): ?>
            <div class="flex items-start gap-2 p-3 mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded-xl">
              <span class="material-symbols-outlined text-[18px] leading-none mt-0.5">error</span>
              <span><?= h($error) ?></span>
            </div>
          <?php endif; ?>

          <form method="POST" class="space-y-5" data-loading-text="Signing in...">
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-1.5">Username</label>
              <div class="relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-[20px]">person</span>
                <input
                  name="username"
                  value="<?= h($postedUsername) ?>"
                  autocomplete="username"
                  required
                  class="w-full rounded-full py-3 pl-10 pr-4 border border-gray-300 bg-white text-gray-900 outline-none focus:border-primary focus:ring-2 focus:ring-primary/25"
                  placeholder="Enter username"
                >
              </div>
            </div>

            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-1.5">Password</label>
              <div class="relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-[20px]">lock</span>
                <input
                  id="password"
                  type="password"
                  name="password"
                  autocomplete="current-password"
                  required
                  class="w-full rounded-full py-3 pl-10 pr-12 border border-gray-300 bg-white text-gray-900 outline-none focus:border-primary focus:ring-2 focus:ring-primary/25"
                  placeholder="Enter password"
                >
                <button
                  type="button"
                  id="togglePasswordBtn"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700"
                  aria-label="Toggle password visibility"
                  title="Show/Hide password"
                >
                  <span id="togglePasswordIcon" class="material-symbols-outlined text-[20px] leading-none">visibility_off</span>
                </button>
              </div>
              <p id="capsLockHint" class="hidden mt-1 text-xs text-amber-700">Caps Lock is on.</p>
            </div>

            <button
              type="submit"
              class="w-full rounded-full bg-primary py-3 text-white font-semibold hover:bg-green-700 transition-colors"
            >
              Login
            </button>

            <div class="text-center text-xs text-gray-600">
              Forgot password? Contact your administrator.
            </div>
          </form>
        </div>
      </div>
    </section>
  </div>
</div>

<script>
(() => {
  const passwordInput = document.getElementById('password');
  const toggleBtn = document.getElementById('togglePasswordBtn');
  const toggleIcon = document.getElementById('togglePasswordIcon');
  const capsLockHint = document.getElementById('capsLockHint');
  if (!passwordInput || !toggleBtn || !toggleIcon || !capsLockHint) return;

  toggleBtn.addEventListener('click', () => {
    const reveal = passwordInput.type === 'password';
    passwordInput.type = reveal ? 'text' : 'password';
    toggleIcon.textContent = reveal ? 'visibility' : 'visibility_off';
  });

  const updateCapsLockState = (event) => {
    const on = event.getModifierState && event.getModifierState('CapsLock');
    capsLockHint.classList.toggle('hidden', !on);
  };

  passwordInput.addEventListener('keydown', updateCapsLockState);
  passwordInput.addEventListener('keyup', updateCapsLockState);
  passwordInput.addEventListener('blur', () => capsLockHint.classList.add('hidden'));
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
