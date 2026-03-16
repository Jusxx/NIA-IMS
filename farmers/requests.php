<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_roles(['Farmer']);
require_once __DIR__ . '/../includes/config.php';

$active = 'my_requests';
$topTitle = 'My Requests';

$userId = (int)($_SESSION['user']['user_id'] ?? 0);

$stmt = $conn->prepare("SELECT farmer_id, farmer_name FROM farmers WHERE user_id=? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$farmer = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$farmer) {
  http_response_code(403);
  exit("Farmer profile not linked.");
}
$farmerId = (int)$farmer['farmer_id'];

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $type = trim($_POST['request_type'] ?? 'Irrigation Request');
  $details = trim($_POST['request_details'] ?? '');

  if ($details === '') {
    $error = "Details are required.";
  } else {
    $allowed = ['Irrigation Request', 'Schedule Adjustment', 'Water Allocation', 'Technical Concern'];
    if (!in_array($type, $allowed, true)) $type = 'Irrigation Request';

    $stmt = $conn->prepare("INSERT INTO farmer_requests(farmer_id, request_type, request_details, status) VALUES(?,?,?,'Pending')");
    $stmt->bind_param("iss", $farmerId, $type, $details);
    $stmt->execute();
    $stmt->close();

    header("Location: " . route('my_requests'));
    exit;
  }
}

$rows = [];
$stmt = $conn->prepare("
  SELECT request_id, request_type, request_details, status, request_stage, created_at
  FROM farmer_requests
  WHERE farmer_id=?
  ORDER BY created_at DESC
  LIMIT 500
");
$stmt->bind_param("i", $farmerId);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$summary = ['Total' => count($rows), 'Pending' => 0, 'Approved' => 0, 'Completed' => 0];
foreach ($rows as $r) {
  $stageRaw = trim((string)($r['request_stage'] ?? ''));
  $stage = $stageRaw !== '' ? $stageRaw : (string)($r['status'] ?? '');
  if ($stage === 'Pending') $summary['Pending']++;
  if ($stage === 'Approved') $summary['Approved']++;
  if ($stage === 'Completed') $summary['Completed']++;
}

include __DIR__ . '/../includes/head.php';
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<div class="flex min-h-screen w-full">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="flex-1 flex flex-col">
    <?php include __DIR__ . '/../includes/topbar.php'; ?>

    <main class="p-4 lg:p-8 pb-24 lg:pb-8 flex-1">
      <div class="max-w-6xl mx-auto">

        <section class="bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark rounded-xl p-5">
          <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
            <div>
              <h1 class="text-xl font-black text-text-light dark:text-text-dark">My Requests</h1>
              <p class="text-sm text-gray-500 dark:text-gray-400">Submit a request and track your approval stage.</p>
            </div>

            <button id="openModal" class="w-full sm:w-auto px-4 py-2 rounded-full bg-primary text-white font-bold inline-flex items-center justify-center gap-1.5">
              <span class="material-symbols-outlined text-[18px] leading-none">add</span>
              <span>New Request</span>
            </button>
          </div>

          <?php if ($error): ?>
            <div class="mt-4 p-3 rounded bg-red-100 text-red-700 border border-red-200"><?= h($error) ?></div>
          <?php endif; ?>

          <div class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-2">
            <div class="rounded-lg border border-border-light dark:border-border-dark p-3">
              <p class="text-xs text-gray-500 dark:text-gray-400">Total</p>
              <p class="text-xl font-black text-text-light dark:text-text-dark"><?= (int)$summary['Total'] ?></p>
            </div>
            <div class="rounded-lg border border-border-light dark:border-border-dark p-3">
              <p class="text-xs text-gray-500 dark:text-gray-400">Pending</p>
              <p class="text-xl font-black text-warning"><?= (int)$summary['Pending'] ?></p>
            </div>
            <div class="rounded-lg border border-border-light dark:border-border-dark p-3">
              <p class="text-xs text-gray-500 dark:text-gray-400">Approved</p>
              <p class="text-xl font-black text-primary"><?= (int)$summary['Approved'] ?></p>
            </div>
            <div class="rounded-lg border border-border-light dark:border-border-dark p-3">
              <p class="text-xs text-gray-500 dark:text-gray-400">Completed</p>
              <p class="text-xl font-black text-primary"><?= (int)$summary['Completed'] ?></p>
            </div>
          </div>
        </section>

        <section class="mt-4 bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark rounded-xl p-4 overflow-hidden">
          <div class="space-y-3 md:hidden">
            <?php foreach ($rows as $r): ?>
              <?php
                [$cls, $label] = badge((string)$r['status']);
                $stageRaw = trim((string)($r['request_stage'] ?? ''));
                $stage = $stageRaw === '' ? (string)$r['status'] : $stageRaw;
                [$stageCls, $stageLabel] = badge($stage);
              ?>
              <article class="rounded-xl border border-border-light dark:border-border-dark p-4">
                <div class="flex items-start justify-between gap-2">
                  <div>
                    <p class="text-sm font-semibold text-text-light dark:text-text-dark">Request #<?= (int)$r['request_id'] ?></p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5"><?= h((string)$r['created_at']) ?></p>
                  </div>
                  <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $cls ?>"><?= h($label) ?></span>
                </div>
                <p class="mt-2 text-sm font-medium text-text-light dark:text-text-dark"><?= h((string)$r['request_type']) ?></p>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300 break-words"><?= h((string)$r['request_details']) ?></p>
                <div class="mt-3">
                  <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $stageCls ?>"><?= h($stageLabel) ?></span>
                </div>
              </article>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
              <div class="rounded-xl border border-border-light dark:border-border-dark p-4 text-sm text-gray-500 dark:text-gray-400">
                No requests found.
              </div>
            <?php endif; ?>
          </div>

          <div class="hidden md:block overflow-x-auto">
            <table id="reqTable" class="w-full text-left">
              <thead>
                <tr class="text-xs text-gray-500 dark:text-gray-400 uppercase">
                  <th>ID</th>
                  <th>Type</th>
                  <th>Details</th>
                  <th>Status</th>
                  <th>Stage</th>
                  <th>Created</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($rows as $r): ?>
                  <?php
                    [$cls, $label] = badge((string)$r['status']);
                    $stageRaw = trim((string)($r['request_stage'] ?? ''));
                    $stage = $stageRaw === '' ? (string)$r['status'] : $stageRaw;
                    [$stageCls, $stageLabel] = badge($stage);
                  ?>
                  <tr class="text-sm text-text-light dark:text-text-dark">
                    <td><?= (int)$r['request_id'] ?></td>
                    <td><?= h((string)$r['request_type']) ?></td>
                    <td><?= h((string)$r['request_details']) ?></td>
                    <td><span class="px-2 py-1 text-xs font-semibold rounded-full <?= $cls ?>"><?= h($label) ?></span></td>
                    <td><span class="px-2 py-1 text-xs font-semibold rounded-full <?= $stageCls ?>"><?= h($stageLabel) ?></span></td>
                    <td><?= h((string)$r['created_at']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </section>

      </div>
    </main>
  </div>
</div>

<div id="modalBg" class="hidden fixed inset-0 bg-black/40 z-50"></div>
<div id="modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
  <div class="w-full max-w-lg bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark rounded-xl p-6">
    <div class="flex items-center justify-between">
      <h3 class="text-lg font-black text-text-light dark:text-text-dark">New Request</h3>
      <button id="closeModal" class="w-9 h-9 inline-flex items-center justify-center rounded-full bg-gray-200 dark:bg-gray-700" aria-label="Close">
        <span class="material-symbols-outlined text-[18px] leading-none">close</span>
      </button>
    </div>

    <form method="POST" class="mt-4 space-y-4">
      <div>
        <label class="block text-sm font-medium text-text-light dark:text-text-dark">Request Type</label>
        <select name="request_type" class="mt-1 w-full rounded-full px-4 py-2 border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
          <?php foreach (['Irrigation Request', 'Schedule Adjustment', 'Water Allocation', 'Technical Concern'] as $t): ?>
            <option value="<?= h($t) ?>"><?= h($t) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-text-light dark:text-text-dark">Details *</label>
        <textarea
          name="request_details"
          rows="4"
          required
          class="mt-1 w-full rounded-lg border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark"
        ></textarea>
      </div>

      <div class="flex gap-2">
        <button class="px-4 py-2 rounded-full bg-primary text-white font-bold">Submit</button>
        <button type="button" id="cancelModal" class="px-4 py-2 rounded-full bg-gray-200 dark:bg-gray-700 text-text-light dark:text-text-dark">Cancel</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../includes/farmer_bottom_nav.php'; ?>
<script>
  $(function () {
    if (window.matchMedia('(min-width: 768px)').matches) {
      $('#reqTable').DataTable({
        pageLength: 10,
        order: [[0, 'desc']]
      });
    }

    const open = () => { $('#modalBg,#modal').removeClass('hidden'); };
    const close = () => { $('#modalBg,#modal').addClass('hidden'); };

    $('#openModal').on('click', open);
    $('#closeModal,#cancelModal,#modalBg').on('click', close);
  });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
