
<!-- 
    In a PHP script, the echo json_encode([...]) statement acts as the response that Flutter receives after sending the request. 
    Its a JSON response so key value pair whatever you need to reply back to the application
-->
<?php
var_dump(file_exists('/var/www/html/PRS-API-UTIL/web_API/.env'));
var_dump(is_readable('/var/www/html/PRS-API-UTIL/web_API/.env'));

error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json");

require '../vendor/autoload.php';
require "../database.php";

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable('/var/www/html/PRS-API-UTIL/config'); 
$dotenv->load();


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\OAuth;
use Greew\OAuth2\Client\Provider\Azure;

// Allow cross-origin requests (CORS)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Set response type to JSON
header("Content-Type: application/json");

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["error" => "Invalid request method. Use POST."]);
    exit;
}

// Check if email is provided
if (!isset($_POST['email']) || empty($_POST['email'])) {
    echo json_encode(["error" => "Missing email parameter."]);
    exit;
}

$userEmail = $_POST['email']; // Extract user email from Flutter request

// Subject and body for the email
$subject = "Thank You for Your Request";
$body = "Hello,<br><br>Thank you for reaching out. We have received your request.<br><br>Best Regards,<br>Team";

$provider = new Azure([
    'clientId' => getenv('AZURE_CLIENT_ID'),
    'clientSecret' => getenv('AZURE_CLIENT_SECRET'),
    'tenantId' => getenv('AZURE_TENANT_ID'),
    'scopes' => ['https://outlook.office.com/SMTP.Send', 'offline_access'],
]);


$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.office365.com';
    $mail->SMTPAuth = true;
    $mail->AuthType = 'XOAUTH2';
    $mail->setOAuth(new OAuth([
        'provider' => $provider,
        'clientId' => getenv('AZURE_CLIENT_ID'),
        'clientSecret' => getenv('AZURE_CLIENT_SECRET'),
        'refreshToken' => getenv('AZURE_REFRESH_TOKEN'),
        'userName' => 'Yair@pavementpreservation.onmicrosoft.com',
    ]));
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('Yair@pavementpreservation.onmicrosoft.com', 'CP2 Support Team');
    $mail->addAddress($userEmail);
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $body;

    $mail->send();
    echo json_encode(["success" => "Email sent successfully to $userEmail"]);
} catch (Exception $e) {
    echo json_encode(["error" => "Mailer Error: {$mail->ErrorInfo}"]);
}
?>
