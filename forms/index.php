<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_roles(['Administrator','Operations Staff','IMO']);
require_once __DIR__ . '/../includes/config.php';

$active = 'forms';
$topTitle = 'Forms';

if (!function_exists('forms_control_number')) {
  function forms_control_number(int $formId, ?string $issuedAt = null): string {
    $issuedTs = $issuedAt ? strtotime($issuedAt) : false;
    $year = $issuedTs ? date('Y', $issuedTs) : date('Y');
    return sprintf('NIA-%s-%05d', $year, max(0, $formId));
  }
}

$legalFormTypes = [
  'Farmer Registration',
  'Irrigation Request',
  'Membership Agreement',
  'Service Acknowledgement',
  'Consent Form',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $templateId = (int)($_POST['template_id'] ?? 0);
  $issuedToFarmer = (int)($_POST['issued_to_farmer_id'] ?? 0);
  $issuedBy = (int)($_SESSION['user']['user_id'] ?? 0);

  $tpl = null;
  if ($templateId > 0) {
    $tplStmt = $conn->prepare("SELECT template_id, template_name, form_type FROM form_templates WHERE template_id=? AND is_active=1 LIMIT 1");
    $tplStmt->bind_param("i", $templateId);
    $tplStmt->execute();
    $tpl = $tplStmt->get_result()->fetch_assoc();
    $tplStmt->close();
  }

  if ($tpl && !in_array((string)$tpl['form_type'], $legalFormTypes, true)) {
    $tpl = null;
  }

  if (!$tpl) {
    $_SESSION['flash'] = "Invalid template. Please select a legal/paper form template.";
    $_SESSION['flash_type'] = 'warning';
    header("Location: " . route('forms'));
    exit;
  }

  if ($issuedToFarmer <= 0) {
    $_SESSION['flash'] = "Please select a farmer recipient.";
    $_SESSION['flash_type'] = 'warning';
    header("Location: " . route('forms'));
    exit;
  }

  $farmerStmt = $conn->prepare("SELECT farmer_id, farmer_name FROM farmers WHERE farmer_id=? LIMIT 1");
  $farmerStmt->bind_param("i", $issuedToFarmer);
  $farmerStmt->execute();
  $farmer = $farmerStmt->get_result()->fetch_assoc();
  $farmerStmt->close();

  if (!$farmer) {
    $_SESSION['flash'] = "Selected farmer was not found.";
    $_SESSION['flash_type'] = 'warning';
    header("Location: " . route('forms'));
    exit;
  }

  $formType = (string)$tpl['form_type'];
  $stmt = $conn->prepare("
    INSERT INTO paper_forms(template_id, form_type, issued_to_farmer_id, issued_by)
    VALUES(?,?,?,?)
  ");
  $stmt->bind_param("isii", $templateId, $formType, $issuedToFarmer, $issuedBy);
  $stmt->execute();
  $formId = (int)$conn->insert_id;
  $stmt->close();

  $controlNo = forms_control_number($formId, date('Y-m-d H:i:s'));
  system_log(
    $conn,
    'Form Issued',
    "Issued {$controlNo} (" . (string)$tpl['template_name'] . ") to " . (string)$farmer['farmer_name']
  );

  header("Location: " . route('forms_print', ['form_id' => $formId]));
  exit;
}

$templates = [];
$stmt = $conn->prepare("
  SELECT template_id, template_name, form_type
  FROM form_templates
  WHERE is_active=1
    AND form_type IN (
      'Farmer Registration',
      'Irrigation Request',
      'Membership Agreement',
      'Service Acknowledgement',
      'Consent Form'
    )
  ORDER BY template_name ASC
");
$stmt->execute();
$templates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$farmers = [];
$stmt = $conn->prepare("
  SELECT f.farmer_id, f.farmer_name, f.association_name, f.address, f.phone, f.is_president,
         sa.area_name, sa.municipality
  FROM farmers f
  LEFT JOIN service_areas sa ON sa.service_area_id = f.service_area_id
  ORDER BY f.farmer_name ASC
");
$stmt->execute();
$farmers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$historyPerPage = 5;
$historyPage = max(1, (int)($_GET['history_p'] ?? 1));
$historyTotal = 0;
$historyTotalPages = 1;
$historyOffset = 0;

$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM paper_forms");
$stmt->execute();
$historyTotal = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
$stmt->close();

$historyTotalPages = max(1, (int)ceil($historyTotal / $historyPerPage));
if ($historyPage > $historyTotalPages) {
  $historyPage = $historyTotalPages;
}
$historyOffset = ($historyPage - 1) * $historyPerPage;

$history = [];
$stmt = $conn->prepare("
  SELECT pf.form_id, pf.issued_at, pf.status, pf.template_id, pf.issued_to_farmer_id,
         ft.template_name, ft.form_type,
         f.farmer_name,
         u.fullname AS issued_by_name, u.username AS issued_by_username
  FROM paper_forms pf
  JOIN form_templates ft ON ft.template_id = pf.template_id
  LEFT JOIN farmers f ON f.farmer_id = pf.issued_to_farmer_id
  LEFT JOIN users u ON u.user_id = pf.issued_by
  ORDER BY pf.issued_at DESC, pf.form_id DESC
  LIMIT ? OFFSET ?
");
$stmt->bind_param("ii", $historyPerPage, $historyOffset);
$stmt->execute();
$history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$printActivity = [];
$stmt = $conn->prepare("
  SELECT sl.log_id, sl.action, sl.description, sl.created_at,
         u.fullname AS actor_name, u.username AS actor_username
  FROM system_logs sl
  LEFT JOIN users u ON u.user_id = sl.user_id
  WHERE sl.action IN ('Form Issued', 'Form Reprint')
  ORDER BY sl.created_at DESC, sl.log_id DESC
  LIMIT 10
");
$stmt->execute();
$printActivity = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$flash = (string)($_SESSION['flash'] ?? '');
$flashType = (string)($_SESSION['flash_type'] ?? 'warning');
unset($_SESSION['flash'], $_SESSION['flash_type']);

include __DIR__ . '/../includes/head.php';
?>
<div class="flex min-h-screen w-full">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="flex-1 flex flex-col">
    <?php include __DIR__ . '/../includes/topbar.php'; ?>

    <main class="p-4 md:p-6 lg:p-8 flex-1">
      <?php if ($flash): ?>
        <div class="mb-4 p-3 rounded border border-border-light dark:border-border-dark <?= $flashType === 'success' ? 'bg-secondary/20 text-secondary' : 'bg-warning/20 text-warning' ?>">
          <?= h($flash) ?>
        </div>
      <?php endif; ?>

      <div class="space-y-6">
        <div class="bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark rounded-lg p-6">
            <h1 class="text-xl font-black text-text-light dark:text-text-dark">Issue and Print Form</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Generate paper/legal forms linked to an existing farmer profile.</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Use for documents that need print/signature trail (registration, request, agreement, consent, acknowledgement).</p>

            <form method="POST" class="mt-4 space-y-4">
              <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium">Form Template</label>
                  <select name="template_id" required class="mt-1 w-full rounded-full px-4 py-2 border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
                    <option value="">Select Template</option>
                    <?php foreach ($templates as $t): ?>
                      <option value="<?= (int)$t['template_id'] ?>">
                        <?= h($t['template_name']) ?> (<?= h($t['form_type']) ?>)
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div>
                  <label class="block text-sm font-medium">Issued To Farmer <span class="text-red-600">*</span></label>
                  <select id="issuedToFarmer" name="issued_to_farmer_id" required class="mt-1 w-full rounded-full px-4 py-2 border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
                    <option value="">Select Farmer</option>
                    <?php foreach ($farmers as $f): ?>
                      <?php
                        $areaLabel = trim((string)($f['area_name'] ?? ''));
                        $municipalityLabel = trim((string)($f['municipality'] ?? ''));
                        $serviceAreaLabel = trim($areaLabel . ($municipalityLabel ? ', ' . $municipalityLabel : ''));
                      ?>
                      <option
                        value="<?= (int)$f['farmer_id'] ?>"
                        data-farmer-name="<?= h((string)$f['farmer_name']) ?>"
                        data-association="<?= h((string)($f['association_name'] ?? '')) ?>"
                        data-phone="<?= h((string)($f['phone'] ?? '')) ?>"
                        data-address="<?= h((string)($f['address'] ?? '')) ?>"
                        data-service-area="<?= h($serviceAreaLabel) ?>"
                        data-president="<?= (int)($f['is_president'] ?? 0) === 1 ? 'Yes' : 'No' ?>"
                      >
                        <?= h((string)$f['farmer_name']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <div class="rounded-lg border border-border-light dark:border-border-dark p-4 bg-background-light dark:bg-background-dark">
                <div class="text-sm font-semibold text-text-light dark:text-text-dark">Farmer Profile Preview</div>
                <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300">
                  <div><span class="font-semibold">Name:</span> <span id="previewFarmerName">-</span></div>
                  <div><span class="font-semibold">Association:</span> <span id="previewAssociation">-</span></div>
                  <div><span class="font-semibold">Phone:</span> <span id="previewPhone">-</span></div>
                  <div><span class="font-semibold">Service Area:</span> <span id="previewServiceArea">-</span></div>
                  <div class="sm:col-span-2"><span class="font-semibold">Address:</span> <span id="previewAddress">-</span></div>
                  <div><span class="font-semibold">Farmer President:</span> <span id="previewPresident">-</span></div>
                </div>
              </div>

              <div class="flex flex-wrap gap-2">
                <button class="px-4 py-2 rounded-full bg-primary text-white font-bold inline-flex items-center gap-1.5">
                  <span class="material-symbols-outlined text-[18px] leading-none">print</span>
                  <span>Issue and Print</span>
                </button>
                <a class="px-4 py-2 rounded-full bg-gray-200 dark:bg-gray-700 text-text-light dark:text-text-dark inline-flex items-center gap-1.5"
                   href="<?= route('forms') ?>">
                  <span class="material-symbols-outlined text-[18px] leading-none">restart_alt</span>
                  <span>Reset</span>
                </a>
              </div>
            </form>
        </div>

        <div class="space-y-6">
          <div class="bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark rounded-lg p-5 md:p-6">
            <div class="flex items-center justify-between gap-2">
              <h2 class="text-lg font-black text-text-light dark:text-text-dark">Issued Forms History</h2>
              <div class="text-xs text-gray-500 dark:text-gray-400">5 per page</div>
            </div>

            <div class="mt-4 overflow-x-auto rounded-lg border border-border-light dark:border-border-dark">
          <table class="w-full text-left min-w-[760px]">
                <thead>
                  <tr class="text-xs uppercase text-gray-500 dark:text-gray-400">
                    <th class="p-2">Control No.</th>
                    <th class="p-2">Template</th>
                    <th class="p-2">Farmer</th>
                    <th class="p-2">Issued By</th>
                    <th class="p-2">Issued At</th>
                    <th class="p-2">Status</th>
                    <th class="p-2">Actions</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-border-light dark:divide-border-dark">
                  <?php foreach ($history as $row): ?>
                    <?php
                      $issuer = trim((string)($row['issued_by_name'] ?? ''));
                      if ($issuer === '') $issuer = (string)($row['issued_by_username'] ?? '-');
                      $controlNo = forms_control_number((int)$row['form_id'], (string)($row['issued_at'] ?? ''));
                    ?>
                    <tr class="text-sm text-text-light dark:text-text-dark">
                      <td class="p-2 font-mono text-xs"><?= h($controlNo) ?></td>
                      <td class="p-2">
                        <div class="font-semibold"><?= h((string)$row['template_name']) ?></div>
                        <div class="text-xs text-gray-500 dark:text-gray-400"><?= h((string)$row['form_type']) ?></div>
                      </td>
                      <td class="p-2"><?= h((string)($row['farmer_name'] ?? '-')) ?></td>
                      <td class="p-2"><?= h($issuer ?: '-') ?></td>
                      <td class="p-2"><?= h((string)$row['issued_at']) ?></td>
                      <td class="p-2"><?= h((string)$row['status']) ?></td>
                      <td class="p-2">
                        <a
                          class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full bg-secondary text-white text-xs font-semibold"
                          href="<?= route('forms_print', ['form_id' => (int)$row['form_id'], 'reprint' => 1]) ?>"
                          target="_blank"
                          rel="noopener"
                          title="Reprint"
                        >
                          <span class="material-symbols-outlined text-[16px] leading-none">print</span>
                          <span>Reprint</span>
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                  <?php if (!$history): ?>
                    <tr>
                      <td class="p-3 text-gray-500 dark:text-gray-400" colspan="7">No issued forms yet.</td>
                    </tr>
                  <?php endif; ?>
            </tbody>
          </table>
        </div>

        <?php
          $historyShowFrom = $historyTotal ? ($historyOffset + 1) : 0;
          $historyShowTo = min($historyOffset + $historyPerPage, $historyTotal);
          $historyPrev = max(1, $historyPage - 1);
          $historyNext = min($historyTotalPages, $historyPage + 1);
        ?>
        <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs text-gray-500 dark:text-gray-400">
          <div>Showing <?= (int)$historyShowFrom ?>-<?= (int)$historyShowTo ?> of <?= (int)$historyTotal ?></div>
          <div class="flex items-center gap-2">
            <?php if ($historyPage > 1): ?>
              <a
                class="w-8 h-8 rounded-full border border-border-light dark:border-border-dark inline-flex items-center justify-center hover:bg-gray-100 dark:hover:bg-gray-800"
                href="<?= route('forms', ['history_p' => $historyPrev]) ?>"
                title="Previous page"
                aria-label="Previous page"
              >
                <span class="material-symbols-outlined text-[16px] leading-none">chevron_left</span>
              </a>
            <?php else: ?>
              <span class="w-8 h-8 rounded-full border border-border-light dark:border-border-dark inline-flex items-center justify-center opacity-40 cursor-not-allowed">
                <span class="material-symbols-outlined text-[16px] leading-none">chevron_left</span>
              </span>
            <?php endif; ?>

            <span class="px-3 py-1 rounded-full border border-border-light dark:border-border-dark">
              Page <?= (int)$historyPage ?> of <?= (int)$historyTotalPages ?>
            </span>

            <?php if ($historyPage < $historyTotalPages): ?>
              <a
                class="w-8 h-8 rounded-full border border-border-light dark:border-border-dark inline-flex items-center justify-center hover:bg-gray-100 dark:hover:bg-gray-800"
                href="<?= route('forms', ['history_p' => $historyNext]) ?>"
                title="Next page"
                aria-label="Next page"
              >
                <span class="material-symbols-outlined text-[16px] leading-none">chevron_right</span>
              </a>
            <?php else: ?>
              <span class="w-8 h-8 rounded-full border border-border-light dark:border-border-dark inline-flex items-center justify-center opacity-40 cursor-not-allowed">
                <span class="material-symbols-outlined text-[16px] leading-none">chevron_right</span>
              </span>
            <?php endif; ?>
          </div>
        </div>
      </div>

          <div class="bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark rounded-lg p-5 md:p-6">
            <div class="flex items-center justify-between gap-2">
              <h2 class="text-lg font-black text-text-light dark:text-text-dark">Print Activity</h2>
              <div class="text-xs text-gray-500 dark:text-gray-400">Issued + Reprint logs</div>
            </div>
            <div class="mt-4 overflow-x-auto rounded-lg border border-border-light dark:border-border-dark">
              <table class="w-full text-left min-w-[680px]">
                <thead>
                  <tr class="text-xs uppercase text-gray-500 dark:text-gray-400">
                    <th class="p-2">When</th>
                    <th class="p-2">Action</th>
                    <th class="p-2">By</th>
                    <th class="p-2">Details</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-border-light dark:divide-border-dark">
                  <?php foreach ($printActivity as $log): ?>
                    <?php
                      $actor = trim((string)($log['actor_name'] ?? ''));
                      if ($actor === '') $actor = (string)($log['actor_username'] ?? '-');
                    ?>
                    <tr class="text-sm text-text-light dark:text-text-dark">
                      <td class="p-2"><?= h((string)$log['created_at']) ?></td>
                      <td class="p-2"><?= h((string)$log['action']) ?></td>
                      <td class="p-2"><?= h($actor ?: '-') ?></td>
                      <td class="p-2"><?= h((string)$log['description']) ?></td>
                    </tr>
                  <?php endforeach; ?>
                  <?php if (!$printActivity): ?>
                    <tr>
                      <td class="p-3 text-gray-500 dark:text-gray-400" colspan="4">No print activity yet.</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<script>
(() => {
  const farmerSelect = document.getElementById('issuedToFarmer');
  if (!farmerSelect) return;

  const previewFarmerName = document.getElementById('previewFarmerName');
  const previewAssociation = document.getElementById('previewAssociation');
  const previewPhone = document.getElementById('previewPhone');
  const previewAddress = document.getElementById('previewAddress');
  const previewServiceArea = document.getElementById('previewServiceArea');
  const previewPresident = document.getElementById('previewPresident');

  const fill = (el, value) => {
    if (!el) return;
    const nextValue = (value || '').trim();
    el.textContent = nextValue !== '' ? nextValue : '-';
  };

  const updatePreview = () => {
    const selected = farmerSelect.options[farmerSelect.selectedIndex];
    if (!selected || !selected.value) {
      fill(previewFarmerName, '');
      fill(previewAssociation, '');
      fill(previewPhone, '');
      fill(previewAddress, '');
      fill(previewServiceArea, '');
      fill(previewPresident, '');
      return;
    }

    fill(previewFarmerName, selected.dataset.farmerName || '');
    fill(previewAssociation, selected.dataset.association || '');
    fill(previewPhone, selected.dataset.phone || '');
    fill(previewAddress, selected.dataset.address || '');
    fill(previewServiceArea, selected.dataset.serviceArea || '');
    fill(previewPresident, selected.dataset.president || '');
  };

  farmerSelect.addEventListener('change', updatePreview);
  updatePreview();
})();
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
