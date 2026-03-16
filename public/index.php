<?php
session_start();

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config.php';

if (!empty($_SESSION['user']['user_id'])) {
  $uid = (int)($_SESSION['user']['user_id'] ?? 0);
  if ($uid > 0) {
    $stmt = $conn->prepare("SELECT role, is_active FROM users WHERE user_id=? LIMIT 1");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$u || (int)($u['is_active'] ?? 0) !== 1) {
      $wasFarmer = normalize_role_name((string)($_SESSION['user']['role'] ?? '')) === 'Farmer';
      unset($_SESSION['user']);

      if ($wasFarmer) {
        $_SESSION['auth_notice'] = "Your membership is currently inactive. Please visit the NIA office for verification and account reactivation.";
      } else {
        $_SESSION['auth_notice'] = "Your account is inactive. Please contact the system administrator.";
      }

      header("Location: " . route('login'));
      exit;
    }
  }
}

$page = $_GET['page'] ?? 'login';

$routes = [
  'login'     => __DIR__ . '/../pages/login.php',
  'logout'    => __DIR__ . '/../pages/logout.php',
  'dashboard' => __DIR__ . '/../pages/dashboard.php',

  'areas'     => __DIR__ . '/../areas/index.php',
  'schedules' => __DIR__ . '/../schedules/index.php',
  'tasks'     => __DIR__ . '/../tasks/index.php',
  'requests'  => __DIR__ . '/../requests/index.php',
  'reports'   => __DIR__ . '/../reports/index.php',
  'users'     => __DIR__ . '/../users/index.php',
  'logs'      => __DIR__ . '/../logs/index.php',
  'sms_logs'  => __DIR__ . '/../sms_logs/index.php',
  'farmers'   => __DIR__ . '/../farmers/index.php',
  'farmer_requests' => __DIR__ . '/../farmers/requests.php',
  'farmer_dashboard' => __DIR__ . '/../farmers/dashboard.php',
  'my_requests'      => __DIR__ . '/../farmers/requests.php',
  'my_schedule'      => __DIR__ . '/../farmers/schedule.php',
  'profile'          => __DIR__ . '/../farmers/profile.php',
  'announcements'    => __DIR__ . '/../farmers/announcements.php',
  'forms'            => __DIR__ . '/../forms/index.php',
  'forms_print'      => __DIR__ . '/../forms/print.php',
  'force_password'   => __DIR__ . '/../pages/force_password.php',
  
];

if (!isset($routes[$page])) {
  $page = (role() === 'Farmer') ? 'farmer_dashboard' : 'dashboard';
}


require_page($page);
require $routes[$page];
