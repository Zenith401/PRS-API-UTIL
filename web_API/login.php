<?php
header("Content-Type: application/json");
require "../database.php";

// Get the POST data
$email = $_POST['email'];
$password = $_POST['password'];

try {
    // Perform your query
    $stmt = $pdo->prepare('SELECT id, Password FROM users WHERE Email_Address = :email');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['Password'])) {
        echo json_encode(['success' => true, 'user_id' => $user['id']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
    }
} catch (PDOException $exception) {
    echo json_encode(['success' => false, 'message' => 'Database query error: ' . $exception->getMessage()]);
}
?>
