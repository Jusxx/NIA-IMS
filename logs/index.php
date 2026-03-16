<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_roles(['Administrator','IMO','Monitoring']);
require_once __DIR__ . '/../includes/config.php';

$active = 'logs';
$topTitle = 'System Logs';

// ✅ Pagination
$perPage = 10;
$page = (int)($_GET['p'] ?? 1);
if ($page < 1) $page = 1;
$offset = ($page - 1) * $perPage;

// ✅ Total rows
$total = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS c FROM system_logs");
$stmt->execute();
$total = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
$stmt->close();

$totalPages = max(1, (int)ceil($total / $perPage));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;

// ✅ Fetch rows for current page
$rows = [];
$stmt = $conn->prepare("
  SELECT l.*, u.fullname, u.username, u.role
  FROM system_logs l
  LEFT JOIN users u ON u.user_id = l.user_id
  ORDER BY l.created_at DESC
  LIMIT ? OFFSET ?
");
$stmt->bind_param("ii", $perPage, $offset);
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
      <div class="bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark rounded-lg overflow-hidden">
        <div class="p-4 space-y-3 md:hidden">
          <?php foreach($rows as $r): ?>
            <div class="rounded-xl border border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark p-3">
              <div class="text-xs text-gray-500 dark:text-gray-400"><?= h($r['created_at']) ?></div>
              <div class="mt-1 font-semibold text-text-light dark:text-text-dark"><?= h($r['fullname'] ?: ($r['username'] ?: 'Unknown User')) ?></div>
              <div class="text-xs text-gray-500 dark:text-gray-400"><?= h(role_label($r['role'] ?? '-')) ?></div>
              <div class="mt-2 text-sm font-semibold text-text-light dark:text-text-dark"><?= h($r['action']) ?></div>
              <div class="mt-1 text-sm text-text-light dark:text-text-dark break-words"><?= h($r['description']) ?></div>
            </div>
          <?php endforeach; ?>
          <?php if(!$rows): ?>
            <div class="p-3 text-gray-500">No logs found.</div>
          <?php endif; ?>
        </div>

        <div class="overflow-x-auto hidden md:block">
          <table class="w-full text-left">
            <thead>
              <tr class="text-xs text-gray-500 dark:text-gray-400 uppercase">
                <th class="p-3">Time</th>
                <th class="p-3">User</th>
                <th class="p-3">Role</th>
                <th class="p-3">Action</th>
                <th class="p-3">Description</th>
              </tr>
            </thead>

            <tbody class="divide-y divide-border-light dark:divide-border-dark">
              <?php foreach($rows as $r): ?>
                <tr class="text-sm text-text-light dark:text-text-dark">
                  <td class="p-3 whitespace-nowrap"><?= h($r['created_at']) ?></td>
                  <td class="p-3 font-semibold">
                    <?= h($r['fullname'] ?: ($r['username'] ?: 'Unknown User')) ?>
                  </td>
                  <td class="p-3"><?= h(role_label($r['role'] ?? '—')) ?></td>
                  <td class="p-3 font-semibold"><?= h($r['action']) ?></td>
                  <td class="p-3"><?= h($r['description']) ?></td>
                </tr>
              <?php endforeach; ?>

              <?php if(!$rows): ?>
                <tr><td class="p-3 text-gray-500" colspan="5">No logs found.</td></tr>
              <?php endif; ?>
            </tbody>

          </table>
        </div>

        <!-- ✅ Pagination footer -->
        <div class="flex items-center justify-between gap-3 p-4 border-t border-border-light dark:border-border-dark text-sm">
          <div class="text-gray-500 dark:text-gray-400">
            Showing
            <span class="font-semibold text-text-light dark:text-text-dark">
              <?= $total ? ($offset + 1) : 0 ?>
            </span>
            -
            <span class="font-semibold text-text-light dark:text-text-dark">
              <?= min($offset + $perPage, $total) ?>
            </span>
            of
            <span class="font-semibold text-text-light dark:text-text-dark">
              <?= $total ?>
            </span>
          </div>

          <div class="flex items-center gap-2">
            <?php
              $prevDisabled = ($page <= 1);
              $nextDisabled = ($page >= $totalPages);

              $prevUrl = route('logs', ['p' => max(1, $page - 1)]);
              $nextUrl = route('logs', ['p' => min($totalPages, $page + 1)]);
            ?>

            <a href="<?= $prevUrl ?>"
               class="px-3 py-2 rounded-DEFAULT border border-border-light dark:border-border-dark
                      <?= $prevDisabled ? 'opacity-50 pointer-events-none' : 'hover:bg-gray-100 dark:hover:bg-gray-800' ?>">
              Prev
            </a>

            <span class="text-gray-500 dark:text-gray-400">
              Page <span class="font-semibold text-text-light dark:text-text-dark"><?= $page ?></span>
              / <span class="font-semibold text-text-light dark:text-text-dark"><?= $totalPages ?></span>
            </span>

            <a href="<?= $nextUrl ?>"
               class="px-3 py-2 rounded-DEFAULT border border-border-light dark:border-border-dark
                      <?= $nextDisabled ? 'opacity-50 pointer-events-none' : 'hover:bg-gray-100 dark:hover:bg-gray-800' ?>">
              Next
            </a>
          </div>
        </div>

      </div>
    </main>

  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
