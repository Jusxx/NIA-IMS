<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_roles(['Administrator']);
require_once __DIR__ . '/../includes/config.php';

$active = 'sms_logs';
$topTitle = 'SMS Logs';

function is_valid_iso_date(?string $value): bool {
  return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$value);
}

function sms_status_badge(string $status): array {
  $s = strtolower(trim($status));
  return match ($s) {
    'sent' => ['bg-primary/20 text-primary', 'Sent'],
    'failed' => ['bg-red-200 text-red-700 dark:bg-red-500/20 dark:text-red-200', 'Failed'],
    'queued' => ['bg-warning/20 text-warning', 'Queued'],
    default => ['bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-200', ucfirst($s ?: 'Unknown')],
  };
}

function sms_type_badge(string $type): array {
  $t = strtolower(trim($type));
  return match ($t) {
    'approved' => ['bg-primary/20 text-primary', 'Approved'],
    'declined' => ['bg-red-200 text-red-700 dark:bg-red-500/20 dark:text-red-200', 'Declined'],
    default => ['bg-secondary/20 text-secondary', 'Info'],
  };
}

$q = trim($_GET['q'] ?? '');
$fStatus = trim($_GET['status'] ?? '');
$fType = trim($_GET['sms_type'] ?? '');
$fRecipientRole = trim($_GET['recipient_role'] ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');

$validStatuses = ['', 'Queued', 'Sent', 'Failed'];
$validTypes = ['', 'Approved', 'Declined', 'Info'];
$validRecipientRoles = ['', 'Farmer', 'President'];

if (!in_array($fStatus, $validStatuses, true)) $fStatus = '';
if (!in_array($fType, $validTypes, true)) $fType = '';
if (!in_array($fRecipientRole, $validRecipientRoles, true)) $fRecipientRole = '';
if ($dateFrom !== '' && !is_valid_iso_date($dateFrom)) $dateFrom = '';
if ($dateTo !== '' && !is_valid_iso_date($dateTo)) $dateTo = '';

$perPage = 5;
$page = max(1, (int)($_GET['p'] ?? 1));
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];
$types = '';

if ($q !== '') {
  $like = "%{$q}%";
  $where[] = "(f.farmer_name LIKE ? OR f.association_name LIKE ? OR s.phone LIKE ? OR s.message LIKE ? OR s.provider_message_id LIKE ?)";
  $params[] = $like;
  $params[] = $like;
  $params[] = $like;
  $params[] = $like;
  $params[] = $like;
  $types .= 'sssss';
}

if ($fStatus !== '') {
  $where[] = "s.status = ?";
  $params[] = $fStatus;
  $types .= 's';
}

if ($fType !== '') {
  $where[] = "s.sms_type = ?";
  $params[] = $fType;
  $types .= 's';
}

if ($fRecipientRole !== '') {
  $where[] = "s.recipient_role = ?";
  $params[] = $fRecipientRole;
  $types .= 's';
}

if ($dateFrom !== '') {
  $where[] = "DATE(s.sent_at) >= ?";
  $params[] = $dateFrom;
  $types .= 's';
}

if ($dateTo !== '') {
  $where[] = "DATE(s.sent_at) <= ?";
  $params[] = $dateTo;
  $types .= 's';
}

$whereSql = $where ? ("WHERE " . implode(" AND ", $where)) : "";

$countSql = "
  SELECT COUNT(*) AS c
  FROM sms_logs s
  LEFT JOIN farmers f ON f.farmer_id = s.farmer_id
  {$whereSql}
";
$stmt = $conn->prepare($countSql);
if ($types !== '') {
  $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
$stmt->close();

$totalPages = max(1, (int)ceil($total / $perPage));
if ($page > $totalPages) {
  $page = $totalPages;
  $offset = ($page - 1) * $perPage;
}

$rows = [];
$sql = "
  SELECT
    s.sms_id, s.farmer_id, s.request_id, s.phone, s.message, s.sms_type, s.provider,
    s.recipient_role, s.status, s.provider_message_id, s.error_message, s.payload_json, s.sent_at,
    f.farmer_name, f.association_name
  FROM sms_logs s
  LEFT JOIN farmers f ON f.farmer_id = s.farmer_id
  {$whereSql}
  ORDER BY s.sent_at DESC, s.sms_id DESC
  LIMIT ? OFFSET ?
";
$stmt = $conn->prepare($sql);
$rowParams = $params;
$rowTypes = $types . 'ii';
$rowParams[] = $perPage;
$rowParams[] = $offset;
$stmt->bind_param($rowTypes, ...$rowParams);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$baseParams = [
  'q' => $q,
  'status' => $fStatus,
  'sms_type' => $fType,
  'recipient_role' => $fRecipientRole,
  'date_from' => $dateFrom,
  'date_to' => $dateTo,
];
$baseParams = array_filter($baseParams, static fn($v) => $v !== '');

include __DIR__ . '/../includes/head.php';
?>

<div class="flex min-h-screen w-full">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="flex-1 flex flex-col">
    <?php include __DIR__ . '/../includes/topbar.php'; ?>

    <main class="p-6 lg:p-8 flex-1">
      <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
        <form id="smsLogsFilterForm" method="GET" action="<?= route('sms_logs') ?>" class="flex flex-col md:flex-row gap-2 flex-1 min-w-0">
          <input type="hidden" name="page" value="sms_logs">

          <div class="relative w-full md:flex-1 md:min-w-[18rem] lg:min-w-[24rem]">
            <span class="material-symbols-outlined pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-[18px] leading-none">search</span>
            <input
              id="smsLogsSearchInput"
              name="q"
              value="<?= h($q) ?>"
              placeholder="Search farmer, phone, message, provider ID..."
              class="w-full pl-9 pr-4 rounded-full border-border-light dark:border-border-dark bg-card-light dark:bg-card-dark"
            >
          </div>

          <select id="smsLogsStatusInput" name="status" class="w-full md:w-32 rounded-full px-3 border-border-light dark:border-border-dark bg-card-light dark:bg-card-dark">
            <?php foreach ($validStatuses as $status): ?>
              <?php $label = ($status === '') ? 'All Status' : $status; ?>
              <option value="<?= h($status) ?>" <?= $fStatus === $status ? 'selected' : '' ?>><?= h($label) ?></option>
            <?php endforeach; ?>
          </select>

          <select id="smsLogsTypeInput" name="sms_type" class="w-full md:w-28 rounded-full px-3 border-border-light dark:border-border-dark bg-card-light dark:bg-card-dark">
            <?php foreach ($validTypes as $type): ?>
              <?php $label = ($type === '') ? 'All Type' : $type; ?>
              <option value="<?= h($type) ?>" <?= $fType === $type ? 'selected' : '' ?>><?= h($label) ?></option>
            <?php endforeach; ?>
          </select>

          <select id="smsLogsRecipientInput" name="recipient_role" class="w-full md:w-36 rounded-full px-3 border-border-light dark:border-border-dark bg-card-light dark:bg-card-dark">
            <?php foreach ($validRecipientRoles as $recipientRole): ?>
              <?php $label = ($recipientRole === '') ? 'All Recipient' : $recipientRole; ?>
              <option value="<?= h($recipientRole) ?>" <?= $fRecipientRole === $recipientRole ? 'selected' : '' ?>><?= h($label) ?></option>
            <?php endforeach; ?>
          </select>

          <input id="smsLogsDateFromInput" type="date" name="date_from" value="<?= h($dateFrom) ?>"
                 class="w-full md:w-40 rounded-full px-3 border-border-light dark:border-border-dark bg-card-light dark:bg-card-dark">
          <input id="smsLogsDateToInput" type="date" name="date_to" value="<?= h($dateTo) ?>"
                 class="w-full md:w-40 rounded-full px-3 border-border-light dark:border-border-dark bg-card-light dark:bg-card-dark">

          <a
            class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700 text-text-light dark:text-text-dark inline-flex items-center justify-center shrink-0"
            href="<?= route('sms_logs') ?>"
            title="Reset"
            aria-label="Reset filters"
          >
            <span class="material-symbols-outlined text-[20px] leading-none">restart_alt</span>
          </a>
        </form>

        <div class="text-sm text-gray-500 dark:text-gray-400">
          Total: <span data-sms-total class="font-semibold text-text-light dark:text-text-dark"><?= (int)$total ?></span>
        </div>
      </div>

      <div id="smsLogsResults">
      <div class="mt-6 bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark rounded-lg overflow-hidden">
        <div class="p-4 space-y-3 md:hidden">
          <?php foreach ($rows as $r): ?>
            <?php
              [$statusClass, $statusLabel] = sms_status_badge((string)$r['status']);
              [$typeClass, $typeLabel] = sms_type_badge((string)$r['sms_type']);
              $farmerName = trim((string)($r['farmer_name'] ?? ''));
              $association = trim((string)($r['association_name'] ?? ''));
            ?>
            <div class="rounded-xl border border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark p-3 text-sm">
              <div class="flex items-start justify-between gap-2">
                <div>
                  <div class="font-semibold text-text-light dark:text-text-dark"><?= h($farmerName !== '' ? $farmerName : 'Unknown farmer') ?></div>
                  <?php if ($association !== ''): ?>
                    <div class="text-xs text-gray-500 dark:text-gray-400"><?= h($association) ?></div>
                  <?php endif; ?>
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400"><?= h((string)$r['sent_at']) ?></div>
              </div>
              <div class="mt-2 flex flex-wrap gap-2">
                <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $typeClass ?>"><?= h($typeLabel) ?></span>
                <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $statusClass ?>"><?= h($statusLabel) ?></span>
              </div>
              <div class="mt-2 text-xs text-gray-600 dark:text-gray-300">
                <div><span class="text-gray-500">Phone:</span> <?= h((string)($r['phone'] ?? '-')) ?></div>
                <div><span class="text-gray-500">Recipient:</span> <?= h((string)($r['recipient_role'] ?: '-')) ?></div>
                <div><span class="text-gray-500">Request:</span> <?= $r['request_id'] ? ('#' . (int)$r['request_id']) : '-' ?></div>
                <div class="break-all"><span class="text-gray-500">Provider ID:</span> <?= h((string)($r['provider_message_id'] ?: '-')) ?></div>
              </div>
              <div class="mt-2 text-sm break-words whitespace-pre-wrap"><?= h((string)$r['message']) ?></div>
              <?php if (!empty($r['error_message'])): ?>
                <div class="mt-2 text-xs text-red-700 dark:text-red-200 break-words whitespace-pre-wrap"><?= h((string)$r['error_message']) ?></div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
          <?php if (!$rows): ?>
            <div class="p-3 text-gray-500 dark:text-gray-400">No SMS logs found.</div>
          <?php endif; ?>
        </div>

        <div class="overflow-x-auto hidden md:block">
          <table class="w-full text-left">
            <thead>
              <tr class="text-xs text-gray-500 dark:text-gray-400 uppercase">
                <th class="p-3">Sent At</th>
                <th class="p-3">Farmer</th>
                <th class="p-3">Phone</th>
                <th class="p-3">Type</th>
                <th class="p-3">Status</th>
                <th class="p-3">Recipient</th>
                <th class="p-3">Request</th>
                <th class="p-3">Provider ID</th>
                <th class="p-3">Message</th>
                <th class="p-3">Error</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-border-light dark:divide-border-dark">
              <?php foreach ($rows as $r): ?>
                <?php
                  [$statusClass, $statusLabel] = sms_status_badge((string)$r['status']);
                  [$typeClass, $typeLabel] = sms_type_badge((string)$r['sms_type']);
                  $farmerName = trim((string)($r['farmer_name'] ?? ''));
                  $association = trim((string)($r['association_name'] ?? ''));
                ?>
                <tr class="text-sm text-text-light dark:text-text-dark align-top">
                  <td class="p-3 whitespace-nowrap"><?= h((string)$r['sent_at']) ?></td>
                  <td class="p-3">
                    <?php if ($farmerName !== ''): ?>
                      <div class="font-semibold"><?= h($farmerName) ?></div>
                    <?php else: ?>
                      <div class="font-semibold text-gray-500 dark:text-gray-400">Unknown farmer</div>
                    <?php endif; ?>
                    <?php if ($association !== ''): ?>
                      <div class="text-xs text-gray-500 dark:text-gray-400"><?= h($association) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($r['farmer_id'])): ?>
                      <div class="text-xs text-gray-500 dark:text-gray-400">ID: <?= (int)$r['farmer_id'] ?></div>
                    <?php endif; ?>
                  </td>
                  <td class="p-3 whitespace-nowrap"><?= h((string)($r['phone'] ?? '-')) ?></td>
                  <td class="p-3">
                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $typeClass ?>"><?= h($typeLabel) ?></span>
                  </td>
                  <td class="p-3">
                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $statusClass ?>"><?= h($statusLabel) ?></span>
                  </td>
                  <td class="p-3"><?= h((string)($r['recipient_role'] ?: '-')) ?></td>
                  <td class="p-3"><?= $r['request_id'] ? ('#' . (int)$r['request_id']) : '-' ?></td>
                  <td class="p-3 font-mono text-xs break-all"><?= h((string)($r['provider_message_id'] ?: '-')) ?></td>
                  <td class="p-3 max-w-sm">
                    <div class="whitespace-pre-wrap break-words"><?= h((string)$r['message']) ?></div>
                  </td>
                  <td class="p-3 max-w-xs">
                    <?php if (!empty($r['error_message'])): ?>
                      <div class="whitespace-pre-wrap break-words text-red-700 dark:text-red-200"><?= h((string)$r['error_message']) ?></div>
                    <?php else: ?>
                      <span class="text-gray-500 dark:text-gray-400">-</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$rows): ?>
                <tr><td class="p-3 text-gray-500 dark:text-gray-400" colspan="10">No SMS logs found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <div class="flex items-center justify-between gap-3 p-4 border-t border-border-light dark:border-border-dark text-sm">
          <div class="text-gray-500 dark:text-gray-400">
            Showing
            <span class="font-semibold text-text-light dark:text-text-dark"><?= $total ? ($offset + 1) : 0 ?></span>
            -
            <span class="font-semibold text-text-light dark:text-text-dark"><?= min($offset + $perPage, $total) ?></span>
            of
            <span class="font-semibold text-text-light dark:text-text-dark"><?= (int)$total ?></span>
          </div>

          <div class="flex items-center gap-2">
            <?php
              $prevDisabled = ($page <= 1);
              $nextDisabled = ($page >= $totalPages);
              $prevUrl = route('sms_logs', array_merge($baseParams, ['p' => max(1, $page - 1)]));
              $nextUrl = route('sms_logs', array_merge($baseParams, ['p' => min($totalPages, $page + 1)]));
            ?>

            <a data-sms-page-link="1" href="<?= $prevUrl ?>"
               class="w-9 h-9 rounded-full border border-border-light dark:border-border-dark inline-flex items-center justify-center <?= $prevDisabled ? 'opacity-50 pointer-events-none' : 'hover:bg-gray-100 dark:hover:bg-gray-800' ?>"
               title="Previous page"
               aria-label="Previous page">
              <span class="material-symbols-outlined text-[20px] leading-none">chevron_left</span>
            </a>

            <span class="text-gray-500 dark:text-gray-400">
              Page <span class="font-semibold text-text-light dark:text-text-dark"><?= (int)$page ?></span>
              / <span class="font-semibold text-text-light dark:text-text-dark"><?= (int)$totalPages ?></span>
            </span>

            <a data-sms-page-link="1" href="<?= $nextUrl ?>"
               class="w-9 h-9 rounded-full border border-border-light dark:border-border-dark inline-flex items-center justify-center <?= $nextDisabled ? 'opacity-50 pointer-events-none' : 'hover:bg-gray-100 dark:hover:bg-gray-800' ?>"
               title="Next page"
               aria-label="Next page">
              <span class="material-symbols-outlined text-[20px] leading-none">chevron_right</span>
            </a>
          </div>
        </div>
      </div>
      </div>
    </main>
  </div>
</div>

<script>
(() => {
  const form = document.getElementById('smsLogsFilterForm');
  const searchInput = document.getElementById('smsLogsSearchInput');
  const statusInput = document.getElementById('smsLogsStatusInput');
  const typeInput = document.getElementById('smsLogsTypeInput');
  const recipientInput = document.getElementById('smsLogsRecipientInput');
  const fromInput = document.getElementById('smsLogsDateFromInput');
  const toInput = document.getElementById('smsLogsDateToInput');
  if (!form || !searchInput || !statusInput || !typeInput || !recipientInput || !fromInput || !toInput) return;

  const resultsContainerId = 'smsLogsResults';
  let timerId = null;
  let activeRequest = null;

  const buildUrl = (params) => {
    const url = new URL(form.action, window.location.origin);
    url.search = params.toString();
    return url;
  };

  const getParamsFromForm = (page = 1) => {
    const params = new URLSearchParams(new FormData(form));
    params.set('page', 'sms_logs');
    params.set('p', String(page));
    return params;
  };

  const updateFromResponse = (html) => {
    const doc = new DOMParser().parseFromString(html, 'text/html');
    const nextResults = doc.getElementById(resultsContainerId);
    const currentResults = document.getElementById(resultsContainerId);
    if (!nextResults || !currentResults) return false;

    currentResults.innerHTML = nextResults.innerHTML;

    const nextTotal = doc.querySelector('[data-sms-total]');
    const currentTotal = document.querySelector('[data-sms-total]');
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
        if (!response.ok) throw new Error('SMS logs filter failed');
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

  [statusInput, typeInput, recipientInput, fromInput, toInput].forEach((el) => {
    el.addEventListener('change', () => {
      loadResults(getParamsFromForm(1));
    });
  });

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    loadResults(getParamsFromForm(1));
  });

  document.addEventListener('click', (event) => {
    const link = event.target.closest('a[data-sms-page-link]');
    if (!link) return;

    event.preventDefault();
    const url = new URL(link.href, window.location.origin);
    searchInput.value = url.searchParams.get('q') ?? '';
    statusInput.value = url.searchParams.get('status') ?? '';
    typeInput.value = url.searchParams.get('sms_type') ?? '';
    recipientInput.value = url.searchParams.get('recipient_role') ?? '';
    fromInput.value = url.searchParams.get('date_from') ?? '';
    toInput.value = url.searchParams.get('date_to') ?? '';
    loadResults(url.searchParams);
  });
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
