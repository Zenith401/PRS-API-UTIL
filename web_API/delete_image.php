<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json");

// Include the database connection file
require "../database.php";

// Add CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Read the input data
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['image_url'])) {
    echo json_encode(['success' => false, 'message' => 'Missing image URL']);
    exit;
}

$image_url = $data['image_url'];

// Log received data for debugging
error_log("Received image_url: $image_url");

try {
    // Verify that the image exists
    $stmt = $pdo->prepare('SELECT image_url FROM images WHERE image_url = :image_url');
    $stmt->execute(['image_url' => $image_url]);
    $image = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($image) {
        // Parse the full path to the image file
        $parsed_url = parse_url($image['image_url']);
        $file_path = $_SERVER['DOCUMENT_ROOT'] . $parsed_url['path'];

        // Log the file path for debugging
        error_log("Attempting to delete file: $file_path");

        // Check if the file exists and is writable
        if (file_exists($file_path)) {
            error_log("File exists: $file_path");

            if (is_writable($file_path)) {
                error_log("File is writable: $file_path");

                // Delete the image file from the server
                if (unlink($file_path)) {
                    error_log("File deleted successfully: $file_path");

                    // Delete the image record from the database
                    $stmt = $pdo->prepare('DELETE FROM images WHERE image_url = :image_url');
                    $stmt->execute(['image_url' => $image_url]);

                    echo json_encode(['success' => true, 'message' => 'Image deleted successfully']);
                } else {
                    error_log("Failed to delete file: $file_path");
                    echo json_encode(['success' => false, 'message' => 'Failed to delete file']);
                }
            } else {
                error_log("File is not writable: $file_path");
                echo json_encode(['success' => false, 'message' => 'File is not writable']);
            }
        } else {
            error_log("File does not exist: $file_path");
            echo json_encode(['success' => false, 'message' => 'File does not exist']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Image not found or permission denied']);
    }
} catch (PDOException $exception) {
    echo json_encode(['success' => false, 'message' => 'Database query error: ' . $exception->getMessage()]);
    error_log('Database query error: ' . $exception->getMessage());
}
?>
