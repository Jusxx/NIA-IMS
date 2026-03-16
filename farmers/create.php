<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_roles(['Administrator']);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/sms.php';

$active = 'farmers';
$topTitle = 'Add Farmer';

$error = '';
$success = '';
$showCreateSuccessModal = !empty($_SESSION['farmer_created_success']);
unset($_SESSION['farmer_created_success']);

// Load areas dropdown
$areas = [];
$stmt = $conn->prepare("SELECT service_area_id, area_name, municipality, province FROM service_areas ORDER BY area_name ASC");
$stmt->execute();
$areas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$areas = filter_focus_service_area_rows($areas);
$allowedAreaIds = array_map('intval', array_column($areas, 'service_area_id'));

$farmer_name = '';
$association = '';
$address = '';
$address_region = '';
$address_cluster = '';
$address_locality = '';
$address_locality_manual = '';
$address_barangay = '';
$address_street = '';
$phoneLocal = '';
$service_area_id = 0;
$is_president = 0;
$onboarded_via = 'Admin';
$lot_code = '';
$lot_location_desc = '';
$lot_latitude = '';
$lot_longitude = '';
$username = '';

$lotTitleColumnExists = false;
$stmt = $conn->prepare("
  SELECT COUNT(*) c
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'farmer_lots'
    AND COLUMN_NAME = 'title_photo_path'
");
$stmt->execute();
$lotTitleColumnExists = ((int)($stmt->get_result()->fetch_assoc()['c'] ?? 0) > 0);
$stmt->close();

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $farmer_name = trim($_POST['farmer_name'] ?? '');
  $association = trim($_POST['association_name'] ?? '');
  $address     = trim($_POST['address'] ?? '');
  $address_region = trim($_POST['address_region'] ?? '');
  $address_cluster = trim($_POST['address_cluster'] ?? '');
  $address_locality = trim($_POST['address_locality'] ?? '');
  $address_locality_manual = trim($_POST['address_locality_manual'] ?? '');
  if ($address_locality === '') {
    $address_locality = $address_locality_manual;
  }
  $address_barangay = trim($_POST['address_barangay'] ?? '');
  $address_street = trim($_POST['address_street'] ?? '');
  $phoneLocal  = trim($_POST['phone_local'] ?? '');
  $service_area_id = (int)($_POST['service_area_id'] ?? 0);
  $is_president = isset($_POST['is_president']) ? 1 : 0;
  $onboarded_via = 'Admin';
  $lot_code = trim($_POST['lot_code'] ?? '');
  $lot_location_desc = trim($_POST['lot_location_desc'] ?? '');
  $lot_latitude = trim($_POST['lot_latitude'] ?? '');
  $lot_longitude = trim($_POST['lot_longitude'] ?? '');

  // login credentials
  $username = trim($_POST['username'] ?? '');
  $password = trim($_POST['password'] ?? '');

  $phoneNormLocal = normalize_phone_local_9($phoneLocal);
  $phone = $phoneNormLocal ? ('639' . $phoneNormLocal) : '';
  $lotLat = is_numeric($lot_latitude) ? (float)$lot_latitude : null;
  $lotLng = is_numeric($lot_longitude) ? (float)$lot_longitude : null;

  if ($farmer_name === '') $error = "Farmer name is required.";
  elseif ($association === '') $error = "Association name is required.";
  elseif ($address === '') $error = "Address is required.";
  elseif ($phoneNormLocal === null) $error = "Cellphone number must be 9 digits after 639.";
  elseif ($service_area_id <= 0) $error = "Please select a service area.";
  elseif (!in_array($service_area_id, $allowedAreaIds, true)) $error = "Please select one of the focused irrigation areas only.";
  elseif ($lot_code === '') $error = "Lot code is required.";
  elseif ($lot_location_desc === '') $error = "Lot location description is required.";
  elseif ($lotLat === null || $lotLng === null) $error = "Please pin the lot location on the map.";
  elseif ($username === '' || $password === '') $error = "Username and Password are required.";
  elseif (strlen($password) < 6) $error = "Password must be at least 6 characters.";
  elseif (empty($_FILES['lot_title']['name'])) $error = "Lot title image is required.";
  else {
    $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
    $lotTitleExt = strtolower(pathinfo($_FILES['lot_title']['name'] ?? '', PATHINFO_EXTENSION));

    if (!in_array($lotTitleExt, $allowedExt, true)) {
      $error = "Lot title image must be JPG, PNG, or WEBP.";
    } elseif (($_FILES['lot_title']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
      $error = "Lot title image upload failed.";
    } elseif ((int)($_FILES['lot_title']['size'] ?? 0) > (5 * 1024 * 1024)) {
      $error = "Lot title image must be 5MB or below.";
    }
  }

  if ($error === '') {
    // check username duplicate
    $stmt = $conn->prepare("SELECT COUNT(*) c FROM users WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $exists = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
    $stmt->close();

    if ($exists > 0) {
      $error = "Username already exists.";
    } else {
      $titlePhotoAbs = null;
      $titlePhotoRel = null;
      $conn->begin_transaction();

      try {
        $createdBy = (int)($_SESSION['user']['user_id'] ?? 0);

        // 1) Create farmer profile first
        $stmt = $conn->prepare("
          INSERT INTO farmers(
            farmer_name, association_name, address, phone, service_area_id,
            is_president, created_by, onboarded_via, onboarded_at
          )
          VALUES(?,?,?,?,?,?,?,?, NOW())
        ");
        $stmt->bind_param(
          "ssssiiis",
          $farmer_name,
          $association,
          $address,
          $phone,
          $service_area_id,
          $is_president,
          $createdBy,
          $onboarded_via
        );
        $stmt->execute();
        $farmerId = (int)$conn->insert_id;
        $stmt->close();

        // 2) Upload lot title image
        $uploadDir = __DIR__ . '/../public/uploads/lot_titles/';
        if (!is_dir($uploadDir)) {
          mkdir($uploadDir, 0777, true);
        }
        $newName = 'lot_title_' . $farmerId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $lotTitleExt;
        $titlePhotoAbs = $uploadDir . $newName;
        $titlePhotoRel = 'uploads/lot_titles/' . $newName;

        if (!move_uploaded_file($_FILES['lot_title']['tmp_name'], $titlePhotoAbs)) {
          throw new RuntimeException("Could not save lot title image.");
        }

        // 3) Create farmer user account
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $role = "Farmer";

        $stmt = $conn->prepare("INSERT INTO users(fullname, username, password, role, phone, is_active, password_change_required) VALUES(?,?,?,?,?,1,1)");
        $stmt->bind_param("sssss", $farmer_name, $username, $hash, $role, $phone);
        $stmt->execute();
        $newUserId = (int)$conn->insert_id;
        $stmt->close();

        // 4) Link farmer profile to account
        $stmt = $conn->prepare("UPDATE farmers SET user_id=? WHERE farmer_id=?");
        $stmt->bind_param("ii", $newUserId, $farmerId);
        $stmt->execute();
        $stmt->close();

        // 5) Save lot details + uploaded title image path
        if ($lotTitleColumnExists) {
          $stmt = $conn->prepare("
            INSERT INTO farmer_lots(farmer_id, lot_code, location_desc, latitude, longitude, title_photo_path)
            VALUES(?,?,?,?,?,?)
          ");
          $stmt->bind_param("issdds", $farmerId, $lot_code, $lot_location_desc, $lotLat, $lotLng, $titlePhotoRel);
        } else {
          $remarks = 'TITLE_PHOTO:' . $titlePhotoRel;
          $stmt = $conn->prepare("
            INSERT INTO farmer_lots(farmer_id, lot_code, location_desc, latitude, longitude, remarks)
            VALUES(?,?,?,?,?,?)
          ");
          $stmt->bind_param("issdds", $farmerId, $lot_code, $lot_location_desc, $lotLat, $lotLng, $remarks);
        }
        $stmt->execute();
        $stmt->close();

        // 6) Log activity
        $action = "Farmer Onboarded";
        $desc = "Created farmer #{$farmerId} and linked account #{$newUserId}.";
        $stmt = $conn->prepare("INSERT INTO system_logs(user_id, action, description) VALUES(?,?,?)");
        $stmt->bind_param("iss", $createdBy, $action, $desc);
        $stmt->execute();
        $stmt->close();

        $conn->commit();

        $smsMessages = sms_message_account_created_variants($farmer_name, $username, $password);
        $smsResult = ['ok' => false, 'error' => 'No SMS message candidates'];
        foreach ($smsMessages as $smsMessage) {
          $smsResult = send_sms_and_log(
            $conn,
            $farmerId,
            $phone,
            $smsMessage,
            "Info",
            null,
            $is_president ? 'President' : 'Farmer',
            null,
            null,
            null,
            false
          );
          if (!empty($smsResult['ok'])) {
            break;
          }
          if (!sms_error_contains_spam_word_filter($smsResult)) {
            break;
          }
        }
        if (empty($smsResult['ok'])) {
          $smsErr = (string)($smsResult['response'] ?? $smsResult['error'] ?? 'Unknown SMS error');
          $act = "Farmer Onboarding SMS Failed";
          $dsc = "Farmer #{$farmerId} | Phone {$phone} | {$smsErr}";
          $stmt = $conn->prepare("INSERT INTO system_logs(user_id, action, description) VALUES(?,?,?)");
          $stmt->bind_param("iss", $createdBy, $act, $dsc);
          $stmt->execute();
          $stmt->close();
        }

        $_SESSION['farmer_created_success'] = 1;
        header("Location: " . base_path('farmers/create.php?created=1'));
        exit;

      } catch (Throwable $e) {
        $conn->rollback();
        if ($titlePhotoAbs && is_file($titlePhotoAbs)) {
          @unlink($titlePhotoAbs);
        }
        $error = "Failed to create farmer: " . $e->getMessage();
      }
    }
  }
}

include __DIR__ . '/../includes/head.php';
?>
<div class="flex h-screen w-full overflow-hidden">
  <div class="h-screen shrink-0 overflow-y-auto">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  </div>
  <div class="flex-1 flex flex-col min-h-0">
    <?php include __DIR__ . '/../includes/topbar.php'; ?>

    <main class="p-6 lg:p-8 flex-1 overflow-y-auto">
      <div class="w-full bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark rounded-lg p-6">
        <div class="flex items-center justify-between gap-3">
          <div>
            <h1 class="text-xl font-black text-text-light dark:text-text-dark">Add Farmer</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Create farmer profile + login credentials.</p>
          </div>
          <a
            class="px-4 py-2 rounded-full bg-gray-200 dark:bg-gray-700 text-text-light dark:text-text-dark inline-flex items-center gap-1.5"
            href="<?= route('farmers') ?>"
            title="Back"
            aria-label="Back to farmers"
          >
            <span class="material-symbols-outlined text-[18px] leading-none">arrow_back</span>
            <span>Back</span>
          </a>
        </div>

        <?php if ($error): ?>
          <div class="mt-4 p-3 rounded bg-red-100 text-red-700"><?= h($error) ?></div>
        <?php endif; ?>

        <form id="farmerCreateForm" method="POST" enctype="multipart/form-data" class="mt-4 space-y-4" data-no-loading>
          <p class="text-sm text-gray-600 dark:text-gray-300">
            Required during onboarding: Personal details, Address, Association, Cellphone, Lot Code, Lot Location, Location Description, and Lot Title image.
          </p>

          <section>
            <h2 class="text-lg font-bold text-text-light dark:text-text-dark">Farmer Details</h2>
            <div class="mt-3 grid grid-cols-1 lg:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium">Farmer Name *</label>
                <input id="farmerName" name="farmer_name" value="<?= h($farmer_name) ?>" required class="mt-1 h-11 w-full rounded-md px-3 py-2 border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
              </div>

              <div>
                <label class="block text-sm font-medium">Association Name *</label>
                <input name="association_name" value="<?= h($association) ?>" required class="mt-1 h-11 w-full rounded-md px-3 py-2 border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
              </div>

              <div class="lg:col-span-2">
                <label class="block text-sm font-medium">Address *</label>
                <div class="mt-1 grid grid-cols-1 md:grid-cols-2 gap-3">
                  <div>
                    <label class="block text-xs text-gray-500 mb-1">Region</label>
                    <select id="addressRegion" name="address_region" required
                            class="h-11 w-full rounded-md px-3 py-2 border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
                      <option value="">Select Region</option>
                      <option value="NCR" <?= $address_region === 'NCR' ? 'selected' : '' ?>>NCR</option>
                      <option value="CAR" <?= $address_region === 'CAR' ? 'selected' : '' ?>>CAR</option>
                      <option value="Region I" <?= $address_region === 'Region I' ? 'selected' : '' ?>>Region I</option>
                      <option value="Region II" <?= $address_region === 'Region II' ? 'selected' : '' ?>>Region II</option>
                      <option value="Region III" <?= $address_region === 'Region III' ? 'selected' : '' ?>>Region III</option>
                      <option value="Region IV-A" <?= $address_region === 'Region IV-A' ? 'selected' : '' ?>>Region IV-A</option>
                      <option value="MIMAROPA" <?= $address_region === 'MIMAROPA' ? 'selected' : '' ?>>MIMAROPA</option>
                      <option value="Region V" <?= $address_region === 'Region V' ? 'selected' : '' ?>>Region V</option>
                      <option value="Region VI" <?= $address_region === 'Region VI' ? 'selected' : '' ?>>Region VI</option>
                      <option value="Region VII" <?= $address_region === 'Region VII' ? 'selected' : '' ?>>Region VII</option>
                      <option value="Region VIII" <?= $address_region === 'Region VIII' ? 'selected' : '' ?>>Region VIII</option>
                      <option value="Region IX" <?= $address_region === 'Region IX' ? 'selected' : '' ?>>Region IX</option>
                      <option value="Region X" <?= $address_region === 'Region X' ? 'selected' : '' ?>>Region X</option>
                      <option value="Region XI" <?= $address_region === 'Region XI' ? 'selected' : '' ?>>Region XI</option>
                      <option value="Region XII" <?= $address_region === 'Region XII' ? 'selected' : '' ?>>Region XII</option>
                      <option value="Region XIII" <?= $address_region === 'Region XIII' ? 'selected' : '' ?>>Region XIII</option>
                      <option value="BARMM" <?= $address_region === 'BARMM' ? 'selected' : '' ?>>BARMM</option>
                      <option value="NIR" <?= $address_region === 'NIR' ? 'selected' : '' ?>>NIR</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-xs text-gray-500 mb-1">Area</label>
                    <select id="addressCluster" name="address_cluster" required
                            class="h-11 w-full rounded-md px-3 py-2 border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
                      <option value="">Select Area</option>
                    </select>
                  </div>
                  <div id="addressLocalitySelectWrap">
                    <label class="block text-xs text-gray-500 mb-1">City / Municipality</label>
                    <select id="addressLocality" name="address_locality"
                            class="h-11 w-full rounded-md px-3 py-2 border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
                      <option value="">Select City / Municipality</option>
                    </select>
                  </div>
                  <div id="addressLocalityManualWrap" class="hidden">
                    <label class="block text-xs text-gray-500 mb-1">City / Municipality</label>
                    <input id="addressLocalityManual" name="address_locality_manual" value="<?= h($address_locality_manual) ?>"
                            placeholder="Type city / municipality"
                            class="h-11 w-full rounded-md px-3 py-2 border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
                  </div>
                  <div>
                    <label class="block text-xs text-gray-500 mb-1">Barangay</label>
                    <input id="addressBarangay" name="address_barangay" value="<?= h($address_barangay) ?>"
                            placeholder="Barangay (optional)"
                            class="h-11 w-full rounded-md px-3 py-2 border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
                  </div>
                  <div class="md:col-span-2">
                    <label class="block text-xs text-gray-500 mb-1">Street / Purok</label>
                    <input id="addressStreet" name="address_street" value="<?= h($address_street) ?>"
                            placeholder="Street, Purok, Sitio (optional)"
                            class="h-11 w-full rounded-md px-3 py-2 border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
                  </div>
                </div>
                <input type="hidden" id="addressInput" name="address" value="<?= h($address) ?>">
                <input type="text" id="addressPreview" value="<?= h($address) ?>" readonly
                        class="mt-2 h-11 w-full rounded-md px-3 py-2 border-border-light dark:border-border-dark bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                <p class="text-xs text-gray-500 mt-1">Address is auto-built from your selected region details.</p>
              </div>

              <div>
                <label class="block text-sm font-medium">Cellphone Number *</label>
                <div class="flex items-center gap-2 mt-1">
                  <span class="px-3 py-2 rounded-md bg-gray-100 dark:bg-gray-800 text-gray-600">639</span>
                  <input name="phone_local" id="phoneInput" value="<?= h($phoneLocal) ?>" maxlength="9" inputmode="numeric" pattern="^[0-9]{9}$" required
                         placeholder="XXXXXXXXX"
                         class="h-11 w-full rounded-md px-3 py-2 border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
                </div>
                <p class="text-xs text-gray-500 mt-1">Enter 9 digits only after 639.</p>
              </div>

              <div>
                <label class="block text-sm font-medium">Service Area *</label>
                <select name="service_area_id" required
                        class="mt-1 h-11 w-full rounded-md px-3 py-2 border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
                  <option value="0">Select Area</option>
                  <?php foreach ($areas as $a): ?>
                    <option value="<?= (int)$a['service_area_id'] ?>" <?= $service_area_id === (int)$a['service_area_id'] ? 'selected' : '' ?>>
                      <?= h($a['area_name']) ?><?= $a['municipality'] ? " - ".h($a['municipality']) : "" ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="flex items-center gap-2 lg:items-end">
                <input type="checkbox" id="is_president" name="is_president" class="rounded" <?= $is_president ? 'checked' : '' ?>>
                <label for="is_president" class="text-sm">Farmer President</label>
              </div>
            </div>
          </section>

          <hr class="border-border-light dark:border-border-dark">

          <section>
            <h2 class="text-lg font-bold text-text-light dark:text-text-dark">Farmer Account</h2>
            <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3">
              <div>
                <label class="block text-sm font-medium">Username *</label>
                <input id="usernameInput" name="username" value="<?= h($username) ?>" required class="mt-1 h-11 w-full rounded-md px-3 py-2 border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
                <p class="text-xs text-gray-500 mt-1">Auto-suggested from farmer name. You can change it.</p>
              </div>
              <div>
                <label class="block text-sm font-medium">Password *</label>
                <div class="mt-1 flex gap-2">
                  <input id="farmerPasswordInput" type="text" name="password" required class="h-11 w-full rounded-md px-3 py-2 border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
                  <button type="button" id="generateFarmerPasswordBtn"
                          class="px-3 py-2 rounded-md bg-secondary text-white text-xs font-semibold whitespace-nowrap">
                    Generate
                  </button>
                </div>
                <p class="text-xs text-gray-500 mt-1">Farmer will be forced to change this on first login.</p>
              </div>
            </div>
          </section>

          <hr class="border-border-light dark:border-border-dark">

          <section>
            <h2 class="text-lg font-bold text-text-light dark:text-text-dark">Lot Details</h2>
            <div class="mt-3 grid grid-cols-1 xl:grid-cols-3 gap-6">
              <div class="space-y-4 xl:col-span-1">
                <div>
                  <label class="block text-sm font-medium">Lot Code *</label>
                  <input name="lot_code" value="<?= h($lot_code) ?>" required class="mt-1 h-11 w-full rounded-md px-3 py-2 border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
                </div>

                <div>
                  <label class="block text-sm font-medium">Location Description *</label>
                  <input name="lot_location_desc" value="<?= h($lot_location_desc) ?>" required placeholder="Example: Block 2, near turnout gate #4"
                         class="mt-1 h-11 w-full rounded-md px-3 py-2 border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
                </div>

                <div>
                  <label class="block text-sm font-medium">Title of Lot (Image only) *</label>
                  <input type="file" name="lot_title" accept="image/*" required
                         class="mt-1 h-11 w-full rounded-md px-3 py-2 border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
                  <p class="text-xs text-gray-500 mt-1">Accepted: JPG, PNG, WEBP. Max 5MB. Stored as confidential.</p>
                </div>
              </div>

              <div class="xl:col-span-2">
                <label class="block text-sm font-medium">Location of the Lot (Pin on map) *</label>
                <div class="mt-2 flex flex-col sm:flex-row gap-2">
                  <div class="relative w-full">
                    <span class="material-symbols-outlined pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-[18px] leading-none">search</span>
                    <input type="text" id="lotMapSearch" placeholder="Search landmark or place (example: barangay hall, school)"
                           class="h-10 w-full pl-9 pr-4 rounded-md border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
                  </div>
                  <button type="button" id="lotMapSearchBtn"
                          class="h-10 px-3 rounded-md bg-secondary text-white inline-flex items-center justify-center shrink-0"
                          title="Search location"
                          aria-label="Search location">
                    <span class="material-symbols-outlined text-[20px] leading-none">search</span>
                  </button>
                </div>
                <p id="lotMapSearchStatus" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                  Tip: Search a landmark, then click the map to adjust the exact lot pin.
                </p>
                <div id="lotMapSearchResults" class="mt-2 hidden rounded-lg border border-border-light dark:border-border-dark bg-card-light dark:bg-card-dark max-h-52 overflow-auto"></div>
                <div id="lotMap" class="mt-2 w-full rounded-lg border border-border-light dark:border-border-dark" style="height: 320px;"></div>
                <input type="hidden" id="lotLatitude" name="lot_latitude" value="<?= h($lot_latitude) ?>">
                <input type="hidden" id="lotLongitude" name="lot_longitude" value="<?= h($lot_longitude) ?>">
                <div class="mt-2 text-xs text-gray-600 dark:text-gray-300">
                  Lat: <span id="lotLatText"><?= h($lot_latitude) ?: '-' ?></span>
                  <span class="ml-4">Lng: <span id="lotLngText"><?= h($lot_longitude) ?: '-' ?></span></span>
                </div>
              </div>
            </div>
          </section>

          <div class="flex flex-wrap gap-2 pt-2">
            <button class="px-4 py-2 rounded-full bg-primary text-white font-bold inline-flex items-center gap-1.5">
              <span class="material-symbols-outlined text-[18px] leading-none">save</span>
              <span>Save Farmer</span>
            </button>
            <a class="px-4 py-2 rounded-full bg-gray-200 dark:bg-gray-700 text-text-light dark:text-text-dark inline-flex items-center gap-1.5"
               href="<?= route('farmers') ?>">
              <span class="material-symbols-outlined text-[18px] leading-none">cancel</span>
              <span>Cancel</span>
            </a>
          </div>
        </form>
      </div>
    </main>
  </div>
</div>

<div id="farmerCreateLoadingModal" class="hidden fixed inset-0 z-[10000] bg-black/45">
  <div class="h-full w-full flex items-center justify-center p-4">
    <div class="w-full max-w-sm bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark rounded-xl p-5 shadow-xl text-center">
      <div class="mx-auto mb-3 h-10 w-10 rounded-full border-4 border-primary/30 border-t-primary animate-spin"></div>
      <h3 class="text-base font-black text-text-light dark:text-text-dark">Creating Farmer...</h3>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Please wait while we save profile, account, and lot details.</p>
    </div>
  </div>
</div>

<div
  id="farmerCreateSuccessModal"
  class="<?= $showCreateSuccessModal ? '' : 'hidden ' ?>fixed inset-0 z-[10010] bg-black/50"
  data-autoclose="<?= $showCreateSuccessModal ? '1' : '0' ?>"
  data-redirect="<?= h(route('farmers')) ?>"
>
  <div class="h-full w-full flex items-center justify-center p-4">
    <div class="w-full max-w-sm bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark rounded-xl p-5 shadow-xl text-center">
      <div class="mx-auto mb-3 inline-flex h-12 w-12 items-center justify-center rounded-full bg-primary/15 text-primary">
        <span class="material-symbols-outlined text-[26px] leading-none">check_circle</span>
      </div>
      <h3 class="text-lg font-black text-text-light dark:text-text-dark">Farmer Created Successfully</h3>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Redirecting to Farmers list in 3 seconds...</p>
    </div>
  </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
  const phoneInput = document.getElementById('phoneInput');
  if (phoneInput) {
    phoneInput.addEventListener('input', () => {
      phoneInput.value = phoneInput.value.replace(/[^0-9]/g, '').slice(0, 9);
    });
  }

  const farmerNameInput = document.getElementById('farmerName');
  const usernameInput = document.getElementById('usernameInput');
  const farmerPasswordInput = document.getElementById('farmerPasswordInput');
  const generateFarmerPasswordBtn = document.getElementById('generateFarmerPasswordBtn');
  let usernameTouched = false;

  function suggestUsername(name) {
    const base = (name || '')
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '')
      .slice(0, 12);
    return base || '';
  }

  if (usernameInput) {
    usernameInput.addEventListener('input', () => { usernameTouched = true; });
  }

  if (farmerNameInput && usernameInput) {
    farmerNameInput.addEventListener('input', () => {
      if (!usernameTouched || usernameInput.value.trim() === '') {
        usernameInput.value = suggestUsername(farmerNameInput.value);
      }
    });
  }

  function generatePassword(length = 12) {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';
    let result = '';
    for (let i = 0; i < length; i += 1) {
      result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return result;
  }

  if (farmerPasswordInput && generateFarmerPasswordBtn) {
    const applyGeneratedPassword = () => {
      farmerPasswordInput.value = generatePassword(12);
    };
    generateFarmerPasswordBtn.addEventListener('click', applyGeneratedPassword);
    if (farmerPasswordInput.value.trim() === '') {
      applyGeneratedPassword();
    }
  }

  const latInput = document.getElementById('lotLatitude');
  const lngInput = document.getElementById('lotLongitude');
  const latText = document.getElementById('lotLatText');
  const lngText = document.getElementById('lotLngText');
  const locationDescInput = document.querySelector('input[name="lot_location_desc"]');
  const lotMapSearchInput = document.getElementById('lotMapSearch');
  const lotMapSearchBtn = document.getElementById('lotMapSearchBtn');
  const lotMapSearchStatus = document.getElementById('lotMapSearchStatus');
  const lotMapSearchResults = document.getElementById('lotMapSearchResults');
  const addressRegion = document.getElementById('addressRegion');
  const addressCluster = document.getElementById('addressCluster');
  const addressLocality = document.getElementById('addressLocality');
  const addressLocalityManual = document.getElementById('addressLocalityManual');
  const addressLocalitySelectWrap = document.getElementById('addressLocalitySelectWrap');
  const addressLocalityManualWrap = document.getElementById('addressLocalityManualWrap');
  const addressBarangay = document.getElementById('addressBarangay');
  const addressStreet = document.getElementById('addressStreet');
  const addressInput = document.getElementById('addressInput');
  const addressPreview = document.getElementById('addressPreview');

  const savedAddressRegion = <?= json_encode($address_region, JSON_UNESCAPED_UNICODE) ?>;
  const savedAddressCluster = <?= json_encode($address_cluster, JSON_UNESCAPED_UNICODE) ?>;
  const savedAddressLocality = <?= json_encode($address_locality, JSON_UNESCAPED_UNICODE) ?>;
  const savedAddressLocalityManual = <?= json_encode($address_locality_manual, JSON_UNESCAPED_UNICODE) ?>;

  const regionToCluster = {
    'NCR': ['National Capital Region'],
    'CAR': ['Cordillera Administrative Region'],
    'Region I': ['Ilocos Region'],
    'Region II': ['Cagayan Valley'],
    'Region III': ['Central Luzon'],
    'Region IV-A': ['CALABARZON'],
    'MIMAROPA': ['MIMAROPA Region'],
    'Region V': ['Bicol Region'],
    'Region VI': ['Western Visayas'],
    'Region VII': ['Central Visayas'],
    'Region VIII': ['Eastern Visayas'],
    'Region IX': ['Zamboanga Peninsula'],
    'Region X': ['Northern Mindanao'],
    'Region XI': ['Davao Region'],
    'Region XII': ['SOCCSKSARGEN'],
    'Region XIII': ['Caraga'],
    'BARMM': ['Bangsamoro Autonomous Region in Muslim Mindanao'],
    'NIR': ['Negros Island Region']
  };

  const clusterToLocality = {
    'SOCCSKSARGEN': [
      'General Santos City',
      'Koronadal City',
      'Tacurong City',
      'Kidapawan City',
      'Alabel',
      'Banga',
      'Bagumbayan',
      'Columbio',
      'Esperanza',
      'Glan',
      'Isulan',
      'Kalamansig',
      'Kiamba',
      'Lake Sebu',
      'Lambayong',
      'Lebak',
      'Lutayan',
      'Maasim',
      'Maitum',
      'Malapatan',
      'Malungon',
      'Norala',
      'Palimbang',
      'Polomolok',
      'President Quirino',
      'Senator Ninoy Aquino',
      'Santo Nino',
      "T'boli",
      'Surallah',
      'Tampakan',
      'Tantangan',
      'Tupi'
    ]
  };

  const fillOptions = (selectNode, options, placeholder) => {
    if (!selectNode) return;
    selectNode.innerHTML = '';
    const first = document.createElement('option');
    first.value = '';
    first.textContent = placeholder;
    selectNode.appendChild(first);
    options.forEach((opt) => {
      const el = document.createElement('option');
      el.value = opt;
      el.textContent = opt;
      selectNode.appendChild(el);
    });
  };

  const updateAddressComposed = () => {
    if (!addressInput || !addressPreview) return;
    const locality = (addressLocality?.value || '').trim() || (addressLocalityManual?.value || '').trim();
    const baseParts = [
      (addressStreet?.value || '').trim(),
      (addressBarangay?.value || '').trim(),
      locality,
      (addressCluster?.value || '').trim(),
      (addressRegion?.value || '').trim()
    ].filter(Boolean);
    if (baseParts.length > 0) {
      baseParts.push('Philippines');
    }
    const composed = baseParts.join(', ');
    addressInput.value = composed;
    addressPreview.value = composed;
  };

  const syncClusterOptions = (selected = '') => {
    const region = (addressRegion?.value || '').trim();
    const options = regionToCluster[region] || [];
    fillOptions(addressCluster, options, 'Select Area');
    if (selected && options.includes(selected)) {
      addressCluster.value = selected;
    }
  };

  const syncLocalityOptions = (selected = '') => {
    const cluster = (addressCluster?.value || '').trim();
    const options = clusterToLocality[cluster] || [];
    const hasPresetLocalities = options.length > 0;

    if (addressLocalitySelectWrap) addressLocalitySelectWrap.classList.toggle('hidden', !hasPresetLocalities);
    if (addressLocalityManualWrap) addressLocalityManualWrap.classList.toggle('hidden', hasPresetLocalities);

    if (hasPresetLocalities) {
      fillOptions(addressLocality, options, 'Select City / Municipality');
      if (addressLocality) {
        addressLocality.required = true;
      }
      if (addressLocalityManual) {
        addressLocalityManual.required = false;
        addressLocalityManual.value = '';
      }
      if (selected && options.includes(selected)) {
        addressLocality.value = selected;
      }
    } else {
      if (addressLocality) {
        fillOptions(addressLocality, [], 'Select City / Municipality');
        addressLocality.required = false;
      }
      if (addressLocalityManual) {
        addressLocalityManual.required = true;
        if (selected && addressLocalityManual.value.trim() === '') {
          addressLocalityManual.value = selected;
        }
      }
    }
  };

  if (addressRegion && addressCluster && addressLocality) {
    if (savedAddressRegion) {
      addressRegion.value = savedAddressRegion;
    }
    syncClusterOptions(savedAddressCluster);
    syncLocalityOptions(savedAddressLocality);
    if (addressLocalityManual && savedAddressLocalityManual && addressLocalityManual.value.trim() === '') {
      addressLocalityManual.value = savedAddressLocalityManual;
    }
    updateAddressComposed();

    addressRegion.addEventListener('change', () => {
      syncClusterOptions('');
      syncLocalityOptions('');
      updateAddressComposed();
    });
    addressCluster.addEventListener('change', () => {
      syncLocalityOptions('');
      updateAddressComposed();
    });
    addressLocality.addEventListener('change', updateAddressComposed);
    if (addressLocalityManual) addressLocalityManual.addEventListener('input', updateAddressComposed);
    if (addressBarangay) addressBarangay.addEventListener('input', updateAddressComposed);
    if (addressStreet) addressStreet.addEventListener('input', updateAddressComposed);
  }

  const SOX_BOUNDS = L.latLngBounds([5.45, 123.50], [7.55, 126.35]);
  const SOX_CENTER = [6.35, 124.95];
  const SOX_VIEWBOX = '123.50,7.55,126.35,5.45';
  const SOX_HINT_RE = /koronadal|general santos|gensan|kidapawan|tacurong|cotabato|south cotabato|sultan kudarat|sarangani|soccsksargen/i;

  const startLat = latInput.value ? parseFloat(latInput.value) : 6.2;
  const startLng = lngInput.value ? parseFloat(lngInput.value) : 125.0;
  const hasSaved = latInput.value !== '' && lngInput.value !== '';

  const savedInSox = hasSaved && SOX_BOUNDS.contains([startLat, startLng]);
  const lotMap = L.map('lotMap', {
    maxBounds: SOX_BOUNDS,
    maxBoundsViscosity: 1.0,
    minZoom: 8
  }).setView(savedInSox ? [startLat, startLng] : SOX_CENTER, savedInSox ? 15 : 9);

  if (!savedInSox) {
    lotMap.fitBounds(SOX_BOUNDS);
  }

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(lotMap);

  let lotMarker = null;
  function setLotMarker(lat, lng) {
    if (lotMarker) lotMap.removeLayer(lotMarker);
    lotMarker = L.marker([lat, lng]).addTo(lotMap);
    latInput.value = lat.toFixed(7);
    lngInput.value = lng.toFixed(7);
    latText.textContent = latInput.value;
    lngText.textContent = lngInput.value;
  }

  if (hasSaved) setLotMarker(startLat, startLng);
  lotMap.on('click', (e) => setLotMarker(e.latlng.lat, e.latlng.lng));

  let lotMapSearchTimer = null;
  let lotMapSearchAbort = null;
  let lotMapSearchSeq = 0;

  function hideLotMapSearchResults() {
    if (!lotMapSearchResults) return;
    lotMapSearchResults.classList.add('hidden');
    lotMapSearchResults.innerHTML = '';
  }

  function pickLotSearchResult(item) {
    const lat = parseFloat(item?.lat ?? '');
    const lng = parseFloat(item?.lon ?? '');
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
      lotMapSearchStatus.textContent = 'Location result is invalid.';
      return;
    }

    lotMap.setView([lat, lng], 16);
    setLotMarker(lat, lng);

    const resultLabel = (item.display_name || 'Location found').split(',').slice(0, 3).join(',').trim();
    lotMapSearchStatus.textContent = `Found: ${resultLabel}`;
    hideLotMapSearchResults();

    if (locationDescInput && locationDescInput.value.trim() === '' && item.display_name) {
      locationDescInput.value = item.display_name;
    }
  }

  function renderLotMapSearchResults(items) {
    if (!lotMapSearchResults) return;
    lotMapSearchResults.innerHTML = '';

    if (!items.length) {
      hideLotMapSearchResults();
      return;
    }

    items.forEach((item) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'w-full text-left px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-800 border-b border-border-light dark:border-border-dark last:border-b-0';
      btn.textContent = (item.display_name || '').split(',').slice(0, 4).join(',').trim() || 'Unknown location';
      btn.addEventListener('click', () => pickLotSearchResult(item));
      lotMapSearchResults.appendChild(btn);
    });

    lotMapSearchResults.classList.remove('hidden');
  }

  function isLotResultInsideSox(item) {
    const lat = parseFloat(item?.lat ?? '');
    const lng = parseFloat(item?.lon ?? '');
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return false;
    return SOX_BOUNDS.contains([lat, lng]);
  }

  function buildLotNominatimUrl(q, bounded) {
    const params = new URLSearchParams({
      format: 'jsonv2',
      limit: '8',
      countrycodes: 'ph',
      viewbox: SOX_VIEWBOX,
      bounded: bounded ? '1' : '0',
      q,
    });
    return `https://nominatim.openstreetmap.org/search?${params.toString()}`;
  }

  async function fetchLotNominatimCandidates(query, signal) {
    const base = query.trim();
    const variants = Array.from(new Set([
      base,
      SOX_HINT_RE.test(base) ? base : `${base}, SOCCSKSARGEN, Philippines`,
      /philippines/i.test(base) ? base : `${base}, Philippines`,
    ]));

    // Strict SOX-bounded pass.
    for (const q of variants) {
      try {
        const res = await fetch(buildLotNominatimUrl(q, true), {
          signal,
          headers: { 'Accept': 'application/json' }
        });
        if (!res.ok) continue;
        const data = await res.json();
        if (Array.isArray(data) && data.length > 0) return data;
      } catch (e) {
        if (e?.name === 'AbortError') throw e;
      }
    }

    // Broader PH pass, then keep only SOX results.
    for (const q of variants) {
      try {
        const res = await fetch(buildLotNominatimUrl(q, false), {
          signal,
          headers: { 'Accept': 'application/json' }
        });
        if (!res.ok) continue;
        const data = await res.json();
        if (!Array.isArray(data) || data.length === 0) continue;
        const inside = data.filter(isLotResultInsideSox);
        if (inside.length > 0) return inside;
      } catch (e) {
        if (e?.name === 'AbortError') throw e;
      }
    }

    return [];
  }

  async function performLotMapSearch(pickFirst = false) {
    if (!lotMapSearchInput || !lotMapSearchBtn || !lotMapSearchStatus) return;
    const query = lotMapSearchInput.value.trim();
    if (query.length < 3) {
      lotMapSearchStatus.textContent = 'Type at least 3 letters to search.';
      hideLotMapSearchResults();
      return;
    }

    if (lotMapSearchAbort) lotMapSearchAbort.abort();
    const controller = new AbortController();
    lotMapSearchAbort = controller;
    const seq = ++lotMapSearchSeq;
    const timeoutId = window.setTimeout(() => controller.abort(), 10000);

    lotMapSearchBtn.disabled = true;
    lotMapSearchStatus.textContent = 'Searching...';

    try {
      const data = await fetchLotNominatimCandidates(query, controller.signal);
      if (seq !== lotMapSearchSeq) return;

      if (!Array.isArray(data) || data.length === 0) {
        lotMapSearchStatus.textContent = 'No SOCCSKSARGEN match found. Try adding municipality (e.g., Koronadal).';
        hideLotMapSearchResults();
        return;
      }

      if (pickFirst) {
        pickLotSearchResult(data[0]);
        return;
      }

      lotMapSearchStatus.textContent = 'Select a location from the list below.';
      renderLotMapSearchResults(data);
    } catch (e) {
      if (seq !== lotMapSearchSeq) return;
      if (e?.name === 'AbortError') {
        lotMapSearchStatus.textContent = 'Search timed out. Please try again.';
        hideLotMapSearchResults();
        return;
      }
      lotMapSearchStatus.textContent = 'Search failed. Check internet connection and try again.';
      hideLotMapSearchResults();
    } finally {
      window.clearTimeout(timeoutId);
      if (seq === lotMapSearchSeq) {
        lotMapSearchBtn.disabled = false;
      }
    }
  }

  function searchLotLocation() {
    return performLotMapSearch(true);
  }

  if (lotMapSearchBtn && lotMapSearchInput) {
    lotMapSearchBtn.addEventListener('click', searchLotLocation);
    lotMapSearchInput.addEventListener('input', () => {
      if (lotMapSearchTimer !== null) window.clearTimeout(lotMapSearchTimer);
      lotMapSearchTimer = window.setTimeout(() => {
        performLotMapSearch(false);
      }, 350);
    });

    lotMapSearchInput.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        searchLotLocation();
      }
    });
  }

  document.addEventListener('click', (e) => {
    if (!lotMapSearchResults || !lotMapSearchInput) return;
    if (lotMapSearchResults.contains(e.target) || e.target === lotMapSearchInput) return;
    hideLotMapSearchResults();
  });

  const farmerCreateForm = document.getElementById('farmerCreateForm');
  const farmerCreateLoadingModal = document.getElementById('farmerCreateLoadingModal');
  if (farmerCreateForm && farmerCreateLoadingModal) {
    farmerCreateForm.addEventListener('submit', () => {
      farmerCreateLoadingModal.classList.remove('hidden');
    });
  }

  const farmerCreateSuccessModal = document.getElementById('farmerCreateSuccessModal');
  if (farmerCreateSuccessModal && farmerCreateSuccessModal.dataset.autoclose === '1') {
    const redirectUrl = farmerCreateSuccessModal.dataset.redirect || '';
    window.setTimeout(() => {
      if (redirectUrl) window.location.href = redirectUrl;
    }, 3000);
  }
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
