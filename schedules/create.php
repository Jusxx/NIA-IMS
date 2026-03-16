<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_roles(['Administrator','Operations Staff','IMO','SWRFT']);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/sms.php';

$active   = 'schedules';
$topTitle = 'Add Schedule';

$error = '';

// ✅ optional: create schedule from a request
$request_id = (int)($_GET['request_id'] ?? 0);
if ($request_id > 0 && !in_array(role(), ['Administrator','Irrigation Association','SWRFT'], true)) {
  http_response_code(403);
  exit('Only authorized request processors can approve and schedule a linked request.');
}

$schedule_date = date('Y-m-d');
$start_time = '08:00';
$end_time = '10:00';
$service_area_id = 0;
$status = 'Active';
$assigned_user_id = 0;

// ✅ Request + farmer info (if request_id provided)
$requestRow = null;
$requestLot = null;
$requestLotLat = null;
$requestLotLng = null;
$hasRequestLotMap = false;
$lockedServiceAreaId = 0;
$canViewConfidentialLotTitle = (role() === 'Administrator');

if ($request_id > 0) {
  $stmt = $conn->prepare("
    SELECT r.request_id, r.farmer_id, r.request_type, r.request_details, r.status, r.request_stage,
           r.lot_id, r.service_area_id AS request_service_area_id,
           r.location_desc AS request_location_desc,
           r.latitude AS request_latitude, r.longitude AS request_longitude,
           r.location_lat, r.location_lng,
           f.farmer_name, f.phone, f.service_area_id
    FROM farmer_requests r
    LEFT JOIN farmers f ON f.farmer_id = r.farmer_id
    WHERE r.request_id = ?
    LIMIT 1
  ");
  $stmt->bind_param("i", $request_id);
  $stmt->execute();
  $requestRow = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if ($requestRow) {
    // auto pick request area first, then fallback to farmer area
    if (!empty($requestRow['request_service_area_id'])) {
      $service_area_id = (int)$requestRow['request_service_area_id'];
    } elseif (!empty($requestRow['service_area_id'])) {
      $service_area_id = (int)$requestRow['service_area_id'];
    }
    $lockedServiceAreaId = $service_area_id > 0 ? $service_area_id : 0;

    $farmerId = (int)($requestRow['farmer_id'] ?? 0);
    $lotId = (int)($requestRow['lot_id'] ?? 0);
    $stmtLot = null;

    if ($lotId > 0) {
      $stmtLot = $conn->prepare("
        SELECT lot_id, lot_code, location_desc, latitude, longitude, title_photo_path
        FROM farmer_lots
        WHERE lot_id = ?
        LIMIT 1
      ");
      $stmtLot->bind_param("i", $lotId);
    } elseif ($farmerId > 0) {
      $stmtLot = $conn->prepare("
        SELECT lot_id, lot_code, location_desc, latitude, longitude, title_photo_path
        FROM farmer_lots
        WHERE farmer_id = ?
        ORDER BY created_at DESC, lot_id DESC
        LIMIT 1
      ");
      $stmtLot->bind_param("i", $farmerId);
    }

    if ($stmtLot) {
      $stmtLot->execute();
      $requestLot = $stmtLot->get_result()->fetch_assoc();
      $stmtLot->close();
    }

    $reqLat = $requestRow['request_latitude'] ?? null;
    $reqLng = $requestRow['request_longitude'] ?? null;
    if (($reqLat === null || $reqLat === '') && isset($requestRow['location_lat'])) {
      $reqLat = $requestRow['location_lat'];
    }
    if (($reqLng === null || $reqLng === '') && isset($requestRow['location_lng'])) {
      $reqLng = $requestRow['location_lng'];
    }

    if (!$requestLot) {
      $requestLot = [
        'lot_id' => $lotId > 0 ? $lotId : null,
        'lot_code' => null,
        'location_desc' => $requestRow['request_location_desc'] ?? null,
        'latitude' => is_numeric($reqLat) ? (float)$reqLat : null,
        'longitude' => is_numeric($reqLng) ? (float)$reqLng : null,
        'title_photo_path' => null,
      ];
    } else {
      if (empty($requestLot['location_desc']) && !empty($requestRow['request_location_desc'])) {
        $requestLot['location_desc'] = $requestRow['request_location_desc'];
      }
      if ((!isset($requestLot['latitude']) || $requestLot['latitude'] === null || $requestLot['latitude'] === '') && is_numeric($reqLat)) {
        $requestLot['latitude'] = (float)$reqLat;
      }
      if ((!isset($requestLot['longitude']) || $requestLot['longitude'] === null || $requestLot['longitude'] === '') && is_numeric($reqLng)) {
        $requestLot['longitude'] = (float)$reqLng;
      }
    }

    if (
      $requestLot &&
      isset($requestLot['latitude'], $requestLot['longitude']) &&
      is_numeric($requestLot['latitude']) &&
      is_numeric($requestLot['longitude'])
    ) {
      $requestLotLat = (float)$requestLot['latitude'];
      $requestLotLng = (float)$requestLot['longitude'];
      $hasRequestLotMap = true;
    }
  } else {
    $error = "Invalid request_id. Request not found.";
    $request_id = 0;
  }
}

// Load areas dropdown
$areas = [];
$stmt = $conn->prepare("SELECT service_area_id, area_name, municipality, province FROM service_areas ORDER BY area_name ASC");
$stmt->execute();
$areas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$areas = filter_focus_service_area_rows($areas);
$allowedAreaIds = array_map('intval', array_column($areas, 'service_area_id'));

if ($lockedServiceAreaId > 0 && !in_array($lockedServiceAreaId, $allowedAreaIds, true)) {
  http_response_code(403);
  exit('The linked request is outside the focused irrigation locations.');
}

// Load technicians dropdown (optional)
$techs = [];
$stmt = $conn->prepare("SELECT user_id, fullname, phone FROM users WHERE role='Irrigation Technician' AND is_active=1 ORDER BY fullname ASC");
$stmt->execute();
$techs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $service_area_id  = (int)($_POST['service_area_id'] ?? 0);
  if ($lockedServiceAreaId > 0) {
    // Do not allow changing area when schedule is created from a linked request.
    $service_area_id = $lockedServiceAreaId;
  }
  $schedule_date_in = trim($_POST['schedule_date'] ?? '');
  $start_time_in    = trim($_POST['start_time'] ?? '');
  $end_time_in      = trim($_POST['end_time'] ?? '');
  $status           = trim($_POST['status'] ?? 'Active');
  $assigned_user_id = (int)($_POST['assigned_user_id'] ?? 0);

  // ✅ keep form values on error
  $schedule_date = $schedule_date_in ?: $schedule_date;
  $start_time    = $start_time_in ?: $start_time;
  $end_time      = $end_time_in ?: $end_time;

  // ✅ Timezone
  $tz = new DateTimeZone('Asia/Manila');
  $now = new DateTimeImmutable('now', $tz);

  // ✅ Parse date
  $dateObj = DateTimeImmutable::createFromFormat('Y-m-d', $schedule_date_in, $tz);

  // Normalize possible 12h display (e.g. "07:00 pm") into 24h "H:i"
  $startNorm = ($start_time_in !== '') ? date('H:i', strtotime($start_time_in)) : '';
  $endNorm   = ($end_time_in !== '') ? date('H:i', strtotime($end_time_in)) : '';

  // Parse full datetime
  $startObj = ($dateObj && $startNorm !== '')
    ? DateTimeImmutable::createFromFormat('Y-m-d H:i', $schedule_date_in . ' ' . $startNorm, $tz)
    : null;

  $endObj = ($dateObj && $endNorm !== '')
    ? DateTimeImmutable::createFromFormat('Y-m-d H:i', $schedule_date_in . ' ' . $endNorm, $tz)
    : null;

  // ✅ Validations
  if ($service_area_id <= 0) {
    $error = "Please select an Area.";
  } elseif (!in_array($service_area_id, $allowedAreaIds, true)) {
    $error = "Please select one of the focused irrigation areas only.";

  } elseif (!$dateObj || $dateObj->format('Y-m-d') !== $schedule_date_in) {
    $error = "Invalid schedule date.";

  } elseif (!$startObj || !$endObj) {
    $error = "Start time and End time are required.";

  } elseif ($endObj <= $startObj) {
    $error = "End time must be later than Start time.";

  } elseif ($dateObj->setTime(0,0) < $now->setTime(0,0)) {
    $error = "Schedule date cannot be in the past.";

  } elseif ($dateObj->format('Y-m-d') === $now->format('Y-m-d') && $startObj <= $now) {
    $error = "Start time must be later than the current time.";

  } elseif (!in_array($status, ['Active','Completed','Cancelled'], true)) {
    $error = "Invalid status.";
  }

  if ($error === '') {

    // ✅ Use normalized values for DB
    $schedule_date_db = $dateObj->format('Y-m-d');
    $start_time_db    = $startObj->format('H:i:s');
    $end_time_db      = $endObj->format('H:i:s');

    // ✅ Prevent overlap
    $stmt = $conn->prepare("
      SELECT COUNT(*) c
      FROM irrigation_schedules
      WHERE service_area_id = ?
        AND schedule_date = ?
        AND NOT (end_time <= ? OR start_time >= ?)
        AND status <> 'Cancelled'
    ");
    $stmt->bind_param("isss", $service_area_id, $schedule_date_db, $start_time_db, $end_time_db);
    $stmt->execute();
    $conflict = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
    $stmt->close();

    if ($conflict > 0) {
      $error = "Conflict: schedule overlaps an existing one.";
    } else {

      $created_by = (int)($_SESSION['user']['user_id'] ?? 0);
      $conn->begin_transaction();

      try {
        // ✅ Insert schedule (link request if exists)
        if ($request_id > 0) {
          $stmt = $conn->prepare("
            INSERT INTO irrigation_schedules
              (service_area_id, schedule_date, start_time, end_time, created_by, status, request_id)
            VALUES (?,?,?,?,?,?,?)
          ");
          $stmt->bind_param("isssisi", $service_area_id, $schedule_date_db, $start_time_db, $end_time_db, $created_by, $status, $request_id);
        } else {
          $stmt = $conn->prepare("
            INSERT INTO irrigation_schedules
              (service_area_id, schedule_date, start_time, end_time, created_by, status)
            VALUES (?,?,?,?,?,?)
          ");
          $stmt->bind_param("isssis", $service_area_id, $schedule_date_db, $start_time_db, $end_time_db, $created_by, $status);
        }

        if (!$stmt->execute()) {
          throw new Exception("Schedule insert failed: " . $stmt->error);
        }
        $newScheduleId = (int)$conn->insert_id;
        $stmt->close();

        // ✅ Auto-create task (SAFE NULL handling)
        $taskStatus = "Due";
        if ($assigned_user_id > 0) {
          $stmt2 = $conn->prepare("
            INSERT INTO tasks (schedule_id, assigned_user_id, status)
            VALUES (?, ?, ?)
          ");
          $stmt2->bind_param("iis", $newScheduleId, $assigned_user_id, $taskStatus);
        } else {
          $stmt2 = $conn->prepare("
            INSERT INTO tasks (schedule_id, assigned_user_id, status)
            VALUES (?, NULL, ?)
          ");
          $stmt2->bind_param("is", $newScheduleId, $taskStatus);
        }

        if (!$stmt2->execute()) {
          throw new Exception("Task insert failed: " . $stmt2->error);
        }
        $stmt2->close();

        // SMS notify assigned technician (10 minutes before start, or immediate if near/past window)
        if ($assigned_user_id > 0) {
          $stmtTech = $conn->prepare("
            SELECT fullname, phone
            FROM users
            WHERE user_id = ?
              AND role = 'Irrigation Technician'
            LIMIT 1
          ");
          $stmtTech->bind_param("i", $assigned_user_id);
          $stmtTech->execute();
          $techInfo = $stmtTech->get_result()->fetch_assoc();
          $stmtTech->close();

          $techPhone = (string)($techInfo['phone'] ?? '');
          $techName = (string)($techInfo['fullname'] ?? '');
          if ($techPhone !== '') {
            $reminderAt = $startObj->modify('-10 minutes');
            $scheduleTime = ($reminderAt > $now) ? $reminderAt->format('Y-m-d H:i') : null;
            $message = sms_message_technician_pending_task($techName);
            send_sms_and_log(
              $conn,
              null,
              $techPhone,
              $message,
              "Info",
              ($request_id > 0 ? $request_id : null),
              null,
              $scheduleTime
            );
          }
        }

        // ✅ Update request stage if linked
        if ($request_id > 0) {
          $newStage = ($assigned_user_id > 0) ? 'Assigned' : 'Scheduled';

          $stmtU = $conn->prepare("
            UPDATE farmer_requests
            SET request_stage = ?,
                status = 'Approved',
                assigned_technician_id = ?
            WHERE request_id = ?
          ");
          $stmtU->bind_param("sii", $newStage, $assigned_user_id, $request_id);
          $stmtU->execute();
          $stmtU->close();

          // SMS notify farmer about scheduled/assigned
          $phone = $requestRow['phone'] ?? null;
          $farmerId = (int)($requestRow['farmer_id'] ?? 0);
          if (!$phone || !$farmerId) {
            $stmtF = $conn->prepare("
              SELECT f.farmer_id, f.phone
              FROM farmer_requests r
              JOIN farmers f ON f.farmer_id = r.farmer_id
              WHERE r.request_id = ?
              LIMIT 1
            ");
            $stmtF->bind_param("i", $request_id);
            $stmtF->execute();
            $info = $stmtF->get_result()->fetch_assoc();
            $stmtF->close();
            $farmerId = (int)($info['farmer_id'] ?? 0);
            $phone = $info['phone'] ?? null;
          }

          if ($phone && $farmerId > 0) {
            $dateLabel = $schedule_date_db;
            $timeLabel = substr($start_time_db, 0, 5) . "-" . substr($end_time_db, 0, 5);
            $msg = ($newStage === 'Assigned')
              ? "NIA: Request #{$request_id} assigned. {$dateLabel} {$timeLabel}."
              : "NIA: Request #{$request_id} scheduled. {$dateLabel} {$timeLabel}.";

            send_sms_and_log(
              $conn,
              $farmerId,
              $phone,
              $msg,
              "Info",
              $request_id,
              null
            );
          }
        }

        // ✅ System log
        $uid = (int)($_SESSION['user']['user_id'] ?? 0);
        $action = "Schedule Created";
        $desc = "Created schedule #{$newScheduleId} and auto-created task."
              . ($request_id > 0 ? " Linked to request #{$request_id}." : "");
        $stmt3 = $conn->prepare("INSERT INTO system_logs(user_id, action, description) VALUES(?,?,?)");
        $stmt3->bind_param("iss", $uid, $action, $desc);
        $stmt3->execute();
        $stmt3->close();

        $conn->commit();

        header("Location: " . route('schedules'));
        exit;

      } catch (Exception $e) {
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
      <div class="max-w-6xl bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark rounded-lg p-6">

        <div class="flex items-center justify-between gap-3">
          <div>
            <h1 class="text-xl font-black text-text-light dark:text-text-dark">Add Schedule</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Create an irrigation schedule.</p>
          </div>

          <a class="px-3 py-2 rounded-DEFAULT bg-gray-200 dark:bg-gray-700 text-text-light dark:text-text-dark"
             href="<?= route('schedules') ?>">Back</a>
        </div>

        <?php if ($error): ?>
          <div class="mt-4 p-3 rounded bg-red-100 text-red-700"><?= h($error) ?></div>
        <?php endif; ?>

        <div class="mt-4 grid grid-cols-1 xl:grid-cols-3 gap-6">
          <div class="<?= ($request_id > 0 && $requestRow) ? 'xl:col-span-2' : 'xl:col-span-3' ?>">
            <form method="POST" class="space-y-4">

              <div>
                <label class="text-sm text-text-light dark:text-text-dark">Area *</label>
                <?php if ($lockedServiceAreaId > 0): ?>
                  <input type="hidden" name="service_area_id" value="<?= (int)$lockedServiceAreaId ?>">
                <?php endif; ?>
                <select name="service_area_id" required <?= $lockedServiceAreaId > 0 ? 'disabled' : '' ?>
                        class="mt-1 w-full rounded-DEFAULT border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark <?= $lockedServiceAreaId > 0 ? 'opacity-70 cursor-not-allowed' : '' ?>">
                  <option value="0">Select Area</option>
                  <?php foreach ($areas as $a): ?>
                    <option value="<?= (int)$a['service_area_id'] ?>"
                      <?= $service_area_id === (int)$a['service_area_id'] ? 'selected' : '' ?>>
                      <?= h($a['area_name']) ?><?= $a['municipality'] ? " - ".h($a['municipality']) : "" ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <?php if ($lockedServiceAreaId > 0): ?>
                  <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Area is locked to the linked request.</p>
                <?php endif; ?>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                  <label class="text-sm text-text-light dark:text-text-dark">Date *</label>
                  <input type="date" name="schedule_date" required
                         value="<?= h($schedule_date) ?>"
                         min="<?= date('Y-m-d') ?>"
                         class="mt-1 w-full rounded-DEFAULT border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
                </div>
                <div>
                  <label class="text-sm text-text-light dark:text-text-dark">Start *</label>
                  <input type="time" name="start_time" required value="<?= h($start_time) ?>"
                         class="mt-1 w-full rounded-DEFAULT border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
                </div>
                <div>
                  <label class="text-sm text-text-light dark:text-text-dark">End *</label>
                  <input type="time" name="end_time" required value="<?= h($end_time) ?>"
                         class="mt-1 w-full rounded-DEFAULT border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
                </div>
              </div>

              <div>
                <label class="text-sm text-text-light dark:text-text-dark">Assign Technician (optional)</label>
                <select name="assigned_user_id"
                        class="mt-1 w-full rounded-DEFAULT border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
                  <option value="0">Unassigned</option>
                  <?php foreach ($techs as $t): ?>
                    <option value="<?= (int)$t['user_id'] ?>" <?= $assigned_user_id === (int)$t['user_id'] ? 'selected' : '' ?>>
                      <?= h($t['fullname']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div>
                <label class="text-sm text-text-light dark:text-text-dark">Status</label>
                <select name="status"
                        class="mt-1 w-full rounded-DEFAULT border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
                  <?php foreach (['Active','Completed','Cancelled'] as $s): ?>
                    <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= $s ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="flex gap-2">
                <button class="px-4 py-2 rounded-DEFAULT bg-primary text-white font-bold">Save</button>
                <a class="px-4 py-2 rounded-DEFAULT bg-gray-200 dark:bg-gray-700 text-text-light dark:text-text-dark"
                   href="<?= route('schedules') ?>">Cancel</a>
              </div>

            </form>
          </div>

          <?php if ($request_id > 0 && $requestRow): ?>
            <aside class="xl:col-span-1">
              <div class="rounded-lg border border-border-light dark:border-border-dark p-4 text-sm text-text-light dark:text-text-dark">
                <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Linked Request</div>
                <div class="mt-1"><b>Request #<?= (int)$requestRow['request_id'] ?></b> — <?= h($requestRow['request_type']) ?></div>
                <div>Farmer: <b><?= h($requestRow['farmer_name'] ?? '—') ?></b></div>
                <div class="mt-1 text-gray-600 dark:text-gray-300"><?= h($requestRow['request_details'] ?? '') ?></div>
              </div>

              <?php if ($requestLot): ?>
                <div class="mt-3 rounded-lg border border-border-light dark:border-border-dark p-4 text-sm text-text-light dark:text-text-dark">
                  <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Farmer Lot Location</div>
                  <div class="mt-1">Lot Code: <b><?= h($requestLot['lot_code'] ?? '—') ?></b></div>
                  <div>Location: <b><?= h($requestLot['location_desc'] ?? '—') ?></b></div>
                  <div>
                    Coordinates:
                    <?php if ($hasRequestLotMap): ?>
                      <b><?= h(number_format($requestLotLat, 7, '.', '')) ?>, <?= h(number_format($requestLotLng, 7, '.', '')) ?></b>
                    <?php else: ?>
                      <b>—</b>
                    <?php endif; ?>
                  </div>
                  <?php if (!empty($requestLot['title_photo_path'])): ?>
                    <div class="mt-1">
                      Lot title:
                      <?php if ($canViewConfidentialLotTitle): ?>
                        <a class="text-primary underline" target="_blank" rel="noopener"
                          href="<?= h(base_path(ltrim((string)$requestLot['title_photo_path'], '/'))) ?>">View confidential image</a>
                      <?php else: ?>
                        <span class="font-semibold text-gray-500 dark:text-gray-400">Confidential (Admin only)</span>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>
                  <?php if ($hasRequestLotMap): ?>
                    <div id="requestLotMap" class="mt-3 w-full rounded-lg border border-border-light dark:border-border-dark" style="height: 220px;"></div>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </aside>
          <?php endif; ?>
        </div>

      </div>
    </main>
  </div>
</div>

<script>
const dateInput  = document.querySelector('input[name="schedule_date"]');
const startInput = document.querySelector('input[name="start_time"]');

function updateMinStartTime() {
  if (!dateInput || !startInput) return;

  const now = new Date();
  const today = now.toISOString().split('T')[0];

  if (dateInput.value === today) {
    const hh = String(now.getHours()).padStart(2,'0');
    const mm = String(now.getMinutes()).padStart(2,'0');
    startInput.min = `${hh}:${mm}`;
  } else {
    startInput.removeAttribute('min');
  }
}

if (dateInput) {
  dateInput.addEventListener('change', updateMinStartTime);
  window.addEventListener('load', updateMinStartTime);
}
</script>

<?php if ($hasRequestLotMap): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function() {
  if (typeof L === 'undefined') return;
  const mapEl = document.getElementById('requestLotMap');
  if (!mapEl) return;

  const lat = <?= json_encode($requestLotLat) ?>;
  const lng = <?= json_encode($requestLotLng) ?>;
  const lotMap = L.map(mapEl).setView([lat, lng], 15);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(lotMap);
  L.marker([lat, lng]).addTo(lotMap).bindPopup('Farmer lot location');
})();
</script>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
