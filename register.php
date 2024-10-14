<?php
session_start();

require 'vendor/autoload.php'; 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['user_details'] = [
        'username' => $_POST['username'],
        'password' => $_POST['password'],
        'email' => $_POST['email'],
        'first_name' => $_POST['first_name'],
        'last_name' => $_POST['last_name']
    ];

    $otp = rand(100000, 999999);
    $_SESSION['otp'] = $otp;
    $_SESSION['otp_email'] = $_POST['email'];

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'hell0secupass@gmail.com';  // Your Gmail address
        $mail->Password   = 'kukq bpzt ugyc snmg';  // Your Google App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('hell0secupass@gmail.com', 'SecuPass'); // Use your full email as sender
        $mail->addAddress($_POST['email']); // Recipient's email

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your OTP Code';
        $mail->Body    = 'Your OTP code is: ' . $otp;

        $mail->send();

        header("Location: verify_otp.php");
        exit();
    } catch (Exception $e) {
        echo 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
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
      form {
          height: 620px;
          width: 400px;
          background-color: rgba(255, 255, 255, 0.13);
          position: absolute;
          transform: translate(-50%, -50%);
          top: 50%;
          left: 50%;
          border-radius: 10px;
          backdrop-filter: blur(10px);
          border: 2px solid rgba(255, 255, 255, 0.1);
          box-shadow: 0 0 40px rgba(8, 7, 16, 0.6);
          padding: 50px 35px;
      }
      form * {
          font-family: 'Poppins', sans-serif;
          color: #ffffff;
          letter-spacing: 0.5px;
          outline: none;
          border: none;
      }
      form h2 {
          font-size: 32px;
          font-weight: 500;
          line-height: 42px;
          margin-bottom: 20px;
          text-align: center;
      }
      label {
          display: block;
          margin-top: 20px;
          font-size: 16px;
          font-weight: 500;
          text-align: left;
      }
      input {
          display: block;
          height: 50px;
          width: 100%;
          background-color: rgba(255, 255, 255, 0.07);
          border-radius: 3px;
          padding: 0 10px;
          margin-top: 8px;
          font-size: 14px;
          font-weight: 300;
      }
      ::placeholder {
          color: #e5e5e5;
      }
      button {
          margin-top: 30px;
          width: 100%;
          background-color: #ffffff;
          color: #080710;
          padding: 15px 0;
          font-size: 18px;
          font-weight: 600;
          border-radius: 5px;
          cursor: pointer;
      }
    </style>
</head>
<body>
    <div class="background">
        <div class="shape"></div>
        <div class="shape"></div>
    </div>
    <form method="POST">
        <h2>Register</h2>
        <label for="username">Username</label>
        <input type="text" name="username" placeholder="Username" required>

        <label for="password">Password</label>
        <input type="password" name="password" placeholder="Password" required>

        <label for="email">Email</label>
        <input type="email" name="email" placeholder="Email" required>

        <label for="first_name">First Name</label>
        <input type="text" name="first_name" placeholder="First Name" required>

        <label for="last_name">Last Name</label>
        <input type="text" name="last_name" placeholder="Last Name" required>

        <button type="submit">Register</button>
    </form>
</body>
</html>
