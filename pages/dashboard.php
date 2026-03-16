<?php
require_once __DIR__ . "/../includes/helpers.php";
require_once __DIR__ . "/../includes/auth.php";
require_login();
require_once __DIR__ . "/../includes/config.php";

$active   = "dashboard";
$topTitle = "Administrator Dashboard";

$today = date('Y-m-d');
$userName = $_SESSION['user']['fullname'] ?? $_SESSION['user']['username'] ?? 'User';

$focusAreaIds = [];
$stmt = $conn->prepare("
  SELECT service_area_id, area_name, municipality, province
  FROM service_areas
");
$stmt->execute();
$focusAreas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$focusAreas = filter_focus_service_area_rows($focusAreas);
$focusAreaIds = array_map('intval', array_column($focusAreas, 'service_area_id'));
$focusAreaInSql = $focusAreaIds ? implode(',', $focusAreaIds) : '0';

/**
 * 1) Pending Water Requests
 */
$pendingRequests = 0;
$stmt = $conn->prepare("
  SELECT COUNT(*) c
  FROM farmer_requests r
  JOIN farmers f ON f.farmer_id = r.farmer_id
  WHERE r.status='Pending'
    AND f.service_area_id IN ({$focusAreaInSql})
");
$stmt->execute();
$pendingRequests = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
$stmt->close();

/**
 * 2) Active Irrigations (today + active + current time in range)
 */
$activeIrrigations = 0;
$stmt = $conn->prepare("
  SELECT COUNT(*) c
  FROM irrigation_schedules
  WHERE schedule_date=?
    AND status='Active'
    AND CURTIME() BETWEEN start_time AND end_time
    AND service_area_id IN ({$focusAreaInSql})
");
$stmt->bind_param("s",$today);
$stmt->execute();
$activeIrrigations = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
$stmt->close();

/**
 * 3) Upcoming schedules (today onwards + active)
 */
$upcomingSchedules = 0;
$stmt = $conn->prepare("
  SELECT COUNT(*) c
  FROM irrigation_schedules
  WHERE schedule_date>=?
    AND status='Active'
    AND service_area_id IN ({$focusAreaInSql})
");
$stmt->bind_param("s",$today);
$stmt->execute();
$upcomingSchedules = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
$stmt->close();

/**
 * 4) Farmers Served Today
 * Based on distinct farmers linked to schedules that have water_release_logs today.
 * Your schema: water_release_logs has schedule_id, irrigation_batches links schedule_id->farmer_id
 */
$farmersServedToday = 0;
$stmt = $conn->prepare("
  SELECT COUNT(DISTINCT b.farmer_id) c
  FROM water_release_logs w
  JOIN irrigation_schedules s ON s.schedule_id = w.schedule_id
  JOIN irrigation_batches b ON b.schedule_id = w.schedule_id
  WHERE DATE(w.released_at)=?
    AND s.service_area_id IN ({$focusAreaInSql})
");
$stmt->bind_param("s",$today);
$stmt->execute();
$farmersServedToday = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
$stmt->close();

/**
 * Upcoming schedule table rows
 * Show first farmer in batch_order=1
 */
/**
 * Upcoming schedule table rows
 * Show first farmer in batch_order=1
 */
$upcomingRows = [];
$stmt = $conn->prepare("
  SELECT
    s.schedule_id,
    COALESCE(f.farmer_name, '-') AS farmer_name,
    CONCAT(COALESCE(sa.area_name,''), IFNULL(CONCAT(', ', sa.municipality),'')) AS location,
    CONCAT(
      DATE_FORMAT(s.schedule_date, '%b %d, %Y'),
      ', ',
      DATE_FORMAT(s.start_time, '%h:%i %p')
    ) AS time_display,
    s.status
  FROM irrigation_schedules s
  LEFT JOIN service_areas sa 
    ON sa.service_area_id = s.service_area_id
  LEFT JOIN irrigation_batches b 
    ON b.schedule_id = s.schedule_id AND b.batch_order = 1
  LEFT JOIN farmers f 
    ON f.farmer_id = b.farmer_id
  WHERE s.schedule_date >= ?
    AND s.service_area_id IN ({$focusAreaInSql})
  ORDER BY s.schedule_date ASC, s.start_time ASC
  LIMIT 8
");
$stmt->bind_param("s", $today);
$stmt->execute();
$upcomingRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();


/**
 * Pending requests table (for the second tab preview)
 */
$pendingRows = [];
$stmt = $conn->prepare("
  SELECT r.request_id,
         COALESCE(f.farmer_name,'-') farmer_name,
         r.request_type,
         LEFT(COALESCE(r.request_details,''), 60) short_details,
         r.created_at,
         r.status
  FROM farmer_requests r
  LEFT JOIN farmers f ON f.farmer_id=r.farmer_id
  WHERE r.status='Pending'
    AND f.service_area_id IN ({$focusAreaInSql})
  ORDER BY r.created_at DESC
  LIMIT 8
");
$stmt->execute();
$pendingRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/**
 * Recent activity (system_logs + users)
 * FIXED: your users table has "user_id" and "fullname", not "id" or "full_name"
 */
$activity = [];
$stmt = $conn->prepare("
  SELECT
    COALESCE(u.fullname, u.username, 'System') AS actor,
    COALESCE(l.action, 'Activity') AS action,
    COALESCE(l.description, '') AS description,
    l.created_at
  FROM system_logs l
  LEFT JOIN users u ON u.user_id = l.user_id
  ORDER BY l.created_at DESC
  LIMIT 6
");
$stmt->execute();
$activity = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/**
 * Chart 1: Water distribution by canal/area (use upcoming schedules count per area)
 * We'll take top 4 areas by schedule count in next 30 days.
 */
$dist = [];
$stmt = $conn->prepare("
  SELECT sa.area_name, COUNT(*) c
  FROM irrigation_schedules s
  JOIN service_areas sa ON sa.service_area_id=s.service_area_id
  WHERE s.schedule_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    AND s.service_area_id IN ({$focusAreaInSql})
  GROUP BY sa.area_name
  ORDER BY c DESC
  LIMIT 4
");
$stmt->execute();
$dist = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$maxC = 1;
foreach ($dist as $d) $maxC = max($maxC, (int)$d['c']);

/**
 * Chart 2: Farmer utilization rate (simple proxy)
 * % of farmers that have at least 1 request OR schedule in last 30 days.
 */
$totalFarmers = 0;
$stmt = $conn->prepare("SELECT COUNT(*) c FROM farmers WHERE service_area_id IN ({$focusAreaInSql})");
$stmt->execute();
$totalFarmers = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
$stmt->close();

$activeFarmers30 = 0;
$stmt = $conn->prepare("
  SELECT COUNT(DISTINCT f.farmer_id) c
  FROM farmers f
  LEFT JOIN farmer_requests r
    ON r.farmer_id=f.farmer_id AND DATE(r.created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
  LEFT JOIN irrigation_batches b
    ON b.farmer_id=f.farmer_id
  LEFT JOIN irrigation_schedules s
    ON s.schedule_id=b.schedule_id AND s.schedule_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
  WHERE f.service_area_id IN ({$focusAreaInSql})
    AND (r.request_id IS NOT NULL OR s.schedule_id IS NOT NULL)
");
$stmt->execute();
$activeFarmers30 = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
$stmt->close();

$util = ($totalFarmers > 0) ? (int)round(($activeFarmers30 / $totalFarmers) * 100) : 0;
$util = max(0, min(100, $util));

/**
 * Action-required strip metrics
 */
$dueTasks = 0;
$stmt = $conn->prepare("
  SELECT COUNT(*) c
  FROM tasks t
  JOIN irrigation_schedules s ON s.schedule_id = t.schedule_id
  WHERE t.status = 'Due'
    AND s.schedule_date <= ?
    AND s.service_area_id IN ({$focusAreaInSql})
");
$stmt->bind_param("s", $today);
$stmt->execute();
$dueTasks = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
$stmt->close();

$unassignedTasks = 0;
$stmt = $conn->prepare("
  SELECT COUNT(*) c
  FROM tasks t
  JOIN irrigation_schedules s ON s.schedule_id = t.schedule_id
  WHERE (t.assigned_user_id IS NULL OR t.assigned_user_id = 0)
    AND t.status IN ('Due', 'In Progress')
    AND s.schedule_date >= ?
    AND s.service_area_id IN ({$focusAreaInSql})
");
$stmt->bind_param("s", $today);
$stmt->execute();
$unassignedTasks = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
$stmt->close();

include __DIR__ . "/../includes/head.php";
?>

<div class="relative flex min-h-screen w-full">
  <?php include __DIR__ . "/../includes/sidebar.php"; ?>

  <div class="flex-1 flex flex-col">
    <?php include __DIR__ . "/../includes/topbar.php"; ?>

    <main class="flex-1 p-6 lg:p-8">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left & Center Column -->
        <div class="lg:col-span-2 flex flex-col gap-6">

          <!-- PageHeading -->
          <div>
            <h1 class="text-text-light dark:text-text-dark text-4xl font-black leading-tight tracking-tight">
              Administrator Dashboard
            </h1>
            <p class="text-gray-500 dark:text-gray-400 text-base font-normal leading-normal mt-2">
              Welcome, <?= h($userName) ?>! Here is a real-time summary of irrigation activities.
            </p>
          </div>

          <!-- Action Required Strip -->
          <div class="rounded-xl border border-border-light dark:border-border-dark bg-gradient-to-r from-amber-50 to-green-50 dark:from-gray-900 dark:to-gray-800 p-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
              <div>
                <p class="text-sm font-black text-text-light dark:text-text-dark">Action Required</p>
                <p class="text-xs text-gray-600 dark:text-gray-300">Pending requests and due field tasks needing follow-up.</p>
              </div>
              <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">
                <?= (int)($pendingRequests + $dueTasks + $unassignedTasks) ?> open
              </span>
            </div>

            <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
              <a href="<?= route('requests', ['request_stage' => 'Pending']) ?>" class="rounded-lg bg-white dark:bg-gray-900 border border-border-light dark:border-border-dark px-3 py-2 flex items-center justify-between hover:border-warning transition-colors">
                <span class="text-xs font-semibold text-gray-600 dark:text-gray-300">Pending Requests</span>
                <span class="text-sm font-black text-warning"><?= (int)$pendingRequests ?></span>
              </a>

              <a href="<?= route('tasks', ['status' => 'Due']) ?>" class="rounded-lg bg-white dark:bg-gray-900 border border-border-light dark:border-border-dark px-3 py-2 flex items-center justify-between hover:border-warning transition-colors">
                <span class="text-xs font-semibold text-gray-600 dark:text-gray-300">Due Tasks</span>
                <span class="text-sm font-black text-amber-600 dark:text-amber-300"><?= (int)$dueTasks ?></span>
              </a>

              <a href="<?= route('tasks') ?>" class="rounded-lg bg-white dark:bg-gray-900 border border-border-light dark:border-border-dark px-3 py-2 flex items-center justify-between hover:border-secondary transition-colors">
                <span class="text-xs font-semibold text-gray-600 dark:text-gray-300">Unassigned Tasks</span>
                <span class="text-sm font-black text-secondary"><?= (int)$unassignedTasks ?></span>
              </a>
            </div>
          </div>

          <!-- Stats -->
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
  <div class="flex flex-col gap-1 rounded-lg p-6 bg-white dark:bg-gray-900 border border-border-light dark:border-border-dark">
    <p class="text-text-light dark:text-text-dark text-sm font-medium leading-normal">
      Pending Water Requests
    </p>
    <p class="text-warning tracking-tight text-xl font-semibold leading-tight">
      <?= (int)$pendingRequests ?>
    </p>
  </div>

  <div class="flex flex-col gap-1 rounded-lg p-6 bg-white dark:bg-gray-900 border border-border-light dark:border-border-dark">
    <p class="text-text-light dark:text-text-dark text-sm font-medium leading-normal">
      Active Irrigations
    </p>
    <p class="text-secondary tracking-tight text-xl font-semibold leading-tight">
      <?= (int)$activeIrrigations ?>
    </p>
  </div>

  <div class="flex flex-col gap-1 rounded-lg p-6 bg-white dark:bg-gray-900 border border-border-light dark:border-border-dark">
    <p class="text-text-light dark:text-text-dark text-sm font-medium leading-normal">
      Upcoming Schedules
    </p>
    <p class="text-primary tracking-tight text-xl font-semibold leading-tight">
      <?= (int)$upcomingSchedules ?>
    </p>
  </div>

  <div class="flex flex-col gap-1 rounded-lg p-6 bg-white dark:bg-gray-900 border border-border-light dark:border-border-dark">
    <p class="text-text-light dark:text-text-dark text-sm font-medium leading-normal">
      Farmers Served Today
    </p>
    <p class="text-text-light dark:text-text-dark tracking-tight text-xl font-semibold leading-tight">
      <?= (int)$farmersServedToday ?>
    </p>
  </div>
</div>


          <!-- Charts -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            <!-- Water Distribution by Canal -->
            <div class="flex flex-col gap-2 rounded-lg border border-border-light dark:border-border-dark p-6 bg-white dark:bg-gray-900">
              <p class="text-text-light dark:text-text-dark text-base font-medium leading-normal">Water Distribution by Canal</p>

              <div class="grid min-h-[220px] grid-flow-col gap-6 grid-rows-[1fr_auto] items-end justify-items-center px-3 pt-4">
                <?php if ($dist): ?>
                  <?php foreach($dist as $d): ?>
                    <?php $h = (int)round(((int)$d['c'] / $maxC) * 100); $h = max(10, min(100, $h)); ?>
                    <div class="bg-secondary/20 dark:bg-secondary/40 w-full rounded-t-sm" style="height: <?= $h ?>%;"></div>
                    <p class="text-gray-500 dark:text-gray-400 text-[13px] font-bold leading-normal"><?= h($d['area_name']) ?></p>
                  <?php endforeach; ?>
                <?php else: ?>
                  <div class="col-span-full text-gray-500 dark:text-gray-400 text-sm">No data yet.</div>
                <?php endif; ?>
              </div>
            </div>

            <!-- Utilization Rate -->
            <div class="flex flex-col gap-2 rounded-lg border border-border-light dark:border-border-dark p-6 bg-white dark:bg-gray-900">
              <p class="text-text-light dark:text-text-dark text-base font-medium leading-normal">Farmer Utilization Rate</p>

              <div class="flex-1 flex items-center justify-center min-h-[220px]">
                <div class="relative w-40 h-40">
                  <svg class="w-full h-full" viewBox="0 0 36 36">
                    <path class="stroke-current text-primary/20 dark:text-primary/30"
                          d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                          fill="none" stroke-width="3"></path>
                    <path class="stroke-current text-primary"
                          d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831"
                          fill="none" stroke-dasharray="<?= (int)$util ?>, 100" stroke-linecap="round" stroke-width="3"></path>
                  </svg>
                  <div class="absolute inset-0 flex flex-col items-center justify-center">
                    <span class="text-3xl font-bold text-text-light dark:text-text-dark"><?= (int)$util ?>%</span>
                    <span class="text-sm text-gray-500 dark:text-gray-400">Active (30d)</span>
                  </div>
                </div>
              </div>
            </div>

          </div>

          <!-- Tabs & Tables -->
          <div class="bg-white dark:bg-gray-900 rounded-lg border border-border-light dark:border-border-dark"
               x-data="{tab:'upcoming'}">
            <div class="border-b border-border-light dark:border-border-dark px-4 flex gap-8">
              <button type="button"
                      class="flex items-center justify-center py-3 border-b-[3px]"
                      :class="tab==='upcoming' ? 'border-primary text-primary' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-text-light dark:hover:text-text-dark'"
                      @click="tab='upcoming'">
                <p class="text-sm font-bold leading-normal tracking-wide">Upcoming Schedules</p>
              </button>

              <button type="button"
                      class="flex items-center justify-center py-3 border-b-[3px]"
                      :class="tab==='pending' ? 'border-primary text-primary' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-text-light dark:hover:text-text-dark'"
                      @click="tab='pending'">
                <p class="text-sm font-bold leading-normal tracking-wide">Pending Requests</p>
              </button>
            </div>

            <div class="p-4">
              <div class="overflow-x-auto" x-show="tab==='upcoming'">
                <table class="w-full text-left">
                  <thead>
                    <tr class="text-xs text-gray-500 dark:text-gray-400 uppercase">
                      <th class="p-3 font-semibold">Farmer Name</th>
                      <th class="p-3 font-semibold">Location</th>
                      <th class="p-3 font-semibold">Time</th>
                      <th class="p-3 font-semibold">Status</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-border-light dark:divide-border-dark">
                    <?php if ($upcomingRows): ?>
                      <?php foreach($upcomingRows as $r): ?>
                        <?php [$cls,$label]=badge($r['status']); ?>
                        <tr class="text-sm text-text-light dark:text-text-dark">
                          <td class="p-3"><?= h($r['farmer_name']) ?></td>
                          <td class="p-3"><?= h($r['location']) ?></td>
                          <td class="p-3"><?= h($r['time_display']) ?></td>
                          <td class="p-3"><span class="px-2 py-1 text-xs font-semibold rounded-full <?= $cls ?>"><?= h($label) ?></span></td>
                        </tr>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <tr><td class="p-3 text-gray-500 dark:text-gray-400" colspan="4">No upcoming schedules.</td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>

              <div class="overflow-x-auto" x-show="tab==='pending'" style="display:none;">
                <table class="w-full text-left">
                  <thead>
                    <tr class="text-xs text-gray-500 dark:text-gray-400 uppercase">
                      <th class="p-3 font-semibold">Farmer Name</th>
                      <th class="p-3 font-semibold">Type</th>
                      <th class="p-3 font-semibold">Details</th>
                      <th class="p-3 font-semibold">Status</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-border-light dark:divide-border-dark">
                    <?php if ($pendingRows): ?>
                      <?php foreach($pendingRows as $r): ?>
                        <?php [$cls,$label]=badge($r['status']); ?>
                        <tr class="text-sm text-text-light dark:text-text-dark">
                          <td class="p-3"><?= h($r['farmer_name']) ?></td>
                          <td class="p-3"><?= h($r['request_type']) ?></td>
                          <td class="p-3"><?= h($r['short_details']) ?>...</td>
                          <td class="p-3"><span class="px-2 py-1 text-xs font-semibold rounded-full <?= $cls ?>"><?= h($label) ?></span></td>
                        </tr>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <tr><td class="p-3 text-gray-500 dark:text-gray-400" colspan="4">No pending requests.</td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>

            </div>
          </div>

        </div>

        <!-- Right Column (Recent Activity) -->
        <div class="lg:col-span-1 flex flex-col gap-6">
          <div class="bg-white dark:bg-gray-900 rounded-lg border border-border-light dark:border-border-dark p-6 h-full">
            <h3 class="text-lg font-bold text-text-light dark:text-text-dark">Recent Activity</h3>

            <div class="mt-4 space-y-4">
              <?php if ($activity): ?>
                <?php foreach($activity as $a): ?>
                  <div class="flex gap-3">
                    <div class="flex-shrink-0 mt-1">
                      <span class="flex items-center justify-center size-8 rounded-full bg-primary/20">
                        <span class="material-symbols-outlined text-primary text-base">history</span>
                      </span>
                    </div>
                    <div>
                      <p class="text-sm font-medium text-text-light dark:text-text-dark">
                        <?= h($a['actor']) ?> - <span class="font-bold"><?= h($a['action']) ?></span>
                      </p>
                      <?php if (!empty($a['description'])): ?>
                        <p class="text-xs text-gray-500 dark:text-gray-400"><?= h($a['description']) ?></p>
                      <?php endif; ?>
                      <p class="text-xs text-gray-500 dark:text-gray-400"><?= h(time_ago($a['created_at'])) ?></p>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <p class="text-sm text-gray-500 dark:text-gray-400">No activity yet.</p>
              <?php endif; ?>
            </div>

          </div>
        </div>

      </div>
    </main>
  </div>
</div>

<!-- Alpine for tab switching (lightweight) -->
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

<?php include __DIR__ . "/../includes/footer.php"; ?>
<?php
$conn->close();
