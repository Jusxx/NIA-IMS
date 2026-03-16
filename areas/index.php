<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_roles(['Administrator','Operations Staff','IMO']);
require_once __DIR__ . '/../includes/config.php';

$active = 'areas';
$topTitle = 'Areas / Canals';

$q = trim($_GET['q'] ?? '');
$rows = [];

// Handle delete (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
  $deleteId = (int)$_POST['delete_id'];

  $stmt = $conn->prepare("DELETE FROM service_areas WHERE service_area_id = ? LIMIT 1");
  $stmt->bind_param("i", $deleteId);
  $stmt->execute();
  $stmt->close();

  // redirect to prevent resubmission
  header("Location: " . route('areas', $q ? ['q' => $q] : []));
  exit;
}

// Fetch rows
if ($q !== '') {
  $like = "%{$q}%";
  $stmt = $conn->prepare("
    SELECT service_area_id, area_name, municipality, province, total_area_ha, latitude, longitude
    FROM service_areas
    WHERE area_name LIKE ? OR municipality LIKE ? OR province LIKE ?
    ORDER BY service_area_id DESC
  ");
  $stmt->bind_param("sss", $like, $like, $like);
} else {
  $stmt = $conn->prepare("
    SELECT service_area_id, area_name, municipality, province, total_area_ha, latitude, longitude
    FROM service_areas
    ORDER BY service_area_id DESC
  ");
}
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$rows = filter_focus_service_area_rows($rows);

include __DIR__ . '/../includes/head.php';
?>

<div class="flex min-h-screen w-full">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="flex-1 flex flex-col">
    <?php include __DIR__ . '/../includes/topbar.php'; ?>

    <main class="p-6 lg:p-8 flex-1">
      <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">

        <form id="areasFilterForm" class="flex flex-col sm:flex-row gap-2 flex-1 min-w-0" method="GET" action="<?= route('areas') ?>">
          <div class="relative w-full sm:flex-1 sm:min-w-[18rem] lg:min-w-[24rem]">
            <span class="material-symbols-outlined pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-[18px] leading-none">search</span>
            <input id="areasSearchInput" name="q" value="<?= h($q) ?>" placeholder="Search area/municipality/province"
                   class="w-full pl-9 pr-4 rounded-full border-border-light dark:border-border-dark bg-card-light dark:bg-card-dark">
          </div>
          <a
            href="<?= route('areas') ?>"
            class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700 text-text-light dark:text-text-dark inline-flex items-center justify-center shrink-0"
            title="Reset"
            aria-label="Reset filters"
          >
            <span class="material-symbols-outlined text-[20px] leading-none">restart_alt</span>
          </a>
        </form>

        <div class="flex gap-2 shrink-0">
          <a
            href="<?= base_path('areas/create.php') ?>"
            class="w-10 h-10 rounded-full bg-primary text-white inline-flex items-center justify-center"
            title="Add Area"
            aria-label="Add Area"
          >
            <span class="material-symbols-outlined text-[20px] leading-none">add</span>
          </a>
        </div>

      </div>

      <div id="areasResults">
      <div class="mt-6 bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead>
              <tr class="text-xs text-gray-500 dark:text-gray-400 uppercase">
                <th class="p-3">Area</th>
                <th class="p-3">Municipality</th>
                <th class="p-3">Province</th>
                <th class="p-3">Total Area (ha)</th>
                <th class="p-3">Area Location</th>
                <th class="p-3">Actions</th>
              </tr>
            </thead>

            <tbody class="divide-y divide-border-light dark:divide-border-dark">
              <?php foreach ($rows as $r): ?>
                <tr class="text-sm text-text-light dark:text-text-dark">
                  <td class="p-3"><?= h($r['area_name']) ?></td>
                  <td class="p-3"><?= h($r['municipality']) ?></td>
                  <td class="p-3"><?= h($r['province']) ?></td>
                  <td class="p-3"><?= h($r['total_area_ha']) ?></td>
                  <td class="p-3 text-xs">
                    <?php if ($r['latitude'] !== null && $r['longitude'] !== null): ?>
                      <?= h($r['latitude']) ?>, <?= h($r['longitude']) ?>
                    <?php else: ?>
                      <span class="text-gray-500 dark:text-gray-400">-</span>
                    <?php endif; ?>
                  </td>

                  <td class="p-3">
                    <div class="flex items-center gap-2">
                      <a
                        class="p-1.5 rounded-DEFAULT hover:bg-gray-100 dark:hover:bg-gray-800 text-secondary inline-flex items-center justify-center"
                        href="<?= base_path('areas/edit.php?id='.(int)$r['service_area_id']) ?>"
                        title="Edit"
                        aria-label="Edit area"
                      >
                        <span class="material-symbols-outlined text-[20px] leading-none">edit</span>
                      </a>

                      <form method="POST" class="inline" onsubmit="return confirm('Delete this area? This may affect schedules linked to it.');">
                        <input type="hidden" name="delete_id" value="<?= (int)$r['service_area_id'] ?>">
                        <button
                          type="submit"
                          class="p-1.5 rounded-DEFAULT hover:bg-red-50 dark:hover:bg-red-900/20 text-red-600 inline-flex items-center justify-center"
                          title="Delete"
                          aria-label="Delete area"
                        >
                          <span class="material-symbols-outlined text-[20px] leading-none">delete</span>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>

              <?php if (!$rows): ?>
                <tr>
                  <td class="p-3 text-gray-500 dark:text-gray-400" colspan="6">No areas found.</td>
                </tr>
              <?php endif; ?>
            </tbody>

          </table>
        </div>
      </div>
      </div>
    </main>
  </div>
</div>

<script>
(() => {
  const form = document.getElementById('areasFilterForm');
  const searchInput = document.getElementById('areasSearchInput');
  if (!form || !searchInput) return;

  const resultsContainerId = 'areasResults';
  let timerId = null;
  let activeRequest = null;

  const buildUrl = (params) => {
    const url = new URL(form.action, window.location.origin);
    url.search = params.toString();
    return url;
  };

  const getParamsFromForm = () => {
    const params = new URLSearchParams(new FormData(form));
    params.set('page', 'areas');
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
        if (!response.ok) throw new Error('Area filter request failed');
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
      loadResults(getParamsFromForm());
    }, 350);
  });

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    loadResults(getParamsFromForm());
  });
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
