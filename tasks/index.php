<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_roles(['Administrator','Operations Staff','Irrigation Technician']);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/sms.php';

$active   = 'tasks';
$topTitle = 'Irrigation Tasks';


$role   = role();
$userId = (int)($_SESSION['user']['user_id'] ?? 0);

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

/**
 * POST actions (Start / Complete / Missed)
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $taskId = (int)($_POST['task_id'] ?? 0);
  $action = trim($_POST['action'] ?? '');
  $completionRemarks = trim((string)($_POST['completion_remarks'] ?? ''));

  if ($taskId > 0 && in_array($action, ['start','complete','missed'], true)) {

    // Load task with assignment
    $stmt = $conn->prepare("SELECT task_id, assigned_user_id, status, remarks FROM tasks WHERE task_id=? LIMIT 1");
    $stmt->bind_param("i", $taskId);
    $stmt->execute();
    $task = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$task) {
      $_SESSION['flash'] = "Task not found.";
      header("Location: " . route('tasks'));
      exit;
    }

    // Technicians can only act on their assigned tasks
    if ($role === 'Irrigation Technician' && (int)$task['assigned_user_id'] !== $userId) {
      http_response_code(403);
      exit("403 Forbidden");
    }

    if ($role === 'Irrigation Technician' && $action === 'complete' && $completionRemarks === '') {
      $_SESSION['flash'] = "Remarks are required when completing a task.";
      header("Location: " . route('tasks'));
      exit;
    }

    // Decide new status + timestamps
    $previousStatus = (string)($task['status'] ?? '');
    $newStatus = $previousStatus;
    $nextRemarks = trim((string)($task['remarks'] ?? ''));
    $setStarted = false;
    $setEnded   = false;

    if ($action === 'start') {
      $newStatus = 'In Progress';
      $setStarted = true;
    } elseif ($action === 'complete') {
      $newStatus = 'Completed';
      $setEnded = true;
      if ($completionRemarks !== '') {
        $taggedRemark = "Completion remarks: " . $completionRemarks;
        $nextRemarks = $nextRemarks !== '' ? ($nextRemarks . PHP_EOL . $taggedRemark) : $taggedRemark;
      }
    } elseif ($action === 'missed') {
      $newStatus = 'Missed';
      $setEnded = true;
    }

    // Update task
    if ($setStarted && !$setEnded) {
      $stmt = $conn->prepare("
        UPDATE tasks
        SET status=?, started_at=COALESCE(started_at, NOW())
        WHERE task_id=?
      ");
      $stmt->bind_param("si", $newStatus, $taskId);
    } elseif ($setEnded) {
      $stmt = $conn->prepare("
        UPDATE tasks
        SET status=?,
            started_at=COALESCE(started_at, NOW()),
            ended_at=COALESCE(ended_at, NOW()),
            remarks=?
        WHERE task_id=?
      ");
      $stmt->bind_param("ssi", $newStatus, $nextRemarks, $taskId);
    } else {
      $stmt = $conn->prepare("UPDATE tasks SET status=? WHERE task_id=?");
      $stmt->bind_param("si", $newStatus, $taskId);
    }

    $stmt->execute();
    $stmt->close();

    // Keep linked request stage aligned with task progress.
    if (in_array($newStatus, ['In Progress', 'Completed'], true)) {
      $stmt = $conn->prepare("
        SELECT s.request_id
        FROM tasks t
        JOIN irrigation_schedules s ON s.schedule_id = t.schedule_id
        WHERE t.task_id = ?
        LIMIT 1
      ");
      $stmt->bind_param("i", $taskId);
      $stmt->execute();
      $req = $stmt->get_result()->fetch_assoc();
      $stmt->close();

      $requestId = (int)($req['request_id'] ?? 0);
      if ($requestId > 0) {
        $requestStage = $newStatus;
        $stmt = $conn->prepare("
          UPDATE farmer_requests
          SET request_stage = ?,
              status = IF(? = 'Completed', 'Completed', status)
          WHERE request_id = ?
        ");
        $stmt->bind_param("ssi", $requestStage, $requestStage, $requestId);
        $stmt->execute();
        $stmt->close();
      }
    }

    // System log
    $actorId = $userId;
    $actionLabel = "Task Updated";
    $desc = "Task #{$taskId} set to {$newStatus}";
    $stmt = $conn->prepare("INSERT INTO system_logs(user_id, action, description) VALUES(?,?,?)");
    $stmt->bind_param("iss", $actorId, $actionLabel, $desc);
    $stmt->execute();
    $stmt->close();

    if ($previousStatus !== $newStatus) {
      send_task_status_sms_if_needed($conn, $taskId, $newStatus);
    }

    $_SESSION['flash'] = "Task #{$taskId} updated to {$newStatus}.";
  }

  header("Location: " . route('tasks'));
  exit;
}

/**
 * Filters (optional)
 */
$fStatus = trim($_GET['status'] ?? '');
$q = trim($_GET['q'] ?? '');

$where = [];
$params = [];
$types = "";

// Technician sees only their tasks
if ($role === 'Irrigation Technician') {
  $where[] = "t.assigned_user_id = ?";
  $params[] = $userId;
  $types .= "i";
}

if ($fStatus !== '' && in_array($fStatus, ['Due','In Progress','Completed','Missed'], true)) {
  $where[] = "t.status = ?";
  $params[] = $fStatus;
  $types .= "s";
}

if ($q !== '') {
  $like = "%{$q}%";
  $where[] = "(sa.area_name LIKE ? OR sa.municipality LIKE ? OR u.username LIKE ? OR f.farmer_name LIKE ? OR f.phone LIKE ?)";
  $params[] = $like;
  $params[] = $like;
  $params[] = $like;
  $params[] = $like;
  $params[] = $like;
  $types .= "sssss";
}

$whereSql = $where ? ("WHERE " . implode(" AND ", $where)) : "";

$perPage = 5;
$pageNum = max(1, (int)($_GET['p'] ?? 1));

$total = 0;
$countSql = "
  SELECT COUNT(*) AS total
  FROM tasks t
  JOIN irrigation_schedules s ON s.schedule_id = t.schedule_id
  LEFT JOIN service_areas sa ON sa.service_area_id = s.service_area_id
  LEFT JOIN users u ON u.user_id = t.assigned_user_id
  LEFT JOIN farmer_requests r ON r.request_id = s.request_id
  LEFT JOIN farmers f ON f.farmer_id = r.farmer_id
  {$whereSql}
";

$stmt = $conn->prepare($countSql);
if ($types !== '') $stmt->bind_param($types, ...$params);
$stmt->execute();
$total = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
$stmt->close();

$totalPages = max(1, (int)ceil($total / $perPage));
if ($pageNum > $totalPages) {
  $pageNum = $totalPages;
}
$offset = ($pageNum - 1) * $perPage;

$rows = [];
$sql = "
  SELECT t.task_id, t.status, t.started_at, t.ended_at,
         s.schedule_date, s.start_time, s.end_time, s.request_id,
         sa.area_name, sa.municipality,
         u.username AS assigned_username,
         t.assigned_user_id,
         f.farmer_id, f.farmer_name, f.phone AS farmer_phone,
         f.association_name, CONCAT_WS(', ', sa.municipality, sa.province) AS association_location, f.address AS farmer_address,
         fl.lot_code AS farmer_lot_code, fl.location_desc AS farmer_lot_location
  FROM tasks t
  JOIN irrigation_schedules s ON s.schedule_id = t.schedule_id
  LEFT JOIN service_areas sa ON sa.service_area_id = s.service_area_id
  LEFT JOIN users u ON u.user_id = t.assigned_user_id
  LEFT JOIN farmer_requests r ON r.request_id = s.request_id
  LEFT JOIN farmers f ON f.farmer_id = r.farmer_id
  LEFT JOIN (
    SELECT fl1.farmer_id, fl1.lot_code, fl1.location_desc
    FROM farmer_lots fl1
    INNER JOIN (
      SELECT farmer_id, MAX(lot_id) AS max_lot_id
      FROM farmer_lots
      GROUP BY farmer_id
    ) latest ON latest.max_lot_id = fl1.lot_id
  ) fl ON fl.farmer_id = f.farmer_id
  {$whereSql}
  ORDER BY s.schedule_date DESC, s.start_time DESC
  LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);
$rowTypes = $types . "ii";
$rowParams = $params;
$rowParams[] = $perPage;
$rowParams[] = $offset;
$stmt->bind_param($rowTypes, ...$rowParams);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include __DIR__ . '/../includes/head.php';
?>

<div class="flex min-h-screen w-full">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="flex-1 flex flex-col">
    <?php include __DIR__ . '/../includes/topbar.php'; ?>

    <main class="p-6 lg:p-8 flex-1">

      <?php if ($flash): ?>
        <div id="tasksFlashModalBg" class="fixed inset-0 z-[100] bg-black/35"></div>
        <div id="tasksFlashModal" class="fixed inset-0 z-[101] flex items-center justify-center p-4">
          <div class="w-full max-w-md rounded-2xl bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark shadow-xl p-5">
            <div class="flex items-start gap-3">
              <span class="material-symbols-outlined text-green-600 dark:text-green-400 text-[24px] leading-none mt-0.5">check_circle</span>
              <div class="min-w-0">
                <h3 class="text-base font-black text-text-light dark:text-text-dark">Task Updated</h3>
                <p class="mt-1 text-sm text-text-light dark:text-text-dark break-words"><?= h($flash) ?></p>
              </div>
            </div>
            <div class="mt-5 flex justify-end">
              <button type="button" id="tasksFlashOk" class="px-4 py-2 rounded-full bg-primary text-white font-semibold">OK</button>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <!-- Filters -->
      <div class="sticky top-2 z-20 rounded-xl border border-border-light dark:border-border-dark bg-background-light/95 dark:bg-background-dark/95 backdrop-blur px-3 py-3 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
        <form id="taskFilterForm" method="GET" action="<?= route('tasks') ?>" class="flex flex-col sm:flex-row gap-2 flex-1 min-w-0">
          <input type="hidden" name="page" value="tasks">

          <div class="relative w-full sm:flex-1 sm:min-w-[18rem] lg:min-w-[24rem]">
            <span class="material-symbols-outlined pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-[18px] leading-none">search</span>
            <input id="taskSearchInput" name="q" value="<?= h($q) ?>" placeholder="Search area / municipality / assigned / farmer..."
                   class="w-full pl-9 pr-4 rounded-full border-border-light dark:border-border-dark bg-card-light dark:bg-card-dark">
          </div>

          <select id="taskStatusSelect" name="status" class="w-full sm:w-40 rounded-full px-3 border-border-light dark:border-border-dark bg-card-light dark:bg-card-dark">
            <option value="">All Status</option>
            <?php foreach(['Due','In Progress','Completed','Missed'] as $s): ?>
              <option value="<?= $s ?>" <?= $fStatus===$s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
          </select>

          <a
            class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700 text-text-light dark:text-text-dark inline-flex items-center justify-center shrink-0"
            href="<?= route('tasks') ?>"
            title="Reset"
            aria-label="Reset filters"
          >
            <span class="material-symbols-outlined text-[20px] leading-none">restart_alt</span>
          </a>
        </form>
      </div>

      <div id="tasksResults">
      <?php
        $statusUiMap = [
          'Due' => [
            'class' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
            'icon' => 'schedule'
          ],
          'In Progress' => [
            'class' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-200',
            'icon' => 'autorenew'
          ],
          'Completed' => [
            'class' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-200',
            'icon' => 'check_circle'
          ],
          'Missed' => [
            'class' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-200',
            'icon' => 'cancel'
          ],
        ];
      ?>

      <!-- Mobile cards -->
      <div class="mt-6 md:hidden space-y-3">
        <?php foreach ($rows as $r): ?>
          <?php
            $statusLabel = (string)($r['status'] ?? '');
            $statusUi = $statusUiMap[$statusLabel] ?? [
              'class' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200',
              'icon' => 'help'
            ];
          ?>
          <div class="rounded-xl border border-border-light dark:border-border-dark bg-card-light dark:bg-card-dark p-4">
            <div class="flex items-start justify-between gap-2">
              <div>
                <p class="text-sm font-black text-text-light dark:text-text-dark"><?= h($r['schedule_date']) ?></p>
                <p class="text-xs text-gray-500 dark:text-gray-400"><?= h($r['start_time']) ?> - <?= h($r['end_time']) ?></p>
              </div>
              <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full <?= $statusUi['class'] ?>">
                <span class="material-symbols-outlined text-[14px] leading-none"><?= h($statusUi['icon']) ?></span>
                <span><?= h($statusLabel) ?></span>
              </span>
            </div>

            <div class="mt-3 space-y-1.5">
              <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Area</p>
              <p class="text-sm font-semibold text-text-light dark:text-text-dark">
                <?= h($r['area_name']) ?><?= $r['municipality'] ? ", " . h($r['municipality']) : "" ?>
              </p>
              <p class="text-xs text-gray-500 dark:text-gray-400">Farmer: <?= h($r['farmer_name'] ?: '-') ?></p>
              <p class="text-xs text-gray-500 dark:text-gray-400">Assigned: <?= h($r['assigned_username'] ?? '-') ?></p>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
              <a
                class="w-9 h-9 rounded-full border border-border-light dark:border-border-dark inline-flex items-center justify-center text-secondary"
                href="<?= base_path('tasks/view.php?id='.(int)$r['task_id']) ?>"
                title="View"
                aria-label="View task"
              >
                <span class="material-symbols-outlined text-[18px] leading-none">visibility</span>
              </a>
              <a
                class="w-9 h-9 rounded-full border border-border-light dark:border-border-dark inline-flex items-center justify-center text-secondary"
                href="<?= base_path('tasks/log.php?id='.(int)$r['task_id']) ?>"
                title="Log"
                aria-label="Task log"
              >
                <span class="material-symbols-outlined text-[18px] leading-none">history</span>
              </a>
              <?php if (!empty($r['farmer_id'])): ?>
                <button
                  type="button"
                  class="w-9 h-9 rounded-full border border-border-light dark:border-border-dark inline-flex items-center justify-center text-secondary js-open-farmer-details"
                  title="Farmer details"
                  aria-label="Farmer details"
                  data-farmer-name="<?= h((string)($r['farmer_name'] ?? '-')) ?>"
                  data-farmer-phone="<?= h((string)($r['farmer_phone'] ?? '-')) ?>"
                  data-farmer-association="<?= h((string)($r['association_name'] ?? '-')) ?>"
                  data-farmer-association-location="<?= h((string)($r['association_location'] ?? '-')) ?>"
                  data-farmer-address="<?= h((string)($r['farmer_address'] ?? '-')) ?>"
                  data-farmer-lot-code="<?= h((string)($r['farmer_lot_code'] ?? '-')) ?>"
                  data-farmer-lot-location="<?= h((string)($r['farmer_lot_location'] ?? '-')) ?>"
                  data-request-id="<?= (int)($r['request_id'] ?? 0) ?>"
                >
                  <span class="material-symbols-outlined text-[18px] leading-none">person</span>
                </button>
              <?php endif; ?>

              <?php if (in_array($r['status'], ['Due','In Progress'], true)): ?>
                <form method="POST" class="inline">
                  <input type="hidden" name="task_id" value="<?= (int)$r['task_id'] ?>">
                  <input type="hidden" name="action" value="start">
                  <button class="px-3 py-2 rounded-full bg-secondary text-white text-xs font-semibold">Start</button>
                </form>
              <?php endif; ?>

              <?php if ($r['status'] === 'In Progress'): ?>
                <form method="POST" class="inline">
                  <input type="hidden" name="task_id" value="<?= (int)$r['task_id'] ?>">
                  <input type="hidden" name="action" value="complete">
                  <input type="hidden" name="completion_remarks" class="js-completion-remarks">
                  <button class="px-3 py-2 rounded-full bg-green-600 text-white text-xs font-bold hover:bg-green-700">Complete</button>
                </form>
              <?php endif; ?>

              <?php if (in_array($r['status'], ['Due','In Progress'], true)): ?>
                <form method="POST" class="inline js-missed-form">
                  <input type="hidden" name="task_id" value="<?= (int)$r['task_id'] ?>">
                  <input type="hidden" name="action" value="missed">
                  <button class="px-3 py-2 rounded-full bg-red-600 text-white text-xs font-bold hover:bg-red-700">Missed</button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
          <div class="rounded-xl border border-border-light dark:border-border-dark bg-card-light dark:bg-card-dark p-4 text-sm text-gray-500">No tasks found.</div>
        <?php endif; ?>
      </div>

      <!-- Desktop table -->
      <div class="mt-6 hidden md:block bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead>
              <tr class="text-xs text-gray-500 dark:text-gray-400 uppercase">
                <th class="p-3">Schedule</th>
                <th class="p-3">Area</th>
                <th class="p-3">Farmer</th>
                <th class="p-3">Assigned</th>
                <th class="p-3">Status</th>
                <th class="p-3">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-border-light dark:divide-border-dark">
              <?php foreach ($rows as $r): ?>
                <?php
                  $statusLabel = (string)($r['status'] ?? '');
                  $statusUi = $statusUiMap[$statusLabel] ?? [
                    'class' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200',
                    'icon' => 'help'
                  ];
                ?>
                <tr class="text-sm text-text-light dark:text-text-dark">
                  <td class="p-3">
                    <div class="font-semibold"><?= h($r['schedule_date']) ?></div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                      <?= h($r['start_time']) ?> - <?= h($r['end_time']) ?>
                    </div>
                  </td>
                  <td class="p-3">
                    <?= h($r['area_name']) ?>
                    <?= $r['municipality'] ? ", " . h($r['municipality']) : "" ?>
                  </td>
                  <td class="p-3">
                    <div class="font-semibold"><?= h($r['farmer_name'] ?: '-') ?></div>
                    <div class="text-xs text-gray-500 dark:text-gray-400"><?= h($r['farmer_phone'] ?: '-') ?></div>
                  </td>
                  <td class="p-3"><?= h($r['assigned_username'] ?? '-') ?></td>
                  <td class="p-3">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full <?= $statusUi['class'] ?>">
                      <span class="material-symbols-outlined text-[14px] leading-none"><?= h($statusUi['icon']) ?></span>
                      <span><?= h($statusLabel) ?></span>
                    </span>
                  </td>

                  <td class="p-3">
                    <div class="flex flex-wrap items-center gap-2">
                      <a
                        class="p-1.5 rounded-DEFAULT hover:bg-gray-100 dark:hover:bg-gray-800 text-secondary inline-flex items-center justify-center"
                        href="<?= base_path('tasks/view.php?id='.(int)$r['task_id']) ?>"
                        title="View"
                        aria-label="View task"
                      >
                        <span class="material-symbols-outlined text-[20px] leading-none">visibility</span>
                      </a>
                      <a
                        class="p-1.5 rounded-DEFAULT hover:bg-gray-100 dark:hover:bg-gray-800 text-secondary inline-flex items-center justify-center"
                        href="<?= base_path('tasks/log.php?id='.(int)$r['task_id']) ?>"
                        title="Log"
                        aria-label="Task log"
                      >
                        <span class="material-symbols-outlined text-[20px] leading-none">history</span>
                      </a>
                      <?php if (!empty($r['farmer_id'])): ?>
                        <button
                          type="button"
                          class="p-1.5 rounded-DEFAULT hover:bg-gray-100 dark:hover:bg-gray-800 text-secondary inline-flex items-center justify-center js-open-farmer-details"
                          title="Farmer details"
                          aria-label="Farmer details"
                          data-farmer-name="<?= h((string)($r['farmer_name'] ?? '-')) ?>"
                          data-farmer-phone="<?= h((string)($r['farmer_phone'] ?? '-')) ?>"
                          data-farmer-association="<?= h((string)($r['association_name'] ?? '-')) ?>"
                          data-farmer-association-location="<?= h((string)($r['association_location'] ?? '-')) ?>"
                          data-farmer-address="<?= h((string)($r['farmer_address'] ?? '-')) ?>"
                          data-farmer-lot-code="<?= h((string)($r['farmer_lot_code'] ?? '-')) ?>"
                          data-farmer-lot-location="<?= h((string)($r['farmer_lot_location'] ?? '-')) ?>"
                          data-request-id="<?= (int)($r['request_id'] ?? 0) ?>"
                        >
                          <span class="material-symbols-outlined text-[20px] leading-none">person</span>
                        </button>
                      <?php endif; ?>

                      <?php if (in_array($r['status'], ['Due','In Progress'], true)): ?>
                        <form method="POST" class="inline">
                          <input type="hidden" name="task_id" value="<?= (int)$r['task_id'] ?>">
                          <input type="hidden" name="action" value="start">
                          <button class="px-3 py-2 rounded-full bg-secondary text-white text-xs font-semibold">Start</button>
                        </form>
                      <?php endif; ?>

                      <?php if ($r['status'] === 'In Progress'): ?>
                        <form method="POST" class="inline">
                          <input type="hidden" name="task_id" value="<?= (int)$r['task_id'] ?>">
                          <input type="hidden" name="action" value="complete">
                          <input type="hidden" name="completion_remarks" class="js-completion-remarks">
                          <button class="px-3 py-2 rounded-full bg-green-600 text-white text-xs font-bold hover:bg-green-700">Complete</button>
                        </form>
                      <?php endif; ?>

                      <?php if (in_array($r['status'], ['Due','In Progress'], true)): ?>
                        <form method="POST" class="inline js-missed-form">
                          <input type="hidden" name="task_id" value="<?= (int)$r['task_id'] ?>">
                          <input type="hidden" name="action" value="missed">
                          <button class="px-3 py-2 rounded-full bg-red-600 text-white text-xs font-bold hover:bg-red-700">Missed</button>
                        </form>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>

              <?php if (!$rows): ?>
                <tr><td class="p-3 text-gray-500" colspan="6">No tasks found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <?php
        $showFrom = $total ? ($offset + 1) : 0;
        $showTo = min($offset + $perPage, $total);
        $prevPage = max(1, $pageNum - 1);
        $nextPage = min($totalPages, $pageNum + 1);
        $baseParams = ['page' => 'tasks', 'q' => $q, 'status' => $fStatus];
      ?>
      <div class="mt-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-sm text-gray-500 dark:text-gray-400">
        <div>
          Showing <?= (int)$showFrom ?>-<?= (int)$showTo ?> of <?= (int)$total ?>
        </div>
        <div class="flex items-center gap-2">
          <?php if ($pageNum > 1): ?>
            <a
              href="<?= route('tasks', array_merge($baseParams, ['p' => $prevPage])) ?>"
              data-page="<?= (int)$prevPage ?>"
              class="w-9 h-9 rounded-full border border-border-light dark:border-border-dark inline-flex items-center justify-center hover:bg-gray-100 dark:hover:bg-gray-800"
              title="Previous page"
              aria-label="Previous page"
            >
              <span class="material-symbols-outlined text-[18px] leading-none">chevron_left</span>
            </a>
          <?php else: ?>
            <span class="w-9 h-9 rounded-full border border-border-light dark:border-border-dark inline-flex items-center justify-center opacity-40 cursor-not-allowed">
              <span class="material-symbols-outlined text-[18px] leading-none">chevron_left</span>
            </span>
          <?php endif; ?>

          <span class="px-3 py-1.5 rounded-full border border-border-light dark:border-border-dark">
            Page <?= (int)$pageNum ?> of <?= (int)$totalPages ?>
          </span>

          <?php if ($pageNum < $totalPages): ?>
            <a
              href="<?= route('tasks', array_merge($baseParams, ['p' => $nextPage])) ?>"
              data-page="<?= (int)$nextPage ?>"
              class="w-9 h-9 rounded-full border border-border-light dark:border-border-dark inline-flex items-center justify-center hover:bg-gray-100 dark:hover:bg-gray-800"
              title="Next page"
              aria-label="Next page"
            >
              <span class="material-symbols-outlined text-[18px] leading-none">chevron_right</span>
            </a>
          <?php else: ?>
            <span class="w-9 h-9 rounded-full border border-border-light dark:border-border-dark inline-flex items-center justify-center opacity-40 cursor-not-allowed">
              <span class="material-symbols-outlined text-[18px] leading-none">chevron_right</span>
            </span>
          <?php endif; ?>
        </div>
      </div>
      </div>

    </main>
  </div>
</div>

<div id="taskFarmerModal" class="fixed inset-0 z-[90] hidden items-center justify-center bg-black/45 p-4">
  <div class="w-full max-w-xl rounded-2xl border border-border-light dark:border-border-dark bg-card-light dark:bg-card-dark shadow-xl">
    <div class="flex items-center justify-between border-b border-border-light dark:border-border-dark px-4 py-3">
      <h3 class="text-base font-black text-text-light dark:text-text-dark">Farmer Details</h3>
      <button type="button" class="w-9 h-9 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 inline-flex items-center justify-center" id="taskFarmerModalClose" aria-label="Close">
        <span class="material-symbols-outlined text-[20px] leading-none">close</span>
      </button>
    </div>
    <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
      <div>
        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Farmer</p>
        <p class="font-semibold" id="taskFarmerName">-</p>
      </div>
      <div>
        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Phone</p>
        <p class="font-semibold" id="taskFarmerPhone">-</p>
      </div>
      <div>
        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Request ID</p>
        <p class="font-semibold" id="taskFarmerRequestId">-</p>
      </div>
      <div>
        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Association</p>
        <p class="font-semibold" id="taskFarmerAssociation">-</p>
      </div>
      <div class="sm:col-span-2">
        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Association Location</p>
        <p class="font-semibold" id="taskFarmerAssociationLocation">-</p>
      </div>
      <div class="sm:col-span-2">
        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Address</p>
        <p class="font-semibold" id="taskFarmerAddress">-</p>
      </div>
      <div>
        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Lot Code</p>
        <p class="font-semibold" id="taskFarmerLotCode">-</p>
      </div>
      <div>
        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Lot Location</p>
        <p class="font-semibold" id="taskFarmerLotLocation">-</p>
      </div>
    </div>
  </div>
</div>

<div id="taskCompletionModal" class="fixed inset-0 z-[95] hidden items-center justify-center bg-black/45 p-4">
  <div class="w-full max-w-lg rounded-2xl border border-border-light dark:border-border-dark bg-card-light dark:bg-card-dark shadow-xl">
    <div class="flex items-center justify-between border-b border-border-light dark:border-border-dark px-4 py-3">
      <h3 class="text-base font-black text-text-light dark:text-text-dark">Completion Remarks</h3>
      <button type="button" id="taskCompletionModalClose" class="w-9 h-9 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 inline-flex items-center justify-center" aria-label="Close">
        <span class="material-symbols-outlined text-[20px] leading-none">close</span>
      </button>
    </div>
    <div class="p-4">
      <label for="taskCompletionRemarksInput" class="block text-sm font-semibold text-text-light dark:text-text-dark">Enter completion remarks *</label>
      <textarea id="taskCompletionRemarksInput" rows="4"
                class="mt-2 w-full rounded-lg border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark"></textarea>
      <p id="taskCompletionRemarksError" class="mt-2 text-xs text-red-600 hidden">Remarks are required when completing a task.</p>
      <div class="mt-4 flex justify-end gap-2">
        <button type="button" id="taskCompletionCancelBtn" class="px-4 py-2 rounded-full bg-gray-200 dark:bg-gray-700 text-text-light dark:text-text-dark">Cancel</button>
        <button type="button" id="taskCompletionSaveBtn" class="px-4 py-2 rounded-full bg-primary text-white font-semibold">Save</button>
      </div>
    </div>
  </div>
</div>

<div id="taskMissedModal" class="fixed inset-0 z-[96] hidden items-center justify-center bg-black/45 p-4">
  <div class="w-full max-w-md rounded-2xl border border-border-light dark:border-border-dark bg-card-light dark:bg-card-dark shadow-xl">
    <div class="flex items-center justify-between border-b border-border-light dark:border-border-dark px-4 py-3">
      <h3 class="text-base font-black text-text-light dark:text-text-dark">Confirm Missed Task</h3>
      <button type="button" id="taskMissedModalClose" class="w-9 h-9 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 inline-flex items-center justify-center" aria-label="Close">
        <span class="material-symbols-outlined text-[20px] leading-none">close</span>
      </button>
    </div>
    <div class="p-4">
      <p class="text-sm text-text-light dark:text-text-dark">Mark this task as <b>Missed</b>?</p>
      <div class="mt-4 flex justify-end gap-2">
        <button type="button" id="taskMissedCancelBtn" class="px-4 py-2 rounded-full bg-gray-200 dark:bg-gray-700 text-text-light dark:text-text-dark">Cancel</button>
        <button type="button" id="taskMissedConfirmBtn" class="px-4 py-2 rounded-full bg-red-600 text-white font-semibold">Confirm</button>
      </div>
    </div>
  </div>
</div>

<script>
(() => {
  const flashBg = document.getElementById('tasksFlashModalBg');
  const flashModal = document.getElementById('tasksFlashModal');
  const flashOk = document.getElementById('tasksFlashOk');
  if (!flashBg || !flashModal) return;

  const closeFlash = () => {
    flashBg.classList.add('hidden');
    flashModal.classList.add('hidden');
  };

  flashOk?.addEventListener('click', closeFlash);
  flashBg.addEventListener('click', closeFlash);
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') closeFlash();
  });

  window.setTimeout(closeFlash, 3500);
})();

(() => {
  const form = document.getElementById('taskFilterForm');
  const searchInput = document.getElementById('taskSearchInput');
  const statusSelect = document.getElementById('taskStatusSelect');
  if (!form || !searchInput || !statusSelect) return;

  const resultsContainerId = 'tasksResults';
  let timerId = null;
  let activeRequest = null;
  const farmerModal = document.getElementById('taskFarmerModal');
  const farmerModalClose = document.getElementById('taskFarmerModalClose');
  const completionModal = document.getElementById('taskCompletionModal');
  const completionModalClose = document.getElementById('taskCompletionModalClose');
  const completionCancelBtn = document.getElementById('taskCompletionCancelBtn');
  const completionSaveBtn = document.getElementById('taskCompletionSaveBtn');
  const completionRemarksInput = document.getElementById('taskCompletionRemarksInput');
  const completionRemarksError = document.getElementById('taskCompletionRemarksError');
  const missedModal = document.getElementById('taskMissedModal');
  const missedModalClose = document.getElementById('taskMissedModalClose');
  const missedCancelBtn = document.getElementById('taskMissedCancelBtn');
  const missedConfirmBtn = document.getElementById('taskMissedConfirmBtn');
  let pendingCompletionForm = null;
  let pendingMissedForm = null;
  const farmerFields = {
    name: document.getElementById('taskFarmerName'),
    phone: document.getElementById('taskFarmerPhone'),
    requestId: document.getElementById('taskFarmerRequestId'),
    association: document.getElementById('taskFarmerAssociation'),
    associationLocation: document.getElementById('taskFarmerAssociationLocation'),
    address: document.getElementById('taskFarmerAddress'),
    lotCode: document.getElementById('taskFarmerLotCode'),
    lotLocation: document.getElementById('taskFarmerLotLocation')
  };

  const setFarmerField = (key, value) => {
    const node = farmerFields[key];
    if (!node) return;
    const text = (value || '').toString().trim();
    node.textContent = text !== '' ? text : '-';
  };

  const closeFarmerModal = () => {
    if (!farmerModal) return;
    farmerModal.classList.add('hidden');
    farmerModal.classList.remove('flex');
  };

  const closeCompletionModal = () => {
    if (!completionModal) return;
    completionModal.classList.add('hidden');
    completionModal.classList.remove('flex');
    pendingCompletionForm = null;
    if (completionRemarksInput) completionRemarksInput.value = '';
    if (completionRemarksError) completionRemarksError.classList.add('hidden');
  };

  const openCompletionModal = (formEl) => {
    if (!completionModal || !completionRemarksInput) return;
    pendingCompletionForm = formEl;
    completionModal.classList.remove('hidden');
    completionModal.classList.add('flex');
    completionRemarksInput.value = '';
    if (completionRemarksError) completionRemarksError.classList.add('hidden');
    window.setTimeout(() => completionRemarksInput.focus(), 0);
  };

  const confirmCompletionRemarks = () => {
    if (!pendingCompletionForm || !completionRemarksInput) return;
    const trimmed = completionRemarksInput.value.trim();
    if (trimmed === '') {
      if (completionRemarksError) completionRemarksError.classList.remove('hidden');
      completionRemarksInput.focus();
      return;
    }
    const remarkInput = pendingCompletionForm.querySelector('.js-completion-remarks');
    if (remarkInput) remarkInput.value = trimmed;
    pendingCompletionForm.dataset.skipCompletionPrompt = '1';
    const formToSubmit = pendingCompletionForm;
    closeCompletionModal();
    formToSubmit.submit();
  };

  const closeMissedModal = () => {
    if (!missedModal) return;
    missedModal.classList.add('hidden');
    missedModal.classList.remove('flex');
    pendingMissedForm = null;
  };

  const openMissedModal = (formEl) => {
    if (!missedModal || !formEl) return;
    pendingMissedForm = formEl;
    missedModal.classList.remove('hidden');
    missedModal.classList.add('flex');
  };

  const confirmMissed = () => {
    if (!pendingMissedForm) return;
    pendingMissedForm.dataset.skipMissedConfirm = '1';
    closeMissedModal();
    pendingMissedForm.submit();
  };

  const openFarmerModal = (btn) => {
    if (!farmerModal || !btn) return;
    setFarmerField('name', btn.dataset.farmerName || '-');
    setFarmerField('phone', btn.dataset.farmerPhone || '-');
    setFarmerField('association', btn.dataset.farmerAssociation || '-');
    setFarmerField('associationLocation', btn.dataset.farmerAssociationLocation || '-');
    setFarmerField('address', btn.dataset.farmerAddress || '-');
    setFarmerField('lotCode', btn.dataset.farmerLotCode || '-');
    setFarmerField('lotLocation', btn.dataset.farmerLotLocation || '-');
    const reqId = parseInt(btn.dataset.requestId || '0', 10);
    setFarmerField('requestId', Number.isFinite(reqId) && reqId > 0 ? `#${reqId}` : '-');
    farmerModal.classList.remove('hidden');
    farmerModal.classList.add('flex');
  };

  if (farmerModalClose) {
    farmerModalClose.addEventListener('click', closeFarmerModal);
  }
  if (completionModalClose) completionModalClose.addEventListener('click', closeCompletionModal);
  if (completionCancelBtn) completionCancelBtn.addEventListener('click', closeCompletionModal);
  if (completionSaveBtn) completionSaveBtn.addEventListener('click', confirmCompletionRemarks);
  if (missedModalClose) missedModalClose.addEventListener('click', closeMissedModal);
  if (missedCancelBtn) missedCancelBtn.addEventListener('click', closeMissedModal);
  if (missedConfirmBtn) missedConfirmBtn.addEventListener('click', confirmMissed);
  if (completionRemarksInput) {
    completionRemarksInput.addEventListener('input', () => {
      if (completionRemarksError) completionRemarksError.classList.add('hidden');
    });
    completionRemarksInput.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' && (event.ctrlKey || event.metaKey)) {
        event.preventDefault();
        confirmCompletionRemarks();
      }
    });
  }

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    if (completionModal && !completionModal.classList.contains('hidden')) {
      closeCompletionModal();
      return;
    }
    if (missedModal && !missedModal.classList.contains('hidden')) {
      closeMissedModal();
      return;
    }
    closeFarmerModal();
  });

  document.addEventListener('click', (event) => {
    const openBtn = event.target.closest('.js-open-farmer-details');
    if (openBtn) {
      event.preventDefault();
      openFarmerModal(openBtn);
      return;
    }
    if (farmerModal && !farmerModal.classList.contains('hidden') && event.target === farmerModal) {
      closeFarmerModal();
      return;
    }
    if (completionModal && !completionModal.classList.contains('hidden') && event.target === completionModal) {
      closeCompletionModal();
      return;
    }
    if (missedModal && !missedModal.classList.contains('hidden') && event.target === missedModal) {
      closeMissedModal();
    }
  });

  const buildUrl = (params) => {
    const url = new URL(form.action, window.location.origin);
    url.search = params.toString();
    return url;
  };

  const getParamsFromForm = (page = 1) => {
    const params = new URLSearchParams(new FormData(form));
    params.set('page', 'tasks');
    params.set('p', String(page));
    return params;
  };

  const updateFromResponse = (html) => {
    const doc = new DOMParser().parseFromString(html, 'text/html');
    const nextResults = doc.getElementById(resultsContainerId);
    const currentResults = document.getElementById(resultsContainerId);
    if (!nextResults || !currentResults) return false;
    currentResults.innerHTML = nextResults.innerHTML;
    return true;
  };

  const loadResults = (params) => {
    if (activeRequest) activeRequest.abort();

    activeRequest = new AbortController();
    const url = buildUrl(params);

    fetch(url.toString(), {
      method: 'GET',
      signal: activeRequest.signal,
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then((response) => {
        if (!response.ok) throw new Error('Task filter request failed');
        return response.text();
      })
      .then((html) => {
        if (!updateFromResponse(html)) {
          window.location.href = url.pathname + url.search;
          return;
        }
        wireCompletionRemarkPrompts();
        window.history.replaceState({}, '', url.pathname + url.search);
      })
      .catch((err) => {
        if (err && err.name === 'AbortError') return;
        window.location.href = url.pathname + url.search;
      });
  };

  searchInput.addEventListener('input', () => {
    if (timerId !== null) window.clearTimeout(timerId);
    timerId = window.setTimeout(() => {
      loadResults(getParamsFromForm(1));
    }, 350);
  });

  statusSelect.addEventListener('change', () => {
    loadResults(getParamsFromForm(1));
  });

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    loadResults(getParamsFromForm(1));
  });

  const results = document.getElementById(resultsContainerId);
  if (results) {
    const wireCompletionRemarkPrompts = () => {
      const completeForms = results.querySelectorAll('form input[name="action"][value="complete"]');
      completeForms.forEach((actionInput) => {
        const formEl = actionInput.closest('form');
        if (!formEl || formEl.dataset.completionPromptBound === '1') return;
        formEl.dataset.completionPromptBound = '1';
        formEl.addEventListener('submit', (event) => {
          if (formEl.dataset.skipCompletionPrompt === '1') {
            formEl.dataset.skipCompletionPrompt = '';
            return;
          }
          event.preventDefault();
          if (!completionModal) {
            const remark = window.prompt('Enter completion remarks for this task:');
            if (remark === null) return;
            const trimmed = remark.trim();
            if (trimmed === '') {
              window.alert('Remarks are required when completing a task.');
              return;
            }
            const remarkInput = formEl.querySelector('.js-completion-remarks');
            if (remarkInput) remarkInput.value = trimmed;
            formEl.dataset.skipCompletionPrompt = '1';
            formEl.submit();
            return;
          }
          openCompletionModal(formEl);
        });
      });
    };

    wireCompletionRemarkPrompts();

    results.addEventListener('click', (event) => {
      const pageLink = event.target.closest('a[data-page]');
      if (!pageLink) return;
      event.preventDefault();
      const nextPage = parseInt(pageLink.getAttribute('data-page') || '1', 10);
      if (!Number.isFinite(nextPage) || nextPage < 1) return;
      loadResults(getParamsFromForm(nextPage));
    });

    document.addEventListener('submit', (event) => {
      const missedForm = event.target.closest('form.js-missed-form');
      if (!missedForm) return;
      if (missedForm.dataset.skipMissedConfirm === '1') {
        missedForm.dataset.skipMissedConfirm = '';
        return;
      }
      event.preventDefault();
      if (!missedModal) {
        if (window.confirm('Mark this task as Missed?')) {
          missedForm.dataset.skipMissedConfirm = '1';
          missedForm.submit();
        }
        return;
      }
      openMissedModal(missedForm);
    });

  }
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
