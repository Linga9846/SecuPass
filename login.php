<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $method = $_POST['method']; // 'password' or 'appid'

    if ($method == 'password') {
        // Authenticate with Keycloak
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
            header('Location: protected.php');
            exit();
        } else {
            echo 'Authentication failed. HTTP Code: ' . $httpCode . ' Response: ' . htmlspecialchars($response);
        }
    } elseif ($method == 'appid') {
        // Authenticate with SQL
        $pdo = new PDO('mysql:host=localhost;dbname=appid', 'root', '');
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username');
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if ($user) {
            $_SESSION['username'] = $username;
            header('Location: protected.php');
            exit();
        } else {
            echo 'User not found.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    <h2>Login</h2>
    <form method="POST">
        <input type="text" name="username" placeholder="Username" required>
        <label>
            <input type="radio" name="method" value="password" required> Password
        </label>
        <label>
            <input type="radio" name="method" value="appid" required> App ID
        </label>
        <input type="password" name="password" placeholder="Password" style="display:none;">
        <button type="submit">Login</button>
    </form>
    <script>
        document.querySelectorAll('input[name="method"]').forEach(function(radio) {
            radio.addEventListener('change', function() {
                document.querySelector('input[name="password"]').style.display = this.value === 'password' ? 'block' : 'none';
            });
        });
    </script>
</body>
</html>
