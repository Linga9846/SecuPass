<?php
session_start();

$tokenUrl = 'http://localhost:8080/realms/secupass/protocol/openid-connect/token';
$clientId = 'php';
$clientSecret = 'nu9m7gb8bzpST8j5nKeEX05F5QhaCZLv'; // Replace with your actual client secret

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Prepare the request to Keycloak token endpoint
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

    if (isset($data['access_token'])) {
        // Save the access token in the session
        $_SESSION['access_token'] = $data['access_token'];

        // Directly redirect to protected page
        header('Location: protected.php');
        exit();
    } else {
        echo 'Authentication failed. Please check your username and password.';
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
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>
</body>
</html>
