<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_roles(['Administrator']);
require_once __DIR__ . '/../includes/config.php';

$active = 'users';
$topTitle = 'Users';

$q = trim($_GET['q'] ?? $_POST['q'] ?? '');

// pagination inputs
$page = (int)($_GET['p'] ?? $_POST['p'] ?? 1);          // use ?p=2, ?p=3, etc.
$per_page = 5;                          // show 5 users per page
if ($page < 1) $page = 1;
$offset = ($page - 1) * $per_page;

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = trim($_POST['action'] ?? '');
  $redirectQ = trim($_POST['q'] ?? '');
  $redirectP = max(1, (int)($_POST['p'] ?? 1));

  if ($action === 'toggle_user_status') {
    $userId = (int)($_POST['user_id'] ?? 0);
    $newStatus = (int)($_POST['new_status'] ?? -1);
    $actorId = (int)($_SESSION['user']['user_id'] ?? 0);

    if ($userId <= 0 || !in_array($newStatus, [0, 1], true)) {
      $_SESSION['flash'] = "Invalid user status action.";
    } elseif ($userId === $actorId && $newStatus === 0) {
      $_SESSION['flash'] = "You cannot deactivate your own account.";
    } else {
      $stmt = $conn->prepare("UPDATE users SET is_active=? WHERE user_id=? LIMIT 1");
      $stmt->bind_param("ii", $newStatus, $userId);
      $ok = $stmt->execute();
      $affected = $stmt->affected_rows;
      $stmt->close();

      if ($ok && $affected >= 0) {
        $statusLabel = $newStatus === 1 ? 'Active' : 'Inactive';
        $stmtLog = $conn->prepare("INSERT INTO system_logs(user_id, action, description) VALUES(?,?,?)");
        $logAction = "User Status Updated";
        $logDesc = "User #{$userId} set to {$statusLabel}";
        $stmtLog->bind_param("iss", $actorId, $logAction, $logDesc);
        $stmtLog->execute();
        $stmtLog->close();
        $_SESSION['flash'] = "User #{$userId} is now {$statusLabel}.";
      } else {
        $_SESSION['flash'] = "Failed to update user status.";
      }
    }
  }

  header("Location: " . route('users', ['q' => $redirectQ, 'p' => $redirectP]));
  exit;
}

$params = [];
$types = '';
$where = "WHERE 1=1";

if ($q !== '') {
  $where .= " AND (u.fullname LIKE ? OR u.username LIKE ? OR u.role LIKE ? OR u.phone LIKE ? OR u.email LIKE ?)";
  $like = "%{$q}%";
  $params = [$like,$like,$like,$like,$like];
  $types = "sssss";
}

/**
 * 1) total count
 */
$countSql = "SELECT COUNT(*) AS total FROM users u $where";
$countStmt = $conn->prepare($countSql);
if ($params) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$total = (int)($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
$countStmt->close();

$total_pages = (int)ceil($total / $per_page);
if ($total_pages < 1) $total_pages = 1;
if ($page > $total_pages) { $page = $total_pages; $offset = ($page - 1) * $per_page; }

/**
 * 2) paged rows
 * IMPORTANT: LIMIT/OFFSET must be integers; bind as "ii"
 */
$sql = "
  SELECT u.user_id, u.fullname, u.username, u.role, u.phone, u.email, u.is_active, u.created_at
  FROM users u
  $where
  ORDER BY u.user_id DESC
  LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);

if ($params) {
  $types2 = $types . "ii";
  $params2 = array_merge($params, [$per_page, $offset]);
  $stmt->bind_param($types2, ...$params2);
} else {
  $stmt->bind_param("ii", $per_page, $offset);
}

$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include __DIR__ . '/../includes/head.php';

function users_url($p, $q) {
  return route('users', [
    'q' => $q,
    'p' => $p,
  ]);
}
?>
<div class="flex min-h-screen w-full">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="flex-1 flex flex-col">
    <?php include __DIR__ . '/../includes/topbar.php'; ?>

    <main class="p-6 lg:p-8 flex-1">
      <?php if ($flash): ?>
        <div class="mb-4 p-3 rounded bg-primary/10 text-primary border border-border-light dark:border-border-dark">
          <?= h($flash) ?>
        </div>
      <?php endif; ?>

      <div class="flex items-center justify-between gap-3">
        <form id="usersFilterForm" class="flex flex-1 min-w-0 gap-2" method="GET" action="<?= route('users') ?>">
          <input type="hidden" name="page" value="users">
          <input type="hidden" name="p" value="1"><!-- reset to page 1 on new search -->
          <div class="relative w-full sm:max-w-2xl">
            <span class="material-symbols-outlined pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-[18px] leading-none">search</span>
            <input id="usersSearchInput" name="q" value="<?= h($q) ?>" placeholder="Search name / username / role / phone / email"
                   class="w-full pl-9 pr-4 rounded-full border-border-light dark:border-border-dark bg-card-light dark:bg-card-dark">
          </div>
          <a
            class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700 text-text-light dark:text-text-dark inline-flex items-center justify-center shrink-0"
            href="<?= route('users') ?>"
            title="Reset"
            aria-label="Reset users filter"
          >
            <span class="material-symbols-outlined text-[20px] leading-none">restart_alt</span>
          </a>
        </form>

        <a href="<?= base_path('users/create.php') ?>"
           class="w-10 h-10 rounded-full bg-primary text-white inline-flex items-center justify-center shrink-0"
           title="Add User"
           aria-label="Add User">
          <span class="material-symbols-outlined text-[20px] leading-none">person_add</span>
        </a>
      </div>

      <div id="usersResults">
      <div class="mt-3 text-sm text-gray-500 dark:text-gray-400">
        Showing <?= $total ? ($offset + 1) : 0 ?>-<?= min($offset + $per_page, $total) ?> of <?= $total ?> users
      </div>

      <div class="mt-4 space-y-3 md:hidden">
        <?php foreach ($rows as $r): ?>
          <?php $isActive = ((int)$r['is_active'] === 1); ?>
          <div class="rounded-xl border border-border-light dark:border-border-dark bg-card-light dark:bg-card-dark p-4 text-sm">
            <div class="flex items-start justify-between gap-2">
              <div>
                <div class="font-semibold text-text-light dark:text-text-dark"><?= h($r['fullname'] ?? '-') ?></div>
                <div class="text-xs text-gray-500 dark:text-gray-400">@<?= h($r['username'] ?? '-') ?></div>
              </div>
              <?php if ($isActive): ?>
                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300">Active</span>
              <?php else: ?>
                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300">Inactive</span>
              <?php endif; ?>
            </div>
            <div class="mt-2 space-y-1 text-xs text-gray-600 dark:text-gray-300">
              <div><span class="text-gray-500">Role:</span> <?= h(role_label($r['role'] ?? '-')) ?></div>
              <div><span class="text-gray-500">Phone:</span> <?= h($r['phone'] ?? '-') ?></div>
              <div><span class="text-gray-500">Email:</span> <?= h($r['email'] ?? '-') ?></div>
              <div><span class="text-gray-500">Created:</span> <?= h($r['created_at'] ?? '-') ?></div>
            </div>
            <div class="mt-3 flex items-center gap-2">
              <a
                class="w-9 h-9 rounded-full border border-border-light dark:border-border-dark inline-flex items-center justify-center text-secondary"
                href="<?= base_path('users/edit.php?id=' . (int)$r['user_id']) ?>"
                title="Edit"
                aria-label="Edit user"
              >
                <span class="material-symbols-outlined text-[18px] leading-none">edit</span>
              </a>
              <form
                method="POST"
                class="inline js-user-status-form"
                data-user-label="<?= h((string)($r['fullname'] ?: ($r['username'] ?: ('User #' . (int)$r['user_id'])))) ?>"
                data-next-status="<?= $isActive ? 'Inactive' : 'Active' ?>"
              >
                <input type="hidden" name="action" value="toggle_user_status">
                <input type="hidden" name="user_id" value="<?= (int)$r['user_id'] ?>">
                <input type="hidden" name="new_status" value="<?= $isActive ? 0 : 1 ?>">
                <input type="hidden" name="q" value="<?= h($q) ?>">
                <input type="hidden" name="p" value="<?= (int)$page ?>">
                <button
                  class="w-9 h-9 rounded-full border border-border-light dark:border-border-dark inline-flex items-center justify-center <?= $isActive ? 'text-green-600' : 'text-red-600' ?>"
                  title="<?= $isActive ? 'Set Inactive' : 'Set Active' ?>"
                  aria-label="<?= $isActive ? 'Set user inactive' : 'Set user active' ?>"
                >
                  <span class="material-symbols-outlined text-[20px] leading-none"><?= $isActive ? 'toggle_on' : 'toggle_off' ?></span>
                </button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
          <div class="rounded-xl border border-border-light dark:border-border-dark bg-card-light dark:bg-card-dark p-4 text-sm text-gray-500">No users found.</div>
        <?php endif; ?>
      </div>

      <div class="mt-6 bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark rounded-lg overflow-hidden hidden md:block">
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead>
              <tr class="text-xs text-gray-500 dark:text-gray-400 uppercase">
                <th class="p-3">Name</th>
                <th class="p-3">Username</th>
                <th class="p-3">Role</th>
                <th class="p-3">Phone</th>
                <th class="p-3">Email</th>
                <th class="p-3">Status</th>
                <th class="p-3">Created</th>
                <th class="p-3">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-border-light dark:divide-border-dark">
              <?php foreach ($rows as $r): ?>
                <tr class="text-sm text-text-light dark:text-text-dark">
                  <td class="p-3 font-semibold"><?= h($r['fullname'] ?? '-') ?></td>
                  <td class="p-3"><?= h($r['username'] ?? '-') ?></td>
                  <td class="p-3"><?= h(role_label($r['role'] ?? '-')) ?></td>
                  <td class="p-3"><?= h($r['phone'] ?? '-') ?></td>
                  <td class="p-3"><?= h($r['email'] ?? '-') ?></td>
                  <td class="p-3">
                    <?= ((int)$r['is_active'] === 1)
                      ? '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300">Active</span>'
                      : '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300">Inactive</span>' ?>
                  </td>
                  <td class="p-3"><?= h($r['created_at'] ?? '') ?></td>
                  <td class="p-3">
                    <div class="flex items-center gap-2">
                      <a
                        class="p-1.5 rounded-DEFAULT hover:bg-gray-100 dark:hover:bg-gray-800 text-secondary inline-flex items-center justify-center"
                        href="<?= base_path('users/edit.php?id=' . (int)$r['user_id']) ?>"
                        title="Edit"
                        aria-label="Edit user"
                      >
                        <span class="material-symbols-outlined text-[20px] leading-none">edit</span>
                      </a>

                      <form
                        method="POST"
                        class="inline js-user-status-form"
                        data-user-label="<?= h((string)($r['fullname'] ?: ($r['username'] ?: ('User #' . (int)$r['user_id'])))) ?>"
                        data-next-status="<?= ((int)$r['is_active'] === 1) ? 'Inactive' : 'Active' ?>"
                      >
                        <input type="hidden" name="action" value="toggle_user_status">
                        <input type="hidden" name="user_id" value="<?= (int)$r['user_id'] ?>">
                        <input type="hidden" name="new_status" value="<?= ((int)$r['is_active'] === 1) ? 0 : 1 ?>">
                        <input type="hidden" name="q" value="<?= h($q) ?>">
                        <input type="hidden" name="p" value="<?= (int)$page ?>">
                        <button
                          class="p-1.5 rounded-DEFAULT hover:bg-gray-100 dark:hover:bg-gray-800 inline-flex items-center justify-center <?= ((int)$r['is_active'] === 1) ? 'text-green-600' : 'text-red-600' ?>"
                          title="<?= ((int)$r['is_active'] === 1) ? 'Set Inactive' : 'Set Active' ?>"
                          aria-label="<?= ((int)$r['is_active'] === 1) ? 'Set user inactive' : 'Set user active' ?>"
                        >
                          <span class="material-symbols-outlined text-[20px] leading-none"><?= ((int)$r['is_active'] === 1) ? 'toggle_on' : 'toggle_off' ?></span>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$rows): ?>
                <tr><td class="p-3 text-gray-500" colspan="8">No users found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Pagination -->
      <?php if ($total_pages > 1): ?>
        <?php
          // windowed page links (e.g., show 5 around current)
          $window = 2;
          $start = max(1, $page - $window);
          $end = min($total_pages, $page + $window);
        ?>
        <div class="mt-6 flex items-center justify-between gap-3">
          <div class="text-sm text-gray-500 dark:text-gray-400">
            Page <?= $page ?> of <?= $total_pages ?>
          </div>

          <nav class="flex items-center gap-2">
            <!-- Prev -->
            <a data-users-page-link="1"
              class="w-9 h-9 rounded-full border border-border-light dark:border-border-dark inline-flex items-center justify-center <?= $page <= 1 ? 'opacity-50 pointer-events-none' : 'hover:bg-gray-100 dark:hover:bg-gray-800' ?>"
              href="<?= h(users_url($page - 1, $q)) ?>"
              title="Previous page"
              aria-label="Previous page"
            ><span class="material-symbols-outlined text-[20px] leading-none">chevron_left</span></a>

            <!-- First + ellipsis -->
            <?php if ($start > 1): ?>
              <a data-users-page-link="1" class="w-9 h-9 rounded-full border border-border-light dark:border-border-dark inline-flex items-center justify-center hover:bg-gray-100 dark:hover:bg-gray-800" href="<?= h(users_url(1, $q)) ?>">1</a>
              <?php if ($start > 2): ?>
                <span class="px-2 text-gray-400">...</span>
              <?php endif; ?>
            <?php endif; ?>

            <!-- Page numbers -->
            <?php for ($i = $start; $i <= $end; $i++): ?>
              <a data-users-page-link="1"
                class="w-9 h-9 rounded-full border border-border-light dark:border-border-dark inline-flex items-center justify-center <?= $i === $page ? 'bg-primary text-white border-primary' : 'hover:bg-gray-100 dark:hover:bg-gray-800' ?>"
                href="<?= h(users_url($i, $q)) ?>"
              ><?= $i ?></a>
            <?php endfor; ?>

            <!-- Last + ellipsis -->
            <?php if ($end < $total_pages): ?>
              <?php if ($end < $total_pages - 1): ?>
                <span class="px-2 text-gray-400">...</span>
              <?php endif; ?>
              <a data-users-page-link="1" class="w-9 h-9 rounded-full border border-border-light dark:border-border-dark inline-flex items-center justify-center hover:bg-gray-100 dark:hover:bg-gray-800" href="<?= h(users_url($total_pages, $q)) ?>"><?= $total_pages ?></a>
            <?php endif; ?>

            <!-- Next -->
            <a data-users-page-link="1"
              class="w-9 h-9 rounded-full border border-border-light dark:border-border-dark inline-flex items-center justify-center <?= $page >= $total_pages ? 'opacity-50 pointer-events-none' : 'hover:bg-gray-100 dark:hover:bg-gray-800' ?>"
              href="<?= h(users_url($page + 1, $q)) ?>"
              title="Next page"
              aria-label="Next page"
            ><span class="material-symbols-outlined text-[20px] leading-none">chevron_right</span></a>
          </nav>
        </div>
      <?php endif; ?>
      </div>

    </main>
  </div>
</div>

<div id="userStatusConfirmModal" class="fixed inset-0 z-[90] hidden items-center justify-center bg-black/45 p-4">
  <div class="w-full max-w-md rounded-2xl border border-border-light dark:border-border-dark bg-card-light dark:bg-card-dark shadow-xl">
    <div class="flex items-center justify-between border-b border-border-light dark:border-border-dark px-4 py-3">
      <h3 id="userStatusConfirmTitle" class="text-base font-black text-text-light dark:text-text-dark">Confirm Status Change</h3>
      <button
        type="button"
        id="userStatusCancelIcon"
        class="w-9 h-9 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 inline-flex items-center justify-center"
        aria-label="Close confirmation"
      >
        <span class="material-symbols-outlined text-[20px] leading-none">close</span>
      </button>
    </div>
    <div class="p-4">
      <p id="userStatusConfirmText" class="text-sm text-text-light dark:text-text-dark">
        Are you sure you want to change this user status?
      </p>
      <div class="mt-4 flex items-center justify-end gap-2">
        <button
          type="button"
          id="userStatusCancelBtn"
          class="px-4 py-2 rounded-full bg-gray-200 dark:bg-gray-700 text-text-light dark:text-text-dark font-semibold"
        >
          Cancel
        </button>
        <button
          type="button"
          id="userStatusConfirmBtn"
          class="px-4 py-2 rounded-full bg-primary text-white font-semibold"
        >
          Confirm
        </button>
      </div>
    </div>
  </div>
</div>

<script>
(() => {
  const form = document.getElementById('usersFilterForm');
  const searchInput = document.getElementById('usersSearchInput');
  if (!form || !searchInput) return;

  const resultsContainerId = 'usersResults';
  let timerId = null;
  let activeRequest = null;
  const statusModal = document.getElementById('userStatusConfirmModal');
  const statusTitle = document.getElementById('userStatusConfirmTitle');
  const statusText = document.getElementById('userStatusConfirmText');
  const statusCancelBtn = document.getElementById('userStatusCancelBtn');
  const statusCancelIcon = document.getElementById('userStatusCancelIcon');
  const statusConfirmBtn = document.getElementById('userStatusConfirmBtn');
  let pendingStatusForm = null;

  const closeStatusModal = () => {
    if (!statusModal) return;
    statusModal.classList.add('hidden');
    statusModal.classList.remove('flex');
    pendingStatusForm = null;
  };

  const openStatusModal = (targetForm) => {
    if (!statusModal || !targetForm) return;
    pendingStatusForm = targetForm;

    const userLabel = (targetForm.dataset.userLabel || 'this user').trim();
    const nextStatus = (targetForm.dataset.nextStatus || 'Active').trim();

    if (statusTitle) statusTitle.textContent = `Set ${nextStatus}`;
    if (statusText) statusText.textContent = `Are you sure you want to set ${userLabel} as ${nextStatus}?`;

    statusModal.classList.remove('hidden');
    statusModal.classList.add('flex');
  };

  if (statusCancelBtn) statusCancelBtn.addEventListener('click', closeStatusModal);
  if (statusCancelIcon) statusCancelIcon.addEventListener('click', closeStatusModal);
  if (statusConfirmBtn) {
    statusConfirmBtn.addEventListener('click', () => {
      if (!pendingStatusForm) return;
      pendingStatusForm.submit();
    });
  }

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') closeStatusModal();
  });

  if (statusModal) {
    statusModal.addEventListener('click', (event) => {
      if (event.target === statusModal) closeStatusModal();
    });
  }

  const buildUrl = (params) => {
    const url = new URL(form.action, window.location.origin);
    url.search = params.toString();
    return url;
  };

  const getParamsFromForm = (page = 1) => {
    const params = new URLSearchParams(new FormData(form));
    params.set('page', 'users');
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
        if (!response.ok) throw new Error('Users filter request failed');
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

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    loadResults(getParamsFromForm(1));
  });

  document.addEventListener('submit', (event) => {
    const statusForm = event.target.closest('form.js-user-status-form');
    if (!statusForm) return;
    event.preventDefault();
    openStatusModal(statusForm);
  });

  document.addEventListener('click', (event) => {
    const link = event.target.closest('a[data-users-page-link]');
    if (!link) return;

    event.preventDefault();
    const url = new URL(link.href, window.location.origin);
    searchInput.value = url.searchParams.get('q') ?? '';
    loadResults(url.searchParams);
  });
})();
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>

