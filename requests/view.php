<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_roles(['Administrator','Operations Staff','IMO','Irrigation Technician','Monitoring']);
require_once __DIR__ . '/../includes/config.php';

$active = 'requests';
$topTitle = 'Request Details';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) exit("Invalid request id.");

$stmt = $conn->prepare("
  SELECT r.*, f.farmer_name, f.phone
  FROM farmer_requests r
  LEFT JOIN farmers f ON f.farmer_id = r.farmer_id
  WHERE r.request_id=?
  LIMIT 1
");
$stmt->bind_param("i",$id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) exit("Request not found.");

$att = [];
$stmt = $conn->prepare("SELECT * FROM request_attachments WHERE request_id=? ORDER BY uploaded_at DESC");
$stmt->bind_param("i",$id);
$stmt->execute();
$att = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include __DIR__ . '/../includes/head.php';
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<div class="flex min-h-screen w-full">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="flex-1 flex flex-col">
    <?php include __DIR__ . '/../includes/topbar.php'; ?>

    <main class="p-6 lg:p-8 flex-1">
      <div class="max-w-5xl bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark rounded-lg p-6">

        <div class="flex items-center justify-between gap-3">
          <div>
            <h1 class="text-xl font-black">Request #<?= (int)$row['request_id'] ?></h1>
            <div class="text-sm text-gray-500">
              Farmer: <b><?= h($row['farmer_name'] ?? '—') ?></b> • Phone: <?= h($row['phone'] ?? '—') ?>
            </div>
          </div>
          <a class="px-3 py-2 rounded-DEFAULT bg-gray-200 dark:bg-gray-700"
             href="<?= route('requests') ?>">Back</a>
        </div>

        <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-4">
          <div class="space-y-2">
            <div><b>Type:</b> <?= h($row['request_type']) ?></div>
            <div><b>Issue:</b> <?= h($row['issue_category'] ?? '') ?></div>
            <div><b>Urgency:</b> <?= h($row['urgency'] ?? '') ?></div>
            <div><b>Location:</b> <?= h($row['location_desc'] ?? '') ?></div>
            <div><b>Landmark:</b> <?= h($row['landmark'] ?? '') ?></div>
            <div><b>Status:</b> <?= h($row['status'] ?? '') ?> • <b>Stage:</b> <?= h($row['request_stage'] ?? '') ?></div>
            <div class="mt-3">
              <b>Details:</b>
              <div class="mt-1 p-3 rounded bg-gray-100 dark:bg-gray-800"><?= nl2br(h($row['request_details'] ?? '')) ?></div>
            </div>

            <?php if (!empty($row['maps_link'])): ?>
              <div class="mt-2">
                <b>Maps Link:</b>
                <a class="text-primary underline" target="_blank" href="<?= h($row['maps_link']) ?>">Open Google Maps</a>
              </div>
            <?php endif; ?>
          </div>

          <div>
            <div class="text-sm text-gray-500 mb-2">Map Preview</div>
            <div id="map" style="height: 320px;" class="rounded-lg overflow-hidden border"></div>
          </div>
        </div>

        <div class="mt-6">
          <div class="text-sm text-gray-500 mb-2">Photos</div>
          <?php if (!$att): ?>
            <div class="text-gray-500">No photos uploaded.</div>
          <?php else: ?>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
              <?php foreach($att as $a): ?>
                <a target="_blank" href="<?= route('public') ?>/<?= h($a['file_path']) ?>">
                  <img src="<?= route('public') ?>/<?= h($a['file_path']) ?>" class="w-full h-32 object-cover rounded border">
                </a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

      </div>
    </main>
  </div>
</div>

<script>
const lat = <?= $row['latitude'] !== null ? (float)$row['latitude'] : 'null' ?>;
const lng = <?= $row['longitude'] !== null ? (float)$row['longitude'] : 'null' ?>;

const map = L.map('map');
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

if (lat !== null && lng !== null) {
  map.setView([lat, lng], 16);
  L.marker([lat, lng]).addTo(map).bindPopup("Request Location").openPopup();
} else {
  map.setView([7.0, 125.0], 6); // Philippines fallback
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
