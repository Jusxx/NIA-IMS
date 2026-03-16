<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_roles(['Farmer']);
require_once __DIR__ . '/../includes/config.php';

$active   = 'my_schedule';
$topTitle = 'My Schedule / Task Status';

$user_id = (int)($_SESSION['user']['user_id'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM farmers WHERE user_id=? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$farmer = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$farmer) {
  include __DIR__ . '/../includes/head.php';
  ?>
  <div class="min-h-screen flex items-center justify-center p-6">
    <div class="max-w-lg w-full bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark rounded-lg p-6">
      <h1 class="text-xl font-black text-text-light dark:text-text-dark">No Farmer Profile Linked</h1>
      <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
        No farmer profile linked to this account. Ask admin to link <b>farmers.user_id</b> to your user.
      </p>
      <a class="inline-block mt-4 px-4 py-2 rounded-DEFAULT bg-secondary text-white font-semibold"
         href="<?= route('dashboard') ?>">Back</a>
    </div>
  </div>
  <?php
  include __DIR__ . '/../includes/footer.php';
  exit;
}

// Show schedules linked to this farmer only:
// 1) schedule created from the farmer's request (irrigation_schedules.request_id -> farmer_requests.farmer_id)
// 2) schedule where farmer is included in irrigation_batches
$rows = [];
$stmt = $conn->prepare("
  SELECT DISTINCT
         s.schedule_id,
         s.schedule_date,
         s.start_time,
         s.end_time,
         s.status AS schedule_status,
         sa.area_name,
         sa.municipality,
         t.task_id,
         t.status AS task_status,
         t.started_at,
         t.ended_at
  FROM irrigation_schedules s
  LEFT JOIN service_areas sa ON sa.service_area_id = s.service_area_id
  LEFT JOIN tasks t ON t.schedule_id = s.schedule_id
  LEFT JOIN farmer_requests r ON r.request_id = s.request_id
  LEFT JOIN irrigation_batches b
    ON b.schedule_id = s.schedule_id
   AND b.farmer_id = ?
  WHERE (r.farmer_id = ? OR b.farmer_id IS NOT NULL)
  ORDER BY s.schedule_date DESC, s.start_time DESC
  LIMIT 200
");
$stmt->bind_param("ii", $farmer['farmer_id'], $farmer['farmer_id']);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include __DIR__ . '/../includes/head.php';
?>
<div class="flex min-h-screen w-full">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="flex-1 flex flex-col">
    <?php include __DIR__ . '/../includes/topbar.php'; ?>

    <main class="p-4 lg:p-8 pb-24 lg:pb-8 flex-1">
      <div class="max-w-5xl mx-auto">

        <div class="mb-4">
          <h1 class="text-xl font-black text-text-light dark:text-text-dark">My Schedule / Task Status</h1>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Showing schedules linked to your account.
          </p>
        </div>

        <div class="space-y-3 md:hidden">
          <?php foreach ($rows as $r): ?>
            <?php [$clsS, $lblS] = badge((string)$r['schedule_status']); ?>
            <?php [$clsT, $lblT] = badge((string)($r['task_status'] ?? 'Due')); ?>
            <article class="bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark rounded-xl p-4">
              <div class="flex items-start justify-between gap-2">
                <div>
                  <p class="font-semibold text-text-light dark:text-text-dark"><?= h((string)$r['schedule_date']) ?></p>
                  <p class="text-xs text-gray-500 dark:text-gray-400"><?= h((string)$r['start_time']) ?> - <?= h((string)$r['end_time']) ?></p>
                </div>
                <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $clsT ?>"><?= h($lblT) ?></span>
              </div>
              <p class="mt-2 text-sm text-text-light dark:text-text-dark">
                <?= h((string)($r['area_name'] ?? '-')) ?><?= !empty($r['municipality']) ? ', ' . h((string)$r['municipality']) : '' ?>
              </p>
              <div class="mt-3 flex flex-wrap items-center gap-2">
                <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $clsS ?>"><?= h($lblS) ?></span>
                <span class="text-xs text-gray-500 dark:text-gray-400 break-all">Started: <?= h((string)($r['started_at'] ?? '-')) ?></span>
                <span class="text-xs text-gray-500 dark:text-gray-400 break-all">Ended: <?= h((string)($r['ended_at'] ?? '-')) ?></span>
              </div>
            </article>
          <?php endforeach; ?>
          <?php if (!$rows): ?>
            <div class="bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark rounded-xl p-4 text-sm text-gray-500 dark:text-gray-400">
              No schedules linked to your account yet.
            </div>
          <?php endif; ?>
        </div>

        <div class="hidden md:block bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark rounded-lg overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left">
              <thead>
                <tr class="text-xs text-gray-500 dark:text-gray-400 uppercase">
                  <th class="p-3">Date</th>
                  <th class="p-3">Time</th>
                  <th class="p-3">Area</th>
                  <th class="p-3">Schedule Status</th>
                  <th class="p-3">Task Status</th>
                  <th class="p-3">Started</th>
                  <th class="p-3">Ended</th>
                </tr>
              </thead>

              <tbody class="divide-y divide-border-light dark:divide-border-dark">
                <?php foreach($rows as $r): ?>
                  <?php [$clsS,$lblS] = badge($r['schedule_status']); ?>
                  <?php [$clsT,$lblT] = badge($r['task_status'] ?? 'Due'); ?>

                  <tr class="text-sm text-text-light dark:text-text-dark">
                    <td class="p-3 whitespace-nowrap"><?= h($r['schedule_date']) ?></td>
                    <td class="p-3 whitespace-nowrap"><?= h($r['start_time']) ?> - <?= h($r['end_time']) ?></td>
                    <td class="p-3">
                      <?= h($r['area_name'] ?? '-') ?>
                      <?= !empty($r['municipality']) ? ", ".h($r['municipality']) : "" ?>
                    </td>

                    <td class="p-3">
                      <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $clsS ?>"><?= h($lblS) ?></span>
                    </td>

                    <td class="p-3">
                      <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $clsT ?>"><?= h($lblT) ?></span>
                    </td>

                    <td class="p-3 whitespace-nowrap"><?= h($r['started_at'] ?? '-') ?></td>
                    <td class="p-3 whitespace-nowrap"><?= h($r['ended_at'] ?? '-') ?></td>
                  </tr>
                <?php endforeach; ?>

                <?php if(!$rows): ?>
                  <tr><td class="p-3 text-gray-500" colspan="7">No schedules linked to your account yet.</td></tr>
                <?php endif; ?>
              </tbody>

            </table>
          </div>
        </div>

      </div>
    </main>
  </div>
</div>
<?php include __DIR__ . '/../includes/farmer_bottom_nav.php'; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
