<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enteredOtp = $_POST['otp'];

    if ($enteredOtp == $_SESSION['otp']) {
        $tokenUrl = 'http://localhost:8080/realms/secupass/protocol/openid-connect/token';
        $clientId = 'php';
        $clientSecret = 'nu9m7gb8bzpST8j5nKeEX05F5QhaCZLv';
        $registerUrl = 'http://localhost:8080/admin/realms/secupass/users';

        $userDetails = $_SESSION['user_details'];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $tokenUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'client_credentials',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'scope' => 'openid',
        ]));

        $response = curl_exec($ch);
        $data = json_decode($response, true);
        curl_close($ch);

        if (isset($data['access_token'])) {
            $adminToken = $data['access_token'];
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $registerUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $adminToken,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'username' => $userDetails['username'],
                'email' => $userDetails['email'],
                'firstName' => $userDetails['first_name'],
                'lastName' => $userDetails['last_name'],
                'enabled' => true,
                'emailVerified' => true,  
                'credentials' => [
                    [
                        'type' => 'password',
                        'value' => $userDetails['password'],
                    ]
                ]
            ]));

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); 
            curl_close($ch);

            if ($httpCode == 201) {
                $keyPair = sodium_crypto_box_keypair();
                $publicKey = sodium_crypto_box_publickey($keyPair);
                $privateKey = sodium_crypto_box_secretkey($keyPair);
                $nonce = random_bytes(SODIUM_CRYPTO_BOX_NONCEBYTES);
                $secureId = uniqid(); 
                $encryptedSecureId = sodium_crypto_box($secureId, $nonce, $keyPair);


                try {
                    $pdoEncrypted = new PDO('mysql:host=localhost;dbname=secureid', 'root', '');
                    $stmtEncrypted = $pdoEncrypted->prepare('INSERT INTO users (username, public_key, secure_id, nonce) VALUES (:username, :public_key, :secure_id, :nonce)');
                    $stmtEncrypted->execute([
                        ':username' => $userDetails['username'],
                        ':public_key' => base64_encode($publicKey),
                        ':secure_id' => base64_encode($encryptedSecureId),
                        ':nonce' => base64_encode($nonce)
                    ]);

                    echo 'Registration successful.';
                    session_destroy();
                    header("Location: login.php");
                    exit();
                } catch (PDOException $e) {
                    echo 'Encrypted database error: ' . $e->getMessage();
                }
            } else {
                echo 'Failed to register user. HTTP Code: ' . $httpCode;
            }
        } else {
            echo 'Failed to obtain admin access token.';
        }
    } else {
        echo 'Invalid OTP.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>
    <style>
      *,
      *:before,
      *:after {
          padding: 0;
          margin: 0;
          box-sizing: border-box;
      }
      body {
          background-image: url('./assets/register.png');
          background-size: 1920px 1080px; 
          background-position: 50% 50%;
          background-repeat: no-repeat;
          background-attachment: fixed;
          display: flex;
          justify-content: center;
          align-items: center;
          height: 100vh;
          margin: 0;
      }
      form {
          width: 340px;
          background-color: rgba(255, 255, 255, 0.5);
          border-radius: 10px;
          backdrop-filter: blur(10px);
          border: 2px solid rgba(255, 255, 255, 0.1);
          box-shadow: 0 0 40px rgba(8, 7, 16, 0.6);
          padding: 30px;
          text-align: center;
      }
      form * {
          font-family: 'Poppins', sans-serif;
          color: #ffffff;
          letter-spacing: 0.5px;
          outline: none;
          border: none;
      }
      form h3 {
          font-size: 24px;
          font-weight: 500;
          line-height: 32px;
          text-align: center;
          margin-bottom: 20px;
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
          background-color: rgba(255, 255, 255, 0.09);
          border-radius: 5px;
          padding: 0 15px;
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
      .method-selection {
          margin-top: 20px;
          display: flex;
          justify-content: space-around;
          align-items: center;
      }
      .method-selection label {
          background-color: rgba(255, 255, 255, 0.27);
          border-radius: 5px;
          padding: 10px 15px;
          cursor: pointer;
          display: flex;
          align-items: center;
          justify-content: center;
      }
      .method-selection input[type="radio"] {
          display: none;
      }
      .method-selection input[type="radio"]:checked + label {
          background-color: rgba(255, 255, 255, 0.47);
          box-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
          border: 2px solid #4CAF50;
      }
      .method-selection label span {
          margin-left: 10px;
      }
    </style>
</head>
</head>
<body>
    <div class="background">
        <div class="shape"></div>
        <div class="shape"></div>
    </div>
    <form method="POST">
        <h2>Verify OTP</h2>
        <input type="text" name="otp" placeholder="OTP" required>
        <button type="submit">Verify OTP</button>
    </form>
</body>
</html>