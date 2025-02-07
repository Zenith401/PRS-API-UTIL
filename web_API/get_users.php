<?php
header("Content-Type: application/json");

<<<<<<< HEAD
<<<<<<< HEAD
<<<<<<< HEAD
require_once "../database.php"; // Adjust path if necessary
=======
require_once "../../util/Database.php"; // Adjust path if necessary
>>>>>>> bb8997b6f7589c13348f40af1f9a2b91b32ff386
=======
require_once "../../util/Database.php"; // Adjust path if necessary
>>>>>>> bb8997b6f7589c13348f40af1f9a2b91b32ff386
=======
require_once "../../util/Database.php"; // Adjust path if necessary
>>>>>>> 9b53d7f1d47f31a2f1cd244cfaba0e7963d831d1

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
<<<<<<< HEAD
<<<<<<< HEAD
<<<<<<< HEAD

=======
>>>>>>> bb8997b6f7589c13348f40af1f9a2b91b32ff386
=======
>>>>>>> bb8997b6f7589c13348f40af1f9a2b91b32ff386
=======
>>>>>>> 9b53d7f1d47f31a2f1cd244cfaba0e7963d831d1
