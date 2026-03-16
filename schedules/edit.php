<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_roles(['Administrator','Operations Staff','IMO']);
require_once __DIR__ . '/../includes/config.php';

$active   = 'schedules';
$topTitle = 'Edit Schedule';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  http_response_code(400);
  exit("Invalid schedule id");
}

// Load areas
$areas = [];
$stmt = $conn->prepare("SELECT service_area_id, area_name, municipality, province FROM service_areas ORDER BY area_name ASC");
$stmt->execute();
$areas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$areas = filter_focus_service_area_rows($areas);
$allowedAreaIds = array_map('intval', array_column($areas, 'service_area_id'));

// Load schedule
$stmt = $conn->prepare("SELECT * FROM irrigation_schedules WHERE schedule_id=? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
  http_response_code(404);
  exit("Schedule not found");
}

$error = '';

$service_area_id = (int)$row['service_area_id'];
if (!in_array($service_area_id, $allowedAreaIds, true)) {
  http_response_code(403);
  exit("This schedule is outside the focused irrigation locations.");
}
$schedule_date   = $row['schedule_date'] ?? '';
$start_time      = $row['start_time'] ?? '';
$end_time        = $row['end_time'] ?? '';
$status          = $row['status'] ?? 'Active';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $service_area_id = (int)($_POST['service_area_id'] ?? 0);
  $schedule_date = trim($_POST['schedule_date'] ?? '');
  $start_time = trim($_POST['start_time'] ?? '');
  $end_time = trim($_POST['end_time'] ?? '');
  $status = trim($_POST['status'] ?? 'Active');

  if ($service_area_id <= 0) {
    $error = "Please select an Area.";
  } elseif (!in_array($service_area_id, $allowedAreaIds, true)) {
    $error = "Please select one of the focused irrigation areas only.";
  } elseif ($schedule_date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $schedule_date)) {
    $error = "Invalid schedule date.";
  } elseif ($start_time === '' || $end_time === '') {
    $error = "Start time and End time are required.";
  } elseif (strtotime($start_time) >= strtotime($end_time)) {
    $error = "End time must be later than Start time.";
  } elseif (!in_array($status, ['Active','Completed','Cancelled'], true)) {
    $error = "Invalid status.";
  } else {

    // Overlap check excluding this schedule
    $stmt = $conn->prepare("
      SELECT COUNT(*) c
      FROM irrigation_schedules
      WHERE service_area_id = ?
        AND schedule_date = ?
        AND schedule_id <> ?
        AND NOT (end_time <= ? OR start_time >= ?)
        AND status <> 'Cancelled'
    ");
    $stmt->bind_param("isiss", $service_area_id, $schedule_date, $id, $start_time, $end_time);
    $stmt->execute();
    $conflict = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
    $stmt->close();

    if ($conflict > 0) {
      $error = "Conflict: schedule overlaps an existing one.";
    } else {

      $stmt = $conn->prepare("
        UPDATE irrigation_schedules
        SET service_area_id=?, schedule_date=?, start_time=?, end_time=?, status=?
        WHERE schedule_id=?
      ");
      $stmt->bind_param("issssi", $service_area_id, $schedule_date, $start_time, $end_time, $status, $id);

      if ($stmt->execute()) {
        $stmt->close();
        header("Location: " . route('schedules'));
        exit;
      } else {
        $error = "Update failed: " . $stmt->error;
        $stmt->close();
      }
    }
  }
}

include __DIR__ . '/../includes/head.php';
?>
<div class="flex min-h-screen w-full">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="flex-1 flex flex-col">
    <?php include __DIR__ . '/../includes/topbar.php'; ?>

    <main class="p-6 lg:p-8 flex-1">
      <div class="max-w-2xl bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark rounded-lg p-6">

        <div class="flex items-center justify-between gap-3">
          <div>
            <h1 class="text-xl font-black text-text-light dark:text-text-dark">Edit Schedule</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Update schedule #<?= (int)$id ?></p>
          </div>
          <a class="px-3 py-2 rounded-DEFAULT bg-gray-200 dark:bg-gray-700 text-text-light dark:text-text-dark"
             href="<?= route('schedules') ?>">Back</a>
        </div>

        <?php if ($error): ?>
          <div class="mt-4 p-3 rounded bg-red-100 text-red-700"><?= h($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="mt-4 space-y-4">

          <div>
            <label class="text-sm text-text-light dark:text-text-dark">Area *</label>
            <select name="service_area_id" required
                    class="mt-1 w-full rounded-DEFAULT border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
              <option value="0">Select Area</option>
              <?php foreach ($areas as $a): ?>
                <option value="<?= (int)$a['service_area_id'] ?>"
                  <?= $service_area_id === (int)$a['service_area_id'] ? 'selected' : '' ?>>
                  <?= h($a['area_name']) ?><?= $a['municipality'] ? " - ".h($a['municipality']) : "" ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
              <label class="text-sm text-text-light dark:text-text-dark">Date *</label>
              <input type="date" name="schedule_date" required value="<?= h($schedule_date) ?>"
                     class="mt-1 w-full rounded-DEFAULT border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
            </div>
            <div>
              <label class="text-sm text-text-light dark:text-text-dark">Start *</label>
              <input type="time" name="start_time" required value="<?= h($start_time) ?>"
                     class="mt-1 w-full rounded-DEFAULT border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
            </div>
            <div>
              <label class="text-sm text-text-light dark:text-text-dark">End *</label>
              <input type="time" name="end_time" required value="<?= h($end_time) ?>"
                     class="mt-1 w-full rounded-DEFAULT border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
            </div>
          </div>

          <div>
            <label class="text-sm text-text-light dark:text-text-dark">Status</label>
            <select name="status"
                    class="mt-1 w-full rounded-DEFAULT border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
              <?php foreach (['Active','Completed','Cancelled'] as $s): ?>
                <option value="<?= $s ?>" <?= $status===$s?'selected':'' ?>><?= $s ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="flex gap-2">
            <button class="px-4 py-2 rounded-DEFAULT bg-primary text-white font-bold">Save Changes</button>
            <a class="px-4 py-2 rounded-DEFAULT bg-gray-200 dark:bg-gray-700 text-text-light dark:text-text-dark"
               href="<?= route('schedules') ?>">Cancel</a>
          </div>

        </form>

      </div>
    </main>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
