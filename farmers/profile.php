<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_roles(['Farmer']);
require_once __DIR__ . '/../includes/config.php';

$active = 'profile';
$topTitle = 'My Profile';

$userId = (int)($_SESSION['user']['user_id'] ?? 0);

$stmt = $conn->prepare("
  SELECT f.*, sa.area_name, sa.municipality, sa.province
  FROM farmers f
  LEFT JOIN service_areas sa ON sa.service_area_id = f.service_area_id
  WHERE f.user_id = ?
  LIMIT 1
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$farmer = $stmt->get_result()->fetch_assoc();
$stmt->close();

include __DIR__ . '/../includes/head.php';
?>

<div class="flex min-h-screen w-full">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="flex-1 flex flex-col">
    <?php include __DIR__ . '/../includes/topbar.php'; ?>

    <main class="p-4 lg:p-8 pb-24 lg:pb-8 flex-1">

      <?php if (!$farmer): ?>
        <div class="max-w-2xl bg-red-100 text-red-700 border border-red-200 p-4 rounded-lg">
          <p class="font-bold">No farmer profile linked to this account.</p>
          <p class="text-sm mt-1">Ask admin to link <b>farmers.user_id</b> to your user.</p>
        </div>
      <?php else: ?>

        <div class="max-w-4xl bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark rounded-xl p-6">

          <div class="flex items-start justify-between gap-3">
            <div>
              <h1 class="text-2xl font-black text-text-light dark:text-text-dark"><?= h((string)$farmer['farmer_name']) ?></h1>
              <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Farmer profile information</p>
            </div>
            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-primary/20 text-primary">Farmer</span>
          </div>

          <div class="mt-4 flex flex-wrap gap-2">
            <a href="<?= route('my_requests') ?>" class="px-3 py-2 rounded-full bg-secondary text-white text-xs font-semibold inline-flex items-center gap-1">
              <span class="material-symbols-outlined text-[16px] leading-none">inbox</span>
              <span>My Requests</span>
            </a>
            <a href="<?= route('my_schedule') ?>" class="px-3 py-2 rounded-full bg-gray-200 dark:bg-gray-700 text-text-light dark:text-text-dark text-xs font-semibold inline-flex items-center gap-1">
              <span class="material-symbols-outlined text-[16px] leading-none">calendar_month</span>
              <span>My Schedule</span>
            </a>
          </div>

          <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">

            <div class="p-4 rounded-lg bg-white/60 dark:bg-gray-900/50 border border-border-light dark:border-border-dark">
              <p class="text-gray-500 dark:text-gray-400">Association</p>
              <p class="font-semibold"><?= h((string)($farmer['association_name'] ?? '-')) ?></p>
            </div>

            <div class="p-4 rounded-lg bg-white/60 dark:bg-gray-900/50 border border-border-light dark:border-border-dark">
              <p class="text-gray-500 dark:text-gray-400">Phone</p>
              <p class="font-semibold"><?= h((string)($farmer['phone'] ?? '-')) ?></p>
            </div>

            <div class="p-4 rounded-lg bg-white/60 dark:bg-gray-900/50 border border-border-light dark:border-border-dark md:col-span-2">
              <p class="text-gray-500 dark:text-gray-400">Address</p>
              <p class="font-semibold"><?= h((string)($farmer['address'] ?? '-')) ?></p>
            </div>

            <div class="p-4 rounded-lg bg-white/60 dark:bg-gray-900/50 border border-border-light dark:border-border-dark">
              <p class="text-gray-500 dark:text-gray-400">Service Area</p>
              <p class="font-semibold"><?= h((string)($farmer['area_name'] ?? '-')) ?></p>
            </div>

            <div class="p-4 rounded-lg bg-white/60 dark:bg-gray-900/50 border border-border-light dark:border-border-dark">
              <p class="text-gray-500 dark:text-gray-400">Location</p>
              <p class="font-semibold">
                <?= h((string)($farmer['municipality'] ?? '')) ?>
                <?= !empty($farmer['province']) ? ', ' . h((string)$farmer['province']) : '' ?>
              </p>
            </div>

            <div class="p-4 rounded-lg bg-white/60 dark:bg-gray-900/50 border border-border-light dark:border-border-dark md:col-span-2">
              <p class="text-gray-500 dark:text-gray-400">Profile Created</p>
              <p class="font-semibold"><?= h((string)($farmer['created_at'] ?? '-')) ?></p>
            </div>

          </div>

        </div>

      <?php endif; ?>

    </main>
  </div>
</div>

<?php include __DIR__ . '/../includes/farmer_bottom_nav.php'; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
