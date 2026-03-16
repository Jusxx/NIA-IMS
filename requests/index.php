<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_roles(['Administrator','Operations Staff','Irrigation Technician','IMO','Monitoring','SWRFT','WRFO Gatekeeper','WRFO Scheduler']);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/sms.php';

$active   = 'requests';
$topTitle = 'Requests';

$meRole = role();
$requestProcessors = ['Administrator','Irrigation Association','SWRFT'];
$canUpdate = in_array($meRole, $requestProcessors, true);

function request_stage_flow(): array {
  return ['Pending','On Process','Approved','Scheduled','Assigned','In Progress','Completed'];
}

function all_request_stages(): array {
  return array_merge(request_stage_flow(), ['Rejected']);
}

function normalize_request_stage(string $stage): string {
  $stage = trim($stage);
  return in_array($stage, all_request_stages(), true) ? $stage : 'Pending';
}

function allowed_stage_targets(string $currentStage): array {
  $current = normalize_request_stage($currentStage);
  $flow = request_stage_flow();

  // Rejected is terminal.
  if ($current === 'Rejected') {
    return ['Rejected'];
  }

  $idx = array_search($current, $flow, true);
  if ($idx === false) {
    return ['Pending', 'Rejected'];
  }

  // Forward-only inside the main flow.
  $targets = array_slice($flow, $idx);

  // Rejection is only allowed before completion.
  if ($current !== 'Completed') {
    $targets[] = 'Rejected';
  }

  return $targets;
}

function request_status_from_stage(string $stage): string {
  $s = normalize_request_stage($stage);
  if ($s === 'Pending') return 'Pending';
  if ($s === 'On Process') return 'On Process';
  if ($s === 'Rejected') return 'Rejected';
  if ($s === 'Completed') return 'Completed';
  // Approved/Scheduled/Assigned/In Progress all keep Approved status.
  return 'Approved';
}

$stageOptions = all_request_stages();

/**
 * Filters (GET)
 */
$q       = trim($_GET['q'] ?? '');
$fStage  = trim($_GET['request_stage'] ?? '');   // ✅ stage filter
$fType   = trim($_GET['type'] ?? '');
$limit   = 5;
$page    = max(1, (int)($_GET['p'] ?? 1));
$offset  = ($page - 1) * $limit;

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

/**
 * Update request stage (POST)
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  require_roles($requestProcessors);

  $id    = (int)($_POST['request_id'] ?? 0);
  $stage = trim($_POST['request_stage'] ?? 'Pending');
  $note  = trim($_POST['note'] ?? '');

  $allowedStages = $stageOptions;

  if ($id > 0 && in_array($stage, $allowedStages, true)) {

    $actorId = (int)($_SESSION['user']['user_id'] ?? 0);

    $stmt = $conn->prepare("
      SELECT request_stage, status
      FROM farmer_requests
      WHERE request_id = ?
      LIMIT 1
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $current = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$current) {
      $_SESSION['flash'] = "Request #{$id} not found.";
    } else {
      $currentStage = normalize_request_stage((string)($current['request_stage'] ?: $current['status'] ?: 'Pending'));
      $allowedTargets = allowed_stage_targets($currentStage);

      if (!in_array($stage, $allowedTargets, true)) {
        $_SESSION['flash'] = "Invalid transition: {$currentStage} -> {$stage}. Status can only move forward.";
      } else {

        if ($stage === 'Approved') {
          $stmt = $conn->prepare("
            UPDATE farmer_requests
            SET request_stage=?, status='Approved',
                approved_by=?, approved_at=NOW()
            WHERE request_id=?
          ");
          $stmt->bind_param("sii", $stage, $actorId, $id);
          $stmt->execute();
          $stmt->close();

        } elseif ($stage === 'Rejected') {
          $stmt = $conn->prepare("
            UPDATE farmer_requests
            SET request_stage=?, status='Rejected'
            WHERE request_id=?
          ");
          $stmt->bind_param("si", $stage, $id);
          $stmt->execute();
          $stmt->close();

        } else {
          $newStatus = request_status_from_stage($stage);
          $stmt = $conn->prepare("
            UPDATE farmer_requests
            SET request_stage=?, status=?
            WHERE request_id=?
          ");
          $stmt->bind_param("ssi", $stage, $newStatus, $id);
          $stmt->execute();
          $stmt->close();
        }

        // Log action
        $action = "Request Stage Updated";
        $desc = "Request #{$id} set to {$stage}" . ($note ? " | Note: {$note}" : "");
        $stmt = $conn->prepare("INSERT INTO system_logs(user_id, action, description) VALUES(?,?,?)");
        $stmt->bind_param("iss", $actorId, $action, $desc);
        $stmt->execute();
        $stmt->close();

        // SMS notification for key stages
        $smsStages = ['On Process','Approved','Rejected','Scheduled'];
        if (in_array($stage, $smsStages, true)) {
          $stmt = $conn->prepare("
            SELECT r.farmer_id, f.farmer_name, f.phone, f.is_president
            FROM farmer_requests r
            LEFT JOIN farmers f ON f.farmer_id = r.farmer_id
            WHERE r.request_id = ?
            LIMIT 1
          ");
          $stmt->bind_param("i", $id);
          $stmt->execute();
          $info = $stmt->get_result()->fetch_assoc();
          $stmt->close();

          if ($info) {
            $farmerName = (string)($info['farmer_name'] ?? '');
            $extra = $note ? " Note: {$note}." : "";
            switch ($stage) {
              case 'On Process':
                $msg = sms_message_request_on_process($farmerName);
                $smsType = "Info";
                break;
              case 'Approved':
                $msg = sms_message_request_approved($farmerName);
                $smsType = "Approved";
                break;
              case 'Rejected':
                $msg = "NIA: Request #{$id} is declined." . $extra;
                $smsType = "Declined";
                break;
              case 'Scheduled':
                $msg = "NIA: Request #{$id} is scheduled." . $extra;
                $smsType = "Info";
                break;
              default:
                $msg = "NIA: Request #{$id} updated." . $extra;
                $smsType = "Info";
                break;
            }
            send_sms_and_log(
              $conn,
              (int)$info['farmer_id'],
              $info['phone'],
              $msg,
              $smsType,
              $id,
              ((int)$info['is_president'] === 1 ? 'President' : 'Farmer')
            );
          }
        }

        $_SESSION['flash'] = "Request #{$id} updated to {$stage}.";
      }
    }
  }

  header("Location: " . route('requests', [
    'q' => $q,
    'request_stage' => $fStage,
    'type' => $fType,
    'p' => $page
  ]));
  exit;
}

/**
 * WHERE clause for filters
 */
$where = [];
$params = [];
$types  = "";

if ($fStage !== '' && in_array($fStage, $stageOptions, true)) {
  $where[] = "COALESCE(NULLIF(r.request_stage, ''), r.status, 'Pending') = ?";
  $params[] = $fStage;
  $types .= "s";
}

if ($fType !== '' && in_array($fType, ['Irrigation Request','Schedule Adjustment','Water Allocation','Technical Concern'], true)) {
  $where[] = "r.request_type = ?";
  $params[] = $fType;
  $types .= "s";
}

if ($q !== '') {
  $like = "%{$q}%";
  $where[] = "(f.farmer_name LIKE ? OR r.request_details LIKE ?)";
  $params[] = $like;
  $params[] = $like;
  $types .= "ss";
}

$whereSql = $where ? ("WHERE " . implode(" AND ", $where)) : "";

/**
 * Total count
 */
$total = 0;
$sqlCount = "
  SELECT COUNT(*) c
  FROM farmer_requests r
  LEFT JOIN farmers f ON f.farmer_id = r.farmer_id
  {$whereSql}
";
$stmt = $conn->prepare($sqlCount);
if ($types !== '') $stmt->bind_param($types, ...$params);
$stmt->execute();
$total = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
$stmt->close();

$totalPages = max(1, (int)ceil($total / $limit));

/**
 * Fetch rows
 */
$rows = [];
$sql = "
  SELECT r.request_id, r.request_type, r.request_details, r.status, r.request_stage, r.created_at,
         COALESCE(f.farmer_name,'-') AS farmer_name
  FROM farmer_requests r
  LEFT JOIN farmers f ON f.farmer_id = r.farmer_id
  {$whereSql}
  ORDER BY r.created_at DESC
  LIMIT {$limit} OFFSET {$offset}
";
$stmt = $conn->prepare($sql);
if ($types !== '') $stmt->bind_param($types, ...$params);
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
        <div id="requestsFlashModalBg" class="fixed inset-0 z-50 bg-black/35"></div>
        <div id="requestsFlashModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div class="w-full max-w-md rounded-2xl bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark shadow-xl p-5">
            <div class="flex items-start gap-3">
              <span class="material-symbols-outlined text-green-600 dark:text-green-400 text-[24px] leading-none mt-0.5">check_circle</span>
              <div class="min-w-0">
                <h3 class="text-base font-black text-text-light dark:text-text-dark">Request Updated</h3>
                <p class="mt-1 text-sm text-text-light dark:text-text-dark break-words"><?= h($flash) ?></p>
              </div>
            </div>
            <div class="mt-5 flex justify-end">
              <button
                type="button"
                id="requestsFlashOk"
                class="px-4 py-2 rounded-full bg-primary text-white font-semibold"
              >
                OK
              </button>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <!-- Filters -->
      <div class="sticky top-2 z-20 rounded-xl border border-border-light dark:border-border-dark bg-background-light/95 dark:bg-background-dark/95 backdrop-blur px-3 py-3 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
        <form id="requestsFilterForm" method="GET" action="<?= route('requests') ?>" class="flex flex-col sm:flex-row gap-2 flex-1 min-w-0">
          <input type="hidden" name="page" value="requests">

          <div class="relative w-full sm:flex-1 sm:min-w-[18rem] lg:min-w-[24rem]">
            <span class="material-symbols-outlined pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-[18px] leading-none">search</span>
            <input id="requestsSearchInput" name="q" value="<?= h($q) ?>" placeholder="Search farmer or details..."
                   class="w-full pl-9 pr-4 rounded-full border-border-light dark:border-border-dark bg-card-light dark:bg-card-dark">
          </div>

          <!-- ✅ Stage filter -->
          <select id="requestsStageInput" name="request_stage"
                  class="w-full sm:w-40 rounded-full px-3 border-border-light dark:border-border-dark bg-card-light dark:bg-card-dark">
            <option value="">All Stages</option>
            <?php foreach($stageOptions as $s): ?>
              <option value="<?= $s ?>" <?= $fStage === $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
          </select>

          <select id="requestsTypeInput" name="type" class="w-full sm:w-44 rounded-full px-3 border-border-light dark:border-border-dark bg-card-light dark:bg-card-dark">
            <option value="">All Types</option>
            <?php foreach (['Irrigation Request','Schedule Adjustment','Water Allocation','Technical Concern'] as $t): ?>
              <option value="<?= $t ?>" <?= $fType === $t ? 'selected' : '' ?>><?= $t ?></option>
            <?php endforeach; ?>
          </select>

          <a
            class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700 text-text-light dark:text-text-dark inline-flex items-center justify-center shrink-0"
            href="<?= route('requests') ?>"
            title="Reset"
            aria-label="Reset filters"
          >
            <span class="material-symbols-outlined text-[20px] leading-none">restart_alt</span>
          </a>
        </form>

        <div class="text-sm text-gray-500 dark:text-gray-400">
          Total: <span data-requests-total class="font-semibold"><?= (int)$total ?></span>
        </div>
      </div>

      <div id="requestsResults">
      <!-- Mobile cards -->
      <div class="mt-6 md:hidden space-y-3">
        <?php foreach ($rows as $r): ?>
          <?php
            $currentStage = normalize_request_stage((string)($r['request_stage'] ?: $r['status'] ?: 'Pending'));
            $nextOptions = allowed_stage_targets((string)$currentStage);
            [$cls, $label] = badge($currentStage);
          ?>
          <div class="rounded-xl border border-border-light dark:border-border-dark bg-card-light dark:bg-card-dark p-4">
            <div class="flex items-start justify-between gap-2">
              <div>
                <p class="text-sm font-black text-text-light dark:text-text-dark"><?= h($r['farmer_name']) ?></p>
                <p class="text-xs text-gray-500 dark:text-gray-400"><?= h(date('M d, Y h:i A', strtotime($r['created_at']))) ?></p>
              </div>
              <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $cls ?>"><?= h($label) ?></span>
            </div>

            <div class="mt-3 grid grid-cols-1 gap-2 text-sm">
              <div>
                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Type</p>
                <p class="font-semibold text-text-light dark:text-text-dark"><?= h($r['request_type']) ?></p>
              </div>
              <div>
                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Details</p>
                <p class="text-text-light dark:text-text-dark"><?= h($r['request_details']) ?></p>
              </div>
            </div>

            <div class="mt-4">
              <?php if ($canUpdate): ?>
                <form method="POST" class="grid grid-cols-1 gap-2">
                  <input type="hidden" name="request_id" value="<?= (int)$r['request_id'] ?>">

                  <select name="request_stage"
                          class="w-full rounded-full px-3 border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark text-sm font-semibold">
                    <?php foreach($nextOptions as $s): ?>
                      <option value="<?= $s ?>" <?= $currentStage === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                  </select>

                  <input type="text" name="note" placeholder="Optional note/reason"
                         class="w-full rounded-full border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark text-sm px-3 py-2">

                  <div class="flex flex-wrap gap-2">
                    <button class="px-3 py-2 rounded-full bg-secondary text-white font-semibold text-sm inline-flex items-center gap-1.5 justify-center">
                      <span class="material-symbols-outlined text-[16px] leading-none">sync</span>
                      <span>Update</span>
                    </button>

                    <?php if ($currentStage === 'Approved'): ?>
                      <a class="px-3 py-2 rounded-full bg-primary text-white font-bold text-sm inline-flex items-center gap-1.5 justify-center"
                         href="../schedules/create.php?request_id=<?= (int)$r['request_id'] ?>">
                        <span class="material-symbols-outlined text-[16px] leading-none">event</span>
                        <span>Create Schedule</span>
                      </a>
                    <?php endif; ?>
                  </div>
                </form>
              <?php else: ?>
                <span class="text-gray-500 text-sm">View only (Administrator and Irrigation Association approval required)</span>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
          <div class="rounded-xl border border-border-light dark:border-border-dark bg-card-light dark:bg-card-dark p-4 text-sm text-gray-500">No requests found.</div>
        <?php endif; ?>
      </div>

      <!-- Desktop table -->
      <div class="mt-6 hidden md:block bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead>
              <tr class="text-xs text-gray-500 dark:text-gray-400 uppercase">
                <th class="p-3">Farmer</th>
                <th class="p-3">Type</th>
                <th class="p-3">Details</th>
                <th class="p-3">Stage</th>
                <th class="p-3">Action</th>
              </tr>
            </thead>

            <tbody class="divide-y divide-border-light dark:divide-border-dark">
              <?php foreach ($rows as $r): ?>
                <?php
                  $currentStage = normalize_request_stage((string)($r['request_stage'] ?: $r['status'] ?: 'Pending'));
                  $nextOptions = allowed_stage_targets((string)$currentStage);
                  [$cls, $label] = badge($currentStage);
                ?>
                <tr class="text-sm text-text-light dark:text-text-dark">
                  <td class="p-3">
                    <div class="font-semibold"><?= h($r['farmer_name']) ?></div>
                    <div class="text-xs text-gray-500 dark:text-gray-400"><?= h(date('M d, Y h:i A', strtotime($r['created_at']))) ?></div>
                  </td>
                  <td class="p-3"><?= h($r['request_type']) ?></td>
                  <td class="p-3 max-w-xl">
                    <div class="truncate"><?= h($r['request_details']) ?></div>
                  </td>
                  <td class="p-3">
                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $cls ?>"><?= h($label) ?></span>
                  </td>

                  <td class="p-3">
                    <?php if ($canUpdate): ?>
                      <form method="POST" class="grid grid-cols-1 md:grid-cols-[11rem_minmax(14rem,1fr)_auto] lg:grid-cols-[11rem_minmax(15rem,1fr)_auto_auto] gap-2 items-center">
                        <input type="hidden" name="request_id" value="<?= (int)$r['request_id'] ?>">

                        <select name="request_stage"
                                class="w-full rounded-full px-3 border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark text-sm font-semibold">
                          <?php foreach($nextOptions as $s): ?>
                            <option value="<?= $s ?>" <?= $currentStage === $s ? 'selected' : '' ?>><?= $s ?></option>
                          <?php endforeach; ?>
                        </select>

                        <input type="text" name="note" placeholder="Optional note/reason"
                               class="w-full rounded-full border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark text-sm px-3 py-2">

                        <button class="w-full md:w-auto px-3 py-2 rounded-full bg-secondary text-white font-semibold text-sm inline-flex items-center gap-1.5 justify-center">
                          <span class="material-symbols-outlined text-[16px] leading-none">sync</span>
                          <span>Update</span>
                        </button>

                        <?php if ($currentStage === 'Approved'): ?>
                          <a class="w-full md:w-auto px-3 py-2 rounded-full bg-primary text-white font-bold text-sm inline-flex items-center gap-1.5 justify-center"
                             href="../schedules/create.php?request_id=<?= (int)$r['request_id'] ?>">
                            <span class="material-symbols-outlined text-[16px] leading-none">event</span>
                            <span>Create Schedule</span>
                          </a>
                        <?php endif; ?>
                      </form>
                    <?php else: ?>
                      <span class="text-gray-500 text-sm">View only (Admin approval required)</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>

              <?php if (!$rows): ?>
                <tr><td class="p-3 text-gray-500" colspan="5">No requests found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Pagination -->
      <div class="mt-4 flex items-center justify-between text-sm">
        <div class="text-gray-500 dark:text-gray-400">
          Page <?= (int)$page ?> of <?= (int)$totalPages ?>
        </div>

        <div class="flex gap-2">
          <?php
            $qsBase = [
              'page' => 'requests',
              'q' => $q,
              'request_stage' => $fStage,
              'type' => $fType,
            ];
          ?>
          <?php if ($page > 1): ?>
            <a
              data-requests-page-link="1"
              class="w-9 h-9 rounded-full bg-gray-200 dark:bg-gray-700 text-text-light dark:text-text-dark inline-flex items-center justify-center"
              href="<?= route('requests', array_merge($qsBase, ['p' => $page - 1])) ?>"
              title="Previous page"
              aria-label="Previous page"
            >
              <span class="material-symbols-outlined text-[20px] leading-none">chevron_left</span>
            </a>
          <?php endif; ?>

          <?php if ($page < $totalPages): ?>
            <a
              data-requests-page-link="1"
              class="w-9 h-9 rounded-full bg-gray-200 dark:bg-gray-700 text-text-light dark:text-text-dark inline-flex items-center justify-center"
              href="<?= route('requests', array_merge($qsBase, ['p' => $page + 1])) ?>"
              title="Next page"
              aria-label="Next page"
            >
              <span class="material-symbols-outlined text-[20px] leading-none">chevron_right</span>
            </a>

          <?php endif; ?>
        </div>
      </div>
      </div>

    </main>
  </div>
</div>

<script>
(() => {
  const flashBg = document.getElementById('requestsFlashModalBg');
  const flashModal = document.getElementById('requestsFlashModal');
  const flashOk = document.getElementById('requestsFlashOk');

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
  const form = document.getElementById('requestsFilterForm');
  const searchInput = document.getElementById('requestsSearchInput');
  const stageInput = document.getElementById('requestsStageInput');
  const typeInput = document.getElementById('requestsTypeInput');
  if (!form || !searchInput || !stageInput || !typeInput) return;

  const resultsContainerId = 'requestsResults';
  let timerId = null;
  let activeRequest = null;

  const buildUrl = (params) => {
    const url = new URL(form.action, window.location.origin);
    url.search = params.toString();
    return url;
  };

  const getParamsFromForm = (page = 1) => {
    const params = new URLSearchParams(new FormData(form));
    params.set('page', 'requests');
    params.set('p', String(page));
    return params;
  };

  const updateFromResponse = (html) => {
    const doc = new DOMParser().parseFromString(html, 'text/html');
    const nextResults = doc.getElementById(resultsContainerId);
    const currentResults = document.getElementById(resultsContainerId);
    if (!nextResults || !currentResults) return false;

    currentResults.innerHTML = nextResults.innerHTML;

    const nextTotal = doc.querySelector('[data-requests-total]');
    const currentTotal = document.querySelector('[data-requests-total]');
    if (nextTotal && currentTotal) {
      currentTotal.textContent = nextTotal.textContent;
    }

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
        if (!response.ok) throw new Error('Request filter failed');
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

  searchInput.addEventListener('input', () => {
    if (timerId !== null) window.clearTimeout(timerId);
    timerId = window.setTimeout(() => {
      loadResults(getParamsFromForm(1));
    }, 350);
  });

  stageInput.addEventListener('change', () => {
    loadResults(getParamsFromForm(1));
  });

  typeInput.addEventListener('change', () => {
    loadResults(getParamsFromForm(1));
  });

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    loadResults(getParamsFromForm(1));
  });

  document.addEventListener('click', (event) => {
    const link = event.target.closest('a[data-requests-page-link]');
    if (!link) return;

    event.preventDefault();
    const url = new URL(link.href, window.location.origin);
    searchInput.value = url.searchParams.get('q') ?? '';
    stageInput.value = url.searchParams.get('request_stage') ?? '';
    typeInput.value = url.searchParams.get('type') ?? '';
    loadResults(url.searchParams);
  });
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
