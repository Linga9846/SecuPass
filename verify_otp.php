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
                $appId = uniqid(); 
                $encryptedAppId = sodium_crypto_box($appId, $nonce, $keyPair);


                try {
                    $pdoEncrypted = new PDO('mysql:host=localhost;dbname=appid', 'root', '');
                    $stmtEncrypted = $pdoEncrypted->prepare('INSERT INTO users (username, public_key, app_id, nonce) VALUES (:username, :public_key, :app_id, :nonce)');
                    $stmtEncrypted->execute([
                        ':username' => $userDetails['username'],
                        ':public_key' => base64_encode($publicKey),
                        ':app_id' => base64_encode($encryptedAppId),
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
      form {
          height: 200px;
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
      form * {
          font-family: 'Poppins', sans-serif;
          color: #ffffff;
          letter-spacing: 0.5px;
          outline: none;
          border: none;
      }
      form h2 {
          font-size: 24px;
          font-weight: 500;
          line-height: 32px;
          margin-bottom: 20px;
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
          margin-top: 20px;
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
        <h2>Verify OTP</h2>r
        <input type="text" name="otp" placeholder="OTP" required>
        <button type="submit">Verify OTP</button>
    </form>
</body>
</html>