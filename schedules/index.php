<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_roles(['Administrator','Operations Staff','IMO']);
require_once __DIR__ . '/../includes/config.php';

$active   = 'schedules';
$topTitle = 'Schedules';

$q = trim($_GET['q'] ?? '');
$dateFrom = trim($_GET['from'] ?? '');
$dateTo   = trim($_GET['to'] ?? '');
$statusF  = trim($_GET['status'] ?? '');
$perPage = 10;
$pageNum = max(1, (int)($_GET['p'] ?? 1));

$todayYmd = date('Y-m-d');
$next7Ymd = date('Y-m-d', strtotime('+7 days'));
$monthStartYmd = date('Y-m-01');
$monthEndYmd = date('Y-m-t');

$isTodayRange = ($dateFrom === $todayYmd && $dateTo === $todayYmd);
$isNext7Range = ($dateFrom === $todayYmd && $dateTo === $next7Ymd);
$isMonthRange = ($dateFrom === $monthStartYmd && $dateTo === $monthEndYmd);

$params = [];
$types  = '';
$where  = "WHERE 1=1";

$focusServiceAreaIds = [];
$focusStmt = $conn->prepare("
  SELECT service_area_id, area_name, municipality, province
  FROM service_areas
");
$focusStmt->execute();
$focusAreas = $focusStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$focusStmt->close();
foreach ($focusAreas as $focusArea) {
  if (is_focus_service_area($focusArea['area_name'] ?? null, $focusArea['municipality'] ?? null, $focusArea['province'] ?? null)) {
    $focusServiceAreaIds[] = (int)($focusArea['service_area_id'] ?? 0);
  }
}
$focusServiceAreaIds = array_values(array_unique(array_filter($focusServiceAreaIds, static fn($id) => $id > 0)));
if ($focusServiceAreaIds) {
  $where .= " AND s.service_area_id IN (" . implode(',', array_fill(0, count($focusServiceAreaIds), '?')) . ")";
  $types .= str_repeat('i', count($focusServiceAreaIds));
  foreach ($focusServiceAreaIds as $focusId) {
    $params[] = $focusId;
  }
} else {
  $where .= " AND 1=0";
}

if ($q !== '') {
  $where .= " AND (sa.area_name LIKE ? OR sa.municipality LIKE ? OR sa.province LIKE ?)";
  $like = "%{$q}%";
  $params[] = $like; $params[] = $like; $params[] = $like;
  $types .= "sss";
}

if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
  $where .= " AND s.schedule_date >= ?";
  $params[] = $dateFrom;
  $types .= "s";
}

if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
  $where .= " AND s.schedule_date <= ?";
  $params[] = $dateTo;
  $types .= "s";
}

if ($statusF !== '' && in_array($statusF, ['Active','Completed','Cancelled'], true)) {
  $where .= " AND s.status = ?";
  $params[] = $statusF;
  $types .= "s";
}

$total = 0;
$countSql = "
  SELECT COUNT(*) AS total
  FROM irrigation_schedules s
  LEFT JOIN service_areas sa ON sa.service_area_id = s.service_area_id
  $where
";
$stmt = $conn->prepare($countSql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$total = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
$stmt->close();

$totalPages = max(1, (int)ceil($total / $perPage));
if ($pageNum > $totalPages) {
  $pageNum = $totalPages;
}
$offset = ($pageNum - 1) * $perPage;

$sql = "
  SELECT s.schedule_id, s.schedule_date, s.start_time, s.end_time, s.status,
         sa.area_name, sa.municipality, sa.province
  FROM irrigation_schedules s
  LEFT JOIN service_areas sa ON sa.service_area_id = s.service_area_id
  $where
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
      <div class="flex flex-col gap-4">

        <div class="sticky top-2 z-20 rounded-xl border border-border-light dark:border-border-dark bg-background-light/95 dark:bg-background-dark/95 backdrop-blur px-3 py-3 flex flex-col lg:flex-row lg:items-end lg:justify-between gap-3">
          <form id="scheduleFilterForm" class="flex flex-col sm:flex-row gap-2 flex-1 min-w-0" method="GET" action="<?= route('schedules') ?>">
            <input type="hidden" name="page" value="schedules">
            <input type="hidden" name="p" value="<?= (int)$pageNum ?>">

            <div class="relative w-full sm:flex-1 sm:min-w-[18rem] lg:min-w-[24rem]">
              <span class="material-symbols-outlined pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-[18px] leading-none">search</span>
              <input id="scheduleSearchInput" name="q" value="<?= h($q) ?>" placeholder="Search area / municipality / province"
                     class="w-full pl-9 pr-4 rounded-full border-border-light dark:border-border-dark bg-card-light dark:bg-card-dark">
            </div>

            <input id="scheduleFromInput" type="date" name="from" value="<?= h($dateFrom) ?>"
                   class="w-full sm:w-40 rounded-DEFAULT border-border-light dark:border-border-dark bg-card-light dark:bg-card-dark">

            <input id="scheduleToInput" type="date" name="to" value="<?= h($dateTo) ?>"
                   class="w-full sm:w-40 rounded-DEFAULT border-border-light dark:border-border-dark bg-card-light dark:bg-card-dark">

            <select id="scheduleStatusInput" name="status"
                    class="w-full sm:w-36 rounded-full px-3 border-border-light dark:border-border-dark bg-card-light dark:bg-card-dark">
              <option value="">All Status</option>
              <?php foreach (['Active','Completed','Cancelled'] as $s): ?>
                <option value="<?= $s ?>" <?= $statusF===$s?'selected':'' ?>><?= $s ?></option>
              <?php endforeach; ?>
            </select>

            <a
              class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700 text-text-light dark:text-text-dark inline-flex items-center justify-center shrink-0"
              href="<?= route('schedules') ?>"
              title="Reset"
              aria-label="Reset filters"
            >
              <span class="material-symbols-outlined text-[20px] leading-none">restart_alt</span>
            </a>
          </form>

          <div class="flex gap-2 shrink-0">
            <a
              class="w-10 h-10 rounded-full bg-primary text-white inline-flex items-center justify-center shrink-0"
              href="<?= base_path('schedules/create.php') ?>"
              title="Add Schedule"
              aria-label="Add Schedule"
            >
              <span class="material-symbols-outlined text-[20px] leading-none">add</span>
            </a>

            <?php if (is_file(__DIR__ . '/print.php')): ?>
              <a
                class="w-10 h-10 rounded-full bg-secondary text-white inline-flex items-center justify-center shrink-0"
                href="<?= base_path('schedules/print.php') ?>"
                target="_blank"
                title="Print View"
                aria-label="Print View"
              >
                <span class="material-symbols-outlined text-[20px] leading-none">print</span>
              </a>
            <?php endif; ?>
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">Quick range:</span>
          <button
            type="button"
            class="js-schedule-range px-3 py-1.5 rounded-full text-xs font-semibold border border-border-light dark:border-border-dark <?= $isTodayRange ? 'bg-secondary text-white' : 'bg-card-light dark:bg-card-dark text-text-light dark:text-text-dark' ?>"
            data-from="<?= h($todayYmd) ?>"
            data-to="<?= h($todayYmd) ?>"
          >
            Today
          </button>
          <button
            type="button"
            class="js-schedule-range px-3 py-1.5 rounded-full text-xs font-semibold border border-border-light dark:border-border-dark <?= $isNext7Range ? 'bg-secondary text-white' : 'bg-card-light dark:bg-card-dark text-text-light dark:text-text-dark' ?>"
            data-from="<?= h($todayYmd) ?>"
            data-to="<?= h($next7Ymd) ?>"
          >
            Next 7 Days
          </button>
          <button
            type="button"
            class="js-schedule-range px-3 py-1.5 rounded-full text-xs font-semibold border border-border-light dark:border-border-dark <?= $isMonthRange ? 'bg-secondary text-white' : 'bg-card-light dark:bg-card-dark text-text-light dark:text-text-dark' ?>"
            data-from="<?= h($monthStartYmd) ?>"
            data-to="<?= h($monthEndYmd) ?>"
          >
            This Month
          </button>
        </div>

        <div id="schedulesResults">
        <div class="md:hidden space-y-3">
          <?php foreach ($rows as $r): ?>
            <?php [$cls,$label] = badge($r['status']); ?>
            <div class="rounded-xl border border-border-light dark:border-border-dark bg-card-light dark:bg-card-dark p-4">
              <div class="flex items-start justify-between gap-2">
                <div>
                  <p class="text-sm font-black text-text-light dark:text-text-dark"><?= h($r['schedule_date']) ?></p>
                  <p class="text-xs text-gray-500 dark:text-gray-400"><?= h($r['start_time']) ?> - <?= h($r['end_time']) ?></p>
                </div>
                <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $cls ?>"><?= h($label) ?></span>
              </div>
              <div class="mt-3">
                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Area</p>
                <p class="text-sm font-semibold text-text-light dark:text-text-dark">
                  <?= h($r['area_name'] ?? '-') ?><?= !empty($r['municipality']) ? ", ".h($r['municipality']) : "" ?>
                </p>
              </div>
              <div class="mt-3 flex items-center gap-2">
                <a
                  class="w-9 h-9 rounded-full border border-border-light dark:border-border-dark inline-flex items-center justify-center text-secondary"
                  href="<?= base_path('schedules/view.php?id=' . (int)$r['schedule_id']) ?>"
                  title="View"
                  aria-label="View schedule"
                >
                  <span class="material-symbols-outlined text-[18px] leading-none">visibility</span>
                </a>
                <a
                  class="w-9 h-9 rounded-full border border-border-light dark:border-border-dark inline-flex items-center justify-center text-secondary"
                  href="<?= base_path('schedules/edit.php?id=' . (int)$r['schedule_id']) ?>"
                  title="Edit"
                  aria-label="Edit schedule"
                >
                  <span class="material-symbols-outlined text-[18px] leading-none">edit</span>
                </a>
              </div>
            </div>
          <?php endforeach; ?>
          <?php if (!$rows): ?>
            <div class="rounded-xl border border-border-light dark:border-border-dark bg-card-light dark:bg-card-dark p-4 text-sm text-gray-500 dark:text-gray-400">No schedules found.</div>
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
                  <th class="p-3">Status</th>
                  <th class="p-3">Actions</th>
                </tr>
              </thead>

              <tbody class="divide-y divide-border-light dark:divide-border-dark">
                <?php foreach ($rows as $r): ?>
                  <?php [$cls,$label] = badge($r['status']); ?>
                  <tr class="text-sm text-text-light dark:text-text-dark">
                    <td class="p-3"><?= h($r['schedule_date']) ?></td>
                    <td class="p-3"><?= h($r['start_time']) ?> - <?= h($r['end_time']) ?></td>
                    <td class="p-3">
                      <?= h($r['area_name'] ?? '-') ?><?= !empty($r['municipality']) ? ", ".h($r['municipality']) : "" ?>
                    </td>
                    <td class="p-3">
                      <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $cls ?>"><?= h($label) ?></span>
                    </td>
                    <td class="p-3">
                      <div class="flex items-center gap-2">
                        <a
                          class="p-1.5 rounded-DEFAULT hover:bg-gray-100 dark:hover:bg-gray-800 text-secondary inline-flex items-center justify-center"
                          href="<?= base_path('schedules/view.php?id=' . (int)$r['schedule_id']) ?>"
                          title="View"
                          aria-label="View schedule"
                        >
                          <span class="material-symbols-outlined text-[20px] leading-none">visibility</span>
                        </a>
                        <a
                          class="p-1.5 rounded-DEFAULT hover:bg-gray-100 dark:hover:bg-gray-800 text-secondary inline-flex items-center justify-center"
                          href="<?= base_path('schedules/edit.php?id=' . (int)$r['schedule_id']) ?>"
                          title="Edit"
                          aria-label="Edit schedule"
                        >
                          <span class="material-symbols-outlined text-[20px] leading-none">edit</span>
                        </a>
                      </div>
                    </td>

                  </tr>
                <?php endforeach; ?>

                <?php if (!$rows): ?>
                  <tr><td class="p-3 text-gray-500 dark:text-gray-400" colspan="5">No schedules found.</td></tr>
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
          $baseParams = ['page' => 'schedules', 'q' => $q, 'from' => $dateFrom, 'to' => $dateTo, 'status' => $statusF];
        ?>
        <div class="mt-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-sm text-gray-500 dark:text-gray-400">
          <div>
            Showing <?= (int)$showFrom ?>-<?= (int)$showTo ?> of <?= (int)$total ?>
          </div>
          <div class="flex items-center gap-2">
            <?php if ($pageNum > 1): ?>
              <a
                href="<?= route('schedules', array_merge($baseParams, ['p' => $prevPage])) ?>"
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
                href="<?= route('schedules', array_merge($baseParams, ['p' => $nextPage])) ?>"
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

      </div>
    </main>
  </div>
</div>
<script>
(() => {
  const form = document.getElementById('scheduleFilterForm');
  const searchInput = document.getElementById('scheduleSearchInput');
  const fromInput = document.getElementById('scheduleFromInput');
  const toInput = document.getElementById('scheduleToInput');
  const statusInput = document.getElementById('scheduleStatusInput');
  const quickRangeButtons = Array.from(document.querySelectorAll('.js-schedule-range'));
  if (!form || !searchInput || !fromInput || !toInput || !statusInput) return;

  const resultsContainerId = 'schedulesResults';
  let timerId = null;
  let activeRequest = null;

  const buildUrl = (params) => {
    const url = new URL(form.action, window.location.origin);
    url.search = params.toString();
    return url;
  };

  const getParamsFromForm = (page = 1) => {
    const params = new URLSearchParams(new FormData(form));
    params.set('page', 'schedules');
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
        if (!response.ok) throw new Error('Schedule filter request failed');
        return response.text();
      })
      .then((html) => {
        if (!updateFromResponse(html)) {
          window.location.href = url.pathname + url.search;
          return;
        }
        window.history.replaceState({}, '', url.pathname + url.search);
      })
      .catch((err) => {
        if (err && err.name === 'AbortError') return;
        window.location.href = url.pathname + url.search;
      });
  };

  const queueSearch = () => {
    if (timerId !== null) window.clearTimeout(timerId);
    timerId = window.setTimeout(() => {
      loadResults(getParamsFromForm(1));
    }, 350);
  };

  const setQuickRangeActive = () => {
    quickRangeButtons.forEach((btn) => {
      const matches = btn.dataset.from === fromInput.value && btn.dataset.to === toInput.value;
      btn.classList.toggle('bg-secondary', matches);
      btn.classList.toggle('text-white', matches);
      btn.classList.toggle('bg-card-light', !matches);
      btn.classList.toggle('dark:bg-card-dark', !matches);
      btn.classList.toggle('text-text-light', !matches);
      btn.classList.toggle('dark:text-text-dark', !matches);
    });
  };

  searchInput.addEventListener('input', queueSearch);
  fromInput.addEventListener('change', () => {
    setQuickRangeActive();
    loadResults(getParamsFromForm(1));
  });
  toInput.addEventListener('change', () => {
    setQuickRangeActive();
    loadResults(getParamsFromForm(1));
  });
  statusInput.addEventListener('change', () => loadResults(getParamsFromForm(1)));
  form.addEventListener('submit', (event) => {
    event.preventDefault();
    loadResults(getParamsFromForm(1));
  });

  quickRangeButtons.forEach((btn) => {
    btn.addEventListener('click', () => {
      fromInput.value = btn.dataset.from ?? '';
      toInput.value = btn.dataset.to ?? '';
      setQuickRangeActive();
      loadResults(getParamsFromForm(1));
    });
  });

  const results = document.getElementById(resultsContainerId);
  if (results) {
    results.addEventListener('click', (event) => {
      const pageLink = event.target.closest('a[data-page]');
      if (!pageLink) return;
      event.preventDefault();
      const nextPage = parseInt(pageLink.getAttribute('data-page') || '1', 10);
      if (!Number.isFinite(nextPage) || nextPage < 1) return;
      loadResults(getParamsFromForm(nextPage));
    });
  }

  setQuickRangeActive();
})();
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
