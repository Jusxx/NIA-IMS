<?php
require_once __DIR__ . '/philsms.php';

function sms_system_name(): string {
  return 'NIA IMS';
}

function sms_display_farmer_name(?string $name): string {
  $clean = trim((string)$name);
  return $clean !== '' ? $clean : 'Farmer';
}

function sms_message_account_created(string $farmerName, string $username, string $password): string {
  $name = sms_display_farmer_name($farmerName);
  $system = sms_system_name();
  return "Dear {$name}, {$system} login details. Username: {$username}, Password: {$password}. Access https://univ-devmode.online/NIA/public/ and change your password on first login.";
}

function sms_message_account_created_fallback(string $farmerName, string $username, string $password): string {
  $name = sms_display_farmer_name($farmerName);
  $system = sms_system_name();
  return "Dear {$name}, {$system} login details. Username {$username}. Passcode {$password}. Access https://univ-devmode.online/NIA/public/ and change your passcode on first login.";
}

function sms_message_account_created_fallback_alt(string $username, string $password): string {
  $system = sms_system_name();
  return "{$system} login: User {$username} PIN {$password}. Access https://univ-devmode.online/NIA/public/ and update PIN on first login.";
}

function sms_message_account_created_fallback_min(string $username, string $password): string {
  $system = sms_system_name();
  return "{$system}: User {$username} Key {$password}. Access https://univ-devmode.online/NIA/public/ and change key on first login.";
}

function sms_message_account_created_variants(string $farmerName, string $username, string $password): array {
  return [
    sms_message_account_created($farmerName, $username, $password),
    sms_message_account_created_fallback($farmerName, $username, $password),
    sms_message_account_created_fallback_alt($username, $password),
    sms_message_account_created_fallback_min($username, $password),
  ];
}

function sms_message_request_on_process(string $farmerName): string {
  $name = sms_display_farmer_name($farmerName);
  $system = sms_system_name();
  return "Dear {$name}, your application is now ON PROCESS. We will notify you once it is approved. - {$system}";
}

function sms_message_request_approved(string $farmerName): string {
  $name = sms_display_farmer_name($farmerName);
  $system = sms_system_name();
  return "Congratulations {$name}! Your application has been APPROVED. Please coordinate with the office for further instructions. - {$system}";
}

function sms_message_irrigation_started(string $farmerName): string {
  $name = sms_display_farmer_name($farmerName);
  $system = sms_system_name();
  return "Dear {$name}, irrigation has started on your farm today. - {$system}";
}

function sms_message_irrigation_completed(string $farmerName): string {
  $name = sms_display_farmer_name($farmerName);
  $system = sms_system_name();
  return "Dear {$name}, irrigation has been successfully completed. Thank you. - {$system}";
}

function sms_message_technician_pending_task(?string $technicianName = null): string {
  $name = trim((string)$technicianName);
  if ($name !== '') {
    return "NIA IMS: {$name}, you have a pending irrigation task. Please check your website.";
  }
  return "NIA IMS: Technician, you have a pending irrigation task. Please check your website.";
}

function sms_message_membership_status(string $farmerName, bool $isActive): string {
  $variants = sms_message_membership_status_variants($farmerName, $isActive);
  return $variants[0];
}

function sms_message_membership_status_variants(string $farmerName, bool $isActive): array {
  $name = sms_display_farmer_name($farmerName);
  $system = sms_system_name();
  if ($isActive) {
    return [
      "Dear {$name}, your membership is now ACTIVE. You may now access all services. - {$system}",
      "{$system}: {$name}, membership is ACTIVE. You may access services.",
      "{$system}: {$name} membership active."
    ];
  }
  return [
    "Dear {$name}, your account has been set to INACTIVE. Please contact the office for assistance. - {$system}",
    "{$system}: {$name}, membership is INACTIVE. Please contact the office.",
    "{$system}: {$name} membership inactive."
  ];
}

function sms_error_contains_spam_word_filter(array $res): bool {
  $decoded = $res['decoded'] ?? null;
  $msg = '';

  if (is_array($decoded)) {
    $msg = (string)($decoded['message'] ?? $decoded['error'] ?? '');
  }

  if ($msg === '') {
    $msg = (string)($res['response'] ?? '');
  }

  return stripos($msg, 'spam word') !== false;
}

function build_safe_fallback_sms(string $smsType, ?int $requestId, int $attempt = 1): string {
  $rid = $requestId ? (" #" . (int)$requestId) : '';

  $attempt = max(1, min(3, $attempt));
  $type = strtolower(trim($smsType));

  if ($attempt === 1) {
    return match ($type) {
      'approved' => "NIA IMS notice{$rid}: irrigation request update.",
      'declined' => "NIA IMS notice{$rid}: irrigation request update.",
      default => "NIA IMS notice{$rid}: irrigation update.",
    };
  }

  if ($attempt === 2) {
    return "NIA IMS notice{$rid}.";
  }

  return "NIA IMS notice.";
}

function send_sms_and_log(
  mysqli $conn,
  ?int $farmerId,
  ?string $phoneRaw,
  string $message,
  string $smsType = 'Info',
  ?int $requestId = null,
  ?string $recipientRole = null,
  ?string $scheduleTime = null,
  ?string $senderId = null,
  ?string $spamSafeFallbackMessage = null,
  bool $allowGenericFallbackOnSpam = true
): array {
  $phone = normalize_ph_phone($phoneRaw);
  $attempts = [];

  if (!$phone) {
    $stmt = $conn->prepare("
      INSERT INTO sms_logs(
        farmer_id, request_id, phone, message, sms_type, provider, recipient_role,
        status, error_message, payload_json
      )
      VALUES(?,?,?,?,?,'PhilSMS',?,'Failed',?,?)
    ");
    $payloadJson = json_encode(['recipient_raw' => $phoneRaw]);
    $err = "Invalid phone format";
    $stmt->bind_param("iissssss", $farmerId, $requestId, $phoneRaw, $message, $smsType, $recipientRole, $err, $payloadJson);
    $stmt->execute();
    $stmt->close();

    return ['ok' => false, 'error' => $err];
  }

  $res = send_philsms($phone, $message, $senderId, $scheduleTime);
  $attempts[] = [
    'label' => 'original',
    'message' => $message,
    'ok' => (bool)($res['ok'] ?? false),
    'status' => $res['status'] ?? null,
    'response' => $res['response'] ?? null,
  ];

  // Provider may reject specific content words. Retry with caller-provided and/or generic safe fallbacks.
  if (!$res['ok'] && sms_error_contains_spam_word_filter($res)) {
    $tryGenericFallback = $allowGenericFallbackOnSpam;

    if ($spamSafeFallbackMessage !== null && trim($spamSafeFallbackMessage) !== '') {
      $fallback = trim($spamSafeFallbackMessage);
      $retry = send_philsms($phone, $fallback, $senderId, $scheduleTime);
      $attempts[] = [
        'label' => 'fallback_custom',
        'message' => $fallback,
        'ok' => (bool)($retry['ok'] ?? false),
        'status' => $retry['status'] ?? null,
        'response' => $retry['response'] ?? null,
      ];

      $res = $retry;
      $message = $fallback;

      if ($retry['ok'] || !sms_error_contains_spam_word_filter($retry)) {
        $tryGenericFallback = false;
      }
      if (!$allowGenericFallbackOnSpam) {
        $tryGenericFallback = false;
      }
    }

    if ($tryGenericFallback) {
      for ($i = 1; $i <= 3; $i++) {
        $fallback = build_safe_fallback_sms($smsType, $requestId, $i);
        $retry = send_philsms($phone, $fallback, $senderId, $scheduleTime);
        $attempts[] = [
          'label' => 'fallback_' . $i,
          'message' => $fallback,
          'ok' => (bool)($retry['ok'] ?? false),
          'status' => $retry['status'] ?? null,
          'response' => $retry['response'] ?? null,
        ];

        $res = $retry;
        $message = $fallback;

        if ($retry['ok']) {
          break;
        }
        if (!sms_error_contains_spam_word_filter($retry)) {
          break;
        }
      }
    }
  }

  $providerId = null;
  $errorMsg = null;

  $decoded = $res['decoded'] ?? json_decode($res['response'] ?? '', true);
  if (is_array($decoded)) {
    $providerId = $decoded['data']['uid']
      ?? $decoded['uid']
      ?? $decoded['data']['message_id']
      ?? $decoded['message_id']
      ?? null;
  }

  if (!$res['ok']) {
    if (is_array($decoded)) {
      $errorMsg = (string)(
        $decoded['error']
        ?? (is_array($decoded['errors'] ?? null) ? json_encode($decoded['errors']) : ($decoded['errors'] ?? ''))
        ?? $decoded['message']
        ?? ''
      );
    }
    if (!$errorMsg) {
      $errorMsg = (string)($res['response'] ?? 'Send failed');
    }
  }

  $stmt = $conn->prepare("
    INSERT INTO sms_logs(
      farmer_id, request_id, phone, message, sms_type, provider, recipient_role, status,
      provider_message_id, error_message, payload_json
    )
    VALUES(?,?,?,?,?,'PhilSMS',?,?,?,?,?)
  ");
  $status = $res['ok'] ? 'Sent' : 'Failed';
  $payloadJson = json_encode([
    'request_payload' => $res['payload'] ?? null,
    'provider_status' => $res['status'] ?? null,
    'provider_response' => $res['response'] ?? null,
    'attempts' => $attempts,
  ]);
  $stmt->bind_param(
    "iissssssss",
    $farmerId,
    $requestId,
    $phone,
    $message,
    $smsType,
    $recipientRole,
    $status,
    $providerId,
    $errorMsg,
    $payloadJson
  );
  $stmt->execute();
  $stmt->close();

  return $res;
}

function send_task_status_sms_if_needed(mysqli $conn, int $taskId, string $newStatus): ?array {
  $status = trim($newStatus);
  if (!in_array($status, ['In Progress', 'Completed'], true)) {
    return null;
  }

  $stmt = $conn->prepare("
    SELECT
      s.request_id,
      COALESCE(r.farmer_id, b.farmer_id) AS farmer_id,
      f.farmer_name,
      f.phone,
      f.is_president
    FROM tasks t
    JOIN irrigation_schedules s ON s.schedule_id = t.schedule_id
    LEFT JOIN farmer_requests r ON r.request_id = s.request_id
    LEFT JOIN (
      SELECT schedule_id, MIN(farmer_id) AS farmer_id
      FROM irrigation_batches
      GROUP BY schedule_id
    ) b ON b.schedule_id = s.schedule_id
    LEFT JOIN farmers f ON f.farmer_id = COALESCE(r.farmer_id, b.farmer_id)
    WHERE t.task_id = ?
    LIMIT 1
  ");
  $stmt->bind_param("i", $taskId);
  $stmt->execute();
  $info = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$info) {
    return null;
  }

  $farmerId = (int)($info['farmer_id'] ?? 0);
  $phone = (string)($info['phone'] ?? '');
  if ($farmerId <= 0 || $phone === '') {
    return null;
  }

  $farmerName = (string)($info['farmer_name'] ?? '');
  $recipientRole = ((int)($info['is_president'] ?? 0) === 1) ? 'President' : 'Farmer';
  $requestId = !empty($info['request_id']) ? (int)$info['request_id'] : null;

  $message = ($status === 'In Progress')
    ? sms_message_irrigation_started($farmerName)
    : sms_message_irrigation_completed($farmerName);

  return send_sms_and_log(
    $conn,
    $farmerId,
    $phone,
    $message,
    'Info',
    $requestId,
    $recipientRole
  );
}

function send_membership_status_sms(
  mysqli $conn,
  int $farmerId,
  ?string $phoneRaw,
  string $farmerName,
  bool $isActive,
  ?string $recipientRole = null
): array {
  $messages = sms_message_membership_status_variants($farmerName, $isActive);
  $result = ['ok' => false, 'error' => 'No SMS message candidates'];

  foreach ($messages as $message) {
    $result = send_sms_and_log(
      $conn,
      $farmerId,
      $phoneRaw,
      $message,
      'Info',
      null,
      $recipientRole,
      null,
      null,
      null,
      false
    );

    if (!empty($result['ok'])) {
      break;
    }
    if (!sms_error_contains_spam_word_filter($result)) {
      break;
    }
  }

  return $result;
}
