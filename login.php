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
        } elseif ($method == 'secureid') {
            $pdo = new PDO('mysql:host=localhost;dbname=secureid', 'root', '');
            $stmt = $pdo->prepare('SELECT secure_id FROM users WHERE username = :username');
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
          background-image: url('./assets/login.png');
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
      .login-link {
            margin-top: 20px;
            text-align: center;
        }
        .login-link a {
            color: #000000;
            text-decoration: none;
            font-size: 16px;
            font-weight: 1000;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <form method="POST">
        <h3>Login Here</h3>

        <label for="username">Username</label>
        <input type="text" name="username" placeholder="Email or Phone" id="username" required>

        <div class="method-selection">
            <input type="radio" name="method" value="password" id="password-method" checked>
            <label for="password-method"><span>Password</span></label>
            
            <input type="radio" name="method" value="secureid" id="secureid-method">
            <label for="secureid-method"><span>Secure ID</span></label>
        </div>

        <div id="password-field">
            <label for="password">Password</label>
            <input type="password" name="password" placeholder="Password" id="password">
        </div>
        
        <button type="submit">Log In</button>
        <div class="login-link">
            <p>Dont have an account? <a href="register.php">Register Now</a></p>
        </div>
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
