<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_roles(['Irrigation Technician']);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/sms.php';

$active = 'tasks';
$topTitle = 'My Assigned Tasks';

$uid = (int)($_SESSION['user']['user_id'] ?? 0);
$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $task_id = (int)($_POST['task_id'] ?? 0);
  $newStatus = trim($_POST['status'] ?? '');
  $completionRemarks = trim((string)($_POST['completion_remarks'] ?? ''));
  $allowed = ['In Progress','Completed'];

  if ($task_id > 0 && in_array($newStatus, $allowed, true)) {
    if ($newStatus === 'Completed' && $completionRemarks === '') {
      $_SESSION['flash'] = "Remarks are required when completing a task.";
      header("Location: " . route('technician_tasks'));
      exit;
    }

    $conn->begin_transaction();
    try {
      $stmt = $conn->prepare("SELECT status, remarks FROM tasks WHERE task_id=? AND assigned_user_id=? LIMIT 1");
      $stmt->bind_param("ii", $task_id, $uid);
      $stmt->execute();
      $currentTask = $stmt->get_result()->fetch_assoc();
      $stmt->close();
      $previousStatus = (string)($currentTask['status'] ?? '');
      $existingRemarks = trim((string)($currentTask['remarks'] ?? ''));

      // Update task
      if ($newStatus === 'In Progress') {
        $stmt = $conn->prepare("UPDATE tasks SET status='In Progress', started_at=NOW() WHERE task_id=? AND assigned_user_id=?");
        $stmt->bind_param("ii", $task_id, $uid);
      } else {
        $updatedRemarks = $completionRemarks !== ''
          ? ($existingRemarks !== '' ? ($existingRemarks . PHP_EOL . "Completion remarks: " . $completionRemarks) : ("Completion remarks: " . $completionRemarks))
          : $existingRemarks;
        $stmt = $conn->prepare("UPDATE tasks SET status='Completed', ended_at=NOW(), remarks=? WHERE task_id=? AND assigned_user_id=?");
        $stmt->bind_param("sii", $updatedRemarks, $task_id, $uid);
      }
      $stmt->execute();
      $stmt->close();

      // Update linked request_stage (if schedule has request_id)
      $stmt = $conn->prepare("
        SELECT s.request_id
        FROM tasks t
        JOIN irrigation_schedules s ON s.schedule_id = t.schedule_id
        WHERE t.task_id=? AND t.assigned_user_id=?
        LIMIT 1
      ");
      $stmt->bind_param("ii", $task_id, $uid);
      $stmt->execute();
      $req = $stmt->get_result()->fetch_assoc();
      $stmt->close();

      if (!empty($req['request_id'])) {
        $rid = (int)$req['request_id'];
        $stage = ($newStatus === 'In Progress') ? 'In Progress' : 'Completed';

        $stmt = $conn->prepare("UPDATE farmer_requests SET request_stage=?, status=IF(?='Completed','Completed',status) WHERE request_id=?");
        $stmt->bind_param("ssi", $stage, $stage, $rid);
        $stmt->execute();
        $stmt->close();
      }

      // Log
      $action = "Task Updated";
      $desc = "Technician updated task #{$task_id} to {$newStatus}";
      $stmt = $conn->prepare("INSERT INTO system_logs(user_id, action, description) VALUES(?,?,?)");
      $stmt->bind_param("iss", $uid, $action, $desc);
      $stmt->execute();
      $stmt->close();

      if ($previousStatus !== $newStatus) {
        send_task_status_sms_if_needed($conn, $task_id, $newStatus);
      }

      $conn->commit();
      $_SESSION['flash'] = "Task #{$task_id} updated to {$newStatus}.";
    } catch (Exception $e) {
      $conn->rollback();
      $_SESSION['flash'] = "Error: " . $e->getMessage();
    }
  }

  header("Location: " . route('technician_tasks'));
  exit;
}

// My tasks
$rows = [];
$stmt = $conn->prepare("
  SELECT t.task_id, t.status AS task_status, t.created_at,
         s.schedule_date, s.start_time, s.end_time, s.request_id,
         sa.area_name, sa.municipality,
         f.farmer_name
  FROM tasks t
  JOIN irrigation_schedules s ON s.schedule_id = t.schedule_id
  JOIN service_areas sa ON sa.service_area_id = s.service_area_id
  LEFT JOIN farmer_requests r ON r.request_id = s.request_id
  LEFT JOIN farmers f ON f.farmer_id = r.farmer_id
  WHERE t.assigned_user_id=?
  ORDER BY s.schedule_date DESC, s.start_time DESC
  LIMIT 200
");
$stmt->bind_param("i", $uid);
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
      <div class="max-w-6xl bg-card-light dark:bg-card-dark border rounded-lg p-6">

        <h1 class="text-xl font-black">My Assigned Tasks</h1>

        <?php if ($flash): ?>
          <div class="mt-4 p-3 rounded bg-primary/10 border"><?= h($flash) ?></div>
        <?php endif; ?>

        <div class="mt-4 space-y-3 md:hidden">
          <?php foreach($rows as $r): ?>
            <div class="rounded-xl border border-border-light dark:border-border-dark bg-card-light dark:bg-card-dark p-4 text-sm">
              <div class="flex items-start justify-between gap-2">
                <div>
                  <div class="font-semibold">#<?= (int)$r['task_id'] ?></div>
                  <div class="text-xs text-gray-500"><?= h($r['task_status']) ?></div>
                </div>
                <div class="text-right">
                  <div class="font-semibold"><?= h($r['schedule_date']) ?></div>
                  <div class="text-xs text-gray-500"><?= h($r['start_time']) ?> - <?= h($r['end_time']) ?></div>
                </div>
              </div>
              <div class="mt-3 space-y-1">
                <div><span class="text-gray-500">Area:</span> <?= h($r['area_name']) ?> <span class="text-xs text-gray-500">(<?= h($r['municipality']) ?>)</span></div>
                <div><span class="text-gray-500">Farmer:</span> <?= h($r['farmer_name'] ?? '-') ?></div>
              </div>
              <div class="mt-3 flex flex-wrap gap-2">
                <?php if (!empty($r['request_id'])): ?>
                  <a class="px-3 py-2 rounded bg-gray-200 dark:bg-gray-700"
                     href="<?= route('request_view', ['id' => (int)$r['request_id']]) ?>">
                    View Request
                  </a>
                <?php endif; ?>
                <form method="POST" class="flex flex-wrap gap-2 js-tech-task-update-form">
                  <input type="hidden" name="task_id" value="<?= (int)$r['task_id'] ?>">
                  <input type="hidden" name="completion_remarks" class="js-completion-remarks">
                  <select name="status" class="rounded border px-2 py-1 bg-background-light dark:bg-background-dark">
                    <option value="In Progress">In Progress</option>
                    <option value="Completed">Completed</option>
                  </select>
                  <button class="px-3 py-2 rounded bg-primary text-white font-bold">Update</button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
          <?php if (!$rows): ?>
            <div class="p-3 text-gray-500 rounded border border-border-light dark:border-border-dark">No assigned tasks yet.</div>
          <?php endif; ?>
        </div>

        <div class="mt-4 overflow-x-auto hidden md:block">
          <table class="w-full text-left">
            <thead>
              <tr class="text-xs uppercase text-gray-500">
                <th class="p-3">Task</th>
                <th class="p-3">Schedule</th>
                <th class="p-3">Area</th>
                <th class="p-3">Farmer</th>
                <th class="p-3">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y">
              <?php foreach($rows as $r): ?>
                <tr class="text-sm">
                  <td class="p-3">
                    <div class="font-semibold">#<?= (int)$r['task_id'] ?></div>
                    <div class="text-xs text-gray-500"><?= h($r['task_status']) ?></div>
                  </td>
                  <td class="p-3">
                    <?= h($r['schedule_date']) ?><br>
                    <span class="text-xs text-gray-500"><?= h($r['start_time']) ?> - <?= h($r['end_time']) ?></span>
                  </td>
                  <td class="p-3"><?= h($r['area_name']) ?> <span class="text-xs text-gray-500">(<?= h($r['municipality']) ?>)</span></td>
                  <td class="p-3"><?= h($r['farmer_name'] ?? '—') ?></td>
                  <td class="p-3">
                    <?php if (!empty($r['request_id'])): ?>
                      <a class="px-3 py-2 rounded bg-gray-200"
                         href="<?= route('request_view', ['id' => (int)$r['request_id']]) ?>">
                        View Request
                      </a>
                    <?php endif; ?>

                    <form method="POST" class="inline-flex gap-2 ml-2 js-tech-task-update-form">
                      <input type="hidden" name="task_id" value="<?= (int)$r['task_id'] ?>">
                      <input type="hidden" name="completion_remarks" class="js-completion-remarks">
                      <select name="status" class="rounded border px-2 py-1">
                        <option value="In Progress">In Progress</option>
                        <option value="Completed">Completed</option>
                      </select>
                      <button class="px-3 py-2 rounded bg-primary text-white font-bold">Update</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$rows): ?>
                <tr><td colspan="5" class="p-3 text-gray-500">No assigned tasks yet.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

      </div>
    </main>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<div id="techPageLoader" class="fixed inset-0 z-[120] hidden items-center justify-center bg-black/45 p-4">
  <div class="w-full max-w-xs rounded-2xl border border-border-light dark:border-border-dark bg-card-light dark:bg-card-dark shadow-xl p-5 text-center">
    <div class="mx-auto h-12 w-12 rounded-full border-4 border-primary/25 border-t-primary animate-spin"></div>
    <p class="mt-3 text-sm font-semibold text-text-light dark:text-text-dark">Processing update...</p>
    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Please wait</p>
  </div>
</div>
<div id="techCompletionModal" class="fixed inset-0 z-[95] hidden items-center justify-center bg-black/45 p-4">
  <div class="w-full max-w-lg rounded-2xl border border-border-light dark:border-border-dark bg-card-light dark:bg-card-dark shadow-xl">
    <div class="flex items-center justify-between border-b border-border-light dark:border-border-dark px-4 py-3">
      <h3 class="text-base font-black text-text-light dark:text-text-dark">Completion Remarks</h3>
      <button type="button" id="techCompletionModalClose" class="w-9 h-9 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 inline-flex items-center justify-center" aria-label="Close">
        <span class="material-symbols-outlined text-[20px] leading-none">close</span>
      </button>
    </div>
    <div class="p-4">
      <label for="techCompletionRemarksInput" class="block text-sm font-semibold text-text-light dark:text-text-dark">Enter completion remarks *</label>
      <textarea id="techCompletionRemarksInput" rows="4"
                class="mt-2 w-full rounded-lg border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark"></textarea>
      <p id="techCompletionRemarksError" class="mt-2 text-xs text-red-600 hidden">Remarks are required when completing a task.</p>
      <div class="mt-4 flex justify-end gap-2">
        <button type="button" id="techCompletionCancelBtn" class="px-4 py-2 rounded-full bg-gray-200 dark:bg-gray-700 text-text-light dark:text-text-dark">Cancel</button>
        <button type="button" id="techCompletionSaveBtn" class="px-4 py-2 rounded-full bg-primary text-white font-semibold">Save</button>
      </div>
    </div>
  </div>
</div>
<script>
(() => {
  const forms = document.querySelectorAll('.js-tech-task-update-form');
  const pageLoader = document.getElementById('techPageLoader');
  const modal = document.getElementById('techCompletionModal');
  const modalClose = document.getElementById('techCompletionModalClose');
  const modalCancel = document.getElementById('techCompletionCancelBtn');
  const modalSave = document.getElementById('techCompletionSaveBtn');
  const remarksInputEl = document.getElementById('techCompletionRemarksInput');
  const remarksErrorEl = document.getElementById('techCompletionRemarksError');
  let pendingForm = null;

  const closeModal = () => {
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    pendingForm = null;
    if (remarksInputEl) remarksInputEl.value = '';
    if (remarksErrorEl) remarksErrorEl.classList.add('hidden');
  };

  const showLoader = () => {
    if (!pageLoader) return;
    pageLoader.classList.remove('hidden');
    pageLoader.classList.add('flex');
  };

  const openModal = (form) => {
    if (!modal || !remarksInputEl) return;
    pendingForm = form;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    remarksInputEl.value = '';
    if (remarksErrorEl) remarksErrorEl.classList.add('hidden');
    window.setTimeout(() => remarksInputEl.focus(), 0);
  };

  const saveModal = () => {
    if (!pendingForm || !remarksInputEl) return;
    const trimmed = remarksInputEl.value.trim();
    if (trimmed === '') {
      if (remarksErrorEl) remarksErrorEl.classList.remove('hidden');
      remarksInputEl.focus();
      return;
    }
    const remarksInput = pendingForm.querySelector('.js-completion-remarks');
    if (remarksInput) remarksInput.value = trimmed;
    pendingForm.dataset.skipCompletionPrompt = '1';
    const formToSubmit = pendingForm;
    closeModal();
    showLoader();
    formToSubmit.submit();
  };

  if (modalClose) modalClose.addEventListener('click', closeModal);
  if (modalCancel) modalCancel.addEventListener('click', closeModal);
  if (modalSave) modalSave.addEventListener('click', saveModal);
  if (remarksInputEl) {
    remarksInputEl.addEventListener('input', () => {
      if (remarksErrorEl) remarksErrorEl.classList.add('hidden');
    });
    remarksInputEl.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' && (event.ctrlKey || event.metaKey)) {
        event.preventDefault();
        saveModal();
      }
    });
  }

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
      closeModal();
    }
  });
  document.addEventListener('click', (event) => {
    if (modal && !modal.classList.contains('hidden') && event.target === modal) {
      closeModal();
    }
  });

  forms.forEach((form) => {
    form.addEventListener('submit', (event) => {
      const statusSelect = form.querySelector('select[name="status"]');
      const remarksInput = form.querySelector('.js-completion-remarks');
      if (!statusSelect || !remarksInput) return;
      if (statusSelect.value !== 'Completed') {
        remarksInput.value = '';
        showLoader();
        return;
      }
      if (form.dataset.skipCompletionPrompt === '1') {
        form.dataset.skipCompletionPrompt = '';
        return;
      }
      event.preventDefault();
      if (!modal) {
        const remark = window.prompt('Enter completion remarks for this task:');
        if (remark === null) return;
        const trimmed = remark.trim();
        if (trimmed === '') {
          window.alert('Remarks are required when completing a task.');
          return;
        }
        remarksInput.value = trimmed;
        form.dataset.skipCompletionPrompt = '1';
        showLoader();
        form.submit();
        return;
      }
      openModal(form);
    });
  });
})();
</script>
