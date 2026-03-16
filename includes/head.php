<?php
// includes/head.php
$topTitle = $topTitle ?? 'NIA IMS';
$faviconFile = __DIR__ . '/../images/nia-logo-circle.png';
$faviconVer = is_file($faviconFile) ? (string)filemtime($faviconFile) : (string)time();
$faviconHref = base_path('images/nia-logo-circle.png') . '?v=' . urlencode($faviconVer);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
  <title><?= h($topTitle) ?></title>
  <link rel="icon" type="image/png" sizes="32x32" href="<?= h($faviconHref) ?>">
  <link rel="shortcut icon" type="image/png" href="<?= h($faviconHref) ?>">
  <link rel="apple-touch-icon" href="<?= h($faviconHref) ?>">

  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700;900&display=swap" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>

  <script>
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: {
            "primary": "#28a745",
            "secondary": "#007bff",
            "warning": "#ffc107",
            "background-light": "#F8F9FA",
            "background-dark": "#102213",
            "text-light": "#343A40",
            "text-dark": "#F8F9FA",
            "border-light": "#DEE2E6",
            "border-dark": "#495057",
            "card-light": "#ffffff",
            "card-dark": "#111827",
          },
          fontFamily: { "display": ["Public Sans", "sans-serif"] },
          borderRadius: { "DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px" },
        },
      },
    }
  </script>

  <style>
    .material-symbols-outlined { font-variation-settings: 'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24; }
  </style>
</head>
<body class="font-display bg-background-light dark:bg-background-dark overflow-x-hidden">
