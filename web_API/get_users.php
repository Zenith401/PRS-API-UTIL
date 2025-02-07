<?php
header("Content-Type: application/json");

require_once "../../util/Database.php"; // Adjust path if necessary

try {
    // Use the existing PDO connection from database.php
    global $pdo; // Ensure we use the $pdo variable defined in database.php

    // Query to fetch all users
    $stmt = $pdo->prepare("SELECT * FROM users");
    $stmt->execute();

    // Fetch data as an associative array
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return JSON response
    echo json_encode(["status" => "success", "data" => $users], JSON_PRETTY_PRINT);
} catch (PDOException $e) {
    // Handle errors gracefully
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
