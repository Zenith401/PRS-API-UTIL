<?php
// Connect to the database as root user 
$DATABASE_HOST = '127.0.0.1'; //Changed from "localhost", using "localhost" caused PDOException SQLSTATE[HY000] [2002]
$DATABASE_USER = 'root';
$DATABASE_PASS = 'password';
$DATABASE_NAME = 'pothole_reporting_system';

try {
    $pdo = new PDO('mysql:host=' . $DATABASE_HOST . ';dbname=' . $DATABASE_NAME . ';charset=utf8', $DATABASE_USER, $DATABASE_PASS);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "DB connected!";
} catch (PDOException $exception) {
    // Output the specific error message
    exit('Failed to connect to database: ' . $exception->getMessage());
}
?>
