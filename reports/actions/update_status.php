<?php
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
require_roles(['Administrator','Operations Staff','IMO']);
require_once __DIR__ . '/../../includes/config.php';

// Placeholder for future report status actions.
// Keep for structure consistency.
http_response_code(200);
echo "OK";
