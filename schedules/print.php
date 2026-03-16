<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_roles(['Administrator','Operations Staff','IMO']);
require_once __DIR__ . '/../includes/config.php';

$active   = 'schedules';
$topTitle = 'Print Schedules';

$q = trim($_GET['q'] ?? '');
$dateFrom = trim($_GET['from'] ?? '');
$dateTo   = trim($_GET['to'] ?? '');
$statusF  = trim($_GET['status'] ?? '');

$params = [];
$types  = '';
$where  = "WHERE 1=1";

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

$sql = "
  SELECT s.schedule_id, s.schedule_date, s.start_time, s.end_time, s.status,
         sa.area_name, sa.municipality, sa.province
  FROM irrigation_schedules s
  LEFT JOIN service_areas sa ON sa.service_area_id = s.service_area_id
  $where
  ORDER BY s.schedule_date ASC, s.start_time ASC
";

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$rows = filter_focus_service_area_rows($rows);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= h($topTitle) ?></title>

  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700;900&display=swap" rel="stylesheet"/>

  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            "primary": "#28a745",
            "secondary": "#007bff",
            "warning": "#ffc107",
            "border-light": "#DEE2E6",
            "text-light": "#343A40",
          },
          fontFamily: { "display": ["Public Sans", "sans-serif"] },
          borderRadius: { "DEFAULT": "0.25rem" },
        }
      }
    }
  </script>

  <style>
    body { font-family: "Public Sans", sans-serif; }
    @media print {
      .no-print { display: none !important; }
      body { background: white !important; }
    }
  </style>
</head>
<body class="bg-white text-text-light p-6">

  <div class="max-w-5xl mx-auto">
    <div class="flex items-start justify-between gap-4 no-print">
      <div>
        <h1 class="text-2xl font-black">Schedules Print View</h1>
        <p class="text-sm text-gray-600 mt-1">NIA IMS - Irrigation Management</p>
      </div>

      <div class="flex gap-2">
        <button onclick="window.print()" class="px-4 py-2 rounded-DEFAULT bg-primary text-white font-bold">
          Print
        </button>
        <button onclick="window.close()" class="px-4 py-2 rounded-DEFAULT bg-gray-200 text-gray-800 font-semibold">
          Close
        </button>
      </div>
    </div>

    <div class="mt-6 border border-border-light rounded-lg overflow-hidden">
      <table class="w-full text-left">
        <thead class="bg-gray-50">
          <tr class="text-xs text-gray-600 uppercase">
            <th class="p-3 font-semibold">Date</th>
            <th class="p-3 font-semibold">Time</th>
            <th class="p-3 font-semibold">Area / Location</th>
            <th class="p-3 font-semibold">Status</th>
          </tr>
        </thead>
        <tbody class="divide-y">
          <?php foreach ($rows as $r): ?>
            <tr class="text-sm">
              <td class="p-3"><?= h($r['schedule_date']) ?></td>
              <td class="p-3"><?= h($r['start_time']) ?> - <?= h($r['end_time']) ?></td>
              <td class="p-3">
                <?= h($r['area_name'] ?? '—') ?>
                <?= !empty($r['municipality']) ? ", " . h($r['municipality']) : "" ?>
                <?= !empty($r['province']) ? ", " . h($r['province']) : "" ?>
              </td>
              <td class="p-3"><?= h($r['status']) ?></td>
            </tr>
          <?php endforeach; ?>

          <?php if (!$rows): ?>
            <tr><td class="p-3 text-gray-500" colspan="4">No schedules found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <p class="text-xs text-gray-500 mt-4">
      Generated at: <?= date('Y-m-d H:i:s') ?>
    </p>
  </div>

</body>
</html>
