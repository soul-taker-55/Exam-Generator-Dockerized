<?php
define('DB_HOST', 'db');
define('DB_USER', 'exam_user'); 
define('DB_PASS', 'exam_pass123');
define('DB_NAME', 'exam_generator');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
$conn->query("SET NAMES 'utf8mb4'");
$conn->query("SET CHARACTER SET utf8mb4");
?>