 <?php
// includes/config.php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = "localhost";
$user = "root";
$pass = "";
$db   = "nia_irrigation";

$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset("utf8mb4");





$env = parse_ini_file(__DIR__ . '/.env');

$philsms_api_token = $env['SMS_API_TOKEN'];
$philsms_sender_id = $env['SMS_SENDER'];