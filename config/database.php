<?php
date_default_timezone_set('Asia/Karachi');

$host = "localhost";
$username = "mindvault_user";
$password = "VaultSecurePass123!";
$database = "engram_db";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Database connection failed.");
}

$conn->set_charset("utf8mb4");
$conn->query("SET time_zone = '+05:00'"); 
// keeps PHP dates and MySQL CURDATE() in sync completely and prevent our apps custom algorithms from breakage 