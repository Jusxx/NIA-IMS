<?php
// includes/auth.php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

function normalize_role_name(string $role): string {
  return $role === 'Operations Staff' ? 'Irrigation Association' : $role;
}

function require_login(): void {
  if (empty($_SESSION['user']) || empty($_SESSION['user']['role'])) {
    header("Location: " . route('login'));
    exit;
  }
}

function role(): string {
  return normalize_role_name($_SESSION['user']['role'] ?? '');
}

function permissions(): array {
  return [
    'login' => ['*'],
    'logout' => ['*'],

    // existing
    'dashboard' => ['Administrator','Irrigation Association','Irrigation Technician','IMO','Monitoring','SWRFT','WRFO Gatekeeper','WRFO Scheduler'],

    // admin/staff modules
    'areas' => ['Administrator','Irrigation Association','IMO','SWRFT','WRFO Gatekeeper','WRFO Scheduler'],
    'schedules' => ['Administrator','Irrigation Association','IMO'],
    'tasks' => ['Administrator','Irrigation Association','Irrigation Technician'],
    'requests' => ['Administrator','Irrigation Association','Irrigation Technician','IMO','Monitoring','SWRFT','WRFO Gatekeeper','WRFO Scheduler'],
    'reports' => ['Administrator','Irrigation Association','IMO','Monitoring'],
    'users' => ['Administrator'],
    'logs' => ['Administrator','IMO','Monitoring'],
    'sms_logs' => ['Administrator'],
    //'farmers' => ['Administrator','Irrigation Association','IMO'],
    'farmers' => ['Administrator','IMO'],
    'forms' => ['Administrator','Irrigation Association','IMO'],
    'forms_print' => ['Administrator','Irrigation Association','IMO'],
    'force_password' => ['Administrator','Irrigation Association','Irrigation Technician','IMO','Monitoring','Farmer','SWRFT','WRFO Gatekeeper','WRFO Scheduler'],

    // farmer pages
    'farmer_dashboard' => ['Farmer'],
    'my_requests'      => ['Farmer'],
    'my_schedule'      => ['Farmer'],
    'farmer_profile'   => ['Farmer'],
    'announcements'    => ['Farmer','Administrator','Irrigation Association','IMO','Monitoring'],
    'profile'          => ['Farmer'],

  ];
}





function can_page(string $page): bool {
  $p = permissions();
  if (!isset($p[$page])) return false;
  if ($p[$page] === ['*']) return true;
  $allowed = array_map('normalize_role_name', $p[$page]);
  return in_array(role(), $allowed, true);
}

function require_page(string $page): void {
  $p = permissions();

  if (!isset($p[$page])) {
    http_response_code(404);
    echo "404 Not Found";
    exit;
  }

  if ($p[$page] !== ['*']) {
    require_login();
    if (!can_page($page)) {
      http_response_code(403);
      echo "403 Forbidden";
      exit;
    }
  }
}

function require_roles(array $roles): void {
  require_login();
  $allowed = array_map('normalize_role_name', $roles);
  if (!in_array(role(), $allowed, true)) {
    http_response_code(403);
    echo "403 Forbidden";
    exit;
  }
}
