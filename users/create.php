<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_roles(['Administrator']);
require_once __DIR__ . '/../includes/config.php';

$active = 'users';
$topTitle = 'Add User';

$error = '';

$fullname = '';
$username = '';
$password = '';
$role = 'Irrigation Association';
$phone = '';
$email = '';
$is_active = 1;

$roles = ['Administrator','Irrigation Association','Irrigation Technician'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $fullname = trim($_POST['fullname'] ?? '');
  $username = trim($_POST['username'] ?? '');
  $password = trim($_POST['password'] ?? '');
  $role     = trim($_POST['role'] ?? 'Irrigation Association');
  $roleDb   = $role;
  if ($role === 'Irrigation Association') {
    $roleDb = 'Operations Staff';
  }
  $phone    = trim($_POST['phone'] ?? '');
  $email    = trim($_POST['email'] ?? '');
  $is_active = (int)($_POST['is_active'] ?? 1);
  $isTechnician = ($role === 'Irrigation Technician');

  if ($fullname === '' || $username === '' || $password === '') {
    $error = "Fullname, Username, and Password are required.";
  } elseif (!in_array($role, $roles, true)) {
    $error = "Invalid role.";
  } elseif ($isTechnician && $phone === '') {
    $error = "Phone is required for Irrigation Technician.";
  } else {
    $stmt = $conn->prepare("SELECT COUNT(*) c FROM users WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $exists = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
    $stmt->close();

    if ($exists > 0) {
      $error = "Username already exists.";
    } else {
      $conn->begin_transaction();

      try {
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $conn->prepare("
          INSERT INTO users(fullname, username, password, role, phone, email, is_active)
          VALUES(?,?,?,?,?,?,?)
        ");
        $stmt->bind_param("ssssssi", $fullname, $username, $hash, $roleDb, $phone, $email, $is_active);

        if (!$stmt->execute()) {
          throw new Exception("Insert user failed: " . $stmt->error);
        }

        $newUserId = (int)$conn->insert_id;
        $stmt->close();

        $adminId = (int)($_SESSION['user']['user_id'] ?? 0);
        $action = "User Created";
        $desc = "Created user #{$newUserId} (" . role_label($roleDb) . ")";
        $stmt3 = $conn->prepare("INSERT INTO system_logs(user_id, action, description) VALUES(?,?,?)");
        $stmt3->bind_param("iss", $adminId, $action, $desc);
        $stmt3->execute();
        $stmt3->close();

        $conn->commit();
        header("Location: " . route('users'));
        exit;
      } catch (Throwable $e) {
        $conn->rollback();
        $error = $e->getMessage();
      }
    }
  }
}

include __DIR__ . '/../includes/head.php';
?>

<div class="flex min-h-screen w-full">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="flex-1 flex flex-col">
    <?php include __DIR__ . '/../includes/topbar.php'; ?>

    <main class="p-6 lg:p-8 flex-1">
      <div class="max-w-2xl bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark rounded-lg p-6">

        <div class="flex items-center justify-between gap-3">
          <div>
            <h1 class="text-xl font-black text-text-light dark:text-text-dark">Add User</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Create staff accounts. Farmer accounts are created in Farmers module.</p>
          </div>
          <a
            class="px-4 py-2 rounded-full bg-gray-200 dark:bg-gray-700 text-text-light dark:text-text-dark inline-flex items-center gap-1.5"
            href="<?= route('users') ?>"
            title="Back"
            aria-label="Back to users"
          >
            <span class="material-symbols-outlined text-[18px] leading-none">arrow_back</span>
            <span>Back</span>
          </a>
        </div>

        <?php if ($error): ?>
          <div class="mt-4 p-3 rounded bg-red-100 text-red-700"><?= h($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="mt-4 space-y-4">
          <div>
            <label class="block text-sm font-medium">Fullname *</label>
            <input name="fullname" value="<?= h($fullname) ?>" required
              class="mt-1 w-full rounded-full px-4 py-2 border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
          </div>

          <div>
            <label class="block text-sm font-medium">Username *</label>
            <input name="username" value="<?= h($username) ?>" required
              class="mt-1 w-full rounded-full px-4 py-2 border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
          </div>

          <div>
            <label class="block text-sm font-medium">Password *</label>
            <div class="relative mt-1">
              <input id="pwd" type="password" name="password" value="<?= h($password) ?>" required
                class="w-full pr-11 pl-4 py-2 rounded-full border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
              <button type="button" id="togglePwd"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 inline-flex items-center justify-center"
                title="Show or hide password"
                aria-label="Show or hide password">
                <span id="togglePwdIcon" class="material-symbols-outlined text-[18px] leading-none">visibility</span>
              </button>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium">Role *</label>
              <select id="userRoleSelect" name="role"
                class="mt-1 w-full rounded-full px-4 py-2 border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
                <?php foreach($roles as $rr): ?>
                  <option value="<?= h($rr) ?>" <?= $role === $rr ? 'selected' : '' ?>><?= h($rr) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium">Status</label>
              <select name="is_active"
                class="mt-1 w-full rounded-full px-4 py-2 border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
                <option value="1" <?= $is_active === 1 ? 'selected' : '' ?>>Active</option>
                <option value="0" <?= $is_active === 0 ? 'selected' : '' ?>>Inactive</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label id="userPhoneLabel" class="block text-sm font-medium">Phone</label>
              <input id="userPhoneInput" name="phone" value="<?= h($phone) ?>"
                class="mt-1 w-full rounded-full px-4 py-2 border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
            </div>
            <div>
              <label class="block text-sm font-medium">Email</label>
              <input type="email" name="email" value="<?= h($email) ?>"
                class="mt-1 w-full rounded-full px-4 py-2 border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
            </div>
          </div>

          <div class="flex flex-wrap gap-2">
            <button class="px-4 py-2 rounded-full bg-primary text-white font-bold inline-flex items-center gap-1.5">
              <span class="material-symbols-outlined text-[18px] leading-none">person_add</span>
              <span>Create</span>
            </button>
            <a class="px-4 py-2 rounded-full bg-gray-200 dark:bg-gray-700 text-text-light dark:text-text-dark inline-flex items-center gap-1.5"
               href="<?= route('users') ?>">
              <span class="material-symbols-outlined text-[18px] leading-none">cancel</span>
              <span>Cancel</span>
            </a>
          </div>
        </form>
      </div>
    </main>
  </div>
</div>

<script>
  const btn = document.getElementById('togglePwd');
  const pwd = document.getElementById('pwd');
  const icon = document.getElementById('togglePwdIcon');
  const roleSelect = document.getElementById('userRoleSelect');
  const phoneInput = document.getElementById('userPhoneInput');
  const phoneLabel = document.getElementById('userPhoneLabel');

  const syncPhoneRequirement = () => {
    if (!roleSelect || !phoneInput || !phoneLabel) return;
    const isTech = roleSelect.value === 'Irrigation Technician';
    phoneInput.required = isTech;
    phoneLabel.textContent = isTech ? 'Phone *' : 'Phone';
  };

  if (roleSelect) {
    roleSelect.addEventListener('change', syncPhoneRequirement);
    syncPhoneRequirement();
  }

  if (btn && pwd && icon) {
    btn.addEventListener('click', () => {
      const isPwd = pwd.getAttribute('type') === 'password';
      pwd.setAttribute('type', isPwd ? 'text' : 'password');
      icon.textContent = isPwd ? 'visibility_off' : 'visibility';
    });
  }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
