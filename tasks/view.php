<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_roles(['Administrator','Operations Staff','Irrigation Technician']);
require_once __DIR__ . '/../includes/config.php';

$active='tasks';
$topTitle='Task Details';

$id=(int)($_GET['id']??0);

$stmt=$conn->prepare("
  SELECT t.*,
         s.schedule_date, s.start_time, s.end_time,
         sa.area_name, sa.municipality,
         u.fullname AS assigned_fullname, u.username AS assigned_username
  FROM tasks t
  JOIN irrigation_schedules s ON s.schedule_id=t.schedule_id
  LEFT JOIN service_areas sa ON sa.service_area_id=s.service_area_id
  LEFT JOIN users u ON u.user_id=t.assigned_user_id
  WHERE t.task_id=?
  LIMIT 1
");
$stmt->bind_param("i",$id);
$stmt->execute();
$row=$stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$row){ http_response_code(404); exit("Task not found"); }

[$cls,$label]=badge($row['status']);

include __DIR__ . '/../includes/head.php';
?>
<div class="flex min-h-screen w-full">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="flex-1 flex flex-col">
    <?php include __DIR__ . '/../includes/topbar.php'; ?>

    <main class="p-6 lg:p-8 flex-1">
      <div class="max-w-3xl bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark rounded-lg p-6">
        <div class="flex items-center justify-between">
          <h2 class="text-xl font-black text-text-light dark:text-text-dark">Task #<?= (int)$row['task_id'] ?></h2>
          <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $cls ?>"><?= h($label) ?></span>
        </div>

        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
          <div class="p-4 rounded-lg bg-white/60 dark:bg-gray-900/50 border border-border-light dark:border-border-dark">
            <p class="text-gray-500 dark:text-gray-400">Schedule</p>
            <p class="font-semibold"><?= h($row['schedule_date']) ?> <?= h($row['start_time']) ?> - <?= h($row['end_time']) ?></p>
          </div>

          <div class="p-4 rounded-lg bg-white/60 dark:bg-gray-900/50 border border-border-light dark:border-border-dark">
            <p class="text-gray-500 dark:text-gray-400">Area</p>
            <p class="font-semibold"><?= h($row['area_name']) ?><?= $row['municipality'] ? ", ".h($row['municipality']) : "" ?></p>
          </div>

          <div class="p-4 rounded-lg bg-white/60 dark:bg-gray-900/50 border border-border-light dark:border-border-dark">
            <p class="text-gray-500 dark:text-gray-400">Assigned Staff</p>
            <p class="font-semibold">
              <?= h($row['assigned_fullname'] ?: ($row['assigned_username'] ?: '—')) ?>
            </p>
          </div>

          <div class="p-4 rounded-lg bg-white/60 dark:bg-gray-900/50 border border-border-light dark:border-border-dark">
            <p class="text-gray-500 dark:text-gray-400">Times</p>
            <p class="font-semibold">Start: <?= h($row['started_at'] ?? '—') ?></p>
            <p class="font-semibold">End: <?= h($row['ended_at'] ?? '—') ?></p>
          </div>
        </div>

        <div class="mt-4 text-sm">
          <p class="text-gray-500 dark:text-gray-400">Remarks</p>
          <p class="mt-1"><?= h($row['remarks'] ?? '') ?></p>
        </div>

        <div class="mt-4 text-sm">
          <p class="text-gray-500 dark:text-gray-400">Issues</p>
          <p class="mt-1 whitespace-pre-wrap"><?= h($row['issues'] ?? '') ?></p>
        </div>

        <div class="mt-6 flex flex-wrap items-center gap-2">
          <a class="px-4 py-2 rounded-full bg-secondary text-white font-semibold inline-flex items-center gap-1.5"
             href="<?= base_path('tasks/log.php?id='.(int)$row['task_id']) ?>">
            <span class="material-symbols-outlined text-[18px] leading-none">edit_note</span>
            <span>Log / Update</span>
          </a>
          <?php if (can_page('logs')): ?>
            <a class="px-4 py-2 rounded-full bg-primary text-white font-semibold inline-flex items-center gap-1.5"
               href="<?= route('logs') ?>">
              <span class="material-symbols-outlined text-[18px] leading-none">list_alt</span>
              <span>System Logs</span>
            </a>
          <?php endif; ?>
          <a class="px-4 py-2 rounded-full bg-gray-200 dark:bg-gray-700 text-text-light dark:text-text-dark inline-flex items-center gap-1.5"
             href="<?= route('tasks') ?>">
            <span class="material-symbols-outlined text-[18px] leading-none">arrow_back</span>
            <span>Back</span>
          </a>
        </div>
      </div>
    </main>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
