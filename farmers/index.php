<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_roles(['Administrator']);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/sms.php';

$active = 'farmers';
$topTitle = 'Farmers';

$q = trim($_GET['q'] ?? '');
$statusF = trim($_GET['status'] ?? '');
if (!in_array($statusF, ['', 'Active', 'Inactive'], true)) {
  $statusF = '';
}
$perPage = 5;
$pageNum = max(1, (int)($_GET['p'] ?? 1));

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

function farmers_redirect_url(string $q, string $statusF, int $pageNum = 1): string {
  $params = [];
  if ($q !== '') $params['q'] = $q;
  if ($statusF !== '') $params['status'] = $statusF;
  if ($pageNum > 1) $params['p'] = $pageNum;
  return route('farmers', $params);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = trim($_POST['action'] ?? '');

  if ($action === 'set_membership') {
    $farmerId = (int)($_POST['farmer_id'] ?? 0);
    $setStatus = trim($_POST['set_status'] ?? '');
    $redirectQ = trim($_POST['redirect_q'] ?? '');
    $redirectStatus = trim($_POST['redirect_status'] ?? '');
    $redirectPage = max(1, (int)($_POST['redirect_p'] ?? 1));
    if (!in_array($redirectStatus, ['', 'Active', 'Inactive'], true)) {
      $redirectStatus = '';
    }

    if ($farmerId <= 0 || !in_array($setStatus, ['Active', 'Inactive'], true)) {
      $_SESSION['flash'] = "Invalid farmer status request.";
      header("Location: " . farmers_redirect_url($redirectQ, $redirectStatus, $redirectPage));
      exit;
    }

    $setActive = ($setStatus === 'Active') ? 1 : 0;

    try {
      $conn->begin_transaction();

      $stmt = $conn->prepare("
        SELECT f.farmer_id, f.farmer_name, f.phone, f.is_president, f.user_id,
               COALESCE(u.is_active, 1) AS current_active
        FROM farmers f
        LEFT JOIN users u ON u.user_id = f.user_id
        WHERE f.farmer_id = ?
        LIMIT 1
      ");
      $stmt->bind_param("i", $farmerId);
      $stmt->execute();
      $farmerRow = $stmt->get_result()->fetch_assoc();
      $stmt->close();

      if (!$farmerRow) {
        throw new RuntimeException("Farmer not found.");
      }

      $linkedUserId = (int)($farmerRow['user_id'] ?? 0);
      if ($linkedUserId <= 0) {
        throw new RuntimeException("Farmer has no linked account to set as active/inactive.");
      }

      $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE user_id = ? LIMIT 1");
      $stmt->bind_param("ii", $setActive, $linkedUserId);
      $stmt->execute();
      $stmt->close();

      $recipientRole = ((int)($farmerRow['is_president'] ?? 0) === 1) ? 'President' : 'Farmer';
      $smsResult = send_membership_status_sms(
        $conn,
        (int)$farmerId,
        (string)($farmerRow['phone'] ?? ''),
        (string)($farmerRow['farmer_name'] ?? ''),
        $setStatus === 'Active',
        $recipientRole
      );

      $desc = "Farmer #{$farmerId} (" . ($farmerRow['farmer_name'] ?? 'Unknown') . ") membership set to {$setStatus}.";
      system_log($conn, "Farmer Membership Updated", $desc);

      $conn->commit();
      if (!empty($smsResult['ok'])) {
        $_SESSION['flash'] = "Farmer #" . (int)$farmerId . " is now {$setStatus}. SMS sent.";
      } else {
        $_SESSION['flash'] = "Farmer #" . (int)$farmerId . " is now {$setStatus}. SMS could not be sent.";
      }
    } catch (Throwable $e) {
      $conn->rollback();
      $_SESSION['flash'] = "Failed to update farmer membership: " . $e->getMessage();
    }

    header("Location: " . farmers_redirect_url($redirectQ, $redirectStatus, $redirectPage));
    exit;
  }
}

$params = [];
$types = "";
$where = "WHERE 1=1";

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
  $where .= " AND f.service_area_id IN (" . implode(',', array_fill(0, count($focusServiceAreaIds), '?')) . ")";
  $types .= str_repeat('i', count($focusServiceAreaIds));
  foreach ($focusServiceAreaIds as $focusId) {
    $params[] = $focusId;
  }
} else {
  $where .= " AND 1=0";
}

if ($q !== '') {
  $where .= " AND (
    f.farmer_name LIKE ?
    OR f.association_name LIKE ?
    OR f.phone LIKE ?
    OR sa.area_name LIKE ?
    OR sa.municipality LIKE ?
    OR sa.province LIKE ?
    OR fl.lot_code LIKE ?
    OR fl.location_desc LIKE ?
  )";
  $like = "%{$q}%";
  $params[] = $like;
  $params[] = $like;
  $params[] = $like;
  $params[] = $like;
  $params[] = $like;
  $params[] = $like;
  $params[] = $like;
  $params[] = $like;
  $types .= "ssssssss";
}

if ($statusF === 'Active') {
  $where .= " AND COALESCE(u.is_active, 1) = 1";
} elseif ($statusF === 'Inactive') {
  $where .= " AND COALESCE(u.is_active, 1) = 0";
}

$fromSql = "
  FROM farmers f
  LEFT JOIN service_areas sa ON sa.service_area_id = f.service_area_id
  LEFT JOIN users u ON u.user_id = f.user_id
  LEFT JOIN (
    SELECT fl1.farmer_id, fl1.lot_code, fl1.location_desc, fl1.latitude, fl1.longitude
    FROM farmer_lots fl1
    INNER JOIN (
      SELECT farmer_id, MAX(lot_id) AS max_lot_id
      FROM farmer_lots
      GROUP BY farmer_id
    ) latest ON latest.max_lot_id = fl1.lot_id
  ) fl ON fl.farmer_id = f.farmer_id
";

$summarySql = "
  SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN COALESCE(u.is_active, 1) = 1 THEN 1 ELSE 0 END) AS active_total,
    SUM(CASE WHEN COALESCE(u.is_active, 1) = 0 THEN 1 ELSE 0 END) AS inactive_total,
    SUM(CASE WHEN f.is_president = 1 THEN 1 ELSE 0 END) AS president_total
  $fromSql
  $where
";

$stmt = $conn->prepare($summarySql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc() ?: [];
$stmt->close();

$total = (int)($summary['total'] ?? 0);
$totalPages = max(1, (int)ceil($total / $perPage));
if ($pageNum > $totalPages) {
  $pageNum = $totalPages;
}
$offset = ($pageNum - 1) * $perPage;

$sql = "
  SELECT f.*, sa.area_name, sa.municipality, sa.province,
         u.username, COALESCE(u.is_active, 1) AS member_active,
         fl.lot_code, fl.location_desc AS lot_location_desc, fl.latitude AS lot_latitude, fl.longitude AS lot_longitude
  $fromSql
  $where
  ORDER BY f.farmer_id DESC
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

$visibleTotalFarmers = $total;
$visibleActiveFarmers = (int)($summary['active_total'] ?? 0);
$visibleInactiveFarmers = (int)($summary['inactive_total'] ?? 0);
$visiblePresidents = (int)($summary['president_total'] ?? 0);

include __DIR__ . '/../includes/head.php';
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

      <div class="mb-4 grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="rounded-lg border border-border-light dark:border-border-dark bg-card-light dark:bg-card-dark p-4">
          <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Visible Farmers</p>
          <p class="mt-1 text-2xl font-black text-text-light dark:text-text-dark"><?= (int)$visibleTotalFarmers ?></p>
        </div>
        <div class="rounded-lg border border-border-light dark:border-border-dark bg-card-light dark:bg-card-dark p-4">
          <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Active</p>
          <p class="mt-1 text-2xl font-black text-green-600 dark:text-green-300"><?= (int)$visibleActiveFarmers ?></p>
        </div>
        <div class="rounded-lg border border-border-light dark:border-border-dark bg-card-light dark:bg-card-dark p-4">
          <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Inactive</p>
          <p class="mt-1 text-2xl font-black text-red-600 dark:text-red-300"><?= (int)$visibleInactiveFarmers ?></p>
        </div>
        <div class="rounded-lg border border-border-light dark:border-border-dark bg-card-light dark:bg-card-dark p-4">
          <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Presidents</p>
          <p class="mt-1 text-2xl font-black text-secondary"><?= (int)$visiblePresidents ?></p>
        </div>
      </div>

      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <form id="farmersFilterForm" class="flex flex-col sm:flex-row flex-1 min-w-0 gap-2" method="GET" action="<?= route('farmers') ?>">
          <input type="hidden" name="page" value="farmers">
          <input type="hidden" name="p" value="<?= (int)$pageNum ?>">
          <div class="relative w-full sm:flex-1 sm:min-w-[18rem] lg:min-w-[24rem]">
            <span class="material-symbols-outlined pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-[18px] leading-none">search</span>
            <input id="farmersSearchInput" name="q" value="<?= h($q) ?>" placeholder="Search farmer / association / phone"
                   class="w-full pl-9 pr-4 rounded-full border-border-light dark:border-border-dark bg-card-light dark:bg-card-dark">
          </div>

          <select id="farmersStatusInput" name="status" class="w-full sm:w-40 rounded-full px-3 border-border-light dark:border-border-dark bg-card-light dark:bg-card-dark">
            <option value="">All Membership</option>
            <?php foreach (['Active','Inactive'] as $s): ?>
              <option value="<?= $s ?>" <?= $statusF === $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
          </select>

          <a
            class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700 text-text-light dark:text-text-dark inline-flex items-center justify-center shrink-0"
            href="<?= route('farmers') ?>"
            title="Reset"
            aria-label="Reset filters"
          >
            <span class="material-symbols-outlined text-[20px] leading-none">restart_alt</span>
          </a>

        </form>

        <a href="<?= base_path('farmers/create.php') ?>" class="w-10 h-10 rounded-full bg-primary text-white inline-flex items-center justify-center shrink-0" title="Add Farmer" aria-label="Add Farmer">
          <span class="material-symbols-outlined text-[20px] leading-none">person_add</span>
        </a>
      </div>

      <div id="farmersResults">
      <div class="mt-2 md:hidden space-y-3">
        <?php foreach ($rows as $r): ?>
          <?php $isActive = ((int)($r['member_active'] ?? 1) === 1); ?>
          <div class="rounded-xl border border-border-light dark:border-border-dark bg-card-light dark:bg-card-dark p-4">
            <div class="flex items-start justify-between gap-2">
              <div>
                <p class="text-sm font-black text-text-light dark:text-text-dark"><?= h($r['farmer_name']) ?></p>
                <?php if (!empty($r['username'])): ?>
                  <p class="text-xs text-gray-500 dark:text-gray-400">@<?= h($r['username']) ?></p>
                <?php endif; ?>
              </div>
              <?php if ($isActive): ?>
                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300">Active</span>
              <?php else: ?>
                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300">Inactive</span>
              <?php endif; ?>
            </div>

            <div class="mt-3 grid grid-cols-1 gap-2 text-sm">
              <p><span class="text-gray-500 dark:text-gray-400">Association:</span> <span class="font-semibold text-text-light dark:text-text-dark"><?= h($r['association_name']) ?></span></p>
              <p><span class="text-gray-500 dark:text-gray-400">Phone:</span> <span class="font-semibold text-text-light dark:text-text-dark"><?= h($r['phone']) ?></span></p>
              <p><span class="text-gray-500 dark:text-gray-400">Area:</span> <span class="font-semibold text-text-light dark:text-text-dark"><?= h($r['area_name'] ?? '-') ?></span></p>
              <?php if (!empty($r['lot_code']) || !empty($r['lot_location_desc'])): ?>
                <p>
                  <span class="text-gray-500 dark:text-gray-400">Lot:</span>
                  <span class="font-semibold text-text-light dark:text-text-dark">
                    <?= h($r['lot_code'] ?? '-') ?><?= !empty($r['lot_location_desc']) ? ' - '.h($r['lot_location_desc']) : '' ?>
                  </span>
                </p>
              <?php endif; ?>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-2">
              <button
                type="button"
                class="w-9 h-9 rounded-full border border-border-light dark:border-border-dark inline-flex items-center justify-center text-secondary js-open-farmer-view"
                title="View"
                data-farmer-name="<?= h($r['farmer_name']) ?>"
                data-association-name="<?= h($r['association_name']) ?>"
                data-association-location="<?= h(trim(($r['municipality'] ?? '') . (!empty($r['province']) ? ', ' . $r['province'] : ''))) ?>"
                data-phone="<?= h($r['phone']) ?>"
                data-area-name="<?= h($r['area_name'] ?? '-') ?>"
                data-lot-code="<?= h($r['lot_code'] ?? '') ?>"
                data-lot-location="<?= h($r['lot_location_desc'] ?? '') ?>"
                data-membership="<?= $isActive ? 'Active' : 'Inactive' ?>"
                data-created-at="<?= h($r['created_at']) ?>"
                data-edit-url="<?= h(base_path('farmers/edit.php?id=' . (int)$r['farmer_id'])) ?>"
              >
                <span class="material-symbols-outlined text-[18px] leading-none">visibility</span>
              </button>

              <a
                class="w-9 h-9 rounded-full border border-border-light dark:border-border-dark inline-flex items-center justify-center text-secondary"
                title="Edit"
                href="<?= base_path('farmers/edit.php?id=' . (int)$r['farmer_id']) ?>"
              >
                <span class="material-symbols-outlined text-[18px] leading-none">edit</span>
              </a>

              <?php if (!empty($r['user_id'])): ?>
                <form method="POST" class="inline js-membership-form"
                      data-confirm-message="Are you sure you want to set this farmer to <?= $isActive ? 'Inactive' : 'Active' ?>?">
                  <input type="hidden" name="action" value="set_membership">
                  <input type="hidden" name="farmer_id" value="<?= (int)$r['farmer_id'] ?>">
                  <input type="hidden" name="set_status" value="<?= $isActive ? 'Inactive' : 'Active' ?>">
                  <input type="hidden" name="redirect_q" value="<?= h($q) ?>">
                  <input type="hidden" name="redirect_status" value="<?= h($statusF) ?>">
                  <input type="hidden" name="redirect_p" value="<?= (int)$pageNum ?>">
                  <button
                    class="w-9 h-9 rounded-full border border-border-light dark:border-border-dark inline-flex items-center justify-center <?= $isActive ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' ?>"
                    title="<?= $isActive ? 'Set Inactive' : 'Set Active' ?>"
                  >
                    <span class="material-symbols-outlined text-[20px] leading-none"><?= $isActive ? 'toggle_on' : 'toggle_off' ?></span>
                  </button>
                </form>
              <?php else: ?>
                <span class="w-9 h-9 rounded-full border border-border-light dark:border-border-dark inline-flex items-center justify-center text-gray-400" title="No linked account">
                  <span class="material-symbols-outlined text-[18px] leading-none">block</span>
                </span>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
          <div class="rounded-xl border border-border-light dark:border-border-dark bg-card-light dark:bg-card-dark p-4 text-sm text-gray-500">No farmers found.</div>
        <?php endif; ?>
      </div>

      <div class="mt-6 hidden md:block bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead>
              <tr class="text-xs text-gray-500 dark:text-gray-400 uppercase">
                <th class="p-3">Farmer</th>
                <th class="p-3">Association</th>
                <th class="p-3">Association Location</th>
                <th class="p-3">Phone</th>
                <th class="p-3">Area</th>
                <th class="p-3">Lot Location</th>
                <th class="p-3">Membership</th>
                <th class="p-3">Created</th>
                <th class="p-3">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-border-light dark:divide-border-dark">
              <?php foreach ($rows as $r): ?>
                <?php $isActive = ((int)($r['member_active'] ?? 1) === 1); ?>
                <tr class="text-sm text-text-light dark:text-text-dark">
                  <td class="p-3">
                    <div class="font-semibold"><?= h($r['farmer_name']) ?></div>
                    <?php if (!empty($r['username'])): ?>
                      <div class="text-xs text-gray-500 dark:text-gray-400">@<?= h($r['username']) ?></div>
                    <?php endif; ?>
                  </td>
                  <td class="p-3"><?= h($r['association_name']) ?></td>
                  <td class="p-3">
                    <?= h($r['municipality'] ?? '') ?>
                    <?= !empty($r['province']) ? ", " . h($r['province']) : "" ?>
                  </td>
                  <td class="p-3"><?= h($r['phone']) ?></td>
                  <td class="p-3"><?= h($r['area_name'] ?? '-') ?></td>
                  <td class="p-3">
                    <?php if (!empty($r['lot_code']) || !empty($r['lot_location_desc'])): ?>
                      <?php if (!empty($r['lot_code'])): ?>
                        <div class="font-semibold"><?= h($r['lot_code']) ?></div>
                      <?php endif; ?>
                      <?php if (!empty($r['lot_location_desc'])): ?>
                        <div class="text-xs text-gray-500 dark:text-gray-400"><?= h($r['lot_location_desc']) ?></div>
                      <?php endif; ?>
                    <?php else: ?>
                      <span class="text-gray-500 dark:text-gray-400">-</span>
                    <?php endif; ?>
                  </td>
                  <td class="p-3">
                    <?php if ($isActive): ?>
                      <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300">Active</span>
                    <?php else: ?>
                      <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300">Inactive</span>
                    <?php endif; ?>
                  </td>
                  <td class="p-3"><?= h($r['created_at']) ?></td>
                  <td class="p-3">
                    <div class="flex items-center gap-2">
                      <button
                        type="button"
                        class="p-1.5 rounded-DEFAULT hover:bg-gray-100 dark:hover:bg-gray-800 text-secondary js-open-farmer-view"
                        title="View"
                        data-farmer-name="<?= h($r['farmer_name']) ?>"
                        data-association-name="<?= h($r['association_name']) ?>"
                        data-association-location="<?= h(trim(($r['municipality'] ?? '') . (!empty($r['province']) ? ', ' . $r['province'] : ''))) ?>"
                        data-phone="<?= h($r['phone']) ?>"
                        data-area-name="<?= h($r['area_name'] ?? '-') ?>"
                        data-lot-code="<?= h($r['lot_code'] ?? '') ?>"
                        data-lot-location="<?= h($r['lot_location_desc'] ?? '') ?>"
                        data-membership="<?= $isActive ? 'Active' : 'Inactive' ?>"
                        data-created-at="<?= h($r['created_at']) ?>"
                        data-edit-url="<?= h(base_path('farmers/edit.php?id=' . (int)$r['farmer_id'])) ?>"
                      >
                        <span class="material-symbols-outlined text-[20px] leading-none">visibility</span>
                      </button>

                      <a
                        class="p-1.5 rounded-DEFAULT hover:bg-gray-100 dark:hover:bg-gray-800 text-secondary"
                        title="Edit"
                        href="<?= base_path('farmers/edit.php?id=' . (int)$r['farmer_id']) ?>"
                      >
                        <span class="material-symbols-outlined text-[20px] leading-none">edit</span>
                      </a>

                      <?php if (!empty($r['user_id'])): ?>
                        <form method="POST" class="inline js-membership-form"
                              data-confirm-message="Are you sure you want to set this farmer to <?= $isActive ? 'Inactive' : 'Active' ?>?">
                          <input type="hidden" name="action" value="set_membership">
                          <input type="hidden" name="farmer_id" value="<?= (int)$r['farmer_id'] ?>">
                          <input type="hidden" name="set_status" value="<?= $isActive ? 'Inactive' : 'Active' ?>">
                          <input type="hidden" name="redirect_q" value="<?= h($q) ?>">
                          <input type="hidden" name="redirect_status" value="<?= h($statusF) ?>">
                          <input type="hidden" name="redirect_p" value="<?= (int)$pageNum ?>">
                          <button
                            class="p-1.5 rounded-DEFAULT hover:bg-gray-100 dark:hover:bg-gray-800 <?= $isActive ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' ?>"
                            title="<?= $isActive ? 'Set Inactive' : 'Set Active' ?>"
                          >
                            <span class="material-symbols-outlined text-[20px] leading-none"><?= $isActive ? 'toggle_on' : 'toggle_off' ?></span>
                          </button>
                        </form>
                      <?php else: ?>
                        <span class="p-1.5 text-gray-400" title="No linked account">
                          <span class="material-symbols-outlined text-[20px] leading-none">block</span>
                        </span>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$rows): ?>
                <tr><td class="p-3 text-gray-500" colspan="9">No farmers found.</td></tr>
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
        $baseParams = ['page' => 'farmers'];
        if ($q !== '') $baseParams['q'] = $q;
        if ($statusF !== '') $baseParams['status'] = $statusF;
      ?>
      <div class="mt-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-sm text-gray-500 dark:text-gray-400">
        <div>
          Showing <?= (int)$showFrom ?>-<?= (int)$showTo ?> of <?= (int)$total ?>
        </div>
        <div class="flex items-center gap-2">
          <?php if ($pageNum > 1): ?>
            <a
              href="<?= route('farmers', array_merge($baseParams, ['p' => $prevPage])) ?>"
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
              href="<?= route('farmers', array_merge($baseParams, ['p' => $nextPage])) ?>"
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

<div id="farmerViewModalBg" class="hidden fixed inset-0 bg-black/40 z-50"></div>
<div id="farmerViewModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
  <div class="w-full max-w-lg bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark rounded-lg p-6">
    <h3 class="text-lg font-black text-text-light dark:text-text-dark">Farmer Details</h3>
    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
      <div>
        <div class="text-xs text-gray-500 dark:text-gray-400">Farmer</div>
        <div id="fvFarmerName" class="font-semibold text-text-light dark:text-text-dark">-</div>
      </div>
      <div>
        <div class="text-xs text-gray-500 dark:text-gray-400">Association</div>
        <div id="fvAssociation" class="font-semibold text-text-light dark:text-text-dark">-</div>
      </div>
      <div>
        <div class="text-xs text-gray-500 dark:text-gray-400">Association Location</div>
        <div id="fvAssocLocation" class="font-semibold text-text-light dark:text-text-dark">-</div>
      </div>
      <div>
        <div class="text-xs text-gray-500 dark:text-gray-400">Phone</div>
        <div id="fvPhone" class="font-semibold text-text-light dark:text-text-dark">-</div>
      </div>
      <div>
        <div class="text-xs text-gray-500 dark:text-gray-400">Area</div>
        <div id="fvArea" class="font-semibold text-text-light dark:text-text-dark">-</div>
      </div>
      <div>
        <div class="text-xs text-gray-500 dark:text-gray-400">Membership</div>
        <div id="fvMembership" class="font-semibold text-text-light dark:text-text-dark">-</div>
      </div>
      <div class="sm:col-span-2">
        <div class="text-xs text-gray-500 dark:text-gray-400">Lot Location</div>
        <div id="fvLotLocation" class="font-semibold text-text-light dark:text-text-dark">-</div>
      </div>
      <div class="sm:col-span-2">
        <div class="text-xs text-gray-500 dark:text-gray-400">Created</div>
        <div id="fvCreatedAt" class="font-semibold text-text-light dark:text-text-dark">-</div>
      </div>
    </div>

    <div class="mt-5 flex justify-end gap-2">
      <button type="button" id="farmerViewClose"
              class="px-4 py-2 rounded-DEFAULT bg-gray-200 dark:bg-gray-700 text-text-light dark:text-text-dark font-semibold">
        Close
      </button>
      <a id="farmerViewEditLink"
         class="px-4 py-2 rounded-DEFAULT bg-secondary text-white font-semibold"
         href="#">
        Edit
      </a>
    </div>
  </div>
</div>

<div id="membershipModalBg" class="hidden fixed inset-0 bg-black/40 z-50"></div>
<div id="membershipModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
  <div class="w-full max-w-md bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark rounded-lg p-6">
    <h3 class="text-lg font-black text-text-light dark:text-text-dark">Confirm Membership Change</h3>
    <p id="membershipModalText" class="mt-2 text-sm text-gray-600 dark:text-gray-300">
      Are you sure you want to continue?
    </p>
    <div class="mt-5 flex justify-end gap-2">
      <button type="button" id="membershipCancel"
              class="px-4 py-2 rounded-DEFAULT bg-gray-200 dark:bg-gray-700 text-text-light dark:text-text-dark font-semibold">
        Cancel
      </button>
      <button type="button" id="membershipConfirm"
              class="px-4 py-2 rounded-DEFAULT bg-primary text-white font-bold">
        Confirm
      </button>
    </div>
  </div>
</div>

<script>
  (() => {
    const viewBg = document.getElementById('farmerViewModalBg');
    const viewModal = document.getElementById('farmerViewModal');
    const viewCloseBtn = document.getElementById('farmerViewClose');
    const viewEditLink = document.getElementById('farmerViewEditLink');
    const fvFarmerName = document.getElementById('fvFarmerName');
    const fvAssociation = document.getElementById('fvAssociation');
    const fvAssocLocation = document.getElementById('fvAssocLocation');
    const fvPhone = document.getElementById('fvPhone');
    const fvArea = document.getElementById('fvArea');
    const fvMembership = document.getElementById('fvMembership');
    const fvLotLocation = document.getElementById('fvLotLocation');
    const fvCreatedAt = document.getElementById('fvCreatedAt');

    const modalBg = document.getElementById('membershipModalBg');
    const modal = document.getElementById('membershipModal');
    const text = document.getElementById('membershipModalText');
    const btnCancel = document.getElementById('membershipCancel');
    const btnConfirm = document.getElementById('membershipConfirm');

    let pendingForm = null;

    const textOrDash = (v) => {
      const value = (v ?? '').trim();
      return value === '' ? '-' : value;
    };

    function openViewModal(trigger) {
      if (!viewBg || !viewModal || !trigger) return;

      const farmerName = trigger.getAttribute('data-farmer-name') || '';
      const association = trigger.getAttribute('data-association-name') || '';
      const assocLocation = trigger.getAttribute('data-association-location') || '';
      const phone = trigger.getAttribute('data-phone') || '';
      const area = trigger.getAttribute('data-area-name') || '';
      const lotCode = trigger.getAttribute('data-lot-code') || '';
      const lotLocation = trigger.getAttribute('data-lot-location') || '';
      const membership = trigger.getAttribute('data-membership') || '';
      const createdAt = trigger.getAttribute('data-created-at') || '';
      const editUrl = trigger.getAttribute('data-edit-url') || '#';

      if (fvFarmerName) fvFarmerName.textContent = textOrDash(farmerName);
      if (fvAssociation) fvAssociation.textContent = textOrDash(association);
      if (fvAssocLocation) fvAssocLocation.textContent = textOrDash(assocLocation);
      if (fvPhone) fvPhone.textContent = textOrDash(phone);
      if (fvArea) fvArea.textContent = textOrDash(area);
      if (fvMembership) fvMembership.textContent = textOrDash(membership);
      if (fvCreatedAt) fvCreatedAt.textContent = textOrDash(createdAt);
      if (fvLotLocation) {
        const lotText = [lotCode, lotLocation].filter((x) => (x ?? '').trim() !== '').join(' - ');
        fvLotLocation.textContent = textOrDash(lotText);
      }
      if (viewEditLink) {
        viewEditLink.href = editUrl;
      }

      viewBg.classList.remove('hidden');
      viewModal.classList.remove('hidden');
    }

    function closeViewModal() {
      viewBg?.classList.add('hidden');
      viewModal?.classList.add('hidden');
    }

    function openModal(message, form) {
      pendingForm = form;
      if (text) text.textContent = message || 'Are you sure you want to continue?';
      modalBg?.classList.remove('hidden');
      modal?.classList.remove('hidden');
    }

    function closeModal() {
      pendingForm = null;
      modalBg?.classList.add('hidden');
      modal?.classList.add('hidden');
    }

    document.addEventListener('click', (e) => {
      const openBtn = e.target.closest('.js-open-farmer-view');
      if (openBtn) {
        openViewModal(openBtn);
        return;
      }

      if (e.target === viewBg) {
        closeViewModal();
      }
    });

    viewCloseBtn?.addEventListener('click', closeViewModal);

    document.addEventListener('submit', (e) => {
      const form = e.target.closest('.js-membership-form');
      if (!form) return;
      e.preventDefault();
      const msg = form.getAttribute('data-confirm-message') || 'Are you sure you want to continue?';
      openModal(msg, form);
    });

    btnCancel?.addEventListener('click', closeModal);
    modalBg?.addEventListener('click', closeModal);
    btnConfirm?.addEventListener('click', () => {
      if (pendingForm) {
        closeViewModal();
        pendingForm.submit();
      }
    });
  })();

  (() => {
    const form = document.getElementById('farmersFilterForm');
    const searchInput = document.getElementById('farmersSearchInput');
    const statusInput = document.getElementById('farmersStatusInput');
    if (!form || !searchInput || !statusInput) return;

    const resultsContainerId = 'farmersResults';
    let timerId = null;
    let activeRequest = null;

    const buildUrl = (params) => {
      const url = new URL(form.action, window.location.origin);
      url.search = params.toString();
      return url;
    };

    const getParamsFromForm = (page = 1) => {
      const params = new URLSearchParams(new FormData(form));
      params.set('page', 'farmers');
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
          if (!response.ok) throw new Error('Farmers filter request failed');
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

    statusInput.addEventListener('change', () => {
      loadResults(getParamsFromForm(1));
    });

    form.addEventListener('submit', (event) => {
      event.preventDefault();
      loadResults(getParamsFromForm(1));
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
  })();
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
