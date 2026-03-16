<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_roles(['Administrator','Operations Staff','IMO']);
require_once __DIR__ . '/../includes/config.php';

$topTitle = 'Edit Area';
$active = 'areas';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(400); exit("Invalid area id"); }

$error = '';

$stmt = $conn->prepare("SELECT * FROM service_areas WHERE service_area_id=? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) { http_response_code(404); exit("Area not found"); }

$area = $row['area_name'];
$mun  = $row['municipality'];
$prov = $row['province'];
$ha   = $row['total_area_ha'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $area = trim($_POST['area_name'] ?? '');
  $mun  = trim($_POST['municipality'] ?? '');
  $prov = trim($_POST['province'] ?? '');
  $haInput = trim($_POST['total_area_ha'] ?? '');

  if ($area === '') {
    $error = "Area name is required.";
  } else {
    if ($haInput === '') {
      $stmt = $conn->prepare("UPDATE service_areas SET area_name=?, municipality=?, province=?, total_area_ha=NULL WHERE service_area_id=?");
      $stmt->bind_param("sssi", $area, $mun, $prov, $id);
    } else {
      $haVal = (float)$haInput;
      $stmt = $conn->prepare("UPDATE service_areas SET area_name=?, municipality=?, province=?, total_area_ha=? WHERE service_area_id=?");
      $stmt->bind_param("sssdi", $area, $mun, $prov, $haVal, $id);
    }
    $stmt->execute();
    $stmt->close();

    header("Location: " . route('areas'));
    exit;
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
        <h2 class="text-xl font-black text-text-light dark:text-text-dark">Edit Area / Canal</h2>

        <?php if ($error): ?>
          <div class="mt-4 p-3 rounded bg-red-100 text-red-700"><?= h($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="mt-4 space-y-4">
          <div>
            <label class="block text-sm font-medium text-text-light dark:text-text-dark">Area Name *</label>
            <input name="area_name" value="<?= h($area) ?>" required
                   class="mt-1 w-full rounded-full px-4 py-2 border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
          </div>

          <div>
            <label class="block text-sm font-medium text-text-light dark:text-text-dark">Municipality</label>
            <input name="municipality" value="<?= h($mun) ?>"
                   class="mt-1 w-full rounded-full px-4 py-2 border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
          </div>

          <div>
            <label class="block text-sm font-medium text-text-light dark:text-text-dark">Province</label>
            <input name="province" value="<?= h($prov) ?>"
                   class="mt-1 w-full rounded-full px-4 py-2 border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
          </div>

          <div>
            <label class="block text-sm font-medium text-text-light dark:text-text-dark">Total Area (ha)</label>
            <input type="number" step="0.01" name="total_area_ha" value="<?= h($ha) ?>"
                   class="mt-1 w-full rounded-full px-4 py-2 border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
          </div>

          <div class="flex flex-wrap gap-2">
            <button class="px-4 py-2 rounded-full bg-primary text-white font-bold inline-flex items-center gap-1.5">
              <span class="material-symbols-outlined text-[18px] leading-none">save</span>
              <span>Save Changes</span>
            </button>
            <a class="px-4 py-2 rounded-full bg-gray-200 dark:bg-gray-700 text-text-light dark:text-text-dark inline-flex items-center gap-1.5"
               href="<?= route('areas') ?>">
              <span class="material-symbols-outlined text-[18px] leading-none">arrow_back</span>
              <span>Cancel</span>
            </a>
          </div>
        </form>
      </div>
    </main>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
