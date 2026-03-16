<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_roles(['Administrator']);
require_once __DIR__ . '/../includes/config.php';

$active = 'users';
$topTitle = 'Edit User';

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) {
  http_response_code(400);
  exit("Invalid user id");
}

$roles = ['Administrator','Irrigation Association','Irrigation Technician','IMO','Monitoring'];
$error = '';

// Load user
$stmt = $conn->prepare("SELECT user_id, fullname, username, role, phone, email, is_active FROM users WHERE user_id=? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
  http_response_code(404);
  exit("User not found");
}

$fullname = (string)($row['fullname'] ?? '');
$username = (string)($row['username'] ?? '');
$role = role_label((string)($row['role'] ?? ''));
$phone = (string)($row['phone'] ?? '');
$email = (string)($row['email'] ?? '');
$is_active = (int)($row['is_active'] ?? 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $fullname = trim($_POST['fullname'] ?? '');
  $username = trim($_POST['username'] ?? '');
  $password = trim($_POST['password'] ?? '');
  $role = trim($_POST['role'] ?? '');
  $roleDb = ($role === 'Irrigation Association') ? 'Operations Staff' : $role;
  $phone = trim($_POST['phone'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $is_active = (int)($_POST['is_active'] ?? 1);
  $isTechnician = ($role === 'Irrigation Technician');
  $actorId = (int)($_SESSION['user']['user_id'] ?? 0);

  if ($fullname === '' || $username === '') {
    $error = "Fullname and Username are required.";
  } elseif (!in_array($role, $roles, true)) {
    $error = "Invalid role.";
  } elseif ($isTechnician && $phone === '') {
    $error = "Phone is required for Irrigation Technician.";
  } elseif ($id === $actorId && $is_active === 0) {
    $error = "You cannot deactivate your own account.";
  } else {
    $stmt = $conn->prepare("SELECT COUNT(*) c FROM users WHERE username=? AND user_id<>?");
    $stmt->bind_param("si", $username, $id);
    $stmt->execute();
    $exists = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
    $stmt->close();

    if ($exists > 0) {
      $error = "Username already exists.";
    } else {
      if ($password !== '') {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("
          UPDATE users
          SET fullname=?, username=?, password=?, role=?, phone=?, email=?, is_active=?
          WHERE user_id=?
          LIMIT 1
        ");
        $stmt->bind_param("ssssssii", $fullname, $username, $hash, $roleDb, $phone, $email, $is_active, $id);
      } else {
        $stmt = $conn->prepare("
          UPDATE users
          SET fullname=?, username=?, role=?, phone=?, email=?, is_active=?
          WHERE user_id=?
          LIMIT 1
        ");
        $stmt->bind_param("sssssii", $fullname, $username, $roleDb, $phone, $email, $is_active, $id);
      }

      if ($stmt->execute()) {
        $stmt->close();

        $logAction = "User Updated";
        $logDesc = "Updated user #{$id} (" . role_label($roleDb) . ")";
        $stmtLog = $conn->prepare("INSERT INTO system_logs(user_id, action, description) VALUES(?,?,?)");
        $stmtLog->bind_param("iss", $actorId, $logAction, $logDesc);
        $stmtLog->execute();
        $stmtLog->close();

        $_SESSION['flash'] = "User #{$id} updated successfully.";
        header("Location: " . route('users'));
        exit;
      }

      $error = "Update failed: " . $stmt->error;
      $stmt->close();
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
            <h1 class="text-xl font-black text-text-light dark:text-text-dark">Edit User</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Update account details for user #<?= (int)$id ?></p>
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
          <input type="hidden" name="id" value="<?= (int)$id ?>">

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
            <label class="block text-sm font-medium">New Password (optional)</label>
            <div class="relative mt-1">
              <input id="pwd" type="password" name="password" value=""
                class="w-full pr-11 pl-4 py-2 rounded-full border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark"
                placeholder="Leave blank to keep current password">
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
              <span class="material-symbols-outlined text-[18px] leading-none">save</span>
              <span>Save Changes</span>
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
