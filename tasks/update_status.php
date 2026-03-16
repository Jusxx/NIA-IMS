<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_roles(['Administrator','Operations Staff','Irrigation Technician']);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/sms.php';

$active='tasks';
$topTitle='Task Logging';

$id=(int)($_GET['id']??0);
$stmt=$conn->prepare("SELECT * FROM tasks WHERE task_id=?");
$stmt->bind_param("i",$id);
$stmt->execute();
$row=$stmt->get_result()->fetch_assoc();
$stmt->close();
if(!$row){ http_response_code(404); exit("Task not found"); }

$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $status=trim($_POST['status']??'Due');
  $started=$_POST['started_at']??null;
  $ended=$_POST['ended_at']??null;
  $remarks=trim($_POST['remarks']??'');
  $issues=trim($_POST['issues']??'');
  $previousStatus = (string)($row['status'] ?? '');
  $previousStarted = (string)($row['started_at'] ?? '');
  $previousEnded = (string)($row['ended_at'] ?? '');

  $allowed=['Due','In Progress','Completed','Missed'];
  if(!in_array($status,$allowed,true)) $error="Invalid status.";
  else{
    $stmt=$conn->prepare("UPDATE tasks SET status=?, started_at=?, ended_at=?, remarks=?, issues=? WHERE task_id=?");
    $stmt->bind_param("sssssi",$status,$started,$ended,$remarks,$issues,$id);
    $stmt->execute();
    $stmt->close();

    if (in_array($status, ['In Progress', 'Completed'], true)) {
      $stmt = $conn->prepare("
        SELECT s.request_id
        FROM tasks t
        JOIN irrigation_schedules s ON s.schedule_id = t.schedule_id
        WHERE t.task_id = ?
        LIMIT 1
      ");
      $stmt->bind_param("i", $id);
      $stmt->execute();
      $req = $stmt->get_result()->fetch_assoc();
      $stmt->close();

      $requestId = (int)($req['request_id'] ?? 0);
      if ($requestId > 0) {
        $requestStage = $status;
        $stmt = $conn->prepare("
          UPDATE farmer_requests
          SET request_stage = ?,
              status = IF(? = 'Completed', 'Completed', status)
          WHERE request_id = ?
        ");
        $stmt->bind_param("ssi", $requestStage, $requestStage, $requestId);
        $stmt->execute();
        $stmt->close();
      }
    }

    $desc = "Task #{$id} saved via Task Logging | Status: {$previousStatus} -> {$status}"
      . " | Start: " . ($started ?: 'NULL')
      . " | End: " . ($ended ?: 'NULL');
    if ($previousStarted !== (string)$started || $previousEnded !== (string)$ended) {
      $desc .= " | Time fields updated";
    }
    if ($remarks !== '') $desc .= " | Remarks updated";
    if ($issues !== '') $desc .= " | Issues updated";
    system_log($conn, "Task Log Updated", $desc);

    if ($previousStatus !== $status) {
      send_task_status_sms_if_needed($conn, $id, $status);
    }
    header("Location: ".base_path("tasks/view.php?id=".$id)); exit;
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
        <?php if($error): ?><div class="p-3 rounded bg-red-100 text-red-700"><?=h($error)?></div><?php endif; ?>

        <form method="POST" class="mt-4 space-y-4">
          <div>
            <label class="text-sm">Status</label>
            <select name="status" class="mt-1 w-full rounded-DEFAULT border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
              <?php foreach(['Due','In Progress','Completed','Missed'] as $s): ?>
                <option value="<?=$s?>" <?=$row['status']===$s?'selected':''?>><?=$s?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
              <label class="text-sm">Start time</label>
              <input type="datetime-local" name="started_at" value="<?=h($row['started_at'] ? date('Y-m-d\TH:i', strtotime($row['started_at'])) : '')?>"
                     class="mt-1 w-full rounded-DEFAULT border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
            </div>
            <div>
              <label class="text-sm">End time</label>
              <input type="datetime-local" name="ended_at" value="<?=h($row['ended_at'] ? date('Y-m-d\TH:i', strtotime($row['ended_at'])) : '')?>"
                     class="mt-1 w-full rounded-DEFAULT border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
            </div>
          </div>

          <div>
            <label class="text-sm">Remarks</label>
            <input name="remarks" value="<?=h($row['remarks'])?>" class="mt-1 w-full rounded-DEFAULT border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
          </div>

          <div>
            <label class="text-sm">Issues</label>
            <textarea name="issues" rows="4" class="mt-1 w-full rounded-DEFAULT border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark"><?=h($row['issues'])?></textarea>
          </div>

          <div class="flex gap-2">
            <button class="px-4 py-2 rounded-DEFAULT bg-primary text-white font-bold">Save</button>
            <a class="px-4 py-2 rounded-DEFAULT bg-gray-200 dark:bg-gray-700" href="<?= base_path('tasks/view.php?id='.$id) ?>">Cancel</a>
          </div>
        </form>
      </div>
    </main>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
