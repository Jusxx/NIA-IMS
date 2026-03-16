<?php

function ensure_dir(string $dir): void {
  if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
  }
}

function save_request_photos(array $files, int $request_id): array {
  // returns array of saved file records
  $saved = [];

  $baseDir = __DIR__ . '/../public/uploads/requests';
  ensure_dir($baseDir);

  if (!isset($files['name']) || !is_array($files['name'])) {
    return $saved;
  }

  $allowed = ['image/jpeg','image/png','image/webp'];
  $maxBytes = 5 * 1024 * 1024; // 5MB each

  for ($i = 0; $i < count($files['name']); $i++) {
    if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;

    $tmp  = $files['tmp_name'][$i];
    $name = $files['name'][$i];
    $size = (int)($files['size'][$i] ?? 0);

    if ($size <= 0 || $size > $maxBytes) continue;

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $tmp);
    finfo_close($finfo);

    if (!in_array($mime, $allowed, true)) continue;

    $ext = match ($mime) {
      'image/jpeg' => 'jpg',
      'image/png'  => 'png',
      'image/webp' => 'webp',
      default      => 'bin',
    };

    $safe = preg_replace('/[^a-zA-Z0-9_\-]/', '_', pathinfo($name, PATHINFO_FILENAME));
    $newFile = "req{$request_id}_" . $safe . "_" . bin2hex(random_bytes(6)) . "." . $ext;

    $destAbs = $baseDir . '/' . $newFile;
    $destRel = "uploads/requests/" . $newFile; // relative to /public

    if (!move_uploaded_file($tmp, $destAbs)) continue;

    $saved[] = [
      'file_name' => $newFile,
      'file_path' => $destRel,
      'mime_type' => $mime,
      'file_size' => $size,
    ];
  }

  return $saved;
}
