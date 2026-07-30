<?php
$host = "localhost";
$user = "root";     // Adjust for your MySQL user
$pass = "";         // Adjust for your MySQL password
$dbname = "ecommerce_db";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}
?>
