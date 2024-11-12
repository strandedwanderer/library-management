<?php
session_start(); // Start session at the beginning
include 'connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['login_submit'])) {
        $user_id = $_POST['user_id'];
        $password = $_POST['password'];
        
        try {
            $stmt = $conn->prepare("SELECT * FROM user WHERE user_id = :user_id AND password = :password");
            $stmt->execute(['user_id' => $user_id, 'password' => $password]);
        
            if ($stmt->rowCount() > 0) {
                // Set session variables before redirecting
                $_SESSION['user_logged_in'] = true;
                $_SESSION['user_id'] = $user_id;
                
                // Redirect to user.php
                header('Location: user.php');
                exit(); // Important to exit after redirect
            } else {
                echo "Invalid credentials.";
            }
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login Processing</title>
</head>
<body>
    <?php if (!isset($_POST['login_submit'])): ?>
        <p>Please use the login form. <a href="index.html">Go back to login</a></p>
    <?php endif; ?>
</body>
</html>