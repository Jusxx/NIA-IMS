<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_roles(['Farmer']);
require_once __DIR__ . '/../includes/config.php';

$active = 'farmer_dashboard';
$topTitle = 'Farmer Dashboard';

$uid = (int)($_SESSION['user']['user_id'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM farmers WHERE user_id=? LIMIT 1");
$stmt->bind_param("i", $uid);
$stmt->execute();
$farmer = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$farmer) {
  echo "No farmer profile linked to this account. Ask admin to link farmers.user_id to your user.";
  exit;
}

$farmerId = (int)$farmer['farmer_id'];
$serviceAreaId = (int)($farmer['service_area_id'] ?? 0);

$stats = ['Pending' => 0, 'Approved' => 0, 'Completed' => 0, 'Rejected' => 0, 'Total' => 0];
$stmt = $conn->prepare("
  SELECT status, COUNT(*) c
  FROM farmer_requests
  WHERE farmer_id=?
  GROUP BY status
");
$stmt->bind_param("i", $farmerId);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) {
  $statusKey = (string)($r['status'] ?? '');
  if ($statusKey !== '') $stats[$statusKey] = (int)$r['c'];
  $stats['Total'] += (int)$r['c'];
}
$stmt->close();

$upcoming = [];
if ($serviceAreaId > 0) {
  $stmt = $conn->prepare("
    SELECT s.schedule_id, s.schedule_date, s.start_time, s.end_time, s.status,
           sa.area_name, sa.municipality
    FROM irrigation_schedules s
    LEFT JOIN service_areas sa ON sa.service_area_id = s.service_area_id
    WHERE s.service_area_id=? AND s.schedule_date >= CURDATE()
    ORDER BY s.schedule_date ASC, s.start_time ASC
    LIMIT 5
  ");
  $stmt->bind_param("i", $serviceAreaId);
  $stmt->execute();
  $upcoming = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  $stmt->close();
}

$nextSchedule = $upcoming[0] ?? null;
$daysUntilNext = null;
if ($nextSchedule && !empty($nextSchedule['schedule_date'])) {
  try {
    $today = new DateTimeImmutable(date('Y-m-d'));
    $scheduleDate = new DateTimeImmutable((string)$nextSchedule['schedule_date']);
    $daysUntilNext = (int)$today->diff($scheduleDate)->format('%r%a');
  } catch (Throwable $e) {
    $daysUntilNext = null;
  }
}

$lotSnapshot = null;
$stmt = $conn->prepare("
  SELECT lot_code, area_ha, location_desc, created_at
  FROM farmer_lots
  WHERE farmer_id=?
  ORDER BY lot_id DESC
  LIMIT 1
");
$stmt->bind_param("i", $farmerId);
$stmt->execute();
$lotSnapshot = $stmt->get_result()->fetch_assoc();
$stmt->close();

$recentRequests = [];
$stmt = $conn->prepare("
  SELECT request_id, request_type, status, request_stage, created_at
  FROM farmer_requests
  WHERE farmer_id=?
  ORDER BY created_at DESC
  LIMIT 5
");
$stmt->bind_param("i", $farmerId);
$stmt->execute();
$recentRequests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include __DIR__ . '/../includes/head.php';
?>
<div class="flex min-h-screen w-full">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="flex-1 flex flex-col">
    <?php include __DIR__ . '/../includes/topbar.php'; ?>

    <main class="p-4 lg:p-8 pb-24 lg:pb-8 flex-1">
      <div class="max-w-6xl mx-auto">

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
          <section class="xl:col-span-2 bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark rounded-xl p-5">
            <div class="flex items-start justify-between gap-3">
              <div>
                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Farmer Workspace</p>
                <h1 class="text-2xl font-black text-text-light dark:text-text-dark mt-1">Welcome, <?= h((string)$farmer['farmer_name']) ?></h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Track requests, schedules, and updates in one place.</p>
              </div>
              <span class="px-3 py-1 rounded-full text-xs font-semibold bg-primary/15 text-primary">Active Member</span>
            </div>

            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
              <a href="<?= route('my_requests') ?>" class="rounded-xl border border-border-light dark:border-border-dark bg-secondary text-white p-4">
                <div class="flex items-center gap-2">
                  <span class="material-symbols-outlined text-[20px] leading-none">add_circle</span>
                  <span class="font-semibold">Request Water</span>
                </div>
                <p class="text-xs mt-1 text-white/90">Submit a new irrigation concern.</p>
              </a>

              <a href="<?= route('my_schedule') ?>" class="rounded-xl border border-border-light dark:border-border-dark bg-white dark:bg-gray-900/50 p-4">
                <div class="flex items-center gap-2">
                  <span class="material-symbols-outlined text-[20px] leading-none text-primary">calendar_month</span>
                  <span class="font-semibold text-text-light dark:text-text-dark">View My Schedule</span>
                </div>
                <p class="text-xs mt-1 text-gray-500 dark:text-gray-400">Check your upcoming irrigation time.</p>
              </a>
            </div>
          </section>

          <section class="bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark rounded-xl p-5">
            <div class="flex items-center justify-between gap-2">
              <h2 class="text-sm font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">Next Irrigation</h2>
              <span class="material-symbols-outlined text-primary text-[20px] leading-none">schedule</span>
            </div>

            <?php if ($nextSchedule): ?>
              <div class="mt-3">
                <p class="text-lg font-black text-text-light dark:text-text-dark"><?= h((string)$nextSchedule['schedule_date']) ?></p>
                <p class="text-sm text-gray-600 dark:text-gray-300"><?= h((string)$nextSchedule['start_time']) ?> - <?= h((string)$nextSchedule['end_time']) ?></p>
                <p class="text-sm mt-2 text-gray-600 dark:text-gray-300">
                  <?= h((string)($nextSchedule['area_name'] ?? '-')) ?><?= !empty($nextSchedule['municipality']) ? ', ' . h((string)$nextSchedule['municipality']) : '' ?>
                </p>
                <?php if ($daysUntilNext !== null): ?>
                  <p class="mt-3 inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-primary/15 text-primary">
                    <?= $daysUntilNext <= 0 ? 'Today' : 'In ' . (int)$daysUntilNext . ' day(s)' ?>
                  </p>
                <?php endif; ?>
              </div>
            <?php else: ?>
              <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">No upcoming schedule yet.</p>
            <?php endif; ?>
          </section>
        </div>

        <div class="mt-4 grid grid-cols-2 lg:grid-cols-5 gap-3">
          <div class="p-4 rounded-xl bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark">
            <p class="text-gray-500 dark:text-gray-400 text-xs">Total</p>
            <p class="text-2xl font-black text-text-light dark:text-text-dark"><?= (int)$stats['Total'] ?></p>
          </div>
          <div class="p-4 rounded-xl bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark">
            <p class="text-gray-500 dark:text-gray-400 text-xs">Pending</p>
            <p class="text-2xl font-black text-warning"><?= (int)$stats['Pending'] ?></p>
          </div>
          <div class="p-4 rounded-xl bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark">
            <p class="text-gray-500 dark:text-gray-400 text-xs">Approved</p>
            <p class="text-2xl font-black text-primary"><?= (int)$stats['Approved'] ?></p>
          </div>
          <div class="p-4 rounded-xl bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark">
            <p class="text-gray-500 dark:text-gray-400 text-xs">Completed</p>
            <p class="text-2xl font-black text-primary"><?= (int)$stats['Completed'] ?></p>
          </div>
          <div class="p-4 rounded-xl bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark">
            <p class="text-gray-500 dark:text-gray-400 text-xs">Rejected</p>
            <p class="text-2xl font-black text-red-600"><?= (int)$stats['Rejected'] ?></p>
          </div>
        </div>

        <div class="mt-4 grid grid-cols-1 xl:grid-cols-3 gap-4">
          <section class="xl:col-span-2 bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark rounded-xl p-5">
            <div class="flex items-center justify-between">
              <h2 class="text-base font-bold text-text-light dark:text-text-dark">Recent Request Updates</h2>
              <a href="<?= route('my_requests') ?>" class="text-xs font-semibold text-secondary">View all</a>
            </div>
            <div class="mt-3 space-y-2">
              <?php foreach ($recentRequests as $req): ?>
                <?php
                  $stageRaw = trim((string)($req['request_stage'] ?? ''));
                  $stage = $stageRaw !== '' ? $stageRaw : (string)$req['status'];
                  [$stageCls, $stageLbl] = badge($stage);
                ?>
                <div class="rounded-lg border border-border-light dark:border-border-dark p-3">
                  <div class="flex items-center justify-between gap-2">
                    <p class="font-semibold text-text-light dark:text-text-dark text-sm">#<?= (int)$req['request_id'] ?> - <?= h((string)$req['request_type']) ?></p>
                    <span class="px-2 py-1 rounded-full text-xs font-semibold <?= $stageCls ?>"><?= h($stageLbl) ?></span>
                  </div>
                  <p class="mt-1 text-xs text-gray-500 dark:text-gray-400"><?= h((string)$req['created_at']) ?></p>
                </div>
              <?php endforeach; ?>
              <?php if (!$recentRequests): ?>
                <p class="text-sm text-gray-500 dark:text-gray-400">No requests yet.</p>
              <?php endif; ?>
            </div>
          </section>

          <section class="bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark rounded-xl p-5">
            <h2 class="text-base font-bold text-text-light dark:text-text-dark">My Lot Snapshot</h2>
            <?php if ($lotSnapshot): ?>
              <div class="mt-3 space-y-2 text-sm">
                <div><span class="text-gray-500 dark:text-gray-400">Lot Code:</span> <span class="font-semibold"><?= h((string)($lotSnapshot['lot_code'] ?? '-')) ?></span></div>
                <div><span class="text-gray-500 dark:text-gray-400">Area (ha):</span> <span class="font-semibold"><?= $lotSnapshot['area_ha'] !== null ? h((string)$lotSnapshot['area_ha']) : '-' ?></span></div>
                <div><span class="text-gray-500 dark:text-gray-400">Location:</span> <span class="font-semibold"><?= h((string)($lotSnapshot['location_desc'] ?? '-')) ?></span></div>
              </div>
            <?php else: ?>
              <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">No lot details available yet.</p>
            <?php endif; ?>
            <a href="<?= route('profile') ?>" class="mt-4 inline-flex items-center gap-1 text-xs font-semibold text-secondary">
              <span class="material-symbols-outlined text-[16px] leading-none">person</span>
              <span>View full profile</span>
            </a>
          </section>
        </div>

        <div class="mt-4 bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark rounded-xl p-5">
          <div class="flex items-center justify-between">
            <h2 class="text-base font-bold text-text-light dark:text-text-dark">Upcoming Schedules</h2>
            <a href="<?= route('my_schedule') ?>" class="text-xs font-semibold text-secondary">Open schedule page</a>
          </div>

          <div class="mt-4 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
            <?php foreach ($upcoming as $r): ?>
              <?php [$cls, $label] = badge((string)$r['status']); ?>
              <article class="rounded-lg border border-border-light dark:border-border-dark p-3">
                <div class="flex items-center justify-between gap-2">
                  <p class="font-semibold text-sm text-text-light dark:text-text-dark"><?= h((string)$r['schedule_date']) ?></p>
                  <span class="px-2 py-1 rounded-full text-xs font-semibold <?= $cls ?>"><?= h($label) ?></span>
                </div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400"><?= h((string)$r['start_time']) ?> - <?= h((string)$r['end_time']) ?></p>
                <p class="mt-2 text-sm text-text-light dark:text-text-dark">
                  <?= h((string)($r['area_name'] ?? '-')) ?><?= !empty($r['municipality']) ? ', ' . h((string)$r['municipality']) : '' ?>
                </p>
              </article>
            <?php endforeach; ?>
            <?php if (!$upcoming): ?>
              <p class="text-sm text-gray-500 dark:text-gray-400">No upcoming schedules for your area.</p>
            <?php endif; ?>
          </div>
        </div>

      </div>
    </main>
  </div>
</div>

<?php include __DIR__ . '/../includes/farmer_bottom_nav.php'; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
