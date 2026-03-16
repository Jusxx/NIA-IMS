<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_roles(['Administrator']);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/sms.php';

$active = 'farmers';
$topTitle = 'Edit Farmer';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  http_response_code(400);
  exit("Invalid farmer id.");
}

$error = '';

// Load areas dropdown (focused areas only)
$areas = [];
$stmt = $conn->prepare("SELECT service_area_id, area_name, municipality, province FROM service_areas ORDER BY area_name ASC");
$stmt->execute();
$areas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$areas = filter_focus_service_area_rows($areas);
$allowedAreaIds = array_map('intval', array_column($areas, 'service_area_id'));

function normalize_phone_local_9(string $raw): ?string {
  $digits = preg_replace('/\D+/', '', $raw);
  if (strlen($digits) === 9) return $digits;

  if (strlen($digits) === 10 && str_starts_with($digits, '9')) {
    return substr($digits, 1);
  }

  if (strlen($digits) === 11 && str_starts_with($digits, '09')) {
    return substr($digits, 2);
  }

  if (strlen($digits) === 12 && str_starts_with($digits, '63')) {
    return substr($digits, 3);
  }

  return null;
}

function phone_to_local_9(?string $raw): string {
  $digits = preg_replace('/\D+/', '', (string)$raw);
  if ($digits === '') return '';
  return strlen($digits) >= 9 ? substr($digits, -9) : $digits;
}

$stmt = $conn->prepare("
  SELECT f.*, u.username, COALESCE(u.is_active, 1) AS member_active
  FROM farmers f
  LEFT JOIN users u ON u.user_id = f.user_id
  WHERE f.farmer_id = ?
  LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
  http_response_code(404);
  exit("Farmer not found.");
}

$farmer_name = (string)($row['farmer_name'] ?? '');
$association = (string)($row['association_name'] ?? '');
$address = (string)($row['address'] ?? '');
$phoneLocal = phone_to_local_9($row['phone'] ?? '');
$service_area_id = (int)($row['service_area_id'] ?? 0);
$is_president = (int)($row['is_president'] ?? 0);
$onboarded_via = (string)($row['onboarded_via'] ?? 'Admin');
$member_status = ((int)($row['member_active'] ?? 1) === 1) ? 'Active' : 'Inactive';
$linked_user_id = (int)($row['user_id'] ?? 0);
$linked_username = (string)($row['username'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $farmer_name = trim($_POST['farmer_name'] ?? '');
  $association = trim($_POST['association_name'] ?? '');
  $address = trim($_POST['address'] ?? '');
  $phoneLocal = trim($_POST['phone_local'] ?? '');
  $service_area_id = (int)($_POST['service_area_id'] ?? 0);
  $is_president = isset($_POST['is_president']) ? 1 : 0;
  $onboarded_via = trim($_POST['onboarded_via'] ?? 'Admin');
  $member_status = trim($_POST['member_status'] ?? $member_status);

  if (!in_array($onboarded_via, ['Website', 'Field Operator', 'Admin'], true)) {
    $onboarded_via = 'Admin';
  }

  $phoneNormLocal = normalize_phone_local_9($phoneLocal);
  $phone = $phoneNormLocal ? ('639' . $phoneNormLocal) : '';

  if ($farmer_name === '') $error = "Farmer name is required.";
  elseif ($association === '') $error = "Association name is required.";
  elseif ($address === '') $error = "Address is required.";
  elseif ($phoneNormLocal === null) $error = "Cellphone number must be 9 digits after 639.";
  elseif ($service_area_id <= 0) $error = "Please select a service area.";
  elseif (!in_array($service_area_id, $allowedAreaIds, true)) $error = "Please select one of the focused irrigation areas only.";
  elseif ($linked_user_id > 0 && !in_array($member_status, ['Active', 'Inactive'], true)) $error = "Invalid membership status.";

  if ($error === '') {
    try {
      $conn->begin_transaction();

      $stmt = $conn->prepare("
        UPDATE farmers
        SET farmer_name=?, association_name=?, address=?, phone=?, service_area_id=?,
            is_president=?, onboarded_via=?
        WHERE farmer_id=?
      ");
      $stmt->bind_param(
        "ssssiisi",
        $farmer_name,
        $association,
        $address,
        $phone,
        $service_area_id,
        $is_president,
        $onboarded_via,
        $id
      );
      $stmt->execute();
      $stmt->close();

      if ($linked_user_id > 0) {
        $isActive = ($member_status === 'Active') ? 1 : 0;
        $wasActive = ((int)($row['member_active'] ?? 1) === 1);

        $stmt = $conn->prepare("UPDATE users SET fullname=?, phone=?, is_active=? WHERE user_id=? LIMIT 1");
        $stmt->bind_param("ssii", $farmer_name, $phone, $isActive, $linked_user_id);
        $stmt->execute();
        $stmt->close();

        if ($wasActive !== ($isActive === 1)) {
          $recipientRole = $is_president === 1 ? 'President' : 'Farmer';
          send_membership_status_sms(
            $conn,
            (int)$id,
            $phone,
            $farmer_name,
            $isActive === 1,
            $recipientRole
          );
        }
      }

      $desc = "Updated farmer #{$id} ({$farmer_name})" . ($linked_user_id > 0 ? " | Membership: {$member_status}" : "");
      system_log($conn, "Farmer Updated", $desc);

      $conn->commit();
      $_SESSION['flash'] = "Farmer profile updated successfully.";
      header("Location: " . route('farmers'));
      exit;
    } catch (Throwable $e) {
      $conn->rollback();
      $error = "Failed to update farmer: " . $e->getMessage();
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
      <div class="w-full bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark rounded-lg p-6">
        <div class="flex items-center justify-between gap-3">
          <div>
            <h1 class="text-xl font-black text-text-light dark:text-text-dark">Edit Farmer</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Update farmer details and membership status.</p>
          </div>
          <a class="px-3 py-2 rounded-DEFAULT bg-gray-200 dark:bg-gray-700 text-text-light dark:text-text-dark"
             href="<?= route('farmers') ?>">Back</a>
        </div>

        <?php if ($error): ?>
          <div class="mt-4 p-3 rounded bg-red-100 text-red-700"><?= h($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="mt-6 space-y-6">
          <section>
            <h2 class="text-lg font-bold text-text-light dark:text-text-dark">Farmer Details</h2>
            <div class="mt-3 grid grid-cols-1 xl:grid-cols-2 gap-4">
              <div>
                <label class="text-sm">Farmer Name *</label>
                <input name="farmer_name" value="<?= h($farmer_name) ?>" required
                       class="mt-1 w-full rounded-DEFAULT border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
              </div>

              <div>
                <label class="text-sm">Association Name *</label>
                <input name="association_name" value="<?= h($association) ?>" required
                       class="mt-1 w-full rounded-DEFAULT border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
              </div>

              <div class="xl:col-span-2">
                <label class="text-sm">Address *</label>
                <input name="address" value="<?= h($address) ?>" required
                       class="mt-1 w-full rounded-DEFAULT border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
              </div>

              <div>
                <label class="text-sm">Cellphone Number *</label>
                <div class="flex items-center gap-2 mt-1">
                  <span class="px-3 py-2 rounded-DEFAULT bg-gray-100 dark:bg-gray-800 text-gray-600">639</span>
                  <input name="phone_local" id="phoneInput" value="<?= h($phoneLocal) ?>" maxlength="9"
                         inputmode="numeric" pattern="^[0-9]{9}$" required placeholder="XXXXXXXXX"
                         class="w-full rounded-DEFAULT border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
                </div>
                <p class="text-xs text-gray-500 mt-1">Enter 9 digits only after 639.</p>
              </div>

              <div>
                <label class="text-sm">Service Area *</label>
                <select name="service_area_id" required
                        class="mt-1 w-full rounded-DEFAULT border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
                  <option value="0">Select Area</option>
                  <?php foreach ($areas as $a): ?>
                    <option value="<?= (int)$a['service_area_id'] ?>" <?= $service_area_id === (int)$a['service_area_id'] ? 'selected' : '' ?>>
                      <?= h($a['area_name']) ?><?= $a['municipality'] ? " - " . h($a['municipality']) : "" ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div>
                <label class="text-sm">Onboarded Via</label>
                <select name="onboarded_via"
                        class="mt-1 w-full rounded-DEFAULT border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
                  <?php foreach (['Admin', 'Field Operator', 'Website'] as $ov): ?>
                    <option value="<?= h($ov) ?>" <?= $onboarded_via === $ov ? 'selected' : '' ?>><?= h($ov) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div>
                <label class="text-sm">Account Username</label>
                <input value="<?= h($linked_username ?: 'No linked account') ?>" readonly
                       class="mt-1 w-full rounded-DEFAULT border-border-light dark:border-border-dark bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
              </div>

              <div>
                <label class="text-sm">Membership Status</label>
                <select name="member_status" <?= $linked_user_id <= 0 ? 'disabled' : '' ?>
                        class="mt-1 w-full rounded-DEFAULT border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark <?= $linked_user_id <= 0 ? 'opacity-60 cursor-not-allowed' : '' ?>">
                  <?php foreach (['Active', 'Inactive'] as $ms): ?>
                    <option value="<?= $ms ?>" <?= $member_status === $ms ? 'selected' : '' ?>><?= $ms ?></option>
                  <?php endforeach; ?>
                </select>
                <?php if ($linked_user_id <= 0): ?>
                  <p class="text-xs text-gray-500 mt-1">No linked user account. Membership toggle is unavailable.</p>
                <?php endif; ?>
              </div>

              <div class="flex items-center gap-2 xl:items-end">
                <input type="checkbox" id="is_president" name="is_president" class="rounded" <?= $is_president ? 'checked' : '' ?>>
                <label for="is_president" class="text-sm">Farmer President</label>
              </div>
            </div>
          </section>

          <div class="flex gap-2 pt-2">
            <button class="px-4 py-2 rounded-DEFAULT bg-primary text-white font-bold">Save Changes</button>
            <a class="px-4 py-2 rounded-DEFAULT bg-gray-200 dark:bg-gray-700"
               href="<?= route('farmers') ?>">Cancel</a>
          </div>
        </form>
      </div>
    </main>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
  const phoneInput = document.getElementById('phoneInput');
  if (phoneInput) {
    phoneInput.addEventListener('input', () => {
      phoneInput.value = phoneInput.value.replace(/[^0-9]/g, '').slice(0, 9);
    });
  }
</script>
