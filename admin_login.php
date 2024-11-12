<?php
session_start(); // Start session at the beginning
include 'connect.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['admin_submit'])) {
        $admin_id = $_POST['admin_id'];  // Fixed variable name
        $password = $_POST['password'];
        
        try {
            $stmt = $conn->prepare("SELECT * FROM admtb WHERE admin_id = :admin_id AND password = :password");
            $stmt->execute([
                'admin_id' => $admin_id,
                'password' => $password
            ]);
        
            if ($stmt->rowCount() > 0) {
                // Set proper admin session variables
                $_SESSION['admin_logged_in'] = true;  // Changed from user_logged_in
                $_SESSION['admin_id'] = $admin_id;
                
                // Redirect to admin.php
                header('Location: admin.php');
                exit(); // Important to exit after redirect
            } else {
                echo "Invalid admin credentials.";
            }
        } catch (PDOException $e) {
            echo "Database Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Login Processing</title>
    <style>
        .error {
            color: red;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <?php if (!isset($_POST['admin_submit'])): ?>
        <p>Please use the admin login form. <a href="index.html">Go back to login</a></p>
    <?php endif; ?>
</body>
</html>