<?php
$host = 'localhost';
$dbname = 'lms';
$user = 'root';  // Default XAMPP MySQL user
$pass = '';      // Default XAMPP MySQL password (leave blank)

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
