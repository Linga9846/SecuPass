<?php
session_start();

if (isset($_SESSION['access_token'])) {
    $authenticated = true;
} elseif (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=appid', 'root', '');
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username');
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();
        
        if ($user) {
            $authenticated = true;
        } else {
            $authenticated = false;
        }
    } catch (PDOException $e) {
        $authenticated = false;
        echo 'Database error: ' . $e->getMessage();
    }
} else {
    $authenticated = false;
}

if (!$authenticated) {
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
    <style>
      *,
      *:before,
      *:after {
          padding: 0;
          margin: 0;
          box-sizing: border-box;
      }
      body {
          background-color: #080710;
          display: flex;
          justify-content: center;
          align-items: center;
          height: 100vh;
      }
      .background {
          width: 430px;
          height: 620px;
          position: absolute;
          transform: translate(-50%, -50%);
          left: 50%;
          top: 50%;
      }
      .background .shape {
          height: 200px;
          width: 200px;
          position: absolute;
          border-radius: 50%;
      }
      .shape:first-child {
          background: linear-gradient(#1845ad, #23a2f6);
          left: -80px;
          top: -80px;
      }
      .shape:last-child {
          background: linear-gradient(to right, #ff512f, #f09819);
          right: -30px;
          bottom: -80px;
      }
      .content {
          width: 300px;
          background-color: rgba(255, 255, 255, 0.13);
          position: absolute;
          transform: translate(-50%, -50%);
          top: 50%;
          left: 50%;
          border-radius: 10px;
          backdrop-filter: blur(10px);
          border: 2px solid rgba(255, 255, 255, 0.1);
          box-shadow: 0 0 40px rgba(8, 7, 16, 0.6);
          padding: 20px 35px;
          text-align: center;
      }
      .content * {
          font-family: 'Poppins', sans-serif;
          color: #ffffff;
          letter-spacing: 0.5px;
          outline: none;
          border: none;
      }
      .content h1 {
          font-size: 24px;
          font-weight: 500;
          line-height: 32px;
          margin-bottom: 20px;
          text-align: left;
      }
      .content a {
          color: #ffffff;
          text-decoration: none;
          font-size: 16px;
          font-weight: 500;
          margin-top: 20px;
          display: block;
      }
      .content a:hover {
          color: #cccccc;
      }
    </style>
</head>
<body>
    <div class="background">
        <div class="shape"></div>
        <div class="shape"></div>
    </div>
    <div class="content">
        <h1>Welcome to the Protected Page!</h1>
        <a href="logout.php">Logout</a>
    </div>
</body>
</html>
