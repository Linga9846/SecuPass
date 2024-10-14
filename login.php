<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $method = $_POST['method']; 

    try {
        if ($method == 'password') {
            $password = $_POST['password'];
            $tokenUrl = 'http://localhost:8080/realms/secupass/protocol/openid-connect/token';
            $clientId = 'php';
            $clientSecret = 'nu9m7gb8bzpST8j5nKeEX05F5QhaCZLv';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $tokenUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'grant_type' => 'password',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'username' => $username,
                'password' => $password,
                'scope' => 'openid',
            ]));

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Get HTTP status code
            curl_close($ch);

            $data = json_decode($response, true);

            if ($httpCode == 200 && isset($data['access_token'])) {
                $_SESSION['access_token'] = $data['access_token'];
                $_SESSION['username'] = $username; // Set username in session
                header('Location: protected.php');
                exit();
            } else {
                echo 'Authentication failed. HTTP Code: ' . $httpCode . ' Response: ' . htmlspecialchars($response);
            }
        } elseif ($method == 'appid') {
            $pdo = new PDO('mysql:host=localhost;dbname=appid', 'root', '');
            $stmt = $pdo->prepare('SELECT app_id FROM users WHERE username = :username');
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();

            if ($user) {
                $_SESSION['username'] = $username;
                header('Location: protected.php');
                exit();
            } else {
                echo 'Invalid app ID.';
            }
        }
    } catch (Exception $e) {
        echo 'An error occurred: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
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
          height: 520px;
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
          height: 520px;
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
      form h3 {
          font-size: 32px;
          font-weight: 500;
          line-height: 42px;
          text-align: center;
      }
      label {
          display: block;
          margin-top: 30px;
          font-size: 16px;
          font-weight: 500;
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
          margin-top: 50px;
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
          margin-top: 30px;
          display: flex;
          justify-content: space-between;
      }
      .method-selection label {
          background-color: rgba(255, 255, 255, 0.27);
          border-radius: 5px;
          padding: 10px;
          cursor: pointer;
      }
      .method-selection input[type="radio"] {
          display: none;
      }
      .method-selection input[type="radio"]:checked + label {
          background-color: rgba(255, 255, 255, 0.47);
          box-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
          border: 2px solid #4CAF50;
      }
    </style>
</head>
<body>
    <div class="background">
        <div class="shape"></div>
        <div class="shape"></div>
    </div>
    <form method="POST">
        <h3>Login Here</h3>

        <label for="username">Username</label>
        <input type="text" name="username" placeholder="Email or Phone" id="username" required>

        <div class="method-selection">
            <label><input type="radio" name="method" value="password" checked> Password</label>
            <label><input type="radio" name="method" value="appid"> App ID</label>
        </div>
        <div id="password-field">
            <label for="password">Password</label>
            <input type="password" name="password" placeholder="Password" id="password">
        </div>

        <button type="submit">Log In</button>
    </form>

    <script>
        document.querySelectorAll('input[name="method"]').forEach((radio) => {
            radio.addEventListener('change', function() {
                document.getElementById('password-field').style.display = this.value === 'password' ? 'block' : 'none';
            });
        });
    </script>
</body>
</html>
