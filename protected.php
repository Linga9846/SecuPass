<?php
session_start();

if (!isset($_SESSION['access_token'])) {
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Protected Page</title>
</head>
<body>
    <h1>Welcome to the Protected Page!</h1>
    <a href="logout.php">Logout</a>
</body>
</html>
