<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json");

// Include database connection and PHPMailer
require '../vendor/autoload.php';
require "../database.php";
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\OAuth;
use Greew\OAuth2\Client\Provider\Azure;

// Function to generate a unique verification token
function generateVerificationToken() {
    return bin2hex(random_bytes(32));
}

// Function to send verification email using Azure OAuth2
function sendVerificationEmail($email, $token) {
    $verificationLink = "http://PRS/Flutter_tools/verify_email.php?token=" . $token;

    $provider = new Azure([
        'clientId' => getenv('AZURE_CLIENT_ID'),
        'clientSecret' => getenv('AZURE_CLIENT_SECRET'),
        'tenantId' => getenv('AZURE_TENANT_ID'),
        'refreshToken' => getenv('AZURE_REFRESH_TOKEN'),
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
            'userName' => getenv('AZURE_EMAIL'),
        ]));
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom(getenv('AZURE_EMAIL'), 'CP2 Support Team');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = "Verify Your Email";
        $mail->Body = "<p>Please click the link below to verify your email:</p>
                       <p><a href='$verificationLink'>$verificationLink</a></p>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Check for required POST data
if (!isset($_POST['fname']) || !isset($_POST['lname']) || !isset($_POST['email']) || !isset($_POST['password']) || !isset($_POST['latitude']) || !isset($_POST['longitude'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$fname = $_POST['fname'];
$lname = $_POST['lname'];
$email = $_POST['email'];
$password = $_POST['password'];
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$role = 1; // Default role for a normal user
$latitude = $_POST['latitude'];
$longitude = $_POST['longitude'];

// Generate a unique verification token
$emailVerificationToken = generateVerificationToken();

// Insert new user into the database
try {
    $stmt = $pdo->prepare('
        INSERT INTO users (fname, lname, email, password, role, email_verification_token, created_at, updated_at) 
        VALUES (:fname, :lname, :email, :password, :role, :email_verification_token, NOW(), NOW())
    ');

    $stmt->execute([
        'fname' => $fname,
        'lname' => $lname,
        'email' => $email,
        'password' => $hashedPassword,
        'role' => $role,
        'email_verification_token' => $emailVerificationToken
    ]);

    $user_id = $pdo->lastInsertId();

    // Send verification email using Azure OAuth
    if (sendVerificationEmail($email, $emailVerificationToken)) {
        echo json_encode(['success' => true, 'user_id' => $user_id, 'message' => 'Sign-up successful. Please check your email to verify your account.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Sign-up successful, but failed to send verification email.']);
    }
} catch (PDOException $exception) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $exception->getMessage()]);
}
?>
