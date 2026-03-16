<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_roles(['Farmer']);
require_once __DIR__ . '/../includes/config.php';

$active   = 'my_requests';
$topTitle = 'My Requests';

$uid = (int)($_SESSION['user']['user_id'] ?? 0);

// Get farmer_id linked to this user
$farmer = null;
$stmt = $conn->prepare("SELECT farmer_id, farmer_name FROM farmers WHERE user_id=? LIMIT 1");
$stmt->bind_param("i", $uid);
$stmt->execute();
$farmer = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$farmer) {
  http_response_code(403);
  exit("No farmer profile linked to this account. Ask admin to link farmers.user_id to your user.");
}

$farmer_id = (int)$farmer['farmer_id'];
$error = '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['success']);

// -----------------------------
// Helpers
// -----------------------------
function normalize_phone_to_e164(?string $phone): ?string {
  if (!$phone) return null;
  $p = preg_replace('/\D+/', '', $phone);
  if ($p === '') return null;

  // Basic PH normalization:
  // 09xxxxxxxxx -> 639xxxxxxxxx
  if (str_starts_with($p, '09') && strlen($p) === 11) return '63' . substr($p, 1);
  // 639xxxxxxxxx stays same
  if (str_starts_with($p, '63') && strlen($p) >= 12) return $p;
  return $p;
}

function safe_float_or_null(string $v): ?float {
  $v = trim($v);
  if ($v === '' || !is_numeric($v)) return null;
  return (float)$v;
}

// -----------------------------
// Create request (Farmer only)
// -----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $type = trim($_POST['request_type'] ?? '');
  $details = trim($_POST['request_details'] ?? '');

  $issue_category = trim($_POST['issue_category'] ?? '');
  $urgency = trim($_POST['urgency'] ?? 'Normal');

  $preferred_date = trim($_POST['preferred_date'] ?? '');
  $preferred_start = trim($_POST['preferred_start'] ?? '');
  $preferred_end = trim($_POST['preferred_end'] ?? '');

  $location_desc = trim($_POST['location_desc'] ?? '');
  $landmark = trim($_POST['landmark'] ?? '');
  $maps_link = trim($_POST['maps_link'] ?? '');

  $latitude  = safe_float_or_null($_POST['latitude'] ?? '');
  $longitude = safe_float_or_null($_POST['longitude'] ?? '');

  // Validate
  $allowedTypes = ['Irrigation Request','Schedule Adjustment','Water Allocation','Technical Concern'];
  $allowedUrgency = ['Normal','Urgent'];

  $allowedCategories = [
    '', // allow empty
    'Canal Blockage',
    'No Water Flow',
    'Low Water Pressure',
    'Broken Gate / Valve',
    'Leakage',
    'Schedule Concern',
    'Other'
  ];

  if (!in_array($type, $allowedTypes, true)) {
    $error = "Invalid request type.";
  } elseif ($details === '') {
    $error = "Details is required.";
  } elseif (!in_array($urgency, $allowedUrgency, true)) {
    $error = "Invalid urgency.";
  } elseif (!in_array($issue_category, $allowedCategories, true)) {
    $error = "Invalid issue category.";
  } elseif ($location_desc === '') {
    $error = "Location description is required (so technician can find your area).";
  } else {

    // Preferred date/time validation (optional, but if date is set, allow only today+)
    if ($preferred_date !== '') {
      $dt = DateTimeImmutable::createFromFormat('Y-m-d', $preferred_date);
      $today = new DateTimeImmutable('today');

      if (!$dt) {
        $error = "Invalid preferred date.";
      } elseif ($dt < $today) {
        $error = "Preferred date cannot be in the past.";
      } elseif ($preferred_start !== '' && $preferred_end !== '') {
        if (strtotime($preferred_start) >= strtotime($preferred_end)) {
          $error = "Preferred end time must be later than preferred start time.";
        }
      }
    }

    // Photo upload (optional) - requires photo_path column
    $photoPath = null;
    if ($error === '' && !empty($_FILES['photo']['name'])) {
      $allowedExt = ['jpg','jpeg','png','webp'];
      $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));

      if (!in_array($ext, $allowedExt, true)) {
        $error = "Photo must be JPG, PNG, or WEBP.";
      } elseif ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        $error = "Photo upload failed.";
      } else {

        // NOTE: this path is relative to your project root.
        // File will be saved under /public/uploads/requests/
        $uploadDir = __DIR__ . '/../public/uploads/requests/';
        if (!is_dir($uploadDir)) {
          mkdir($uploadDir, 0777, true);
        }

        $newName = 'req_' . $farmer_id . '_' . time() . '.' . $ext;
        $dest = $uploadDir . $newName;

        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
          $error = "Could not save uploaded photo.";
        } else {
          $photoPath = 'uploads/requests/' . $newName; // accessible from public
        }
      }
    }

    if ($error === '') {

      // If you DID NOT add photo_path column, comment out photo_path below
      $stmt = $conn->prepare("
        INSERT INTO farmer_requests
          (farmer_id, request_type, issue_category, request_details,
           preferred_date, preferred_start, preferred_end,
           urgency, location_desc, landmark, maps_link,
           latitude, longitude,
           status, request_stage, photo_path)
        VALUES
          (?,?,?,?,?,?,?,?,?,?,?,?,?,'Pending','Pending',?)
      ");

      // If your table DOES NOT have photo_path yet, use this version instead:
      // $stmt = $conn->prepare("
      //   INSERT INTO farmer_requests
      //     (farmer_id, request_type, issue_category, request_details,
      //      preferred_date, preferred_start, preferred_end,
      //      urgency, location_desc, landmark, maps_link,
      //      latitude, longitude,
      //      status, request_stage)
      //   VALUES
      //     (?,?,?,?,?,?,?,?,?,?,?,?,?,'Pending','Pending')
      // ");

      $stmt->bind_param(
        "issssssssssdds",
        $farmer_id,
        $type,
        $issue_category,
        $details,
        $preferred_date,
        $preferred_start,
        $preferred_end,
        $urgency,
        $location_desc,
        $landmark,
        $maps_link,
        $latitude,
        $longitude,
        $photoPath
      );

      $stmt->execute();
      $stmt->close();

      $_SESSION['success'] = "Request submitted successfully.";
      header("Location: " . route('my_requests'));
      exit;
    }
  }
}

// Load my requests (include new fields for viewing)
$rows = [];
$stmt = $conn->prepare("
  SELECT request_id, request_type, issue_category, request_details, status, request_stage,
         preferred_date, preferred_start, preferred_end,
         urgency, location_desc, landmark, maps_link, latitude, longitude,
         photo_path,
         created_at
  FROM farmer_requests
  WHERE farmer_id=?
  ORDER BY created_at DESC
  LIMIT 500
");
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include __DIR__ . '/../includes/head.php';
?>

<!-- DataTables -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css"/>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<!-- Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<div class="flex min-h-screen w-full">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="flex-1 flex flex-col">
    <?php include __DIR__ . '/../includes/topbar.php'; ?>

    <main class="p-6 lg:p-8 flex-1">
      <div class="max-w-6xl mx-auto">
        <div class="flex items-end justify-between gap-3">
          <div>
            <h1 class="text-xl font-black text-text-light dark:text-text-dark">My Requests</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Submit irrigation requests and track approval status.</p>
          </div>

          <button id="openModal" class="px-4 py-2 rounded-DEFAULT bg-primary text-white font-bold">
            + New Request
          </button>
        </div>

        <?php if ($error): ?>
          <div class="mt-4 p-3 rounded bg-red-100 text-red-700"><?= h($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
          <div class="mt-4 p-3 rounded bg-green-100 text-green-700"><?= h($success) ?></div>
        <?php endif; ?>

        <div class="mt-6 bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark rounded-lg overflow-hidden">
          <div class="p-4 border-b border-border-light dark:border-border-dark">
            <p class="text-sm text-gray-500 dark:text-gray-400">
              Farmer: <span class="font-semibold text-text-light dark:text-text-dark"><?= h($farmer['farmer_name']) ?></span>
            </p>
          </div>

          <div class="p-4 overflow-x-auto">
            <table id="reqTable" class="w-full">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Type</th>
                  <th>Category</th>
                  <th>Urgency</th>
                  <th>Stage</th>
                  <th>Created</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($rows as $r): ?>
                  <?php [$cls,$label]=badge($r['status']); ?>
                  <tr class="text-sm">
                    <td><?= (int)$r['request_id'] ?></td>
                    <td><?= h($r['request_type']) ?></td>
                    <td><?= h($r['issue_category'] ?? '—') ?></td>
                    <td><?= h($r['urgency'] ?? 'Normal') ?></td>
                    <td><?= h($r['request_stage'] ?? $r['status']) ?></td>
                    <td><?= h($r['created_at']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>

            <p class="text-xs text-gray-500 dark:text-gray-400 mt-3">
              Tip: For faster technician response, always pin your location (GPS) and add a landmark.
            </p>
          </div>
        </div>

      </div>
    </main>
  </div>
</div>

<!-- Modal -->
<div id="modalBg" class="fixed inset-0 bg-black/50 hidden items-center justify-center p-4">
  <div class="w-full max-w-2xl bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark rounded-xl p-6">
    <div class="flex items-center justify-between">
      <h2 class="text-lg font-black text-text-light dark:text-text-dark">New Irrigation Request</h2>
      <button id="closeModal" class="px-3 py-1 rounded-DEFAULT bg-gray-200 dark:bg-gray-700 text-text-light dark:text-text-dark">X</button>
    </div>

    <form method="POST" enctype="multipart/form-data" class="mt-4 space-y-4">

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label class="text-sm text-text-light dark:text-text-dark">Request Type *</label>
          <select name="request_type" required
            class="mt-1 w-full rounded-DEFAULT border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
            <?php foreach(['Irrigation Request','Schedule Adjustment','Water Allocation','Technical Concern'] as $t): ?>
              <option value="<?= h($t) ?>"><?= h($t) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="text-sm text-text-light dark:text-text-dark">Urgency *</label>
          <select name="urgency" required
            class="mt-1 w-full rounded-DEFAULT border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
            <option value="Normal" selected>Normal</option>
            <option value="Urgent">Urgent</option>
          </select>
        </div>
      </div>

      <div>
        <label class="text-sm text-text-light dark:text-text-dark">Issue Category</label>
        <select name="issue_category"
          class="mt-1 w-full rounded-DEFAULT border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
          <option value="">(Optional)</option>
          <?php foreach(['Canal Blockage','No Water Flow','Low Water Pressure','Broken Gate / Valve','Leakage','Schedule Concern','Other'] as $c): ?>
            <option value="<?= h($c) ?>"><?= h($c) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div>
          <label class="text-sm text-text-light dark:text-text-dark">Preferred Date</label>
          <input type="date" name="preferred_date" min="<?= date('Y-m-d') ?>"
            class="mt-1 w-full rounded-DEFAULT border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
        </div>
        <div>
          <label class="text-sm text-text-light dark:text-text-dark">Preferred Start</label>
          <input type="time" name="preferred_start"
            class="mt-1 w-full rounded-DEFAULT border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
        </div>
        <div>
          <label class="text-sm text-text-light dark:text-text-dark">Preferred End</label>
          <input type="time" name="preferred_end"
            class="mt-1 w-full rounded-DEFAULT border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label class="text-sm text-text-light dark:text-text-dark">Location Description *</label>
          <input name="location_desc" required
            placeholder="Example: Canal B Section 3, turnout #12"
            class="mt-1 w-full rounded-DEFAULT border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
        </div>
        <div>
          <label class="text-sm text-text-light dark:text-text-dark">Landmark (optional)</label>
          <input name="landmark"
            placeholder="Example: near Barangay Hall / waiting shed"
            class="mt-1 w-full rounded-DEFAULT border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
        </div>
      </div>

      <div>
        <label class="text-sm text-text-light dark:text-text-dark">Google Maps Link (optional)</label>
        <input name="maps_link"
          placeholder="Paste a Maps link if you have one"
          class="mt-1 w-full rounded-DEFAULT border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
      </div>

      <!-- GPS -->
      <div class="space-y-2">
        <div class="flex items-center justify-between">
          <label class="text-sm text-text-light dark:text-text-dark">GPS Pin (recommended)</label>
          <button type="button" onclick="getMyLocation()"
            class="px-3 py-2 rounded-DEFAULT bg-secondary text-white text-sm font-semibold">
            📍 Get My Location
          </button>
        </div>

        <input type="hidden" name="latitude" id="lat">
        <input type="hidden" name="longitude" id="lng">

        <div id="gpsStatus" class="text-xs text-gray-500 dark:text-gray-400">
          No GPS captured yet.
        </div>

        <div id="map" class="w-full h-56 rounded-lg border border-border-light dark:border-border-dark hidden"></div>
      </div>

      <div>
        <label class="text-sm text-text-light dark:text-text-dark">Details *</label>
        <textarea name="request_details" rows="4" required
          placeholder="Describe the problem: when it started, water condition, etc."
          class="mt-1 w-full rounded-DEFAULT border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark"></textarea>
      </div>

      <div>
        <label class="text-sm text-text-light dark:text-text-dark">Photo (optional)</label>
        <input type="file" name="photo" accept="image/*"
          class="mt-1 w-full rounded-DEFAULT border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
          Upload photo of canal/gate issue to help technician.
        </p>
      </div>

      <div class="flex gap-2">
        <button class="px-4 py-2 rounded-DEFAULT bg-primary text-white font-bold">Submit</button>
        <button type="button" id="cancelModal"
          class="px-4 py-2 rounded-DEFAULT bg-gray-200 dark:bg-gray-700 text-text-light dark:text-text-dark">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
  $(function(){
    $('#reqTable').DataTable({
      pageLength: 10,
      order: [[0,'desc']]
    });

    const modalBg = document.getElementById('modalBg');
    const openBtn = document.getElementById('openModal');
    const closeBtn = document.getElementById('closeModal');
    const cancelBtn = document.getElementById('cancelModal');

    function openModal(){ modalBg.classList.remove('hidden'); modalBg.classList.add('flex'); }
    function closeModal(){ modalBg.classList.add('hidden'); modalBg.classList.remove('flex'); }

    openBtn.addEventListener('click', openModal);
    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);

    modalBg.addEventListener('click', (e) => {
      if (e.target === modalBg) closeModal();
    });
  });

  // Leaflet GPS pin
  let map, marker;

  function initMap(lat, lng) {
    const mapDiv = document.getElementById('map');
    mapDiv.classList.remove('hidden');

    if (!map) {
      map = L.map('map').setView([lat, lng], 16);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap'
      }).addTo(map);
      marker = L.marker([lat, lng]).addTo(map);
    } else {
      map.setView([lat, lng], 16);
      marker.setLatLng([lat, lng]);
    }

    // Fix map rendering in modal
    setTimeout(() => map.invalidateSize(), 200);
  }

  function getMyLocation() {
    const gpsStatus = document.getElementById('gpsStatus');

    if (!navigator.geolocation) {
      gpsStatus.textContent = "Geolocation not supported by your browser.";
      return;
    }

    gpsStatus.textContent = "Getting GPS location...";

    navigator.geolocation.getCurrentPosition(
      (pos) => {
        const lat = pos.coords.latitude;
        const lng = pos.coords.longitude;

        document.getElementById('lat').value = lat;
        document.getElementById('lng').value = lng;

        gpsStatus.textContent = `GPS captured: ${lat.toFixed(6)}, ${lng.toFixed(6)}`;
        initMap(lat, lng);
      },
      () => {
        gpsStatus.textContent = "Failed to get location. Please allow location permission.";
      },
      { enableHighAccuracy: true, timeout: 10000 }
    );
  }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
