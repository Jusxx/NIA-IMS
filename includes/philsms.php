<?php

require_once __DIR__ . '/config.php';

function send_philsms(string $recipientsCsv, string $message, ?string $senderId = null, ?string $scheduleTime = null): array
{
  $url = "https://dashboard.philsms.com/api/v3/sms/send";

  $api_token = trim((string)($GLOBALS['philsms_api_token'] ?? ''));
  $sender_id = trim((string)($senderId ?? ($GLOBALS['philsms_sender_id'] ?? '')));

  if ($api_token === '') {
    return [
      'ok' => false,
      'status' => 0,
      'response' => 'PhilSMS API token is missing.',
      'payload' => [],
      'decoded' => null,
    ];
  }

  $headers = [
    "Authorization: Bearer {$api_token}",
    "Content-Type: application/json",
    "Accept: application/json"
  ];

  $payload = [
    "recipient" => $recipientsCsv,
    "type" => "plain",
    "message" => $message
  ];

  if ($sender_id !== '') {
    $payload["sender_id"] = $sender_id;
  }

  if ($scheduleTime) {
    $payload["schedule_time"] = $scheduleTime; // Y-m-d H:i
  }

  $ch = curl_init($url);
  if ($ch === false) {
    return [
      'ok' => false,
      'status' => 0,
      'response' => 'Could not initialize cURL.',
      'payload' => $payload,
      'decoded' => null,
    ];
  }

  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
  curl_setopt($ch, CURLOPT_TIMEOUT, 30);

  $response = curl_exec($ch);
  $statusCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

  if (curl_errno($ch)) {
    $err = curl_error($ch);
    curl_close($ch);
    return [
        'ok' => false,
        'status' => 0,
        'response' => $err,
        'payload' => $payload,
        'decoded' => null,
      ];
  }

  curl_close($ch);

  $decoded = null;
  if (is_string($response) && $response !== '') {
    $tmp = json_decode($response, true);
    if (is_array($tmp)) {
      $decoded = $tmp;
    }
  }

  $ok = ($statusCode >= 200 && $statusCode < 300);
  if (is_array($decoded)) {
    if (array_key_exists('success', $decoded)) {
      $ok = $ok && (bool)$decoded['success'];
    }

    $statusTxt = strtolower(trim((string)($decoded['status'] ?? '')));
    if (in_array($statusTxt, ['error', 'failed', 'failure'], true)) {
      $ok = false;
    }

    if (!empty($decoded['error']) || !empty($decoded['errors'])) {
      $ok = false;
    }
  }

  return [
    'ok' => $ok,
    'status' => $statusCode,
    'response' => $response,
    'payload' => $payload,
    'decoded' => $decoded,
  ];
}

/**
 * Normalize Philippine mobile numbers to PhilSMS format
 * 09xxxxxxxxx -> 63xxxxxxxxxx
 * +639xxxxxxxxx -> 63xxxxxxxxxx
 * 639xxxxxxxxx -> 63xxxxxxxxxx
 * 9xxxxxxxxx -> 63xxxxxxxxxx
 */
function normalize_ph_phone(?string $phone): ?string
{
  if (!$phone) return null;

  $p = preg_replace('/[^0-9+]/', '', $phone);

  if (strpos($p, '+63') === 0) {
    $p = '63' . substr($p, 3);
  } elseif (strpos($p, '63') === 0) {
    // already in 63xxxxxxxxxx format
    $p = $p;
  } elseif (strpos($p, '09') === 0) {
    $p = '63' . substr($p, 1);
  } elseif (preg_match('/^9\d{9}$/', $p)) {
    $p = '63' . $p;
  }

  return preg_match('/^63\d{10}$/', $p) ? $p : null;
}
