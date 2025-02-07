<?php
require "../database.php";
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['email'])) {
    echo json_encode(["success" => false, "message" => "Missing email parameter."]);
    exit;
}

$email = $data['email'];

try {
    $stmt = $pdo->prepare("SELECT email_verified_at FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(["success" => false, "message" => "User not found."]);
        exit;
    }

    $isVerified = $user['email_verified_at'] !== null;

    echo json_encode(["success" => true, "is_verified" => $isVerified]);
    exit;
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    exit;
}
?>
