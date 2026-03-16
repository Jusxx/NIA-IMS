<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_roles(['Administrator','Operations Staff','IMO']);
require_once __DIR__ . '/../includes/config.php';

if (!function_exists('forms_control_number')) {
  function forms_control_number(int $formId, ?string $issuedAt = null): string {
    $issuedTs = $issuedAt ? strtotime($issuedAt) : false;
    $year = $issuedTs ? date('Y', $issuedTs) : date('Y');
    return sprintf('NIA-%s-%05d', $year, max(0, $formId));
  }
}

$formId = (int)($_GET['form_id'] ?? 0);
$templateId = (int)($_GET['template_id'] ?? 0);
$isReprint = (int)($_GET['reprint'] ?? 0) === 1;

$template = null;
$fields = [];
$lotData = null;

if ($formId) {
  $stmt = $conn->prepare("
    SELECT pf.form_id, pf.form_type, pf.issued_to_name, pf.issued_at, pf.issued_to_farmer_id,
           f.farmer_name, f.association_name, f.address, f.phone, f.is_president,
           sa.area_name AS service_area_name, sa.municipality AS service_area_municipality,
           ft.template_id, ft.template_name
    FROM paper_forms pf
    JOIN form_templates ft ON ft.template_id = pf.template_id
    LEFT JOIN farmers f ON f.farmer_id = pf.issued_to_farmer_id
    LEFT JOIN service_areas sa ON sa.service_area_id = f.service_area_id
    WHERE pf.form_id = ?
    LIMIT 1
  ");
  $stmt->bind_param("i", $formId);
  $stmt->execute();
  $template = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if ($template) $templateId = (int)$template['template_id'];
}

if ($templateId && !$template) {
  $stmt = $conn->prepare("SELECT template_id, template_name, form_type FROM form_templates WHERE template_id=? AND is_active=1 LIMIT 1");
  $stmt->bind_param("i", $templateId);
  $stmt->execute();
  $template = $stmt->get_result()->fetch_assoc();
  $stmt->close();
}

if ($template && !empty($template['issued_to_farmer_id'])) {
  $farmerId = (int)$template['issued_to_farmer_id'];
  $stmt = $conn->prepare("
    SELECT fl.lot_code, fl.area_ha, fl.location_desc, fl.canal_width_m, fl.canal_length_m,
           c.canal_name, d.drainage_name
    FROM farmer_lots fl
    LEFT JOIN canals c ON c.canal_id = fl.canal_id
    LEFT JOIN drainages d ON d.drainage_id = fl.drainage_id
    WHERE fl.farmer_id = ?
    ORDER BY fl.created_at DESC, fl.lot_id DESC
    LIMIT 1
  ");
  $stmt->bind_param("i", $farmerId);
  $stmt->execute();
  $lotData = $stmt->get_result()->fetch_assoc();
  $stmt->close();
}

if ($templateId) {
  $stmt = $conn->prepare("
    SELECT field_label, field_key, field_type
    FROM form_template_fields
    WHERE template_id=?
    ORDER BY sort_order ASC
  ");
  $stmt->bind_param("i", $templateId);
  $stmt->execute();
  $fields = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  $stmt->close();
}

$registrationExcludedFieldKeys = [
  'area_ha',
  'canal_id',
  'drainage_id',
  'canal_width_m',
  'canal_length_m',
];
$registrationExcludedFieldLabels = [
  'lot area (ha)',
  'canal',
  'drainage',
  'canal width (m)',
  'canal length (m)',
];
if ($template && (string)($template['form_type'] ?? '') === 'Farmer Registration' && $fields) {
  $fields = array_values(array_filter($fields, static function (array $field) use ($registrationExcludedFieldKeys, $registrationExcludedFieldLabels): bool {
    $key = strtolower(trim((string)($field['field_key'] ?? '')));
    $label = strtolower(trim((string)($field['field_label'] ?? '')));
    if (in_array($key, $registrationExcludedFieldKeys, true)) return false;
    if (in_array($label, $registrationExcludedFieldLabels, true)) return false;
    return true;
  }));
}

$controlNo = $formId > 0
  ? forms_control_number($formId, (string)($template['issued_at'] ?? ''))
  : 'DRAFT-' . date('YmdHis');
$printStatusLabel = $isReprint ? 'Reprint Copy' : 'Issued';
$printStatusClass = $isReprint
  ? 'bg-amber-100 text-amber-800'
  : 'bg-green-100 text-green-800';

if ($isReprint && $template && $formId > 0) {
  $farmerLabel = trim((string)($template['farmer_name'] ?? $template['issued_to_name'] ?? 'Unknown'));
  system_log(
    $conn,
    'Form Reprint',
    "Reprinted {$controlNo} (" . (string)($template['template_name'] ?? 'Form') . ") for {$farmerLabel}"
  );
}

$prefillValues = [
  'farmer_name' => (string)($template['farmer_name'] ?? ''),
  'association_name' => (string)($template['association_name'] ?? ''),
  'address' => (string)($template['address'] ?? ''),
  'phone' => (string)($template['phone'] ?? ''),
  'is_president' => ((int)($template['is_president'] ?? 0) === 1) ? 'Yes' : 'No',
  'service_area' => trim((string)($template['service_area_name'] ?? '') . ((string)($template['service_area_municipality'] ?? '') !== '' ? ', ' . (string)$template['service_area_municipality'] : '')),
  'lot_id' => (string)($lotData['lot_code'] ?? ''),
  'lot_code' => (string)($lotData['lot_code'] ?? ''),
  'area_ha' => ($lotData && $lotData['area_ha'] !== null && $lotData['area_ha'] !== '') ? number_format((float)$lotData['area_ha'], 2) : '',
  'canal_id' => (string)($lotData['canal_name'] ?? ''),
  'drainage_id' => (string)($lotData['drainage_name'] ?? ''),
  'canal_width_m' => ($lotData && $lotData['canal_width_m'] !== null && $lotData['canal_width_m'] !== '') ? (string)$lotData['canal_width_m'] : '',
  'canal_length_m' => ($lotData && $lotData['canal_length_m'] !== null && $lotData['canal_length_m'] !== '') ? (string)$lotData['canal_length_m'] : '',
  'location_desc' => (string)($lotData['location_desc'] ?? ''),
];

$shouldKeepBlank = static function(string $fieldKey, string $fieldLabel, string $fieldType): bool {
  $key = strtolower(trim($fieldKey));
  $label = strtolower(trim($fieldLabel));
  if ($fieldType === 'date') return true;
  if (str_contains($key, 'signature') || str_contains($label, 'signature')) return true;
  if (str_contains($key, 'remark') || str_contains($label, 'remark')) return true;
  if (str_contains($key, 'date') || str_contains($label, 'date')) return true;
  return false;
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= h($template['template_name'] ?? 'Print Form') ?></title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700;900&display=swap" rel="stylesheet"/>
  <style>
    body { font-family: "Public Sans", sans-serif; }
    @media print {
      .no-print { display: none !important; }
      body { background: white !important; }
      .print-card {
        box-shadow: none !important;
        border-color: #d1d5db !important;
      }
    }
  </style>
</head>
<body class="bg-slate-100 text-[#343A40] p-6">
  <div class="max-w-3xl mx-auto">
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm p-4 print-card no-print">
      <div class="flex items-start justify-between gap-4">
        <div>
          <div class="flex flex-wrap items-center gap-2">
            <h1 class="text-2xl font-black"><?= h($template['template_name'] ?? 'Form') ?></h1>
            <span class="px-2.5 py-1 rounded-full text-xs font-bold <?= h($printStatusClass) ?>">
              <?= h($printStatusLabel) ?>
            </span>
          </div>
          <p class="text-sm text-gray-600 mt-1">NIA IMS - Paper/Legal Form</p>
          <p class="text-xs text-gray-500 mt-1 font-mono">Control No: <?= h($controlNo) ?></p>
        </div>
        <div class="flex gap-2">
          <button onclick="window.print()" class="px-4 py-2 rounded bg-green-600 text-white font-bold">Print</button>
          <button onclick="closePrintPage()" class="px-4 py-2 rounded bg-gray-200 text-gray-800 font-semibold">Close</button>
        </div>
      </div>
    </div>

    <div class="mt-4 border border-gray-200 rounded-lg p-4 bg-white shadow-sm print-card">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm text-gray-700">
        <div><span class="font-semibold">Control No.:</span> <?= h($controlNo) ?></div>
        <div><span class="font-semibold">Issued Date:</span> <?= h((string)($template['issued_at'] ?? date('Y-m-d H:i:s'))) ?></div>
        <div><span class="font-semibold">Issued To:</span> <?= h($template['farmer_name'] ?? $template['issued_to_name'] ?? '__________') ?></div>
        <div><span class="font-semibold">Print Date:</span> <?= date('Y-m-d') ?></div>
      </div>
    </div>

    <div class="mt-4 border border-gray-200 rounded-lg overflow-hidden bg-white shadow-sm print-card">
      <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
        <div class="text-sm font-semibold text-gray-700">Auto-filled Profile Data</div>
      </div>
      <table class="w-full text-left">
        <thead class="bg-gray-50 text-xs text-gray-600 uppercase">
          <tr>
            <th class="p-3">Field</th>
            <th class="p-3">Fill Out</th>
          </tr>
        </thead>
        <tbody class="divide-y">
          <?php foreach ($fields as $f): ?>
            <?php
              $fieldKey = (string)($f['field_key'] ?? '');
              $fieldLabel = (string)($f['field_label'] ?? '');
              $fieldType = (string)($f['field_type'] ?? 'text');
              $prefilled = trim((string)($prefillValues[$fieldKey] ?? ''));
              $forceBlank = $shouldKeepBlank($fieldKey, $fieldLabel, $fieldType);
            ?>
            <tr class="text-sm">
              <td class="p-3"><?= h($fieldLabel) ?></td>
              <td class="p-3">
                <?php if (!$forceBlank && $prefilled !== ''): ?>
                  <div class="font-semibold"><?= h($prefilled) ?></div>
                <?php elseif ($fieldType === 'textarea'): ?>
                  <div class="h-14 border border-dashed border-gray-400 rounded"></div>
                <?php else: ?>
                  <div class="h-8 border-b border-dashed border-gray-400"></div>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$fields): ?>
            <tr><td class="p-3 text-gray-500" colspan="2">No fields found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="mt-4 border border-gray-200 rounded-lg p-4 bg-white shadow-sm print-card">
      <div class="text-sm font-semibold mb-3">Manual Fill Section</div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
        <div>
          <div class="text-gray-600">Date Signed</div>
          <div class="h-8 border-b border-dashed border-gray-400"></div>
        </div>
        <div>
          <div class="text-gray-600">Remarks</div>
          <div class="h-8 border-b border-dashed border-gray-400"></div>
        </div>
        <div>
          <div class="text-gray-600">Farmer Signature</div>
          <div class="h-8 border-b border-dashed border-gray-400"></div>
        </div>
        <div>
          <div class="text-gray-600">Received By / Witness Signature</div>
          <div class="h-8 border-b border-dashed border-gray-400"></div>
        </div>
      </div>
    </div>

    <p class="text-xs text-gray-500 mt-4">Generated at: <?= date('Y-m-d H:i:s') ?></p>
  </div>

  <script>
    function closePrintPage() {
      if (window.opener && !window.opener.closed) {
        window.close();
        return;
      }
      if (window.history.length > 1) {
        window.history.back();
        return;
      }
      window.location.href = <?= json_encode(route('forms')) ?>;
    }
  </script>
</body>
</html>
