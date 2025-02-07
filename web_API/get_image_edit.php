<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require "../database.php";

// Add CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Check if image_url is provided
if (!isset($_POST['image_url'])) {
    echo json_encode(['success' => false, 'message' => 'Image URL not provided']);
    exit;
}

$image_url = $_POST['image_url'];

// Fetch image details from the database based on image URL
try {
    $stmt = $pdo->prepare('SELECT * FROM images WHERE image_url = :image_url');
    $stmt->bindParam(':image_url', $image_url, PDO::PARAM_STR);
    $stmt->execute();
    $image = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($image) {
        // Return the image data as JSON response
        echo json_encode([$image]);
    } else {
        // Handle case where no image is found for the given URL
        echo json_encode(['success' => false, 'message' => 'No image found for this URL']);
    }
} catch (PDOException $exception) {
    // Handle database query error
    echo json_encode(['success' => false, 'message' => 'Database query error: ' . $exception->getMessage()]);
}
?>
