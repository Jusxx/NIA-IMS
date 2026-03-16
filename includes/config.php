 <?php
// includes/config.php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = "localhost";
$user = "root";
$pass = "";
$db   = "nia_irrigation";

$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset("utf8mb4");

// PhilSMS v3 config
$philsms_api_token = "848|igzCQi9nD9BABZQCpznBr9wxbPI4cl8ZMdpiaXpi0c8d4cc1";
$philsms_sender_id = "PhilSMS"; 
