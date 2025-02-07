<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json"); // Ensure JSON response

require "../database.php";

// Check if a token is provided
if (!isset($_GET['token'])) {
    echo json_encode(["success" => false, "message" => "Invalid request. No token provided."]);
    exit;
}

$token = $_GET['token'];

try {
    // Look up user by token
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email_verification_token = :token");
    $stmt->execute(['token' => $token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(["success" => false, "message" => "Invalid or expired verification link."]);
        exit;
    }

    // Mark email as verified
    $stmt = $pdo->prepare("UPDATE users SET email_verified_at = NOW(), email_verification_token = NULL WHERE id = :id");
    $stmt->execute(['id' => $user['id']]);

    echo json_encode(["success" => true, "message" => "Email verified successfully."]);
    exit;
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    exit;
}
?>
