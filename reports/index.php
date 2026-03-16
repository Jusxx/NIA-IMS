<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_roles(['Administrator','Operations Staff','IMO','Monitoring']);
require_once __DIR__ . '/../includes/config.php';

$active='reports';
$topTitle='Reports';

$isValidDate = static function (?string $value): bool {
  return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$value);
};

$from = trim((string)($_GET['from'] ?? date('Y-m-01')));
$to   = trim((string)($_GET['to'] ?? date('Y-m-d')));
$allDates = (($_GET['all_dates'] ?? '0') === '1');

if (!$isValidDate($from)) $from = '';
if (!$isValidDate($to)) $to = '';
if ($allDates) {
  $from = '';
  $to = '';
}
$source = trim($_GET['source'] ?? 'all');
if (!in_array($source, ['all', 'water', 'system'], true)) {
  $source = 'all';
}
$perPage = 5;
$pageNum = max(1, (int)($_GET['p'] ?? 1));

$rows=[];
$waterDateParts = [];
$systemDateParts = [];
$bindParams = [];
$bindTypes = '';

if ($from !== '') {
  $waterDateParts[] = "DATE(w.released_at) >= ?";
  $bindParams[] = $from;
  $bindTypes .= 's';
}
if ($to !== '') {
  $waterDateParts[] = "DATE(w.released_at) <= ?";
  $bindParams[] = $to;
  $bindTypes .= 's';
}
if ($from !== '') {
  $systemDateParts[] = "DATE(s.created_at) >= ?";
  $bindParams[] = $from;
  $bindTypes .= 's';
}
if ($to !== '') {
  $systemDateParts[] = "DATE(s.created_at) <= ?";
  $bindParams[] = $to;
  $bindTypes .= 's';
}

$waterDateSql = $waterDateParts ? implode(' AND ', $waterDateParts) : '1=1';
$systemDateSql = $systemDateParts ? implode(' AND ', $systemDateParts) : '1=1';

$sql = "
  SELECT *
  FROM (
    SELECT
      'Water Release' AS source_label,
      w.log_id AS row_id,
      w.released_at AS event_at,
      sa.area_name,
      sa.municipality,
      sa.province,
      COALESCE(u.fullname,u.username,'-') AS actor_name,
      'Release Logged' AS action_label,
      COALESCE(w.remarks, '-') AS details
    FROM water_release_logs w
    LEFT JOIN service_areas sa ON sa.service_area_id = w.service_area_id
    LEFT JOIN users u ON u.user_id = w.released_by
    WHERE {$waterDateSql}

    UNION ALL

    SELECT
      'System Log' AS source_label,
      s.log_id AS row_id,
      s.created_at AS event_at,
      NULL AS area_name,
      NULL AS municipality,
      NULL AS province,
      COALESCE(u.fullname,u.username,'-') AS actor_name,
      COALESCE(s.action, '-') AS action_label,
      COALESCE(s.description, '-') AS details
    FROM system_logs s
    LEFT JOIN users u ON u.user_id = s.user_id
    WHERE {$systemDateSql}
  ) x
";

if ($source === 'water') {
  $sql .= " WHERE x.source_label = 'Water Release'";
} elseif ($source === 'system') {
  $sql .= " WHERE x.source_label = 'System Log'";
}

$sql .= " ORDER BY x.event_at DESC LIMIT 500";

$stmt=$conn->prepare($sql);
if ($bindTypes !== '') {
  $stmt->bind_param($bindTypes, ...$bindParams);
}
$stmt->execute();
$rows=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$rows = array_values(array_filter($rows, static function(array $row): bool {
  if (($row['source_label'] ?? '') !== 'Water Release') return true;
  return is_focus_service_area(
    $row['area_name'] ?? null,
    $row['municipality'] ?? null,
    $row['province'] ?? null
  );
}));

$total = count($rows);
$totalPages = max(1, (int)ceil($total / $perPage));
if ($pageNum > $totalPages) {
  $pageNum = $totalPages;
}
$offset = ($pageNum - 1) * $perPage;
$rows = array_slice($rows, $offset, $perPage);

include __DIR__ . '/../includes/head.php';
?>
<div class="flex min-h-screen w-full">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="flex-1 flex flex-col">
    <?php include __DIR__ . '/../includes/topbar.php'; ?>
    <main class="p-6 lg:p-8 flex-1">
      <form class="flex flex-wrap gap-2 items-end" method="GET" action="<?= route('reports') ?>">
        <input type="hidden" name="page" value="reports">
        <input type="hidden" name="all_dates" value="0">
        <input type="hidden" name="p" value="1">
        <div>
          <label class="block text-sm font-medium">From</label>
          <input type="date" name="from" value="<?=h($from)?>" class="mt-1 rounded-full px-4 py-2 border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
        </div>
        <div>
          <label class="block text-sm font-medium">To</label>
          <input type="date" name="to" value="<?=h($to)?>" class="mt-1 rounded-full px-4 py-2 border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
        </div>
        <div>
          <label class="block text-sm font-medium">Source</label>
          <select name="source" class="mt-1 rounded-full px-4 py-2 border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
            <option value="all" <?= $source === 'all' ? 'selected' : '' ?>>All</option>
            <option value="water" <?= $source === 'water' ? 'selected' : '' ?>>Water Release</option>
            <option value="system" <?= $source === 'system' ? 'selected' : '' ?>>System Logs</option>
          </select>
        </div>
        <button
          class="w-10 h-10 rounded-full bg-secondary text-white inline-flex items-center justify-center"
          title="Filter"
          aria-label="Filter reports"
        >
          <span class="material-symbols-outlined text-[20px] leading-none">filter_alt</span>
        </button>
        <button
          name="all_dates"
          value="1"
          class="px-4 h-10 rounded-full bg-gray-200 dark:bg-gray-700 text-text-light dark:text-text-dark font-semibold"
          title="Show all dates"
          aria-label="Show all dates"
        >
          All Dates
        </button>
        <button
          type="button"
          onclick="window.print()"
          class="w-10 h-10 rounded-full bg-primary text-white inline-flex items-center justify-center"
          title="Print"
          aria-label="Print reports"
        >
          <span class="material-symbols-outlined text-[20px] leading-none">print</span>
        </button>
      </form>

      <div class="mt-6 bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead>
              <tr class="text-xs text-gray-500 dark:text-gray-400 uppercase">
                <th class="p-3">Date/Time</th>
                <th class="p-3">Source</th>
                <th class="p-3">Area</th>
                <th class="p-3">Actor</th>
                <th class="p-3">Action</th>
                <th class="p-3">Details</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-border-light dark:divide-border-dark">
              <?php foreach($rows as $r): ?>
                <tr class="text-sm text-text-light dark:text-text-dark">
                  <td class="p-3"><?=h($r['event_at'])?></td>
                  <td class="p-3"><?=h($r['source_label'])?></td>
                  <td class="p-3">
                    <?php if (($r['source_label'] ?? '') === 'Water Release'): ?>
                      <?=h($r['area_name'] ?? '-')?><?= !empty($r['municipality']) ? ", ".h($r['municipality']) : "" ?>
                    <?php else: ?>
                      -
                    <?php endif; ?>
                  </td>
                  <td class="p-3"><?=h($r['actor_name'] ?? '-')?></td>
                  <td class="p-3"><?=h($r['action_label'] ?? '-')?></td>
                  <td class="p-3"><?=h($r['details'] ?? '-')?></td>
                </tr>
              <?php endforeach; ?>
              <?php if(!$rows): ?><tr><td class="p-3 text-gray-500" colspan="6">No report data for selected range.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php
        $baseParams = ['page' => 'reports', 'source' => $source];
        if ($from !== '') $baseParams['from'] = $from;
        if ($to !== '') $baseParams['to'] = $to;
        if ($allDates) $baseParams['all_dates'] = '1';
        $showFrom = $total ? ($offset + 1) : 0;
        $showTo = min($offset + $perPage, $total);
        $prevPage = max(1, $pageNum - 1);
        $nextPage = min($totalPages, $pageNum + 1);
      ?>
      <div class="mt-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-sm text-gray-500 dark:text-gray-400">
        <div>
          Showing <?= (int)$showFrom ?>-<?= (int)$showTo ?> of <?= (int)$total ?>
        </div>
        <div class="flex items-center gap-2">
          <?php if ($pageNum > 1): ?>
            <a
              href="<?= route('reports', array_merge($baseParams, ['p' => $prevPage])) ?>"
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
              href="<?= route('reports', array_merge($baseParams, ['p' => $nextPage])) ?>"
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
    </main>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
