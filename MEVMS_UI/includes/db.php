<?php
// includes/db.php - Database connection for MEVMS
$host = "localhost";
$dbname = "mevms";
$username = "root";
$password = "";

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error . ". Please make sure MySQL is started in XAMPP.");
}

// Ensure UTF-8 character encoding
$conn->set_charset("utf8mb4");
?>
