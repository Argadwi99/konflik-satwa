<?php
session_start();
require_once('../config/database.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = clean($conn, $_POST['username']);
    $password = md5($_POST['password']); // MD5 untuk simple demo
    
    if (empty($username) || empty($_POST['password'])) {
        header("Location: ../index.php?error=empty");
        exit();
    }
    
    $query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
        $_SESSION['role'] = $user['role'];
        
        header("Location: ../pages/dashboard.php");
        exit();
    } else {
        header("Location: ../index.php?error=invalid");
        exit();
    }
} else {
    header("Location: ../index.php");
    exit();
}
?>