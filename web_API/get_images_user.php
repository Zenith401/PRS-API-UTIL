<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require "../database.php";

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Check if user_id is provided
if (!isset($_GET['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User ID not provided']);
    exit;
}

$user_id = $_GET['user_id'];

// Fetch image URLs and details from the database
try {
    $stmt = $pdo->prepare('
        SELECT images.id, images.image_url, images.description, images.severity, images.upload_date, images.location, users.Email_Address 
        FROM images 
        JOIN users 
        ON images.id = users.id 
        WHERE users.id = :user_id
    ');
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($images);
} catch (PDOException $exception) {
    echo json_encode(['success' => false, 'message' => 'Database query error: ' . $exception->getMessage()]);
}
?>
