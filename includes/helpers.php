<?php
// includes/helpers.php

function h($v): string {
  return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function role_label(?string $role): string {
  $r = trim((string)$role);
  return $r === 'Operations Staff' ? 'Irrigation Association' : $r;
}

function app_base_root(): string {
  static $cached = null;
  if ($cached !== null) return $cached;

  $envRoot = trim((string)(getenv('APP_BASE_PATH') ?: ''));
  if ($envRoot !== '') {
    $cached = '/' . trim($envRoot, '/');
    return $cached === '/' ? '' : $cached;
  }

  $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
  $root = '';

  $publicPos = strpos($script, '/public/');
  if ($publicPos !== false) {
    $root = substr($script, 0, $publicPos);
  } else {
    $dir = rtrim(str_replace('\\', '/', dirname($script)), '/');
    $moduleSuffixes = [
      '/announcements', '/areas', '/farmers', '/forms', '/includes', '/logs',
      '/pages', '/public', '/reports', '/requests', '/schedules', '/sms_logs',
      '/tasks', '/tools', '/users'
    ];
    foreach ($moduleSuffixes as $suffix) {
      if ($dir !== '' && str_ends_with($dir, $suffix)) {
        $dir = substr($dir, 0, -strlen($suffix));
        break;
      }
    }
    $root = $dir;
  }

  $root = trim((string)$root);
  if ($root === '' || $root === '.') {
    $cached = '';
    return $cached;
  }

  $cached = '/' . trim($root, '/');
  return $cached;
}

function base_path(string $path = ''): string {
  $root = app_base_root();
  return ($root === '' ? '' : rtrim($root, '/')) . '/' . ltrim($path, '/');
}

function route(string $page, array $params = []): string {
  $url = base_path("public/index.php?page=" . urlencode($page));
  if ($params) $url .= '&' . http_build_query($params);
  return $url;
}

function focus_irrigation_targets(): array {
  return [
    ['area_name' => 'Bo. 2', 'municipality' => 'Koronadal City', 'province' => 'South Cotabato'],
    ['area_name' => 'Namnama', 'municipality' => 'Koronadal City', 'province' => 'South Cotabato'],
    ['area_name' => 'Lutayan', 'municipality' => 'Lutayan', 'province' => 'Sultan Kudarat'],
  ];
}

function normalize_focus_token(?string $value): string {
  $v = strtolower(trim((string)$value));
  $v = preg_replace('/[^a-z0-9]+/', '', $v);
  return $v ?? '';
}

function is_focus_service_area(?string $areaName, ?string $municipality, ?string $province): bool {
  $area = normalize_focus_token($areaName);
  $mun = normalize_focus_token($municipality);
  $prov = normalize_focus_token($province);
  $munHasKoronadal = str_contains($mun, 'koronadal');
  $munHasLutayan = str_contains($mun, 'lutayan');
  $provHasSultanKudarat = str_contains($prov, 'sultankudarat');

  // Bo. 2 / Namnama in Koronadal City
  if ($munHasKoronadal && in_array($area, ['bo2', 'barangay2', 'namnama'], true)) {
    return true;
  }

  // Any Lutayan irrigation area.
  // Kept tolerant because some deployments store municipality/province with mixed formats.
  if ($munHasLutayan || str_starts_with($area, 'lutayan') || ($provHasSultanKudarat && $munHasLutayan)) {
    return true;
  }

  return false;
}

function filter_focus_service_area_rows(
  array $rows,
  string $areaKey = 'area_name',
  string $municipalityKey = 'municipality',
  string $provinceKey = 'province'
): array {
  return array_values(array_filter($rows, static function(array $row) use ($areaKey, $municipalityKey, $provinceKey): bool {
    return is_focus_service_area(
      $row[$areaKey] ?? null,
      $row[$municipalityKey] ?? null,
      $row[$provinceKey] ?? null
    );
  }));
}

function badge(string $status): array {
  $s = strtolower(trim((string)$status));
  return match ($s) {
    'pending' => ['bg-warning/20 text-warning', 'Pending'],
    'approved' => ['bg-primary/20 text-primary', 'Approved'],
    'rejected' => ['bg-red-200 text-red-700 dark:bg-red-500/20 dark:text-red-200', 'Rejected'],
    'active' => ['bg-secondary/20 text-secondary', 'Active'],
    'completed' => ['bg-primary/20 text-primary', 'Completed'],
    'cancelled' => ['bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-200', 'Cancelled'],
    'due' => ['bg-warning/20 text-warning', 'Due'],
    'in progress' => ['bg-secondary/20 text-secondary', 'In Progress'],
    'missed' => ['bg-red-200 text-red-700 dark:bg-red-500/20 dark:text-red-200', 'Missed'],
    default => ['bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-200', ucfirst($s ?: 'Unknown')],
  };
}

function time_ago(?string $dt): string {
  if (!$dt) return '';
  $ts = strtotime($dt);
  if (!$ts) return '';
  $diff = time() - $ts;
  if ($diff < 60) return $diff . " seconds ago";
  if ($diff < 3600) return floor($diff/60) . " minutes ago";
  if ($diff < 86400) return floor($diff/3600) . " hours ago";
  return floor($diff/86400) . " days ago";
}
function system_log(mysqli $conn, string $action, string $description): void {
  $uid = (int)($_SESSION['user']['user_id'] ?? 0);

  $stmt = $conn->prepare("INSERT INTO system_logs(user_id, action, description) VALUES(?,?,?)");
  $stmt->bind_param("iss", $uid, $action, $description);
  $stmt->execute();
  $stmt->close();
}
