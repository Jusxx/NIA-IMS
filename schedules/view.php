<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_roles(['Administrator','Operations Staff','IMO']);
require_once __DIR__ . '/../includes/config.php';

$active   = 'schedules';
$topTitle = 'Schedule Details';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  http_response_code(400);
  exit("Invalid schedule id");
}

$stmt = $conn->prepare("
  SELECT s.*,
         sa.area_name, sa.municipality, sa.province, sa.total_area_ha,
         u.fullname AS created_fullname, u.username AS created_username,
         r.request_id AS req_request_id, r.farmer_id AS req_farmer_id, r.request_type AS req_request_type,
         r.lot_id AS req_lot_id, r.location_desc AS req_location_desc,
         r.latitude AS req_latitude, r.longitude AS req_longitude,
         r.location_lat AS req_location_lat, r.location_lng AS req_location_lng,
         f.farmer_name AS req_farmer_name
  FROM irrigation_schedules s
  LEFT JOIN service_areas sa ON sa.service_area_id = s.service_area_id
  LEFT JOIN users u ON u.user_id = s.created_by
  LEFT JOIN farmer_requests r ON r.request_id = s.request_id
  LEFT JOIN farmers f ON f.farmer_id = r.farmer_id
  WHERE s.schedule_id = ?
  LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
  http_response_code(404);
  exit("Schedule not found");
}

if (!is_focus_service_area($row['area_name'] ?? null, $row['municipality'] ?? null, $row['province'] ?? null)) {
  http_response_code(403);
  exit("This schedule is outside the focused irrigation locations.");
}

$scheduleLot = null;
$scheduleLotLat = null;
$scheduleLotLng = null;
$hasScheduleLotMap = false;
$canViewConfidentialLotTitle = (role() === 'Administrator');

$reqFarmerId = (int)($row['req_farmer_id'] ?? 0);
$reqLotId = (int)($row['req_lot_id'] ?? 0);
$stmtLot = null;

if ($reqLotId > 0) {
  $stmtLot = $conn->prepare("
    SELECT lot_id, lot_code, location_desc, latitude, longitude, title_photo_path
    FROM farmer_lots
    WHERE lot_id = ?
    LIMIT 1
  ");
  $stmtLot->bind_param("i", $reqLotId);
} elseif ($reqFarmerId > 0) {
  $stmtLot = $conn->prepare("
    SELECT lot_id, lot_code, location_desc, latitude, longitude, title_photo_path
    FROM farmer_lots
    WHERE farmer_id = ?
    ORDER BY created_at DESC, lot_id DESC
    LIMIT 1
  ");
  $stmtLot->bind_param("i", $reqFarmerId);
}

if ($stmtLot) {
  $stmtLot->execute();
  $scheduleLot = $stmtLot->get_result()->fetch_assoc();
  $stmtLot->close();
}

$reqLat = $row['req_latitude'] ?? null;
$reqLng = $row['req_longitude'] ?? null;
if (($reqLat === null || $reqLat === '') && isset($row['req_location_lat'])) {
  $reqLat = $row['req_location_lat'];
}
if (($reqLng === null || $reqLng === '') && isset($row['req_location_lng'])) {
  $reqLng = $row['req_location_lng'];
}

if (!$scheduleLot && !empty($row['request_id'])) {
  $scheduleLot = [
    'lot_id' => $reqLotId > 0 ? $reqLotId : null,
    'lot_code' => null,
    'location_desc' => $row['req_location_desc'] ?? null,
    'latitude' => is_numeric($reqLat) ? (float)$reqLat : null,
    'longitude' => is_numeric($reqLng) ? (float)$reqLng : null,
    'title_photo_path' => null,
  ];
} elseif ($scheduleLot) {
  if (empty($scheduleLot['location_desc']) && !empty($row['req_location_desc'])) {
    $scheduleLot['location_desc'] = $row['req_location_desc'];
  }
  if ((!isset($scheduleLot['latitude']) || $scheduleLot['latitude'] === null || $scheduleLot['latitude'] === '') && is_numeric($reqLat)) {
    $scheduleLot['latitude'] = (float)$reqLat;
  }
  if ((!isset($scheduleLot['longitude']) || $scheduleLot['longitude'] === null || $scheduleLot['longitude'] === '') && is_numeric($reqLng)) {
    $scheduleLot['longitude'] = (float)$reqLng;
  }
}

if (
  $scheduleLot &&
  isset($scheduleLot['latitude'], $scheduleLot['longitude']) &&
  is_numeric($scheduleLot['latitude']) &&
  is_numeric($scheduleLot['longitude'])
) {
  $scheduleLotLat = (float)$scheduleLot['latitude'];
  $scheduleLotLng = (float)$scheduleLot['longitude'];
  $hasScheduleLotMap = true;
}

[$cls, $label] = badge($row['status'] ?? 'Active');

include __DIR__ . '/../includes/head.php';
?>

<div class="flex min-h-screen w-full">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="flex-1 flex flex-col">
    <?php include __DIR__ . '/../includes/topbar.php'; ?>

    <main class="p-6 lg:p-8 flex-1">
      <div class="max-w-4xl bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark rounded-lg p-6">

        <div class="flex items-start justify-between gap-4">
          <div>
            <h1 class="text-xl font-black text-text-light dark:text-text-dark">
              Schedule #<?= (int)$row['schedule_id'] ?>
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
              Detailed schedule information.
            </p>
          </div>

          <div class="flex items-center gap-2">
            <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $cls ?>"><?= h($label) ?></span>

            <a class="px-3 py-2 rounded-DEFAULT bg-secondary text-white font-semibold"
               href="<?= base_path('schedules/edit.php?id='.(int)$row['schedule_id']) ?>">
              Edit
            </a>

            <a class="px-3 py-2 rounded-DEFAULT bg-gray-200 dark:bg-gray-700 text-text-light dark:text-text-dark"
               href="<?= route('schedules') ?>">
              Back
            </a>
          </div>
        </div>

        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">

          <div class="p-4 rounded-lg bg-white/60 dark:bg-gray-900/50 border border-border-light dark:border-border-dark">
            <p class="text-gray-500 dark:text-gray-400">Date</p>
            <p class="font-semibold text-text-light dark:text-text-dark"><?= h($row['schedule_date'] ?? '—') ?></p>
          </div>

          <div class="p-4 rounded-lg bg-white/60 dark:bg-gray-900/50 border border-border-light dark:border-border-dark">
            <p class="text-gray-500 dark:text-gray-400">Time Window</p>
            <p class="font-semibold text-text-light dark:text-text-dark">
              <?= h($row['start_time'] ?? '—') ?> - <?= h($row['end_time'] ?? '—') ?>
            </p>
          </div>

          <div class="p-4 rounded-lg bg-white/60 dark:bg-gray-900/50 border border-border-light dark:border-border-dark">
            <p class="text-gray-500 dark:text-gray-400">Area / Canal</p>
            <p class="font-semibold text-text-light dark:text-text-dark">
              <?= h($row['area_name'] ?? '—') ?>
              <?= !empty($row['municipality']) ? " • " . h($row['municipality']) : "" ?>
              <?= !empty($row['province']) ? " • " . h($row['province']) : "" ?>
            </p>
          </div>

          <div class="p-4 rounded-lg bg-white/60 dark:bg-gray-900/50 border border-border-light dark:border-border-dark">
            <p class="text-gray-500 dark:text-gray-400">Total Area (ha)</p>
            <p class="font-semibold text-text-light dark:text-text-dark"><?= h($row['total_area_ha'] ?? '—') ?></p>
          </div>

          <div class="p-4 rounded-lg bg-white/60 dark:bg-gray-900/50 border border-border-light dark:border-border-dark md:col-span-2">
            <p class="text-gray-500 dark:text-gray-400">Created By</p>
            <p class="font-semibold text-text-light dark:text-text-dark">
              <?= h($row['created_fullname'] ?: ($row['created_username'] ?: '—')) ?>
              <?php if (!empty($row['created_at'])): ?>
                <span class="text-gray-500 dark:text-gray-400 font-normal"> • <?= h($row['created_at']) ?></span>
              <?php endif; ?>
            </p>
          </div>

        </div>

        <?php if (!empty($row['request_id'])): ?>
          <div class="mt-6 p-4 rounded-lg bg-white/60 dark:bg-gray-900/50 border border-border-light dark:border-border-dark text-sm">
            <p class="text-gray-500 dark:text-gray-400">Linked Request</p>
            <p class="font-semibold text-text-light dark:text-text-dark mt-1">
              Request #<?= (int)$row['request_id'] ?>
              <?php if (!empty($row['req_request_type'])): ?>
                • <?= h($row['req_request_type']) ?>
              <?php endif; ?>
            </p>
            <p class="text-text-light dark:text-text-dark mt-1">
              Farmer: <b><?= h($row['req_farmer_name'] ?? '—') ?></b>
            </p>

            <?php if ($scheduleLot): ?>
              <div class="mt-3 rounded-lg border border-border-light dark:border-border-dark p-3">
                <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Farmer Lot Location</div>
                <div class="mt-1 text-text-light dark:text-text-dark">Lot Code: <b><?= h($scheduleLot['lot_code'] ?? '—') ?></b></div>
                <div class="text-text-light dark:text-text-dark">Location: <b><?= h($scheduleLot['location_desc'] ?? '—') ?></b></div>
                <div class="text-text-light dark:text-text-dark">
                  Coordinates:
                  <?php if ($hasScheduleLotMap): ?>
                    <b><?= h(number_format($scheduleLotLat, 7, '.', '')) ?>, <?= h(number_format($scheduleLotLng, 7, '.', '')) ?></b>
                  <?php else: ?>
                    <b>—</b>
                  <?php endif; ?>
                </div>
                <?php if (!empty($scheduleLot['title_photo_path'])): ?>
                  <div class="mt-1">
                    Lot title:
                    <?php if ($canViewConfidentialLotTitle): ?>
                      <a class="text-primary underline" target="_blank" rel="noopener"
                        href="<?= h(base_path(ltrim((string)$scheduleLot['title_photo_path'], '/'))) ?>">View confidential image</a>
                    <?php else: ?>
                      <span class="font-semibold text-gray-500 dark:text-gray-400">Confidential (Admin only)</span>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
                <?php if ($hasScheduleLotMap): ?>
                  <div id="scheduleLotMap" class="mt-3 w-full rounded-lg border border-border-light dark:border-border-dark" style="height: 230px;"></div>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>

      </div>
    </main>
  </div>
</div>

<?php if ($hasScheduleLotMap): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function() {
  if (typeof L === 'undefined') return;
  const mapEl = document.getElementById('scheduleLotMap');
  if (!mapEl) return;

  const lat = <?= json_encode($scheduleLotLat) ?>;
  const lng = <?= json_encode($scheduleLotLng) ?>;
  const lotMap = L.map(mapEl).setView([lat, lng], 15);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(lotMap);
  L.marker([lat, lng]).addTo(lotMap).bindPopup('Farmer lot location');
})();
</script>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
